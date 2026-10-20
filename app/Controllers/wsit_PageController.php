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

        $pageTitle = $page['title'] . ' | ' . app_name();
        $pageDescription = mb_substr(strip_tags($page['content'] ?? ''), 0, 160);
        if ($pageDescription === '') {
            $pageDescription = seo_default_description();
        }

        $crumbs = [
            ['name' => trans('nav_home'), 'url' => locale_url('/')],
            ['name' => $page['title'], 'url' => locale_url('/pages/' . $page['slug'])],
        ];
        $schemas = seo_default_jsonld($page['title'], $pageDescription, seo_canonical_url());
        $breadcrumbSchema = seo_breadcrumb_jsonld($crumbs);
        if (!empty($breadcrumbSchema)) {
            $schemas['breadcrumbs'] = $breadcrumbSchema;
        }

        return $this->view('pages/wsit_show', [
            'pageTitle'       => $pageTitle,
            'pageDescription' => $pageDescription,
            'canonicalUrl'    => seo_canonical_url(),
            'jsonLd'          => $schemas,
            'page'            => $page,
            'contactForm'     => ($page['slug'] ?? '') === 'contact',
            'captchaEnabled'  => contact_captcha_enabled(),
        ]);
    }
}