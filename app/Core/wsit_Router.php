<?php

namespace App\Core;

class wsit_Router
{
    private array $routes = [];
    private string $basePath;
    private string $currentLocale;
    private array $params = [];
    private wsit_Request|null $currentRequest = null;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
        $this->currentLocale = app_locale();
    }

    public function get(string $uri, array $action): void
    {
        $this->add('GET', $uri, $action);
    }

    public function post(string $uri, array $action): void
    {
        $this->add('POST', $uri, $action);
    }

    public function add(string $method, string $uri, array $action): void
    {
        // Route without a locale prefix.
        $this->routes[strtoupper($method)][trim($uri, '/')] = $action;
        // Duplicate for every configured locale: /{lang}/uri
        foreach (locales() as $locale) {
            $this->routes[strtoupper($method)]["{$locale}/" . trim($uri, '/')] = $action;
        }
    }

    public function dispatch(wsit_Request $request): void
    {
        $this->currentRequest = $request;
        $method = $request->getMethod();
        $path = $request->getPath();

        // Remove base path (e.g. /methofresh.com) if present.
        if ($this->basePath !== '' && str_starts_with($path, $this->basePath)) {
            $path = substr($path, strlen($this->basePath));
        }
        $path = trim($path, '/');
        $path = $path === '' ? '/' : $path;

        // Detect locale from the first URL segment.
        $locale = app_locale();
        $uri = $path;
        if ($path !== '/') {
            $segments = explode('/', $path);
            if (in_array($segments[0], locales(), true)) {
                $locale = $segments[0];
                restore_locale($locale);
                $uri = implode('/', array_slice($segments, 1));
                $uri = $uri === '' ? '/' : $uri;
            }
        }

        $route = $this->match($method, $uri);

        if ($route === null) {
            http_response_code(404);
            $this->dispatchNotFound($request);
            return;
        }

        [$controllerClass, $methodName] = $route;

        $controller = new $controllerClass();
        $params = array_values($this->params);

        // Inject the Request into actions whose first parameter is typed Request
        // (e.g. index(Request $request)), leaving slug/id params untouched.
        $ref = new \ReflectionMethod($controllerClass, $methodName);
        $first = $ref->getParameters()[0] ?? null;
        if ($first !== null) {
            $type = $first->getType();
            if ($type instanceof \ReflectionNamedType && $type->getName() === wsit_Request::class) {
                array_unshift($params, $this->getRequest());
            }
        }

        $output = $controller->{$methodName}(...$params);
        if (is_string($output) && $output !== '') {
            echo $output;
        }
    }

    private function match(string $method, string $uri): ?array
    {
        // Default route is the home page.
        if ($uri === '/') {
            $uri = '';
        }

        $routes = $this->routes[$method] ?? [];

        if (isset($routes[$uri])) {
            $this->params = [];
            return $routes[$uri];
        }

        foreach ($routes as $route => $action) {
            $pattern = preg_replace('/\{([A-Za-z0-9_]+)\}/', '(?P<$1>[^/]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            if (preg_match($pattern, $uri, $matches)) {
                $this->params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return $action;
            }
        }

        return null;
    }

    private function dispatchNotFound(wsit_Request $request): void
    {
        $controller = new \App\Controllers\wsit_ErrorController();
        $output = $controller->notFound($request);
        if (is_string($output) && $output !== '') {
            echo $output;
        }
    }

    private function getRequest(): ?wsit_Request
    {
        return $this->currentRequest;
    }
}