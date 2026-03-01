<?php

namespace App\Controllers\Admin;

use App\Core\AdminController;
use App\Models\SettingModel;

/**
 * Contrôleur Admin — paramètres du CMS.
 */
class SettingsController extends AdminController
{
    private SettingModel $model;

    public function __construct()
    {
        parent::__construct();
        $this->requirePermission('settings');
        $this->model = new SettingModel();
    }

    public function index(): void
    {
        $this->render('admin/settings/index', [
            'settings'  => $this->model->loadAll(),
            'pageTitle' => 'Paramètres',
        ]);
    }

    public function save(): void
    {
        $this->verifyCsrf();

        // Tout ce qui arrive en POST (sauf csrf_token) est sauvegardé
        $data = $_POST;
        unset($data['csrf_token']);

        $this->model->saveMany($data);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/settings?updated=1');
    }

    /* ── Homepage ────────────────────────────────────────────────────────── */

    public function homepage(): void
    {
        $this->render('admin/settings/homepage', [
            'settings'  => $this->model->loadAll(),
            'pageTitle' => 'Configuration accueil',
        ]);
    }

    public function homepageSave(): void
    {
        $this->verifyCsrf();

        $fields = [
            'home_hero_kicker','home_hero_title','home_hero_text',
            'home_hero_cta_primary','home_hero_cta_secondary',
            'home_trust_badge1','home_trust_badge2','home_trust_badge3',
            'home_approach_title','home_approach_text',
            'home_cta_devis_title','home_cta_devis_text',
            'home_realisations_title','home_realisations_text',
            'home_prestations_items','home_featured_realisation_id',
            'home_meta_title','home_meta_desc',
        ];

        $data = [];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) {
                $data[$f] = $_POST[$f];
            }
        }
        $this->model->saveMany($data);

        $base = defined('BASE_URL') ? BASE_URL : '';
        $this->redirect($base . '/admin/homepage?updated=1');
    }
}
