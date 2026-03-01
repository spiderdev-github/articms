<?php

use App\Core\Router;
use App\Controllers\Front\HomeController;
use App\Controllers\Front\PageController;
use App\Controllers\Front\RealisationController;
use App\Controllers\Admin\AuthController;
use App\Controllers\Admin\DashboardController;
use App\Controllers\Admin\ContactController;
use App\Controllers\Admin\RealisationController as AdminRealisationController;
use App\Controllers\Admin\CmsController;
use App\Controllers\Admin\GalleryController;
use App\Controllers\Admin\MediaController;
use App\Controllers\Admin\FormController;
use App\Controllers\Admin\UserController;
use App\Controllers\Admin\SettingsController;
use App\Controllers\Admin\CrmController;

$router = new Router();

/* ══════════════════════════════════════════════════════════════════════════
   FRONT-END
══════════════════════════════════════════════════════════════════════════ */
$router->get('/',                    [HomeController::class,         'index']);
// /realisations/:id — détail d'une réalisation (avant le catch-all /:parent/:slug)
$router->get('/realisations/:id',    [RealisationController::class,  'show']);

/* Pages CMS dynamiques (catch-all — doit rester en dernier des routes front) */
/* /realisations sans :id tombe sur la page CMS slug='realisations' */
$router->get('/:slug',               [PageController::class,         'show']);
$router->get('/:parent/:slug',       [PageController::class,         'showChild']);

/* ══════════════════════════════════════════════════════════════════════════
   ADMIN — AUTH (pas besoin d'être connecté)
══════════════════════════════════════════════════════════════════════════ */
$router->get( '/admin/login',           [AuthController::class, 'loginForm']);
$router->post('/admin/login',           [AuthController::class, 'loginPost']);
$router->any( '/admin/logout',          [AuthController::class, 'logout']);
$router->get( '/admin/forgot-password', [AuthController::class, 'forgotForm']);
$router->post('/admin/forgot-password', [AuthController::class, 'forgotPost']);
$router->get( '/admin/reset-password',  [AuthController::class, 'resetForm']);
$router->post('/admin/reset-password',  [AuthController::class, 'resetPost']);

/* ══════════════════════════════════════════════════════════════════════════
   ADMIN — DASHBOARD
══════════════════════════════════════════════════════════════════════════ */
$router->get('/admin',           [DashboardController::class, 'index']);
$router->get('/admin/dashboard', [DashboardController::class, 'index']);

/* ══════════════════════════════════════════════════════════════════════════
   ADMIN — CONTACTS
══════════════════════════════════════════════════════════════════════════ */
$router->get( '/admin/contacts',                    [ContactController::class, 'index']);
$router->get( '/admin/contacts/:id',                [ContactController::class, 'show']);
$router->post('/admin/contacts/archive',            [ContactController::class, 'archive']);
$router->post('/admin/contacts/restore',            [ContactController::class, 'restore']);
$router->post('/admin/contacts/update-status',      [ContactController::class, 'updateStatus']);
$router->post('/admin/contacts/update-pipeline',    [ContactController::class, 'updatePipeline']);
$router->post('/admin/contacts/add-note',           [ContactController::class, 'addNote']);
$router->post('/admin/contacts/add-tag',            [ContactController::class, 'addTag']);
$router->post('/admin/contacts/remove-tag',         [ContactController::class, 'removeTag']);
$router->post('/admin/contacts/followup-done',      [ContactController::class, 'followupDone']);

/* ══════════════════════════════════════════════════════════════════════════
   ADMIN — RÉALISATIONS
══════════════════════════════════════════════════════════════════════════ */
$router->get( '/admin/realisations',                    [AdminRealisationController::class, 'index']);
$router->get( '/admin/realisations/create',             [AdminRealisationController::class, 'create']);
$router->post('/admin/realisations',                    [AdminRealisationController::class, 'store']);
$router->get( '/admin/realisations/:id/edit',           [AdminRealisationController::class, 'edit']);
$router->post('/admin/realisations/:id/edit',           [AdminRealisationController::class, 'update']);
$router->post('/admin/realisations/:id/destroy',        [AdminRealisationController::class, 'destroy']);
$router->post('/admin/realisations/:id/toggle',         [AdminRealisationController::class, 'toggle']);
$router->post('/admin/realisations/:id/sort-images',    [AdminRealisationController::class, 'sortImages']);
$router->post('/admin/realisations/:id/delete-image',   [AdminRealisationController::class, 'deleteImage']);

/* ══════════════════════════════════════════════════════════════════════════
   ADMIN — CMS PAGES
══════════════════════════════════════════════════════════════════════════ */
$router->get( '/admin/cms',              [CmsController::class, 'index']);
$router->get( '/admin/cms/create',       [CmsController::class, 'create']);
$router->post('/admin/cms',              [CmsController::class, 'store']);
$router->get( '/admin/cms/:id/edit',     [CmsController::class, 'edit']);
$router->post('/admin/cms/:id/edit',     [CmsController::class, 'update']);
$router->post('/admin/cms/:id/destroy',  [CmsController::class, 'destroy']);

