<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Models\wsit_Category;
use App\Models\wsit_Product;

class wsit_CategoryController extends wsit_Controller
{
    private wsit_Category $categoryModel;
    private wsit_Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->categoryModel = new wsit_Category();
        $this->productModel = new wsit_Product();
    }

    public function index(): string
    {
        $categories = $this->categoryModel->localized($this->categoryModel->all(true), app_locale());

        foreach ($categories as &$category) {
            $category['product_count'] = $this->categoryModel->countProducts((int)$category['id']);
        }
        unset($category);

        return $this->view('category/wsit_index', [
            'pageTitle'  => trans('nav_categories'),
            'categories' => $categories,
        ]);
    }

    public function show(wsit_Request $request, string $slug): string
    {
        $category = $this->categoryModel->findBySlug($slug);

        if ($category === null) {
            return (new wsit_ErrorController())->notFound();
        }

        $perPage = 12;
        $page = max(1, (int)$request->query('page', 1));
        $limit = $perPage;
        $offset = ($page - 1) * $perPage;

        $products = $this->productModel->localized(
            $this->productModel->byCategory((int)$category['id'], $perPage, $offset),
            app_locale()
        );
        $total = $this->productModel->countByCategory((int)$category['id']);
        $pages = max(1, (int)ceil($total / $perPage));

        $category = $this->categoryModel->localized([$category], app_locale())[0];

        return $this->view('category/wsit_show', [
            'pageTitle'  => $category['name'],
            'category'   => $category,
            'products'   => $products,
            'page'       => $page,
            'pages'      => $pages,
            'total'      => $total,
            'perPage'    => $perPage,
        ]);
    }
}