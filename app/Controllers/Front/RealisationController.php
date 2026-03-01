<?php

namespace App\Controllers\Front;

use App\Core\Controller;
use App\Models\RealisationModel;
use App\Models\SettingModel;

/**
 * Contrôleur des réalisations (front-end).
 */
class RealisationController extends Controller
{
    public function index(): void
    {
        $model    = new RealisationModel();
        $settings = new SettingModel();

        $this->render('front/realisations', [
            'realisations'   => $model->published(),
            'pageTitle'      => $settings->get('realisations_meta_title', 'Nos Réalisations'),
            'pageDescription'=> $settings->get('realisations_meta_desc', ''),
            'layout'         => 'front',
        ]);
    }

    public function show(string $slug): void
    {
        // Les réalisations sont identifiées par ID dans l'URL (ex: /realisations/42)
        $id    = (int)$slug;
        $model = new RealisationModel();
        $item  = $model->findPublished($id);

        if (!$item) {
            $this->abort(404);
        }

        $images = $model->getImages($id);

        $this->render('front/realisation-detail', [
            'realisation'    => $item,
            'images'         => $images,
            'pageTitle'      => $item['meta_title'] ?: $item['title'],
            'pageDescription'=> $item['meta_description'] ?? '',
            'layout'         => 'front',
        ]);
    }
}
