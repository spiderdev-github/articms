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

        // ─── Résolution du JSON home (par thème) ─────────────────────────────
        $activeTheme   = $settings->get('active_theme', 'default');
        $rootDir       = dirname(dirname(dirname(__DIR__)));
        $jsonPath      = $rootDir . '/themes/' . $activeTheme . '/partials/home.json';
        $fallbackPath  = $rootDir . '/themes/default/partials/home.json';

        if (!file_exists($jsonPath) && file_exists($fallbackPath)) {
            $jsonPath = $fallbackPath;
        }

        $cfg = file_exists($jsonPath) ? (json_decode(file_get_contents($jsonPath), true) ?? []) : [];
        $S   = $cfg['sections'] ?? [];

        /** Lecture sûre dans un tableau associatif via chemin pointé. */
        $hcfg = function (string $path, $default = '') use ($S) {
            $node = $S;
            foreach (explode('.', $path) as $k) {
                if (is_array($node) && array_key_exists($k, $node)) {
                    $node = $node[$k];
                } else {
                    return $default;
                }
            }
            return $node ?? $default;
        };

        // ─── Réalisation mise en avant ────────────────────────────────────────
        $featuredId = (int)($S['realisations']['featured_realisation_id'] ?? 0);
        $hero       = null;
        if ($featuredId > 0) {
            $hero = $realisations->findPublished($featuredId);
        }
        if (!$hero) {
            $hero = $realisations->featured();
        }

        // ─── Prestations ──────────────────────────────────────────────────────
        $defaultPrestations = [
            ['title' => 'Peinture intérieure',               'url' => '/prestations/peinture-interieure-en-alsace',    'enabled' => true],
            ['title' => 'Isolation intérieure / extérieure', 'url' => '/prestations/isolation-interieure-exterieure',  'enabled' => true],
            ['title' => 'Travaux de facade',                  'url' => '/prestations/travaux-de-facade',                'enabled' => true],
            ['title' => 'Revêtements muraux et décoration',  'url' => '/prestations/revetements-muraux-et-decoration', 'enabled' => true],
            ['title' => 'Peinture exterieure',                'url' => '/prestations/peinture-exterieure-en-alsace',    'enabled' => true],
        ];
        $prestationsItems = $S['prestations_card']['items'] ?? $defaultPrestations;
        if (empty($prestationsItems)) {
            $prestationsItems = $defaultPrestations;
        }

        // ─── Réalisations récentes ────────────────────────────────────────────
        $latestRealisations = $realisations->published(6);

        // ─── KPIs Avant/Après : comptes par type ─────────────────────────────
        $kpis = [];
        if (!empty($S['avant_apres']['kpis']) && is_array($S['avant_apres']['kpis'])) {
            try {
                $pdo  = \App\Core\Database::getInstance();
                $rows = $pdo->query(
                    "SELECT type, COUNT(*) AS cnt FROM realisations WHERE is_published = 1 GROUP BY type"
                )->fetchAll(\PDO::FETCH_KEY_PAIR);
                foreach ($S['avant_apres']['kpis'] as $kpi) {
                    $kpis[$kpi['type_key']] = (int)($rows[$kpi['type_key']] ?? 0);
                }
            } catch (\Throwable $e) {
                // silencieux si la table n'existe pas encore
            }
        }

        // ─── Résolution de la vue (surcharge thème) ───────────────────────────
        $themeHomePath = $rootDir . '/themes/' . $activeTheme . '/partials/home.php';
        $template      = file_exists($themeHomePath) ? $themeHomePath : 'front/home';

        $this->render($template, [
            'settings'           => $settings,
            'cfg'                => $cfg,
            'hero'               => $hero,
            'prestationsItems'   => $prestationsItems,
            'latestRealisations' => $latestRealisations,
            'kpis'               => $kpis,
            // Textes dynamiques (compatibilité)
            'heroKicker'     => $hcfg('hero.kicker',             'Votre artisan peintre en Alsace'),
            'heroTitle'      => $hcfg('hero.title',              'Finitions haut de gamme'),
            'heroText'       => $hcfg('hero.text',               ''),
            'heroCtaPrimary' => $hcfg('hero.cta_primary.label',  'Demander un devis gratuit'),
            'heroCtaSecond'  => $hcfg('hero.cta_secondary.label','Voir les prestations'),
            'homeTitle'      => $hcfg('realisations.title',      'Réalisations'),
            'homeText'       => $hcfg('realisations.text',       ''),
            'pageTitle'      => $cfg['seo']['meta_title']        ?? (defined('COMPANY_NAME') ? COMPANY_NAME : 'Joker Peintre'),
            'pageDescription'=> $cfg['seo']['meta_description']  ?? '',
            'layout'         => 'front',
        ]);
    }
}
