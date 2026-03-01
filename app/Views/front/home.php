<?php
/**
 * Vue home — contenu principal de la page d'accueil.
 * Variables injectées par HomeController.
 *
 * @var \App\Models\SettingModel $settings
 * @var array|null $hero
 * @var array  $prestationsItems
 * @var array  $latestRealisations
 */

$heroImg = '';
if (!empty($hero['cover_thumb'])) {
    $heroImg = BASE_URL . '/' . ltrim($hero['cover_thumb'], '/');
} elseif (!empty($hero['cover_image'])) {
    $heroImg = BASE_URL . '/' . ltrim($hero['cover_image'], '/');
}

$heroTitle = $settings->get('home_hero_title', 'Finition premium pour vos projets de peinture');
$heroText  = $settings->get('home_hero_text', 'Peinture interieure et exterieure, isolation, rénovation, revêtements muraux, boiserie, décoration et mosaïques... Votre projet maitrisé de A a Z, avec une attention particulière aux détails et finitions');
$trustBadge1   = $settings->get('home_trust_badge1',   'Devis rapide');
$trustBadge2   = $settings->get('home_trust_badge2',   'Finitions propres');
$trustBadge3   = $settings->get('home_trust_badge3',   'Intervention Alsace');
$approachTitle = $settings->get('home_approach_title', 'Une approche premium, simple et transparente');
$approachText  = $settings->get('home_approach_text',  'Préparation sérieuse, matériaux adaptés, exécution propre. L\'objectif : un résultat net et durable.');
$ctaDevisTitle = $settings->get('home_cta_devis_title','Besoin d\'un devis ?');
$ctaDevisText  = $settings->get('home_cta_devis_text', 'Réponse rapide. Décris ton projet, surface, ville et délai.');
$cmsPhone        = $settings->get('company_phone',        defined('PHONE')         ? PHONE         : '');
$cmsPhoneDisplay = $settings->get('company_phone_display',defined('PHONE_DISPLAY') ? PHONE_DISPLAY : $cmsPhone);
$section_realisations_enabled = $settings->get('section_realisations_enabled', '0') === '1';
$baEnabled = $settings->get('realisations_before_after_enabled', '0') === '1';
$section_hero_enabled = $settings->get('section_hero_enabled', '0') === '1';
$section_badges_enabled = $settings->get('section_badges_enabled', '0') === '1';
$section_prestations_enabled = $settings->get('section_prestations_enabled', '0') === '1';
$section_approche_enabled = $settings->get('section_approche_enabled', '0') === '1';
$section_cta_enabled = $settings->get('section_cta_enabled', '0') === '1';
$section_local_enabled = $settings->get('section_local_enabled', '0') === '1';
$homeTitle = $settings->get('home_realisations_title', 'Nos dernières réalisations');
$homeText  = $settings->get('home_realisations_text', 'Découvre quelques projets récents en Alsace. Finition propre, rendu durable.');
$baTitle    = $settings->get('realisations_before_after_title', 'Le Avant/Après, c\'est impressionnant');
$baSub      = $settings->get('realisations_before_after_subtitle', 'Un mur abîmé, une façade défraîchie, une pièce à rafraîchir ? Découvre nos transformations les plus marquantes.');
$home_prestations_card_title = $settings->get('home_prestations_card_title', 'Nos prestations');
$home_prestations_card_subtitle = $settings->get('home_prestations_card_subtitle', 'Peinture & Décoration');
$home_prestations_footer_enabled = $settings->get('home_prestations_footer_enabled', '0') === '1';
$home_prestations_footer_city = $settings->get('home_prestations_footer_city', 'Alsace - Bas-Rhin - Haut-Rhin');


$home_approach_card1_title = $settings->get('home_approach_card1_title', 'Préparation sérieuse');
$home_approach_card1_text  = $settings->get('home_approach_card1_text', 'Protection, rebouchage, ponçage et accroche. C\'est la clé d\'une finition haut de gamme.');
$home_approach_card2_title = $settings->get('home_approach_card2_title', 'Finition nette');
$home_approach_card2_text  = $settings->get('home_approach_card2_text', 'Angles propres, uniformité, rendu régulier. Un travail qui se voit, sans surprises.');
$home_approach_card3_title = $settings->get('home_approach_card3_title', 'Chantier maîtrisé');
$home_approach_card3_text  = $settings->get('home_approach_card3_text', 'Organisation, respect des lieux, nettoyage. Vous retrouvez un espace impeccable.');

