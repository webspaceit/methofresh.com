<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Models\wsit_Category;

class wsit_AdminCategoryController extends wsit_Controller
{
    private wsit_Category $categoryModel;

    protected function before(): void
    {
        $this->requireAdmin();
    }

    public function __construct()
    {
        parent::__construct();
        $this->categoryModel = new wsit_Category();
    }

    public function index(): string
    {
        $categories = $this->categoryModel->all();
        foreach ($categories as &$category) {
            $category['product_count'] = $this->categoryModel->countProducts((int)$category['id']);
        }
        unset($category);
        return $this->view('admin/categories/wsit_index', [
            'pageTitle' => trans('admin_categories') . ' | ' . trans('admin'),
            'categories'=> $categories,
        ], 'wsit_admin');
    }

    public function create(): string
    {
        return $this->view('admin/categories/wsit_form', [
            'pageTitle' => trans('admin_add_category') . ' | ' . trans('admin'),
            'category'  => null,
        ], 'wsit_admin');
    }

    public function edit(int $id): string
    {
        $category = $this->categoryModel->find($id);
        if ($category === null) {
            $this->redirect('/mf-dashboard/categories');
        }
        return $this->view('admin/categories/wsit_form', [
            'pageTitle' => trans('admin_edit_category') . ' | ' . trans('admin'),
            'category'  => $category,
        ], 'wsit_admin');
    }

    public function store(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/categories');
        }
        $data = $this->collectData($request);
        $data['slug'] = $this->uniqueSlug($data['slug']);
        $image = $this->handleUpload($request);
        if ($image !== null) {
            $data['image'] = $image;
        }
        $this->categoryModel->create($data);
        $this->flash('success', trans('admin_category_saved'));
        $this->redirect('/mf-dashboard/categories');
    }

    public function update(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/categories/' . $id . '/edit');
        }
        $data = $this->collectData($request);
        $data['slug'] = $this->uniqueSlug($data['slug'], $id);
        $image = $this->handleUpload($request);
        if ($image !== null) {
            $data['image'] = $image;
        }
        $this->categoryModel->update($id, $data);
        $this->flash('success', trans('admin_category_saved'));
        $this->redirect('/mf-dashboard/categories/' . $id . '/edit');
    }

    public function destroy(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/categories');
        }
        $this->categoryModel->delete($id);
        $this->flash('success', trans('admin_deleted'));
        $this->redirect('/mf-dashboard/categories');
    }

    public function toggle(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/categories');
        }
        $category = $this->categoryModel->find($id);
        if ($category) {
            $this->categoryModel->update($id, ['active' => (int)$category['active'] === 1 ? 0 : 1]);
        }
        $this->redirect('/mf-dashboard/categories');
    }

    private function collectData(wsit_Request $request): array
    {
        $active = $request->input('active');
        return [
            'slug'           => slugify($request->input('slug', $request->input('name_en'))),
            'name_en'        => $request->input('name_en'),
            'name_bn'        => $request->input('name_bn', $request->input('name_en')),
            'description_en' => $request->input('description_en'),
            'description_bn' => $request->input('description_bn'),
            'active'         => $active === '1' ? 1 : 0,
        ];
    }

    private function uniqueSlug(string $slug, int|null $ignoreId = null): string
    {
        $base = $slug;
        $i = 1;
        while ($this->categoryModel->slugExists($slug, $ignoreId)) {
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

        $name = 'cat_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$type];
        $target = public_path('assets/img/' . $name);
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return null;
        }
        return 'assets/img/' . $name;
    }
}