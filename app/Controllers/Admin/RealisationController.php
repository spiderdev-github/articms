<?php

namespace App\Controllers\Admin;

use App\Core\AdminController;
use App\Models\RealisationModel;

/**
 * Contrôleur Admin — gestion des réalisations.
 */
class RealisationController extends AdminController
{
    private RealisationModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requirePermission('realisations');
        $this->model = new RealisationModel();
    }

    /* ── Liste ───────────────────────────────────────────────────────────── */

    public function index(): void
    {
        $this->render('admin/realisations/index', [
            'rows'      => $this->model->allOrdered(),
            'pageTitle' => 'Réalisations',
        ]);
    }

    /* ── Création ────────────────────────────────────────────────────────── */

    public function create(): void
    {
        $this->render('admin/realisations/create', [
            'pageTitle' => 'Nouvelle réalisation',
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();

        $data = $this->collectFormData();
        $id   = $this->model->create($data);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/realisations/' . $id . '/edit?created=1');
    }

    /* ── Édition ─────────────────────────────────────────────────────────── */

    public function edit(string $id): void
    {
        $row = $this->model->find((int)$id);
        if (!$row) $this->abort(404);

        $images = $this->model->getImages((int)$id);

        $this->render('admin/realisations/edit', [
            'row'       => $row,
            'images'    => $images,
            'pageTitle' => 'Éditer — ' . htmlspecialchars($row['title']),
        ]);
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();

        $data = $this->collectFormData();
        $this->model->update((int)$id, $data);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/realisations/' . $id . '/edit?updated=1');
    }

    /* ── Suppression ─────────────────────────────────────────────────────── */

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        $this->model->delete((int)$id);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/realisations?deleted=1');
    }

    /* ── Toggle publish/featured ─────────────────────────────────────────── */

    public function toggle(string $id): void
    {
        $this->verifyCsrf();
        $field = $this->inputStr('field');
        $this->model->toggle((int)$id, $field);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/realisations');
    }

    /* ── Images ──────────────────────────────────────────────────────────── */

    public function sortImages(string $id): void
    {
        $this->verifyCsrf();
        $orderedIds = $_POST['order'] ?? [];
        $this->model->sortImages($orderedIds);
        $this->json(['ok' => true]);
    }

    public function deleteImage(string $id): void
    {
        $this->verifyCsrf();
        $imageId = $this->inputInt('image_id');
        $img     = $this->model->deleteImage($imageId);

        // Suppression du fichier physique
        if ($img) {
            $root = dirname(__DIR__, 3);
            foreach ([$img['image_path'], $img['thumb_path']] as $path) {
                if ($path && file_exists($root . '/' . ltrim($path, '/'))) {
                    @unlink($root . '/' . ltrim($path, '/'));
                }
            }
        }

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/realisations/' . $id . '/edit');
    }

    /* ── Collecte du formulaire ──────────────────────────────────────────── */

    private function collectFormData(): array
    {
        return [
            'title'            => $this->inputStr('title'),
            'slug'             => $this->inputStr('slug'),
            'city'             => $this->inputStr('city'),
            'type'             => $this->inputStr('type'),
            'description'      => $_POST['description'] ?? '',
            'meta_title'       => $this->inputStr('meta_title'),
            'meta_description' => $this->inputStr('meta_description'),
            'is_published'     => $this->inputInt('is_published', 0),
            'is_featured'      => $this->inputInt('is_featured', 0),
            'sort_order'       => $this->inputInt('sort_order', 0),
            'updated_at'       => date('Y-m-d H:i:s'),
        ];
    }
}
