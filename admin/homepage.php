<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth.php';
requirePermission('themes');
require_once __DIR__ . '/../includes/settings.php';

$csrf    = getCsrfToken();
$saved   = isset($_GET['saved']);
$section = $_GET['section'] ?? '';

$pdo = getPDO();
// Liste des réalisations publiées pour le sélecteur
$realisationsList = $pdo->query(
  "SELECT id, title, city, type FROM realisations WHERE is_published=1 ORDER BY sort_order ASC, created_at DESC"
)->fetchAll();

// Prestations JSON
$defaultPrestations = [
  ['title'=>'Peinture intérieure',               'subtitle'=>'Tous types de travaux',                                                        'url'=>'/prestations/peinture-interieure-en-alsace',    'enabled'=>true],
  ['title'=>'Isolation intérieure / extérieure', 'subtitle'=>"Confort thermique, economies d'energie, reduction des nuisances sonores",   'url'=>'/prestations/isolation-interieure-exterieure',  'enabled'=>true],
  ['title'=>'Travaux de facade',                  'subtitle'=>'Rénovation, protection aux intempéries',                                        'url'=>'/prestations/travaux-de-facade',                'enabled'=>true],
  ['title'=>'Revêtements muraux et décoration',  'subtitle'=>'Decoratif, relief, cachet premium',                                           'url'=>'/prestations/revetements-muraux-et-decoration', 'enabled'=>true],
  ['title'=>'Peinture exterieure',                'subtitle'=>'Nettoyage, protection, tenue aux intemperies, rendu durable',                   'url'=>'/prestations/peinture-exterieure-en-alsace',    'enabled'=>true],
];
$prestationsRaw   = getSetting('home_prestations_items', '');
$prestationsItems = $prestationsRaw ? (json_decode($prestationsRaw, true) ?: $defaultPrestations) : $defaultPrestations;

