<?php

namespace App\Controllers;

use App\Core\wsit_Controller;
use App\Core\wsit_Request;
use App\Models\wsit_Page;

class wsit_AdminPageController extends wsit_Controller
{
    private wsit_Page $pageModel;

    protected function before(): void
    {
        $this->requireAdmin();
    }

    public function __construct()
    {
        parent::__construct();
        $this->pageModel = new wsit_Page();
    }

    public function index(): string
    {
        return $this->view('admin/pages/wsit_index', [
            'pageTitle' => trans('admin_pages') . ' | ' . trans('admin'),
            'pages'     => $this->pageModel->all(),
        ], 'wsit_admin');
    }

    public function create(): string
    {
        return $this->view('admin/pages/wsit_form', [
            'pageTitle' => trans('admin_add_page') . ' | ' . trans('admin'),
            'page'      => null,
        ], 'wsit_admin');
    }

    public function edit(int $id): string
    {
        $page = $this->pageModel->find($id);
        if ($page === null) {
            $this->redirect('/mf-dashboard/pages');
        }
        return $this->view('admin/pages/wsit_form', [
            'pageTitle' => trans('admin_edit_page') . ' | ' . trans('admin'),
            'page'      => $page,
        ], 'wsit_admin');
    }

    public function store(wsit_Request $request): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/pages');
        }
        $data = $this->collectData($request);
        $data['slug'] = $this->uniqueSlug($data['slug']);
        $image = $this->handleUpload($request);
        if ($image !== null) {
            $data['image'] = $image;
        }
        $this->pageModel->create($data);
        $this->flash('success', trans('admin_page_saved'));
        $this->redirect('/mf-dashboard/pages');
    }

    public function update(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/pages/' . $id . '/edit');
        }
        $data = $this->collectData($request);
        $data['slug'] = $this->uniqueSlug($data['slug'], $id);

        $current = $this->pageModel->find($id);
        $image = $this->handleUpload($request);
        if ($image !== null) {
            $data['image'] = $image;
            $this->removeStoredImage($current);
        }
        $this->pageModel->update($id, $data);
        $this->flash('success', trans('admin_page_saved'));
        $this->redirect('/mf-dashboard/pages/' . $id . '/edit');
    }

    public function destroy(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/pages');
        }
        $this->removeStoredImage($this->pageModel->find($id));
        $this->pageModel->delete($id);
        $this->flash('success', trans('admin_deleted'));
        $this->redirect('/mf-dashboard/pages');
    }

    public function toggle(wsit_Request $request, int $id): void
    {
        if (!verify_csrf($request->input('_token'))) {
            $this->flash('error', trans('validate_token'));
            $this->redirect('/mf-dashboard/pages');
        }
        $page = $this->pageModel->find($id);
        if ($page) {
            $this->pageModel->update($id, ['active' => (int)$page['active'] === 1 ? 0 : 1]);
        }
        $this->redirect('/mf-dashboard/pages');
    }

    private function collectData(wsit_Request $request): array
    {
        $active = $request->input('active');
        return [
            'slug'       => slugify($request->input('slug', $request->input('title_en'))),
            'title_en'   => $request->input('title_en'),
            'title_bn'   => $request->input('title_bn', $request->input('title_en')),
            'content_en' => $request->input('content_en'),
            'content_bn' => $request->input('content_bn'),
            'menu_order' => max(0, (int)$request->input('menu_order', 0)),
            'active'     => $active === '1' ? 1 : 0,
        ];
    }

    private function uniqueSlug(string $slug, int|null $ignoreId = null): string
    {
        $base = $slug;
        $i = 1;
        while ($this->pageModel->slugExists($slug, $ignoreId)) {
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

        $name = 'page_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $allowed[$type];
        $target = public_path('assets/img/' . $name);
        if (!move_uploaded_file((string)$file['tmp_name'], $target)) {
            return null;
        }
        return 'assets/img/' . $name;
    }

    private function removeStoredImage(?array $page): void
    {
        if ($page === null) {
            return;
        }
        $path = trim((string)($page['image'] ?? ''));
        if ($path !== '' && str_starts_with($path, 'assets/img/')) {
            $file = public_path($path);
            if (is_file($file)) {
                @unlink($file);
            }
        }
    }
}