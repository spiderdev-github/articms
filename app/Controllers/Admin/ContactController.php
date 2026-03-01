<?php

namespace App\Controllers\Admin;

use App\Core\AdminController;
use App\Models\ContactModel;

/**
 * Contrôleur Admin — gestion des contacts / pipeline.
 */
class ContactController extends AdminController
{
    private ContactModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requirePermission('contacts');
        $this->model = new ContactModel();
    }

    /* ── Liste ───────────────────────────────────────────────────────────── */

    public function index(): void
    {
        $filters = [
            'search'   => $this->inputStr('search'),
            'pipeline' => $this->inputStr('pipeline'),
            'tag'      => $this->inputStr('tag'),
            'archived' => ($this->inputStr('archived') === '1'),
            'due'      => ($this->inputStr('due') === '1'),
        ];

        $page     = max(1, $this->inputInt('page', 1));
        $sort     = $this->inputStr('sort', 'created_at');
        $dir      = $this->inputStr('dir', 'desc');

        $result   = $this->model->paginate($filters, $page, 20, $sort, $dir);

        $this->render('admin/contacts/index', [
            'contacts'  => $result['data'],
            'total'     => $result['total'],
            'pages'     => $result['pages'],
            'page'      => $page,
            'filters'   => $filters,
            'sort'      => $sort,
            'dir'       => $dir,
            'pageTitle' => 'Contacts',
        ]);
    }

    /* ── Vue détail ──────────────────────────────────────────────────────── */

    public function show(string $id): void
    {
        $contact = $this->model->find((int)$id);
        if (!$contact) $this->abort(404);

        $notes  = $this->model->getNotes((int)$id);
        $tags   = $this->model->getTags((int)$id);

        $this->render('admin/contacts/view', [
            'contact'   => $contact,
            'notes'     => $notes,
            'tags'      => $tags,
            'pageTitle' => 'Contact — ' . htmlspecialchars($contact['name']),
        ]);
    }

    /* ── Actions POST ────────────────────────────────────────────────────── */

    public function updateStatus(): void
    {
        $this->verifyCsrf();
        $id     = $this->inputInt('id');
        $status = $this->inputStr('status');

        $allowed = ['new','in_progress','treated','archived'];
        if (!in_array($status, $allowed, true)) $this->abort(422);

        $this->model->update($id, ['status' => $status]);
        $this->redirect($this->referer('/admin/contacts'));
    }

    public function updatePipeline(): void
    {
        $this->verifyCsrf();
        $id       = $this->inputInt('id');
        $pipeline = $this->inputStr('pipeline_status');

        $allowed = ['new','in_progress','quoted','won','lost'];
        if (!in_array($pipeline, $allowed, true)) $this->abort(422);

        $this->model->update($id, ['pipeline_status' => $pipeline]);
        $this->redirect($this->referer('/admin/contacts'));
    }

    public function archive(): void
    {
        $this->verifyCsrf();
        $id = $this->inputInt('id');
        $this->model->archive($id);
        $this->redirect($this->referer('/admin/contacts'));
    }

    public function restore(): void
    {
        $this->verifyCsrf();
        $id = $this->inputInt('id');
        $this->model->restore($id);
        $this->redirect($this->referer('/admin/contacts'));
    }

    public function addNote(): void
    {
        $this->verifyCsrf();
        $id      = $this->inputInt('contact_id');
        $content = $this->inputStr('content');
        if ($content) {
            $this->model->addNote($id, \App\Core\Auth::id(), $content);
        }
        $this->redirect($this->referer('/admin/contacts/' . $id));
    }

    public function addTag(): void
    {
        $this->verifyCsrf();
        $id  = $this->inputInt('contact_id');
        $tag = $this->inputStr('tag');
        if ($tag) $this->model->addTag($id, $tag);
        $this->redirect($this->referer('/admin/contacts/' . $id));
    }

    public function removeTag(): void
    {
        $this->verifyCsrf();
        $contactId = $this->inputInt('contact_id');
        $tagId     = $this->inputInt('tag_id');
        $this->model->removeTag($contactId, $tagId);
        $this->redirect($this->referer('/admin/contacts/' . $contactId));
    }

    public function followupDone(): void
    {
        $this->verifyCsrf();
        $id = $this->inputInt('id');
        $this->model->update($id, ['next_followup_at' => null]);
        $this->redirect($this->referer('/admin/contacts'));
    }

    /* ── Helper ──────────────────────────────────────────────────────────── */

    private function referer(string $fallback): string
    {
        return $_SERVER['HTTP_REFERER'] ?? ((defined('BASE_URL') ? BASE_URL : '') . $fallback);
    }
}
