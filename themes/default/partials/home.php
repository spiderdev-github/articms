<?php
/**
 * Vue home — contenu principal de la page d'accueil.
 * Variables injectées par HomeController :
 *   $settings, $cfg, $hero, $prestationsItems, $latestRealisations, $kpis,
 *   $heroKicker, $heroTitle, $heroText, $heroCtaPrimary, $heroCtaSecond,
 *   $homeTitle, $homeText
 *
 * @var \App\Models\SettingModel $settings
 * @var array $cfg   — contenu de home.json (thème actif)
 * @var array|null $hero
 * @var array  $prestationsItems
 * @var array  $latestRealisations
 * @var array  $kpis
 */

$heroImg = '';
if (!empty($hero['cover_thumb'])) {
    $heroImg = BASE_URL . '/' . ltrim($hero['cover_thumb'], '/');
} elseif (!empty($hero['cover_image'])) {
    $heroImg = BASE_URL . '/' . ltrim($hero['cover_image'], '/');
}

// ── Lecture du JSON config ────────────────────────────────────────────────────
$S = $cfg['sections'] ?? [];

/** @param mixed $default */
$sc = function (string $path, $default = '') use ($S) {
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
$sb = fn(string $path, bool $d = true): bool => (bool)$sc($path, $d);

// Visibilité des sections
$section_hero_enabled         = $sb('hero.enabled');
$section_badges_enabled       = $sb('badges.enabled');
$section_prestations_enabled  = $sb('prestations_card.enabled');
$section_approche_enabled     = $sb('approche.enabled');
$section_realisations_enabled = $sb('realisations.enabled');
$baEnabled                    = $sb('avant_apres.enabled');
$section_local_enabled        = $sb('local.enabled');
$section_cta_enabled          = $sb('cta_devis.enabled');

// Badges
$trustBadge1 = $S['badges']['items'][0] ?? 'Devis rapide';
$trustBadge2 = $S['badges']['items'][1] ?? 'Finitions propres';
$trustBadge3 = $S['badges']['items'][2] ?? 'Intervention Alsace';

// Approche
$approachTitle = $sc('approche.title', 'Une approche premium, simple et transparente');
$approachText  = $sc('approche.text',  '');
$home_approach_card1_title = $S['approche']['cards'][0]['title'] ?? 'Préparation des supports';
$home_approach_card1_text  = $S['approche']['cards'][0]['text']  ?? '';
$home_approach_card2_title = $S['approche']['cards'][1]['title'] ?? 'Finition nette';
$home_approach_card2_text  = $S['approche']['cards'][1]['text']  ?? '';
$home_approach_card3_title = $S['approche']['cards'][2]['title'] ?? 'Chantier maîtrisé';
$home_approach_card3_text  = $S['approche']['cards'][2]['text']  ?? '';

// Prestations carte
$home_prestations_card_title     = $sc('prestations_card.card_title',    'Nos prestations');
$home_prestations_card_subtitle  = $sc('prestations_card.card_subtitle', 'Peinture & Décoration');
$home_prestations_footer_enabled = $sb('prestations_card.footer.enabled');
$home_prestations_footer_city    = $sc('prestations_card.footer.city_label', 'Alsace - Bas-Rhin - Haut-Rhin');

// Avant/Après
$baTitle = $sc('avant_apres.title',    'Avant / Après');
$baSub   = $sc('avant_apres.subtitle', 'La différence se voit dans les détails.');

// SEO Local
$home_local_badge_title = $sc('local.badge_title', "Zone d'intervention");
$home_local_title  = $sc('local.title', "Joker Peintre intervient dans toute l'Alsace");
$home_local_intro  = $sc('local.intro',  '');
// cities est un tableau dans le JSON (pas une chaîne CSV)
$home_local_cities = is_array($S['local']['cities'] ?? null)
    ? $S['local']['cities']
    : array_filter(array_map('trim', explode(',', $S['local']['cities'] ?? 'Strasbourg, Haguenau, Colmar')));

// CTA Devis
$ctaDevisTitle = $sc('cta_devis.title', "Besoin d'un devis ?");
$ctaDevisText  = $sc('cta_devis.text',  '');

// Coordonnées entreprise : toujours depuis les settings globaux
$cmsPhone        = $settings->get('company_phone',         defined('PHONE')         ? PHONE         : '');
$cmsPhoneDisplay = $settings->get('company_phone_display', defined('PHONE_DISPLAY') ? PHONE_DISPLAY : $cmsPhone);
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
            <?php foreach ($home_local_cities as $city): ?>
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
