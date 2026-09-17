<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Models\wsit_Category;
use App\Models\wsit_Product;

class wsit_AdminProductController extends wsit_Controller
{
    private wsit_Product $productModel;
    private wsit_Category $categoryModel;

    protected function before(): void
    {
        $this->requireAdmin();
    }

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new wsit_Product();
        $this->categoryModel = new wsit_Category();
    }

    public function index(wsit_Request $request): string
    {
        $search = trim((string)$request->query('q', ''));
        $products = $this->productModel->search($search);

        return $this->view('admin/products/wsit_index', [
            'pageTitle' => trans('admin_products') . ' | ' . trans('admin'),
            'products'  => $products,
            'search'    => $search,
        ], 'wsit_admin');
    }

    public function searchJson(wsit_Request $request): void
    {
        $search = trim((string)$request->query('q', ''));
        $products = $this->productModel->search($search);
        $html = $this->render('admin/products/wsit__rows', ['products' => $products]);

        $this->json([
            'count'       => count($products),
            'count_label' => format_number(count($products)) . ' ' . trans('admin_products_count'),
            'html'        => $html,
        ]);
    }

    public function create(): string
    {
        $categories = $this->categoryModel->localized($this->categoryModel->all(), app_locale());
        return $this->view('admin/products/wsit_form', [
            'pageTitle' => trans('admin_add_product') . ' | ' . trans('admin'),
            'product'   => null,
            'categories'=> $categories,
        ], 'wsit_admin');
    }

    public function edit(int $id): string
    {
        $product = $this->productModel->find($id);
        if ($product === null) {
            $this->flash('error', trans('admin_saved') === '' ? 'Not found' : 'Product not found');
            $this->redirect('/mf-dashboard/products');
        }
        $categories = $this->categoryModel->localized($this->categoryModel->all(), app_locale());
        return $this->view('admin/products/wsit_form', [
            'pageTitle' => trans('admin_edit_product') . ' | ' . trans('admin'),
            'product'   => $product,
            'categories'=> $categories,
        ], 'wsit_admin');
    }

    public function store(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/products');
        }

        $data = $this->collectData($request);
        $data['slug'] = $this->uniqueSlug($data['slug']);

        $image = $this->handleUpload($request);
        if ($image !== null) {
            $data['image'] = $image;
        }

        $this->productModel->create($data);
        $this->flash('success', trans('admin_product_saved'));
        $this->redirect('/mf-dashboard/products');
    }

    public function update(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/products/' . $id . '/edit');
        }

        $data = $this->collectData($request);
        $data['slug'] = $this->uniqueSlug($data['slug'], $id);

        $image = $this->handleUpload($request);
        if ($image !== null) {
            $data['image'] = $image;
        }

        $this->productModel->update($id, $data);
        $this->flash('success', trans('admin_product_saved'));
        $this->redirect('/mf-dashboard/products/' . $id . '/edit');
    }

    public function destroy(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/products');
        }
        $this->productModel->delete($id);
        $this->flash('success', trans('admin_deleted'));
        $this->redirect('/mf-dashboard/products');
    }

    public function toggle(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/products');
        }
        $product = $this->productModel->find($id);
        if ($product) {
            $this->productModel->update($id, [
                'active'   => (int)$product['active'] === 1 ? 0 : 1,
                'featured' => (int)$product['featured'],
            ]);
        }
        $this->redirect('/mf-dashboard/products');
    }

    public function toggleFeatured(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/products');
        }
        $product = $this->productModel->find($id);
        if ($product) {
            $this->productModel->update($id, [
                'featured' => (int)$product['featured'] === 1 ? 0 : 1,
                'active'   => (int)$product['active'],
            ]);
        }
        $this->redirect('/mf-dashboard/products');
    }

    public function toggleNewArrival(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/products');
        }
        $product = $this->productModel->find($id);
        if ($product) {
            $this->productModel->update($id, [
                'new_arrival' => (int)$product['new_arrival'] === 1 ? 0 : 1,
                'active'      => (int)$product['active'],
            ]);
        }
        $this->redirect('/mf-dashboard/products');
    }

    private function collectData(wsit_Request $request): array
    {
        $active = $request->input('active');
        $featured = $request->input('featured');
        $salePrice = $request->input('sale_price');

        return [
            'category_id'    => (int)$request->input('category_id'),
            'slug'           => slugify($request->input('slug', $request->input('name_en'))),
            'sku'            => $request->input('sku'),
            'name_en'        => $request->input('name_en'),
            'name_bn'        => $request->input('name_bn', $request->input('name_en')),
            'description_en' => $request->input('description_en'),
            'description_bn' => $request->input('description_bn'),
            'price'          => (float)$request->input('price', 0),
            'sale_price'     => $salePrice !== '' && (float)$salePrice > 0 ? (float)$salePrice : null,
            'stock'          => (int)$request->input('stock', 0),
            'active'         => $active === '1' ? 1 : 0,
            'featured'       => $featured === '1' ? 1 : 0,
        ];
    }

    private function uniqueSlug(string $slug, int|null $ignoreId = null): string
    {
        $base = $slug;
        $i = 1;
        while ($this->productModel->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . ($i++);
        }
        return $slug;
    }

    private function handleUpload(wsit_Request $request): ?string
    {
        $file = $request->file('image');
        if ($file === null || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }

        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $type = $file['type'] ?? '';
        if (!isset($allowed[$type])) {
            return null;
        }

        $name = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$type];
        $target = public_path('assets/img/' . $name);
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return null;
        }
        return 'assets/img/' . $name;
    }
}