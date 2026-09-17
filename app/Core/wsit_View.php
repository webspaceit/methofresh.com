<?php

namespace App\Core;

class wsit_View
{
    private string $viewsPath;

    public function __construct(string $viewsPath)
    {
        $this->viewsPath = $viewsPath;
    }

    public function render(string $template, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include $this->viewsPath . '/' . $template . '.php';
        return ob_get_clean();
    }

    public function renderWithLayout(string $template, array $data = [], string $layout = 'wsit_main'): string
    {
        extract($data, EXTR_SKIP);
        $content = $this->render($template, $data);
        $pageTitle = $pageTitle ?? app_name();
        ob_start();
        include $this->viewsPath . '/layouts/' . $layout . '.php';
        return ob_get_clean();
    }
}