$home_local_title = $settings->get('home_local_title', 'Intervention locale en Alsace');
$home_local_intro = $settings->get('home_local_intro', 'Bas-Rhin et Haut-Rhin : peinture interieure, exterieure, isolation, crepi facade et decoration. Pour un meilleur referencement local, cree ensuite des pages par ville (ex: Strasbourg, Colmar, Mulhouse).');
$home_local_cities = $settings->get('home_local_cities', "Strasbourg, Haguenau, Selestat, Colmar, Mulhouse, Saint-Louis");
$home_local_badge_title = $settings->get('home_local_badge_title', 'Zone d\'intervention');
?>

<main>

  <!-- HERO -->
  <section class="hero">
    <div class="container">
      <div class="hero-grid">

        <?php if ($section_hero_enabled): ?>
          <div>
            <span class="kicker"><span class="dot"></span><b><?= htmlspecialchars($heroKicker) ?></b></span>
            <h1><?= htmlspecialchars($heroTitle) ?></h1>
            <p><?= htmlspecialchars($heroText) ?></p>

            <div class="hero-actions">
              <a class="btn btn-primary" href="<?= BASE_URL ?>/contact"><?= htmlspecialchars($heroCtaPrimary) ?></a>
              <a class="btn btn-ghost"   href="<?= BASE_URL ?>/prestations"><?= htmlspecialchars($heroCtaSecond) ?></a>
              <?php if ($cmsPhone): ?>
              <a class="phone-pill" href="tel:<?= htmlspecialchars($cmsPhone) ?>"><em>Tél</em> <?= htmlspecialchars($cmsPhoneDisplay) ?></a>
              <?php endif; ?>
            </div>
            <?php if ($section_badges_enabled): ?>
              <div class="trust-row">
                <span class="badge"><i></i> <?= htmlspecialchars($trustBadge1) ?></span>
                <span class="badge"><i></i> <?= htmlspecialchars($trustBadge2) ?></span>
                <span class="badge"><i></i> <?= htmlspecialchars($trustBadge3) ?></span>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
        
        <?php if ($section_prestations_enabled): ?>
          <aside class="hero-card" aria-label="Prestations principales">
            <div class="hero-card-inner">
              <div class="mini-title">
                <strong><?= htmlspecialchars($home_prestations_card_title) ?></strong>
                <span><?= htmlspecialchars($home_prestations_card_subtitle) ?></span>
              </div>

              <ul class="hero-list">
                <?php foreach ($prestationsItems as $prest): ?>
                <?php if (!empty($prest['enabled'])): ?>
                  <li>
                    <a href="<?= htmlspecialchars($prest['url']) ?>">
                      <div>
                        <b><?= htmlspecialchars($prest['title']) ?></b>
                        <small><?= htmlspecialchars($prest['subtitle']) ?></small>
                      </div>
                      <span class="hero-list-arrow"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"></polyline></svg></span>
                    </a>
                  </li>
                <?php endif; ?>
                <?php endforeach; ?>
              </ul>
              <?php if ($home_prestations_footer_enabled): ?>
                <div class="hero-card-footer">
                  <span class="chip"><?= htmlspecialchars($home_prestations_footer_city) ?></span>
                  <a class="btn btn-gold" href="<?= BASE_URL ?>/contact">Devis</a>
                </div>
              <?php endif; ?>
            </div>
          </aside>
        <?php endif; ?>

      </div>
    </div>
  </section>

  <!-- APPROCHE -->
  <?php if($section_approche_enabled): ?>
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
            <div class="icon"></div>
            <h3><?= htmlspecialchars($home_approach_card1_title) ?></h3>
            <p><?= htmlspecialchars($home_approach_card1_text) ?></p>
          </article>
          <article class="card">
            <div class="icon gold"></div>
            <h3><?= htmlspecialchars($home_approach_card2_title) ?></h3>
            <p><?= htmlspecialchars($home_approach_card2_text) ?></p>
          </article>
          <article class="card">
            <div class="icon"></div>
            <h3><?= htmlspecialchars($home_approach_card3_title) ?></h3>
            <p><?= htmlspecialchars($home_approach_card3_text) ?></p>
          </article>
        </div>
      </div>
    </section>
  <?php endif; ?>
  
  <!-- RÉALISATIONS RÉCENTES -->
  <!--section class="section">
    <div class="container">
      <div class="section-title">
        <div>
          <h2><?= htmlspecialchars($homeTitle) ?></h2>
          <?php if ($homeText): ?><p><?= htmlspecialchars($homeText) ?></p><?php endif; ?>
        </div>
        <a class="btn btn-ghost" href="<?= BASE_URL ?>/realisations">Voir la galerie</a>
      </div>

      <div class="gallery">

        <a class="big"
           href="<?= BASE_URL ?>/realisations"
           role="img"
           aria-label="Aperçu réalisation"
           style="<?= $heroImg ? "background-image:url('".htmlspecialchars($heroImg, ENT_QUOTES)."');background-size:cover;background-position:center;" : "" ?>">
          <div class="label">
            <?php if ($hero): ?>
            <b><?= htmlspecialchars($hero['title']) ?></b>
            <?= !empty($hero['city']) ? '— '.htmlspecialchars($hero['city']) : '' ?>
            <?php else: ?>
            <b>Nos réalisations</b>
            <?php endif; ?>
          </div>
        </a>

        <?php foreach (array_slice($latestRealisations, 0, 5) as $r): ?>
        <?php
        $thumb = '';
        if (!empty($r['cover_thumb'])) {
            $thumb = BASE_URL . '/' . ltrim($r['cover_thumb'], '/');
        } elseif (!empty($r['cover_image'])) {
            $thumb = BASE_URL . '/' . ltrim($r['cover_image'], '/');
        }
        ?>
        <a href="<?= BASE_URL ?>/realisations/<?= (int)$r['id'] ?>"
           role="img"
           aria-label="<?= htmlspecialchars($r['title']) ?>"
           style="<?= $thumb ? "background-image:url('".htmlspecialchars($thumb, ENT_QUOTES)."');background-size:cover;background-position:center;" : "" ?>">
          <div class="label">
            <b><?= htmlspecialchars($r['title']) ?></b>
            <?= !empty($r['city']) ? '— '.htmlspecialchars($r['city']) : '' ?>
          </div>
        </a>
        <?php endforeach; ?>

      </div>
    </div>
  </section-->
  
  <?php if($section_realisations_enabled): ?>
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
          href="<?= BASE_URL ?>/realisations<?= $hero ? '/' . (int)$hero['id'] : '' ?>"
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
          <?php if ($baEnabled): ?>
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
                <a class="btn btn-ghost" href="<?= BASE_URL ?>/realisations<?= $hero ? '/' . (int)$hero['id'].'#avant-apres' : '' ?>">Voir le Avant/Apres</a>
            </div>
          </div>
          <?php endif; ?>

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
  <?php endif; ?>

  <?php if ($section_local_enabled): ?>
    <section class="section">
      <div class="container">
        <div class="local">
          <span class="kicker"><span class="dot"></span><b><?= htmlspecialchars($home_local_badge_title) ?></b></span>
          <h2 style="margin:14px 0 8px"><?= htmlspecialchars($home_local_title) ?></h2>
          <p class="muted" style="margin:0; line-height:1.75">
            <?= htmlspecialchars($home_local_intro) ?>
          </p>
          <ul>
            <?php foreach (explode(',', $home_local_cities) as $city): ?>
              <li><?= htmlspecialchars(trim($city)) ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </section>
  <?php endif; ?>
  
  <!-- CTA DEVIS -->
  <?php if($section_cta_enabled): ?>
    <section class="section cta-section">
      <div class="container" style="text-align:center">
        <h2><?= htmlspecialchars($ctaDevisTitle) ?></h2>
        <p class="muted" style="max-width:650px;margin:0 auto 20px;"><?= htmlspecialchars($ctaDevisText) ?></p>
        <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
          <a class="btn btn-primary" href="<?= BASE_URL ?>/contact">Demander un devis</a>
          <?php if ($cmsPhone): ?>
          <a class="btn btn-ghost" href="tel:<?= htmlspecialchars($cmsPhone) ?>">Appeler <?= htmlspecialchars($cmsPhoneDisplay) ?></a>
          <?php endif; ?>
        </div>
      </div>
    </section>
  <?php endif; ?>


</main>
