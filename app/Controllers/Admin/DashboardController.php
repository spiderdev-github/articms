<?php

namespace App\Controllers\Admin;

use App\Core\AdminController;
use App\Core\Auth;
use App\Models\ContactModel;
use App\Models\RealisationModel;
use App\Models\GalleryModel;
use App\Models\CmsPageModel;
use App\Models\MediaModel;
use App\Models\FormModel;
use App\Models\AdminModel;
use App\Models\CrmClientModel;
use App\Models\CrmDevisModel;
use App\Models\SettingModel;

/**
 * Contrôleur du Dashboard admin.
 */
class DashboardController extends AdminController
{
    public function index(): void
    {
        $this->requirePermission('dashboard');

        $contacts     = new ContactModel();
        $realisations = new RealisationModel();
        $galleries    = new GalleryModel();
        $cmsPages     = new CmsPageModel();
        $media        = new MediaModel();
        $forms        = new FormModel();
        $admins       = new AdminModel();
        $settings     = new SettingModel();

        // KPI stats
        $kpi = [
            'contacts'          => $contacts->stats(),
            'realisations_total'=> $realisations->count(['is_published' => 1]),
            'realisations_draft'=> $realisations->count(['is_published' => 0]),
            'galleries_total'   => $galleries->countGalleries(),
            'gallery_items'     => $galleries->countItems(),
            'cms_pages'         => $cmsPages->countPublished(),
            'media_total'       => $media->countMedia(),
            'submissions_unread'=> $forms->countUnread(),
            'admins_active'     => $admins->countActive(),
        ];

        // CRM (si permission)
        $crmStats    = [];
        $recentDevis = [];
        if (Auth::can('crm')) {
            $crm         = new CrmDevisModel();
            $crmStats    = $crm->stats();
            $crmStats['clients'] = (new CrmClientModel())->countAll();
            $recentDevis = $crm->recentWithClient(6);
        }

        // Graphiques
        $monthRows    = $contacts->contactsByMonth();
        $serviceRows  = $contacts->topServices(6);

        // Reconstruit les 12 derniers mois
        $monthMap = [];
        for ($i = 11; $i >= 0; $i--) {
            $key = date('Y-m', strtotime("-$i month"));
            $monthMap[$key] = ['new_c' => 0, 'treated_c' => 0];
        }
        foreach ($monthRows as $mr) {
            if (isset($monthMap[$mr['m']])) {
                $monthMap[$mr['m']] = ['new_c' => (int)$mr['new_c'], 'treated_c' => (int)$mr['treated_c']];
            }
        }

        $monthNames  = ['01'=>'Jan','02'=>'Fév','03'=>'Mar','04'=>'Avr','05'=>'Mai','06'=>'Juin',
                        '07'=>'Juil','08'=>'Aoû','09'=>'Sep','10'=>'Oct','11'=>'Nov','12'=>'Déc'];
        $chartLabels = $chartNew = $chartTreated = [];
        foreach ($monthMap as $ym => $vals) {
            [$y, $mo] = explode('-', $ym);
            $chartLabels[]  = ($monthNames[$mo] ?? $mo) . " '" . substr($y, 2);
            $chartNew[]     = $vals['new_c'];
            $chartTreated[] = $vals['treated_c'];
        }

        $this->render('admin/dashboard/index', [
            'kpi'             => $kpi,
            'crmStats'        => $crmStats,
            'recentDevis'     => $recentDevis,
            'recentContacts'  => $contacts->recentContacts(5),
            'recentRealisations' => $realisations->recent(5),
            'chartLabels'     => json_encode($chartLabels, JSON_UNESCAPED_UNICODE),
            'chartNew'        => json_encode($chartNew),
            'chartTreated'    => json_encode($chartTreated),
            'serviceLabels'   => json_encode(array_column($serviceRows, 'service'), JSON_UNESCAPED_UNICODE),
            'serviceValues'   => json_encode(array_map('intval', array_column($serviceRows, 'c'))),
            'activeTheme'     => $settings->get('active_theme', 'default'),
            'pageTitle'       => 'Dashboard',
        ]);
    }
}
