<?php

namespace App\Controllers\Admin;

use App\Core\AdminController;
use App\Models\CmsPageModel;

/**
 * Contrôleur Admin — pages CMS.
 */
class CmsController extends AdminController
{
    private CmsPageModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requirePermission('cms');
        $this->model = new CmsPageModel();
    }

    public function index(): void
    {
        $this->render('admin/cms/index', [
            'pages'     => $this->model->allPages(),
            'pageTitle' => 'Pages CMS',
        ]);
    }

    public function create(): void
    {
        $this->render('admin/cms/edit', [
            'page'      => null,
            'pageTitle' => 'Nouvelle page',
        ]);
    }

    public function edit(string $id): void
    {
        $page = $this->model->find((int)$id);
        if (!$page) $this->abort(404);

        $this->render('admin/cms/edit', [
            'page'      => $page,
            'pageTitle' => 'Éditer — ' . htmlspecialchars($page['title']),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $data = $this->collectFormData();
        $data['created_at'] = date('Y-m-d H:i:s');
        $id = $this->model->create($data);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/cms/' . $id . '/edit?created=1');
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $data = $this->collectFormData();
        $this->model->update((int)$id, $data);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/cms/' . $id . '/edit?updated=1');
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        $this->model->delete((int)$id);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/cms?deleted=1');
    }

    private function collectFormData(): array
    {
        $slug = $this->inputStr('slug')
            ?: preg_replace('/[^a-z0-9\-]/', '-', strtolower($this->inputStr('title')));

        return [
            'title'            => $this->inputStr('title'),
            'slug'             => $slug,
            'content'          => $_POST['content'] ?? '',
            'meta_title'       => $this->inputStr('meta_title'),
            'meta_description' => $this->inputStr('meta_description'),
            'is_published'     => $this->inputInt('is_published', 0),
            'updated_at'       => date('Y-m-d H:i:s'),
        ];
    }
}
