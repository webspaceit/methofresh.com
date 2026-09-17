<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Models\wsit_Page;

class wsit_PageController extends wsit_Controller
{
    private wsit_Page $pageModel;

    public function __construct()
    {
        parent::__construct();
        $this->pageModel = new wsit_Page();
    }

    public function show(string $slug): string
    {
        $page = $this->pageModel->findBySlug($slug);

        if ($page === null) {
            return (new wsit_ErrorController())->notFound();
        }

        $page = $this->pageModel->localized([$page], app_locale())[0];

        return $this->view('pages/wsit_show', [
            'pageTitle'      => $page['title'],
            'page'           => $page,
            'contactForm'    => ($page['slug'] ?? '') === 'contact',
            'captchaEnabled' => contact_captcha_enabled(),
        ]);
    }
}