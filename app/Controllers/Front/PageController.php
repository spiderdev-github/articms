<?php

namespace App\Controllers\Front;

use App\Core\Controller;
use App\Models\CmsPageModel;

/**
 * Contrôleur des pages CMS dynamiques (front-end).
 */
class PageController extends Controller
{
    /**
     * Page CMS racine — URL : /slug
     */
    public function show(string $slug = ''): void
    {
        if (!$slug) {
            $slug = trim($_GET['slug'] ?? '');
        }

        if (!$slug) {
            $this->abort(404);
        }

        $model = new CmsPageModel();
        $page  = $model->findPublishedBySlug($slug);

        if (!$page) {
            $this->abort(404);
        }

        $this->renderPage($page);
    }

    /**
     * Page CMS enfant — URL : /parent/slug
     */
    public function showChild(string $parentSlug, string $slug): void
    {
        $model = new CmsPageModel();
        $page  = $model->findPublishedByParentAndSlug($parentSlug, $slug);

        if (!$page) {
            // Fallback : essayer comme page racine (slug unique absolu)
            $page = $model->findPublishedBySlug($slug);
        }

        if (!$page) {
            $this->abort(404);
        }

        $this->renderPage($page);
    }

    /**
     * Rendu commun.
     */
    private function renderPage(array $page): void
    {
        $this->render('front/page', [
            'page'           => $page,
            'pageTitle'      => $page['meta_title']       ?: $page['title'],
            'pageDescription'=> $page['meta_description'] ?: '',
            'layout'         => 'front',
        ]);
    }
}
