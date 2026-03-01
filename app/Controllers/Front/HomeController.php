<?php

namespace App\Controllers\Front;

use App\Core\Controller;
use App\Models\RealisationModel;
use App\Models\SettingModel;

/**
 * Contrôleur de la page d'accueil (front-end).
 */
class HomeController extends Controller
{
    public function index(): void
    {
        $realisations = new RealisationModel();
        $settings     = new SettingModel();
        $settings->loadAll();

        // Featured réalisation (favoris ou forcé par setting)
        $featuredId = (int)$settings->get('home_featured_realisation_id', '0');
        $hero       = null;

        if ($featuredId > 0) {
            $hero = $realisations->findPublished($featuredId);
        }
        if (!$hero) {
            $hero = $realisations->featured();
        }

        // Prestations (JSON → tableau)
        $defaultPrestations = [
            ['title' => 'Peinture intérieure',               'url' => '/prestations/peinture-interieure-en-alsace',    'enabled' => true],
            ['title' => 'Isolation intérieure / extérieure', 'url' => '/prestations/isolation-interieure-exterieure',  'enabled' => true],
            ['title' => 'Travaux de facade',                  'url' => '/prestations/travaux-de-facade',                'enabled' => true],
            ['title' => 'Revêtements muraux et décoration',  'url' => '/prestations/revetements-muraux-et-decoration', 'enabled' => true],
            ['title' => 'Peinture exterieure',                'url' => '/prestations/peinture-exterieure-en-alsace',    'enabled' => true],
        ];
        $prestationsRaw   = $settings->get('home_prestations_items', '');
        $prestationsItems = $prestationsRaw ? (json_decode($prestationsRaw, true) ?: $defaultPrestations) : $defaultPrestations;

        // Réalisations récentes pour la home
        $latestRealisations = $realisations->published(6);

        $this->render('front/home', [
            'settings'           => $settings,
            'hero'               => $hero,
            'prestationsItems'   => $prestationsItems,
            'latestRealisations' => $latestRealisations,
            // Textes dynamiques
            'heroKicker'     => $settings->get('home_hero_kicker',         'Votre artisan peintre en Alsace'),
            'heroTitle'      => $settings->get('home_hero_title',          'Finitions haut de gamme'),
            'heroText'       => $settings->get('home_hero_text',           ''),
            'heroCtaPrimary' => $settings->get('home_hero_cta_primary',    'Demander un devis gratuit'),
            'heroCtaSecond'  => $settings->get('home_hero_cta_secondary',  'Voir les prestations'),
            'homeTitle'      => $settings->get('home_realisations_title',  'Réalisations'),
            'homeText'       => $settings->get('home_realisations_text',   ''),
            'pageTitle'      => $settings->get('home_meta_title',          defined('COMPANY_NAME') ? COMPANY_NAME : 'Joker Peintre'),
            'pageDescription'=> $settings->get('home_meta_desc',           ''),
            'layout'         => 'front',
        ]);
    }
}
