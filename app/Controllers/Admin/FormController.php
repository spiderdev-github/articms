<?php

namespace App\Controllers\Admin;

use App\Core\AdminController;
use App\Models\FormModel;

/**
 * Contrôleur Admin — formulaires dynamiques et soumissions.
 */
class FormController extends AdminController
{
    private FormModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requirePermission('forms');
        $this->model = new FormModel();
    }

    public function index(): void
    {
        $this->render('admin/forms/index', [
            'forms'     => $this->model->allWithSubmissionCount(),
            'pageTitle' => 'Formulaires',
        ]);
    }

    public function edit(string $id): void
    {
        $form = $this->model->find((int)$id);
        if (!$form) $this->abort(404);

        $this->render('admin/forms/edit', [
            'form'      => $form,
            'pageTitle' => 'Éditer — ' . htmlspecialchars($form['name']),
        ]);
    }

    public function create(): void
    {
        $this->render('admin/forms/edit', [
            'form'      => null,
            'pageTitle' => 'Nouveau formulaire',
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $id = $this->model->create($this->collectFormData() + ['created_at' => date('Y-m-d H:i:s')]);
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/forms/' . $id . '/edit?created=1');
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $this->model->update((int)$id, $this->collectFormData());
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/forms/' . $id . '/edit?updated=1');
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        $this->model->delete((int)$id);
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/forms?deleted=1');
    }

    public function duplicate(string $id): void
    {
        $this->verifyCsrf();
        $newId = $this->model->duplicate((int)$id);
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/forms/' . $newId . '/edit');
    }

    /* ── Soumissions ─────────────────────────────────────────────────────── */

    public function submissions(string $id): void
    {
        $form = $this->model->find((int)$id);
        if (!$form) $this->abort(404);

        $page   = max(1, $this->inputInt('page', 1));
        $result = $this->model->getSubmissions((int)$id, $page);

        $this->render('admin/forms/submissions', [
            'form'         => $form,
            'submissions'  => $result['data'],
            'total'        => $result['total'],
            'pages'        => $result['pages'],
            'page'         => $page,
            'pageTitle'    => 'Soumissions — ' . htmlspecialchars($form['name']),
        ]);
    }

    public function clearSubmissions(string $id): void
    {
        $this->verifyCsrf();
        $this->model->clearSubmissions((int)$id);
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/forms/' . $id . '/submissions?cleared=1');
    }

    public function deleteSubmission(string $formId): void
    {
        $this->verifyCsrf();
        $this->model->deleteSubmission($this->inputInt('submission_id'));
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/forms/' . $formId . '/submissions');
    }

    private function collectFormData(): array
    {
        return [
            'name'        => $this->inputStr('name'),
            'slug'        => $this->inputStr('slug'),
            'description' => $this->inputStr('description'),
            'fields'      => $_POST['fields']   ?? '{}',
            'settings'    => $_POST['settings'] ?? '{}',
            'is_active'   => $this->inputInt('is_active', 0),
            'updated_at'  => date('Y-m-d H:i:s'),
        ];
    }
}