// Charger toutes les valeurs actuelles
$fields = [
    // SEO
    'home_meta_title'    => ['Titre SEO (balise title)', 'text', 'Joker Peintre - Peinture & Décoration en Alsace'],
    'home_meta_desc'     => ['Meta description', 'textarea', 'Entreprise de peinture en Alsace : intérieur, extérieur, isolation, crépi facade et mosaïque effet pierre. Devis gratuit rapide.'],
    // Hero
    'home_hero_kicker'       => ['Kicker (texte au-dessus du titre)', 'text', 'Votre artisan peintre en Alsace'],
    'home_hero_title'        => ['Titre principal (H1)', 'text', 'Finitions haut de gamme pour vos murs, facades et renovations'],
    'home_hero_text'         => ['Texte sous le titre', 'textarea', 'Peinture intérieure et extérieure, isolation, rénovation, revêtements muraux, boiserie, décoration et mosaïques... Votre projet maitrisé de A à Z, avec une attention particulière aux détails et finitions'],
    'home_hero_cta_primary'  => ['Bouton CTA principal', 'text', 'Demander un devis gratuit'],
    'home_hero_cta_secondary'=> ['Bouton CTA secondaire', 'text', 'Voir les prestations'],
    //Prestations
    'home_prestations_card_subtitle' => ['Sous-titre de la carte Prestations', 'text', 'Peinture & Decoration'],
    // Badges
    'home_trust_badge1'  => ['Badge de confiance 1', 'text', 'Devis rapide'],
    'home_trust_badge2'  => ['Badge de confiance 2', 'text', 'Finitions propres'],
    'home_trust_badge3'  => ['Badge de confiance 3', 'text', 'Intervention Alsace'],
    // Section réalisations
    'home_realisations_title'        => ['Titre section réalisations', 'text', 'Réalisations'],
    'home_realisations_text'         => ['Texte section réalisations', 'textarea', 'Découvre quelques projets récents en Alsace. Finition propre, rendu durable.'],
    'home_featured_realisation_id'   => ['Réalisation mise en avant', 'select', ''],
    // SEO local
    'home_local_badge_title' => ['Titre du badge SEO local', 'text', 'Zone d\'intervention'],
    'home_local_title'  => ['Titre SEO local', 'text', "Joker Peintre intervient dans toute l'Alsace"],
    'home_local_intro'  => ['Texte d\'introduction', 'textarea', 'Bas-Rhin et Haut-Rhin : peinture intérieure, extérieure, isolation, crépi facade et décoration.'],
    'home_local_cities' => ['Villes (séparées par virgule)', 'text', 'Strasbourg, Haguenau, Selestat, Colmar, Mulhouse, Saint-Louis'],
    // Approche
    'home_approach_title'       => ['Titre section approche', 'text', 'Une approche premium, simple et transparente'],
    'home_approach_text'        => ['Texte section approche', 'textarea', "Préparation sérieuse, matériaux adaptés, exécution propre. L'objectif : un résultat net et durable."],
    'home_approach_card1_title' => ['Bloc 1 — Titre', 'text', 'Préparation des supports'],
    'home_approach_card1_text'  => ['Bloc 1 — Texte', 'textarea', "Protection, rebouchage, poncéage et accroche. C'est la clé d'une finition haut de gamme."],
    'home_approach_card2_title' => ['Bloc 2 — Titre', 'text', 'Finition nette'],
    'home_approach_card2_text'  => ['Bloc 2 — Texte', 'textarea', 'Angles propres, uniformité, rendu régulier. Un travail qui se voit, sans surprises.'],
    'home_approach_card3_title' => ['Bloc 3 — Titre', 'text', 'Chantier maîtrisé'],
    'home_approach_card3_text'  => ['Bloc 3 — Texte', 'textarea', 'Organisation, respect des lieux, nettoyage. Vous retrouvez un espace impeccable.'],
    // CTA Devis
    'home_cta_devis_title' => ['Titre du bandeau devis', 'text', "Besoin d'un devis ?"],
    'home_cta_devis_text'  => ['Texte du bandeau devis', 'textarea', 'Réponse rapide. Décris ton projet, surface, ville et délai.'],
    // Avant / Après
    'realisations_before_after_enabled'  => ['Activer la section Avant/Après', 'toggle', '1'],
    'realisations_before_after_title'    => ['Titre section Avant/Après', 'text', 'Avant / Après'],
    'realisations_before_after_subtitle' => ['Sous-titre section Avant/Après', 'text', 'La différence se voit dans les détails.'],
    // Visibilité des sections
    'section_hero_enabled'         => ['Section Héro visible',          'toggle', '1'],
    'section_prestations_enabled'  => ['Carte Prestations visible',     'toggle', '1'],
    'section_badges_enabled'       => ['Badges de confiance visibles',  'toggle', '1'],
    'section_approche_enabled'     => ['Section Approche visible',      'toggle', '1'],
    'section_realisations_enabled' => ['Section Réalisations visible',  'toggle', '1'],
    'section_ba_enabled'           => ['Carte Avant/Après visible',     'toggle', '1'],
    'section_cta_enabled'          => ['Bandeau Devis visible',         'toggle', '1'],
    'section_local_enabled'        => ['Bloc SEO Local visible',        'toggle', '1'],
    'home_prestations_footer_enabled'    => ['Bloc présentation pied de page visible', 'toggle', '1'],
    'home_prestations_footer_city' => ['Ville affichée dans le pied de page', 'text', 'Alsace - Bas-Rhin - Haut-Rhin']
];

$values = [];
foreach ($fields as $key => $def) {
    $values[$key] = getSetting($key, $def[2]);
}

