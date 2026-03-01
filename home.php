<?php
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/settings.php";
require_once __DIR__ . "/includes/db.php";

$pageTitle       = getSetting('home_meta_title', 'Joker Peintre - Peinture & Decoration en Alsace');
$pageDescription = getSetting('home_meta_desc', 'Entreprise de peinture en Alsace : interieur, exterieur, isolation, crepi facade et mosaique effet pierre. Devis gratuit rapide.');

$pdo = getPDO();

/* Settings home */
$homeTitle = getSetting('home_realisations_title', 'Realisations');
$homeText  = getSetting('home_realisations_text', 'Decouvre quelques projets recents en Alsace. Finition propre, rendu durable.');
$heroKicker     = getSetting('home_hero_kicker',        'Votre artisan peintre en Alsace');
$heroTitle      = getSetting('home_hero_title',         'Finitions haut de gamme pour vos murs, facades et renovations');
$heroText       = getSetting('home_hero_text',          'Peinture interieure et exterieure, isolation, rénovation, revêtements muraux, boiserie, décoration et mosaïques...');
$heroCtaPrimary = getSetting('home_hero_cta_primary',   'Demander un devis gratuit');
$heroCtaSecond  = getSetting('home_hero_cta_secondary', 'Voir les prestations');
$trustBadge1    = getSetting('home_trust_badge1', 'Devis rapide');
$trustBadge2    = getSetting('home_trust_badge2', 'Finitions propres');
$trustBadge3    = getSetting('home_trust_badge3', 'Intervention Alsace');
$approachTitle  = getSetting('home_approach_title', 'Une approche premium, simple et transparente');
$approachText   = getSetting('home_approach_text', 'Preparation serieuse, materiaux adaptes, execution propre. L objectif : un resultat net et durable.');
$ctaDevisTitle  = getSetting('home_cta_devis_title', 'Besoin d un devis ?');
$ctaDevisText   = getSetting('home_cta_devis_text', 'Reponse rapide. Decris ton projet, surface, ville et delai.');

