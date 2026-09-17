<?php

namespace App\Core;

use App\Core\wsit_View;

abstract class wsit_Controller
{
    protected wsit_View $view;

    public function __construct()
    {
        $this->view = new wsit_View(views_path());
        $this->before();
    }

    protected function before(): void
    {
    }

    protected function view(string $template, array $data = [], string $layout = 'wsit_main'): string
    {
        return $this->view->renderWithLayout($template, $data, $layout);
    }

    protected function render(string $template, array $data = []): string
    {
        return $this->view->render($template, $data);
    }

    protected function redirect(string $path): void
    {
        header('Location: ' . locale_url($path));
        exit;
    }

    protected function redirectTo(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function json(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function requireLogin(): void
    {
        if (!current_user()) {
            $this->flash('error', trans('login_required'));
            $this->redirect('/login');
        }
    }

    protected function requireAdmin(): void
    {
        $user = current_user();
        if (!$user) {
            $this->flash('error', trans('login_required'));
            $this->redirect('/login');
        }
        if ((int)$user['is_admin'] !== 1) {
            http_response_code(403);
            exit($this->view('errors/wsit_403', ['pageTitle' => '403'], 'wsit_main'));
        }
    }

    protected function flash(string $key, string $message): void
    {
        \App\Core\wsit_Session::flash($key, $message);
    }
}