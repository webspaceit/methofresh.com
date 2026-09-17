<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Models\wsit_Category;
use App\Models\wsit_Product;

class wsit_HomeController extends wsit_Controller
{
    public function index(): string
    {
        $categoryModel = new wsit_Category();
        $productModel = new wsit_Product();

        $categories = $categoryModel->localized($categoryModel->all(true), app_locale());
        $featured = $productModel->localized($productModel->featured(8), app_locale());
        $newArrivals = $productModel->localized($productModel->newArrivals(8), app_locale());

        return $this->view('home/wsit_index', [
            'pageTitle'  => trans('site_name'),
            'categories' => $categories,
            'featured'   => $featured,
            'newArrivals'=> $newArrivals,
            'blocks'     => homepage_blocks(),
            'heroSlides' => hero_slides(),
        ]);
    }

    public function newsletter(wsit_Request $request): void
    {
        $email = $request->input('newsletter_email');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('success', trans('subscribe_success'));
        } else {
            $this->flash('error', trans('validate_email'));
        }
        $this->redirect('/');
    }
}