/* Hero realisation : featured d'abord */
$stmt = $pdo->prepare("
  SELECT id, title, city, type, cover_thumb, cover_image
  FROM realisations
  WHERE is_published=1
  ORDER BY is_featured DESC, sort_order ASC, created_at DESC
  LIMIT 1
");
$stmt->execute();
$hero = $stmt->fetch();

$heroImg = '';
if (!empty($hero['cover_thumb'])) {
  $heroImg = BASE_URL . '/' . ltrim($hero['cover_thumb'], '/');
} elseif (!empty($hero['cover_image'])) {
  $heroImg = BASE_URL . '/' . ltrim($hero['cover_image'], '/');
}

/* KPIs : comptage par type */
$kpis = [
  'Peinture interieure' => 0,
  'Crepi / Facade' => 0,
  'Mosaique effet pierre' => 0
];

$stmtKpi = $pdo->query("
  SELECT type, COUNT(*) as c
  FROM realisations
  WHERE is_published=1 AND type IS NOT NULL AND type <> ''
  GROUP BY type
");
$rowsK = $stmtKpi->fetchAll();

foreach ($rowsK as $r) {
  if (isset($kpis[$r['type']])) {
    $kpis[$r['type']] = (int)$r['c'];
  }
}

/* Avant/Apres */
$baEnabled = (int)getSetting('realisations_before_after_enabled', 1) === 1;
$baTitle = getSetting('realisations_before_after_title', 'Avant / Apres');
$baSub = getSetting('realisations_before_after_subtitle', 'La difference se voit dans les details.');
?>

<main>

  <section class="hero">
    <div class="container">
      <div class="hero-grid">

        <div>
          <span class="kicker"><span class="dot"></span><b><?= htmlspecialchars($heroKicker) ?></b></span>

          <h1><?= htmlspecialchars($heroTitle) ?></h1>
          <p><?= htmlspecialchars($heroText) ?></p>

          <div class="hero-actions">
            <a class="btn btn-primary" href="<?= BASE_URL ?>/contact"><?= htmlspecialchars($heroCtaPrimary) ?></a>
            <a class="btn btn-ghost" href="<?= BASE_URL ?>/prestations"><?= htmlspecialchars($heroCtaSecond) ?></a>
            <a class="phone-pill" href="tel:<?= htmlspecialchars($cmsPhone) ?>"><em>Tel</em> <?= htmlspecialchars($cmsPhoneDisplay) ?></a>
          </div>

          <div class="trust-row">
            <span class="badge"><i></i> <?= htmlspecialchars($trustBadge1) ?></span>
            <span class="badge"><i></i> <?= htmlspecialchars($trustBadge2) ?></span>
            <span class="badge"><i></i> <?= htmlspecialchars($trustBadge3) ?></span>
          </div>
        </div>

        <aside class="hero-card" aria-label="Prestations principales">
          <div class="hero-card-inner">
            <div class="mini-title">
              <strong>Prestations</strong>
              <span>Peinture & Decoration</span>
            </div>

            <ul class="hero-list">
              <li>
                <div>
                  <b>Peinture interieure</b>
                  <small>Tous types de travaux</small>
                </div>
              </li>
               <li>
                <div>
                  <b>Isolation intérieure / extérieure</b>
                  <small>Confort thermique, economies d'energie, reduction des nuisances sonores</small>
                </div>
              </li>
              <li>
                <div>
                  <b> Travaux de facade</b>
                  <small>Rénovation, protection aux intempéries</small>
                </div>
              </li>
              <li>
                <div>
                  <b>Revêtements muraux et décoration</b>
                  <small>Decoratif, relief, cachet premium</small>
                </div>
              </li>
              <li>
                <div>
                  <b>Peinture exterieure</b>
                  <small>Nettoyage, protection, tenue aux intemperies, rendu durable</small>
                </div>
              </li>
            </ul>

            <div class="hero-card-footer">
              <span class="chip">Alsace - Bas-Rhin - Haut-Rhin</span>
              <a class="btn btn-gold" href="<?= BASE_URL ?>/contact">Devis</a>
            </div>
          </div>
        </aside>

      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="section-title">
        <div>
          <h2><?= htmlspecialchars($approachTitle) ?></h2>
          <p><?= htmlspecialchars($approachText) ?></p>
        </div>
      </div>

      <div class="grid-3">
        <article class="card">
          <img src="assets/images/preparation-des-supports.png" alt="Preparation des supports">
          <h3>Preparation des supports</h3>
          <p>Protection, rebouchage, poncage et accroche. C est la cle d une finition haut de gamme.</p>
        </article>

        <article class="card">
          <div class="icon gold"></div>
          <h3>Finition nette</h3>
          <p>Angles propres, uniformite, rendu regulier. Un travail qui se voit, sans surprises.</p>
        </article>

        <article class="card">
          <div class="icon"></div>
          <h3>Chantier maitrise</h3>
          <p>Organisation, respect des lieux, nettoyage. Vous retrouvez un espace impeccable.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="section">
    <div class="container">

      <div class="section-title">
        <div>
          <h2><?= htmlspecialchars($homeTitle) ?></h2>
          <p><?= htmlspecialchars($homeText) ?></p>
        </div>
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/realisations.php">Voir la galerie</a>
      </div>

      <div class="gallery">

        <!-- BIG : hero dynamique -->
        <a class="big"
          href="<?= BASE_URL ?>/realisations.php"
          role="img"
          aria-label="Apercu realisation"
          style="<?= $heroImg ? "background-image:url('".htmlspecialchars($heroImg, ENT_QUOTES)."'); background-size:cover; background-position:center;" : "" ?>">

          <div class="label">
            <span class="dot"></span>
            <b><?= $hero ? htmlspecialchars($hero['title']) : 'Finition premium' ?></b>
          </div>

          <?php if ($hero && (!empty($hero['type']) || !empty($hero['city']))): ?>
            <div style="position:absolute; top:16px; left:16px;">
              <span class="kicker" style="background:rgba(0,0,0,.35); padding:10px 14px; border-radius:999px; border:1px solid rgba(255,255,255,.12); backdrop-filter: blur(10px);">
                <span class="dot"></span>
                <b><?= htmlspecialchars($hero['type'] ?? 'Projet') ?></b>
                <?= !empty($hero['city']) ? ' - ' . htmlspecialchars($hero['city']) : '' ?>
              </span>
            </div>
          <?php endif; ?>

        </a>

        <div class="stack">

          <!-- CARD AVANT/APRES -->
          <div class="small-card">
            <b><?= htmlspecialchars($baTitle) ?></b>
            <p class="muted" style="margin-top:8px">
              <?= htmlspecialchars($baSub) ?>
            </p>

            <div class="kpis">
              <div class="kpi">
                <b>Interieur</b>
                <span><?= (int)$kpis['Peinture interieure'] ?> projets</span>
              </div>
              <div class="kpi">
                <b>Facade</b>
                <span><?= (int)$kpis['Crepi / Facade'] ?> projets</span>
              </div>
              <div class="kpi">
                <b>Decor</b>
                <span><?= (int)$kpis['Mosaique effet pierre'] ?> projets</span>
              </div>
            </div>

            <div style="margin-top:12px; display:flex; gap:10px; flex-wrap:wrap;">
              <?php if ($baEnabled): ?>
                <a class="btn btn-ghost" href="<?= BASE_URL ?>/realisations.php#avant-apres">Voir le Avant/Apres</a>
              <?php else: ?>
                <span class="muted small">Module Avant/Apres desactive dans l admin.</span>
              <?php endif; ?>
            </div>
          </div>

          <!-- CTA -->
          <div class="small-card">
            <b><?= htmlspecialchars($ctaDevisTitle) ?></b>
            <p class="muted" style="margin-top:8px">
              <?= htmlspecialchars($ctaDevisText) ?>
            </p>
            <div style="display:flex; gap:10px; flex-wrap:wrap; margin-top:12px">
              <a class="btn btn-primary" href="<?= BASE_URL ?>/contact">Demander un devis</a>
              <a class="btn btn-ghost" href="tel:<?= htmlspecialchars($cmsPhone) ?>"><?= htmlspecialchars($cmsPhoneDisplay) ?></a>
            </div>
          </div>

        </div>
      </div>

    </div>
  </section>

  <section class="section">
    <div class="container">
      <div class="local">
        <span class="kicker"><span class="dot"></span><b>SEO local</b> zone d intervention</span>
        <h2 style="margin:14px 0 8px"><?= htmlspecialchars($cmsName) ?> intervient dans toute l <?= htmlspecialchars($cmsRegion) ?></h2>
        <p class="muted" style="margin:0; line-height:1.75">
          Bas-Rhin et Haut-Rhin : peinture interieure, exterieure, isolation, crepi facade et decoration.
          Pour un meilleur referencement local, cree ensuite des pages par ville (ex: Strasbourg, Colmar, Mulhouse).
        </p>
        <ul>
          <li>Strasbourg</li>
          <li>Haguenau</li>
          <li>Selestat</li>
          <li>Colmar</li>
          <li>Mulhouse</li>
          <li>Saint-Louis</li>
        </ul>
      </div>
    </div>
  </section>

</main>

<?php include __DIR__ . "/includes/footer.php"; ?>