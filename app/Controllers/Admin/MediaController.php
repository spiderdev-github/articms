<?php

namespace App\Controllers\Admin;

use App\Core\AdminController;
use App\Models\MediaModel;

/**
 * Contrôleur Admin — médiathèque.
 */
class MediaController extends AdminController
{
    private MediaModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requirePermission('media');
        $this->model = new MediaModel();
    }

    public function index(): void
    {
        $meta    = $this->model->getAllMeta();
        $baseDir = realpath(dirname(__DIR__, 3) . '/assets/images');
        $baseUrl = (defined('BASE_URL') ? BASE_URL : '') . '/assets/images';
        $images  = $this->scanImages($baseDir, $baseUrl);

        // Enrichit avec alt_text depuis BDD
        foreach ($images as &$img) {
            $img['alt'] = $meta[$img['rel']]['alt'] ?? '';
        }
        unset($img);

        $this->render('admin/media/index', [
            'images'    => $images,
            'pageTitle' => 'Médiathèque',
        ]);
    }

    public function saveMeta(): void
    {
        $this->verifyCsrf();
        $rel = $this->inputStr('rel');
        $alt = $this->inputStr('alt_text');
        $this->model->saveAlt($rel, $alt);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/media?updated=meta');
    }

    public function upload(): void
    {
        $this->verifyCsrf();

        $file      = $_FILES['image'] ?? null;
        $targetDir = dirname(__DIR__, 3) . '/assets/images/medias/';

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $this->abort(422, 'Fichier invalide.');
        }

        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['jpg','jpeg','png','webp','gif','svg','avif'];
        if (!in_array($ext, $allowed, true)) {
            $this->abort(422, 'Type de fichier non autorisé.');
        }

        if (!is_dir($targetDir)) mkdir($targetDir, 0775, true);

        $filename = uniqid('img_', true) . '.' . $ext;
        move_uploaded_file($file['tmp_name'], $targetDir . $filename);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/media?updated=uploaded');
    }

    public function destroy(): void
    {
        $this->verifyCsrf();
        $rel   = $this->inputStr('rel');
        $root  = dirname(__DIR__, 3) . '/assets/images/';
        $clean = ltrim($rel, '/');

        if (
            !str_contains($rel, '..') &&
            str_starts_with($clean, 'medias/') &&
            file_exists($root . $clean)
        ) {
            unlink($root . $clean);
            $this->model->deleteByRel($rel);
        }

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/media?updated=deleted');
    }

    /* ── Helpers ─────────────────────────────────────────────────────────── */

    private function scanImages(string $dir, string $baseUrl): array
    {
        if (!$dir || !is_dir($dir)) return [];

        $imgs = [];
        $iter = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iter as $file) {
            if (!$file->isFile()) continue;
            $ext = strtolower($file->getExtension());
            if (!in_array($ext, ['jpg','jpeg','png','gif','webp','svg','avif'], true)) continue;

            $real = str_replace('\\', '/', $file->getRealPath());
            $base = str_replace('\\', '/', $dir);

            if (str_contains($real, '/realisations/')) continue;

            $rel = ltrim(str_replace($base, '', $real), '/');
            $imgs[] = [
                'path'      => $real,
                'rel'       => $rel,
                'url'       => $baseUrl . '/' . $rel,
                'name'      => $file->getFilename(),
                'size'      => $file->getSize(),
                'mtime'     => $file->getMTime(),
                'deletable' => str_contains($rel, 'medias/'),
                'editable'  => str_contains($rel, 'medias/'),
            ];
        }

        usort($imgs, fn($a, $b) => $b['mtime'] - $a['mtime']);
        return $imgs;
    }
}