/* ══════════════════════════════════════════════════════════════════════════
   ADMIN — GALERIES
══════════════════════════════════════════════════════════════════════════ */
$router->get( '/admin/galleries',                   [GalleryController::class, 'index']);
$router->post('/admin/galleries',                   [GalleryController::class, 'store']);
$router->get( '/admin/galleries/:id/edit',          [GalleryController::class, 'edit']);
$router->post('/admin/galleries/:id/edit',          [GalleryController::class, 'update']);
$router->post('/admin/galleries/:id/destroy',       [GalleryController::class, 'destroy']);
$router->post('/admin/galleries/:id/delete-item',   [GalleryController::class, 'deleteItem']);

/* ══════════════════════════════════════════════════════════════════════════
   ADMIN — MÉDIATHÈQUE
══════════════════════════════════════════════════════════════════════════ */
$router->get( '/admin/media',            [MediaController::class, 'index']);
$router->post('/admin/media/upload',     [MediaController::class, 'upload']);
$router->post('/admin/media/save-meta',  [MediaController::class, 'saveMeta']);
$router->post('/admin/media/destroy',    [MediaController::class, 'destroy']);

/* ══════════════════════════════════════════════════════════════════════════
   ADMIN — FORMULAIRES
══════════════════════════════════════════════════════════════════════════ */
$router->get( '/admin/forms',                              [FormController::class, 'index']);
$router->get( '/admin/forms/create',                       [FormController::class, 'create']);
$router->post('/admin/forms',                              [FormController::class, 'store']);
$router->get( '/admin/forms/:id/edit',                     [FormController::class, 'edit']);
$router->post('/admin/forms/:id/edit',                     [FormController::class, 'update']);
$router->post('/admin/forms/:id/destroy',                  [FormController::class, 'destroy']);
$router->post('/admin/forms/:id/duplicate',                [FormController::class, 'duplicate']);
$router->get( '/admin/forms/:id/submissions',              [FormController::class, 'submissions']);
$router->post('/admin/forms/:id/submissions/clear',        [FormController::class, 'clearSubmissions']);
$router->post('/admin/forms/:id/submissions/delete',       [FormController::class, 'deleteSubmission']);

/* ══════════════════════════════════════════════════════════════════════════
   ADMIN — UTILISATEURS
══════════════════════════════════════════════════════════════════════════ */
$router->get( '/admin/users',               [UserController::class, 'index']);
$router->get( '/admin/users/create',        [UserController::class, 'create']);
$router->post('/admin/users',               [UserController::class, 'store']);
$router->get( '/admin/users/:id/edit',      [UserController::class, 'edit']);
$router->post('/admin/users/:id/edit',      [UserController::class, 'update']);
$router->post('/admin/users/:id/toggle',    [UserController::class, 'toggle']);
$router->post('/admin/users/:id/destroy',   [UserController::class, 'destroy']);

/* ══════════════════════════════════════════════════════════════════════════
   ADMIN — PARAMÈTRES
══════════════════════════════════════════════════════════════════════════ */
$router->get( '/admin/settings',     [SettingsController::class, 'index']);
$router->post('/admin/settings',     [SettingsController::class, 'save']);
$router->get( '/admin/homepage',     [SettingsController::class, 'homepage']);
$router->post('/admin/homepage',     [SettingsController::class, 'homepageSave']);

/* ══════════════════════════════════════════════════════════════════════════
   ADMIN — CRM
══════════════════════════════════════════════════════════════════════════ */
$router->get( '/admin/crm/clients',             [CrmController::class, 'clients']);
$router->get( '/admin/crm/clients/create',      [CrmController::class, 'clientEdit']);
$router->post('/admin/crm/clients',             [CrmController::class, 'clientStore']);
$router->get( '/admin/crm/clients/:id/edit',    [CrmController::class, 'clientEdit']);
$router->post('/admin/crm/clients/:id/edit',    [CrmController::class, 'clientUpdate']);
$router->post('/admin/crm/clients/:id/destroy', [CrmController::class, 'clientDestroy']);
$router->get( '/admin/crm/devis',               [CrmController::class, 'devis']);
$router->get( '/admin/crm/devis/create',        [CrmController::class, 'devisEdit']);
$router->post('/admin/crm/devis',               [CrmController::class, 'devisStore']);
$router->get( '/admin/crm/devis/:id/edit',      [CrmController::class, 'devisEdit']);
$router->post('/admin/crm/devis/:id/edit',      [CrmController::class, 'devisUpdate']);
$router->post('/admin/crm/devis/:id/destroy',   [CrmController::class, 'devisDestroy']);

return $router;