$pageTitle = 'Gestion de la page d\'accueil';
require_once __DIR__ . '/partials/header.php';
?>
<div class="content-wrapper" style="margin:-15px">
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h4 class="m-0"><i class="fas fa-home mr-2"></i>Page d'accueil</h4>
        </div>
        <div class="col-sm-6">
          <ol class="breadcrumb float-sm-right">
            <li class="breadcrumb-item"><a href="themes.php">Thèmes</a></li>
            <li class="breadcrumb-item active">Page d'accueil</li>
          </ol>
        </div>
      </div>
    </div>
  </div>

  <div class="content">
    <div class="container-fluid">

      <?php if ($saved): ?>
      <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle mr-2"></i> Modifications enregistrées avec succès.
        <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
      </div>
      <?php endif; ?>

      <div class="row">
        <!-- ── Sidebar navigation ─────────────────────────────────────────── -->
        <div class="col-lg-3 col-md-4 mb-3">
          <div class="card card-outline card-primary sticky-top" style="top:70px;">
            <div class="card-header">
              <h3 class="card-title"><i class="fas fa-list mr-2"></i>Sections</h3>
            </div>
            <div class="card-body p-0">
              <div class="list-group list-group-flush">
                <a href="#section-seo"          class="list-group-item list-group-item-action"><i class="fas fa-search mr-2 text-muted"></i>SEO / Meta</a>
                <a href="#section-hero"         class="list-group-item list-group-item-action"><i class="fas fa-star mr-2 text-muted"></i>Section Héro</a>
                <a href="#section-badges"       class="list-group-item list-group-item-action"><i class="fas fa-shield-alt mr-2 text-muted"></i>Badges de confiance</a>
                <a href="#section-prestations"   class="list-group-item list-group-item-action"><i class="fas fa-list-ul mr-2 text-muted"></i>Prestations (carte)</a>
                <a href="#section-approche"     class="list-group-item list-group-item-action"><i class="fas fa-gem mr-2 text-muted"></i>Section Approche</a>                
                <a href="#section-realisations" class="list-group-item list-group-item-action"><i class="fas fa-paint-roller mr-2 text-muted"></i>Section Réalisations</a>
                <a href="#section-ba"           class="list-group-item list-group-item-action"><i class="fas fa-exchange-alt mr-2 text-muted"></i>Avant / Après</a>
                <a href="#section-local"        class="list-group-item list-group-item-action"><i class="fas fa-map-marker-alt mr-2 text-muted"></i>SEO Local / Villes</a>
                <a href="#section-cta"          class="list-group-item list-group-item-action"><i class="fas fa-file-invoice mr-2 text-muted"></i>Bandeau Devis</a>
              </div>
            </div>
            <div class="card-footer d-flex flex-column" style="gap:6px;">
              <a href="page-editor.php?file=index.php" class="btn btn-sm btn-outline-warning btn-block">
                <i class="fas fa-code mr-1"></i> Modifier le template HTML
              </a>
              <a href="<?= BASE_URL ?>/" target="_blank" class="btn btn-sm btn-outline-secondary btn-block">
                <i class="fas fa-eye mr-1"></i> Voir la page d'accueil
              </a>
            </div>
          </div>
        </div><!-- /.sidebar -->

        <!-- ── Formulaire principal ───────────────────────────────────────── -->
        <div class="col-lg-9 col-md-8">
          <form method="POST" action="actions/homepage-save.php">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <!-- ── SEO ───────────────────────────────────────────────────── -->
            <div class="card card-outline card-info mb-4" id="section-seo">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-search mr-2"></i>SEO / Meta</h3>
                <div class="card-tools">
                  <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
              </div>
              <div class="card-body">
                <div class="form-group">
                  <label for="home_meta_title">Titre SEO <small class="text-muted">(balise &lt;title&gt;)</small></label>
                  <input type="text" id="home_meta_title" name="home_meta_title"
                         class="form-control" maxlength="120"
                         value="<?= htmlspecialchars($values['home_meta_title']) ?>">
                  <small class="form-text text-muted">Recommandé : 50–70 caractères.</small>
                </div>
                <div class="form-group mb-0">
                  <label for="home_meta_desc">Meta description</label>
                  <textarea id="home_meta_desc" name="home_meta_desc"
                            class="form-control" rows="3" maxlength="320"><?= htmlspecialchars($values['home_meta_desc']) ?></textarea>
                  <small class="form-text text-muted">Recommandé : 120–160 caractères.</small>
                </div>
              </div>
            </div>

            <!-- ── HERO ──────────────────────────────────────────────────── -->
            <div class="card card-outline card-danger mb-4" id="section-hero">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-star mr-2"></i>Section Héro</h3>
                <div class="card-tools d-flex align-items-center">
                  <div class="custom-control custom-switch mr-2">
                    <input type="checkbox" class="custom-control-input" id="section_hero_enabled" name="section_hero_enabled" value="1" <?= $values['section_hero_enabled']==='1'?'checked':'' ?>>
                    <label class="custom-control-label" for="section_hero_enabled" style="font-size:11px;color:#6c757d;">Visible</label>
                  </div>
                  <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
              </div>
              <div class="card-body">
                <div class="form-group">
                  <label for="home_hero_kicker">Kicker <small class="text-muted">(texte court au-dessus du titre)</small></label>
                  <input type="text" id="home_hero_kicker" name="home_hero_kicker"
                         class="form-control" value="<?= htmlspecialchars($values['home_hero_kicker']) ?>">
                </div>
                <div class="form-group">
                  <label for="home_hero_title">Titre principal <small class="text-muted">(H1)</small></label>
                  <input type="text" id="home_hero_title" name="home_hero_title"
                         class="form-control" value="<?= htmlspecialchars($values['home_hero_title']) ?>">
                </div>
                <div class="form-group">
                  <label for="home_hero_text">Texte sous le titre</label>
                  <textarea id="home_hero_text" name="home_hero_text"
                            class="form-control" rows="3"><?= htmlspecialchars($values['home_hero_text']) ?></textarea>
                </div>
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group mb-0">
                      <label for="home_hero_cta_primary">Bouton CTA principal</label>
                      <input type="text" id="home_hero_cta_primary" name="home_hero_cta_primary"
                             class="form-control" value="<?= htmlspecialchars($values['home_hero_cta_primary']) ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-0">
                      <label for="home_hero_cta_secondary">Bouton CTA secondaire</label>
                      <input type="text" id="home_hero_cta_secondary" name="home_hero_cta_secondary"
                             class="form-control" value="<?= htmlspecialchars($values['home_hero_cta_secondary']) ?>">
                    </div>
                  </div>
                </div>
              </div>

            
              <!-- ── BADGES ────────────────────────────────────────────────── -->


              <div class="card-body">
                <hr>
                <div class="form-group">
                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="section_badges_enabled"
                           name="section_badges_enabled" value="1"
                           <?= $values['section_badges_enabled'] === '1' ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="section_badges_enabled">Afficher la section Badges de confiance sur la page d'accueil</label>
                  </div>
                </div>

                <div class="row">
                  <?php foreach (['home_trust_badge1'=>'Badge 1','home_trust_badge2'=>'Badge 2','home_trust_badge3'=>'Badge 3'] as $k => $l): ?>
                  <div class="col-md-4">
                    <div class="form-group mb-0">
                      <label for="<?= $k ?>"><?= $l ?></label>
                      <input type="text" id="<?= $k ?>" name="<?= $k ?>"
                             class="form-control" value="<?= htmlspecialchars($values[$k]) ?>">
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              </div>
            </div>

            <!-- ── PRESTATIONS (carte héro) ───────────────────────────────── -->
            <div class="card card-outline card-purple mb-4" id="section-prestations">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-list-ul mr-2"></i>Prestations (carte héro)</h3>
                <div class="card-tools d-flex align-items-center">
                  <div class="custom-control custom-switch mr-2">
                    <input type="checkbox" class="custom-control-input" id="section_prestations_enabled" name="section_prestations_enabled" value="1" <?= $values['section_prestations_enabled']==='1'?'checked':'' ?>>
                    <label class="custom-control-label" for="section_prestations_enabled" style="font-size:11px;color:#6c757d;">Visible</label>
                  </div>
                  <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
              </div>
              <div class="card-body">

                <div class="row mb-3">
                  <div class="col-md-6">
                    <div class="form-group mb-0">
                      <label for="home_prestations_card_title">Titre de la carte</label>
                      <input type="text" id="home_prestations_card_title" name="home_prestations_card_title"
                             class="form-control" value="<?= htmlspecialchars(getSetting('home_prestations_card_title','Prestations')) ?>">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group mb-0">
                      <label for="home_prestations_card_subtitle">Sous-titre de la carte</label>
                      <input type="text" id="home_prestations_card_subtitle" name="home_prestations_card_subtitle"
                             class="form-control" value="<?= htmlspecialchars(getSetting('home_prestations_card_subtitle','Peinture &amp; Decoration')) ?>">
                    </div>
                  </div>
                </div>

                <hr>

                <div id="prestations-list">
                  <?php foreach ($prestationsItems as $idx => $p): ?>
                  <div class="prestation-row card card-body bg-light mb-2 p-2" data-index="<?= $idx ?>">
                    <input type="hidden" name="prestation_index[]" value="<?= $idx ?>">
                    <div class="d-flex align-items-start" style="gap:10px;">
                      <!-- Toggle -->
                      <div style="padding-top:6px;flex-shrink:0;">
                        <div class="custom-control custom-switch">
                          <input type="checkbox" class="custom-control-input"
                                 id="prest_enabled_<?= $idx ?>" name="prestation_enabled[<?= $idx ?>]" value="1"
                                 <?= !empty($p['enabled']) ? 'checked' : '' ?>>
                          <label class="custom-control-label" for="prest_enabled_<?= $idx ?>"></label>
                        </div>
                      </div>
                      <!-- Champs -->
                      <div class="flex-grow-1">
                        <div class="row" style="row-gap:6px;">
                          <div class="col-md-4">
                            <input type="text" name="prestation_title[<?= $idx ?>]" placeholder="Titre"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($p['title']) ?>">
                          </div>
                          <div class="col-md-4">
                            <input type="text" name="prestation_subtitle[<?= $idx ?>]" placeholder="Sous-titre"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($p['subtitle']) ?>">
                          </div>
                          <div class="col-md-4">
                            <input type="text" name="prestation_url[<?= $idx ?>]" placeholder="/prestations/slug"
                                   class="form-control form-control-sm"
                                   value="<?= htmlspecialchars($p['url']) ?>">
                          </div>
                        </div>
                      </div>
                      <!-- Delete -->
                      <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0 prestation-delete" title="Supprimer">
                        <i class="fas fa-trash"></i>
                      </button>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
                  
                <button type="button" id="prestation-add" class="btn btn-sm btn-outline-success mt-2">
                  <i class="fas fa-plus mr-1"></i> Ajouter une prestation
                </button>
                <small class="d-block text-muted mt-1">Titre, sous-titre et lien (chemin relatif). Le toggle active/désactive l'affichage.</small>
                
                <hr>
                <div class="form-group">
                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="home_prestations_footer_enabled"
                           name="home_prestations_footer_enabled" value="1"
                           <?= $values['home_prestations_footer_enabled'] === '1' ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="home_prestations_footer_enabled">Afficher la section Avant/Après sur la page d'accueil</label>
                  </div>
                </div>

                <div class="row mb-3">
                  <div class="col-md-12">
                    <div class="form-group mb-0">
                      <label for="home_prestations_footer_city">Ville liste</label>
                      <input type="text" id="home_prestations_footer_city" name="home_prestations_footer_city"
                            class="form-control" value="<?= htmlspecialchars(getSetting('home_prestations_footer_city','')) ?>">
                    </div>
                  </div>
                </div>
              </div>

            </div>

            <!-- ── APPROCHE ───────────────────────────────────────────────── -->
            <div class="card card-outline card-secondary mb-4" id="section-approche">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-gem mr-2"></i>Section Approche</h3>
                <div class="card-tools d-flex align-items-center">
                  <div class="custom-control custom-switch mr-2">
                    <input type="checkbox" class="custom-control-input" id="section_approche_enabled" name="section_approche_enabled" value="1" <?= $values['section_approche_enabled']==='1'?'checked':'' ?>>
                    <label class="custom-control-label" for="section_approche_enabled" style="font-size:11px;color:#6c757d;">Visible</label>
                  </div>
                  <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
              </div>
              <div class="card-body">
                <div class="form-group">
                  <label for="home_approach_title">Titre</label>
                  <input type="text" id="home_approach_title" name="home_approach_title"
                         class="form-control" value="<?= htmlspecialchars($values['home_approach_title']) ?>">
                </div>
                <div class="form-group">
                  <label for="home_approach_text">Texte</label>
                  <textarea id="home_approach_text" name="home_approach_text"
                            class="form-control" rows="3"><?= htmlspecialchars($values['home_approach_text']) ?></textarea>
                </div>

                <hr>
                <p class="text-muted mb-2"><small>Les 3 blocs affichés sous le titre :</small></p>

                <?php foreach ([1,2,3] as $n): ?>
                <div class="card card-body bg-light mb-2 p-2">
                  <div class="row" style="row-gap:6px;">
                    <div class="col-md-4">
                      <label class="mb-1"><small>Bloc <?= $n ?> — Titre</small></label>
                      <input type="text" name="home_approach_card<?= $n ?>_title"
                             class="form-control form-control-sm"
                             value="<?= htmlspecialchars($values['home_approach_card'.$n.'_title']) ?>">
                    </div>
                    <div class="col-md-8">
                      <label class="mb-1"><small>Bloc <?= $n ?> — Texte</small></label>
                      <textarea name="home_approach_card<?= $n ?>_text"
                                class="form-control form-control-sm" rows="2"><?= htmlspecialchars($values['home_approach_card'.$n.'_text']) ?></textarea>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

            
            <!-- ── RÉALISATIONS ───────────────────────────────────────────── -->
            <div class="card card-outline card-success mb-4" id="section-realisations">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-paint-roller mr-2"></i>Section Réalisations</h3>
                <div class="card-tools d-flex align-items-center">
                  <div class="custom-control custom-switch mr-2">
                    <input type="checkbox" class="custom-control-input" id="section_realisations_enabled" name="section_realisations_enabled" value="1" <?= $values['section_realisations_enabled']==='1'?'checked':'' ?>>
                    <label class="custom-control-label" for="section_realisations_enabled" style="font-size:11px;color:#6c757d;">Visible</label>
                  </div>
                  <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
              </div>
              <div class="card-body">
                <div class="form-group">
                  <label for="home_realisations_title">Titre de la section</label>
                  <input type="text" id="home_realisations_title" name="home_realisations_title"
                         class="form-control" value="<?= htmlspecialchars($values['home_realisations_title']) ?>">
                </div>
                <div class="form-group">
                  <label for="home_realisations_text">Texte d'introduction</label>
                  <textarea id="home_realisations_text" name="home_realisations_text"
                            class="form-control" rows="3"><?= htmlspecialchars($values['home_realisations_text']) ?></textarea>
                </div>

                <div class="form-group mb-0">
                  <label for="home_featured_realisation_id">Réalisation mise en avant <small class="text-muted">(grande carte à gauche)</small></label>
                  <select id="home_featured_realisation_id" name="home_featured_realisation_id" class="form-control">
                    <option value="">— Automatique (la plus récente / mise en avant) —</option>
                    <?php foreach ($realisationsList as $r): ?>
                    <option value="<?= (int)$r['id'] ?>"
                      <?= (string)$values['home_featured_realisation_id'] === (string)$r['id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($r['title']) ?>
                      <?= !empty($r['city'])  ? ' — ' . htmlspecialchars($r['city'])  : '' ?>
                      <?= !empty($r['type'])  ? ' (' . htmlspecialchars($r['type']) . ')' : '' ?>
                    </option>
                    <?php endforeach; ?>
                  </select>
                  <small class="form-text text-muted">Laissez sur Automatique pour afficher la réalisation marquée "featured", ou la plus récente.</small>
                </div>
              </div>

            

            <!-- ── AVANT / APRÈS ──────────────────────────────────────────── -->
              <div class="card-body">
                <!-- Toggle activer -->
                <div class="form-group">
                  <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="ba_enabled"
                           name="realisations_before_after_enabled" value="1"
                           <?= $values['realisations_before_after_enabled'] === '1' ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="ba_enabled">Afficher la section Avant/Après sur la page d'accueil</label>
                  </div>
                </div>
                <div class="form-group">
                  <label for="ba_title">Titre</label>
                  <input type="text" id="ba_title" name="realisations_before_after_title"
                         class="form-control" value="<?= htmlspecialchars($values['realisations_before_after_title']) ?>">
                </div>
                <div class="form-group mb-0">
                  <label for="ba_subtitle">Sous-titre</label>
                  <input type="text" id="ba_subtitle" name="realisations_before_after_subtitle"
                         class="form-control" value="<?= htmlspecialchars($values['realisations_before_after_subtitle']) ?>">
                </div>
              </div>
            </div>
            
            

            <!-- ── SEO LOCAL ───────────────────────────────────────────────── -->
            <div class="card card-outline card-teal mb-4" id="section-local">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-map-marker-alt mr-2"></i>SEO Local / Zone d'intervention</h3>
                <div class="card-tools d-flex align-items-center">
                  <div class="custom-control custom-switch mr-2">
                    <input type="checkbox" class="custom-control-input" id="section_local_enabled" name="section_local_enabled" value="1" <?= $values['section_local_enabled']==='1'?'checked':'' ?>>
                    <label class="custom-control-label" for="section_local_enabled" style="font-size:11px;color:#6c757d;">Visible</label>
                  </div>
                  <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
              </div>
              <div class="card-body">
                <div class="form-group">
                  <label for="home_local_badge_title">Badge title</label>
                  <input type="text" id="home_local_badge_title" name="home_local_badge_title"
                         class="form-control" value="<?= htmlspecialchars($values['home_local_badge_title']) ?>">
                </div>
                <div class="form-group">
                  <label for="home_local_title">Titre</label>
                  <input type="text" id="home_local_title" name="home_local_title"
                         class="form-control" value="<?= htmlspecialchars($values['home_local_title']) ?>">
                </div>
                <div class="form-group">
                  <label for="home_local_intro">Texte d'introduction</label>
                  <textarea id="home_local_intro" name="home_local_intro"
                            class="form-control" rows="3"><?= htmlspecialchars($values['home_local_intro']) ?></textarea>
                  <small class="form-text text-muted">Texte affiché sous le titre, décrit la zone d'intervention.</small>
                </div>
                <div class="form-group mb-0">
                  <label for="home_local_cities">Villes <small class="text-muted">(séparées par des virgules)</small></label>
                  <input type="text" id="home_local_cities" name="home_local_cities"
                         class="form-control" value="<?= htmlspecialchars($values['home_local_cities']) ?>">
                  <small class="form-text text-muted">Ex : Strasbourg, Colmar, Mulhouse, Haguenau, Sélestat</small>
                </div>
              </div>
            </div>
            
            <!-- ── CTA DEVIS ──────────────────────────────────────────────── -->
            <div class="card card-outline card-primary mb-4" id="section-cta">
              <div class="card-header">
                <h3 class="card-title"><i class="fas fa-file-invoice mr-2"></i>Bandeau Devis</h3>
                <div class="card-tools d-flex align-items-center">
                  <div class="custom-control custom-switch mr-2">
                    <input type="checkbox" class="custom-control-input" id="section_cta_enabled" name="section_cta_enabled" value="1" <?= $values['section_cta_enabled']==='1'?'checked':'' ?>>
                    <label class="custom-control-label" for="section_cta_enabled" style="font-size:11px;color:#6c757d;">Visible</label>
                  </div>
                  <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                </div>
              </div>
              <div class="card-body">
                <div class="form-group">
                  <label for="home_cta_devis_title">Titre</label>
                  <input type="text" id="home_cta_devis_title" name="home_cta_devis_title"
                         class="form-control" value="<?= htmlspecialchars($values['home_cta_devis_title']) ?>">
                </div>
                <div class="form-group mb-0">
                  <label for="home_cta_devis_text">Texte</label>
                  <textarea id="home_cta_devis_text" name="home_cta_devis_text"
                            class="form-control" rows="2"><?= htmlspecialchars($values['home_cta_devis_text']) ?></textarea>
                </div>
              </div>
            </div>

            <!-- ── BOUTON SAVE ─────────────────────────────────────────────── -->
            <div class="d-flex justify-content-end mb-5">
              <a href="themes.php" class="btn btn-secondary mr-2">
                <i class="fas fa-arrow-left mr-1"></i> Retour aux thèmes
              </a>
              <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save mr-1"></i> Enregistrer les modifications
              </button>
            </div>

          </form>
        </div><!-- /.col form -->
      </div><!-- /.row -->

    </div><!-- /.container-fluid -->
  </div><!-- /.content -->
</div><!-- /.content-wrapper -->

<?php require_once __DIR__ . '/partials/footer.php'; ?>
<script>
(function(){
  var list    = document.getElementById('prestations-list');
  var addBtn  = document.getElementById('prestation-add');
  var counter = <?= count($prestationsItems) ?>;

  // Delete
  list.addEventListener('click', function(e){
    var btn = e.target.closest('.prestation-delete');
    if (btn) btn.closest('.prestation-row').remove();
  });

  // Add
  addBtn.addEventListener('click', function(){
    var idx = counter++;
    var row = document.createElement('div');
    row.className = 'prestation-row card card-body bg-light mb-2 p-2';
    row.dataset.index = idx;
    row.innerHTML = `
      <input type="hidden" name="prestation_index[]" value="${idx}">
      <div class="d-flex align-items-start" style="gap:10px;">
        <div style="padding-top:6px;flex-shrink:0;">
          <div class="custom-control custom-switch">
            <input type="checkbox" class="custom-control-input"
                   id="prest_enabled_${idx}" name="prestation_enabled[${idx}]" value="1" checked>
            <label class="custom-control-label" for="prest_enabled_${idx}"></label>
          </div>
        </div>
        <div class="flex-grow-1">
          <div class="row" style="row-gap:6px;">
            <div class="col-md-4">
              <input type="text" name="prestation_title[${idx}]" placeholder="Titre"
                     class="form-control form-control-sm">
            </div>
            <div class="col-md-4">
              <input type="text" name="prestation_subtitle[${idx}]" placeholder="Sous-titre"
                     class="form-control form-control-sm">
            </div>
            <div class="col-md-4">
              <input type="text" name="prestation_url[${idx}]" placeholder="/prestations/slug"
                     class="form-control form-control-sm">
            </div>
          </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-danger flex-shrink-0 prestation-delete" title="Supprimer">
          <i class="fas fa-trash"></i>
        </button>
      </div>`;
    list.appendChild(row);
    row.querySelector('input[type=text]').focus();
  });
}());
</script>

<script>
/* ── Persistance de l'état collapsed des panels (localStorage) ─────────────── */
(function () {
  const LS_KEY = 'hp_panels_state';
  const PANEL_IDS = [
    'section-seo', 'section-hero', 'section-prestations',
    'section-approche', 'section-realisations', 'section-ba',
    'section-cta', 'section-local'
  ];

  function loadState() {
    try { return JSON.parse(localStorage.getItem(LS_KEY) || '{}'); }
    catch (e) { return {}; }
  }
  function saveState(s) {
    try { localStorage.setItem(LS_KEY, JSON.stringify(s)); }
    catch (e) {}
  }

  // Restaurer APRÈS que AdminLTE a initialisé ses CardWidgets
  $(window).on('load', function () {
    var state = loadState();
    PANEL_IDS.forEach(function (id) {
      if (state[id] !== 'collapsed') return;
      var $card = $('#' + id);
      if (!$card.length) return;
      // Cliquer sur le bouton collapse — AdminLTE gère tout
      $card.find('[data-card-widget="collapse"]').trigger('click');
    });
  });

  // Écouter avec délégation sur .card (les événements AdminLTE 3.x sont émis sur la .card)
  $(document).on('collapsed.lte.cardwidget', '.card', function () {
    var id = $(this).attr('id');
    if (!id || !PANEL_IDS.includes(id)) return;
    var s = loadState();
    s[id] = 'collapsed';
    saveState(s);
  });

  $(document).on('expanded.lte.cardwidget', '.card', function () {
    var id = $(this).attr('id');
    if (!id || !PANEL_IDS.includes(id)) return;
    var s = loadState();
    delete s[id];
    saveState(s);
  });
}());
</script>
