<?php

namespace App\Controllers\Admin;

use App\Core\AdminController;
use App\Models\GalleryModel;

/**
 * Contrôleur Admin — galeries photos.
 */
class GalleryController extends AdminController
{
    private GalleryModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requirePermission('galleries');
        $this->model = new GalleryModel();
    }

    public function index(): void
    {
        $this->render('admin/galleries/index', [
            'galleries' => $this->model->allWithItemCount(),
            'pageTitle' => 'Galeries',
        ]);
    }

    public function edit(string $id): void
    {
        $gallery = $this->model->findWithItems((int)$id);
        if (!$gallery) $this->abort(404);

        $this->render('admin/galleries/edit', [
            'gallery'   => $gallery,
            'pageTitle' => 'Galerie — ' . htmlspecialchars($gallery['name']),
        ]);
    }

    public function store(): void
    {
        $this->verifyCsrf();
        $id = $this->model->create([
            'name'       => $this->inputStr('name'),
            'slug'       => $this->inputStr('slug'),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/galleries/' . $id . '/edit?created=1');
    }

    public function update(string $id): void
    {
        $this->verifyCsrf();
        $this->model->update((int)$id, [
            'name'       => $this->inputStr('name'),
            'slug'       => $this->inputStr('slug'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/galleries/' . $id . '/edit?updated=1');
    }

    public function destroy(string $id): void
    {
        $this->verifyCsrf();
        $this->model->delete((int)$id);
        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/galleries?deleted=1');
    }

    public function deleteItem(string $galleryId): void
    {
        $this->verifyCsrf();
        $itemId = $this->inputInt('item_id');
        $item   = $this->model->deleteItem($itemId);

        if ($item) {
            $root = dirname(__DIR__, 3);
            foreach ([$item['image_path'], $item['thumb_path']] as $p) {
                if ($p && file_exists($root . '/' . ltrim($p, '/'))) {
                    @unlink($root . '/' . ltrim($p, '/'));
                }
            }
        }

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/galleries/' . $galleryId . '/edit');
    }
}
