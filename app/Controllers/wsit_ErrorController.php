<?php

namespace App\Controllers;

use App\Core\wsit_Controller;

class wsit_ErrorController extends wsit_Controller
{
    public function notFound(): string
    {
        http_response_code(404);
        return $this->view('errors/wsit_404', ['pageTitle' => trans('error_404_title')]);
    }

    public function forbidden(): string
    {
        http_response_code(403);
        return $this->view('errors/wsit_403', ['pageTitle' => trans('error_403_title')]);
    }
}