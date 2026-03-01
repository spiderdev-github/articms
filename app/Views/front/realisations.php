<?php
/**
 * Vue liste des réalisations.
 * Variables injectées par Front\RealisationController::index()
 *
 * @var array  $realisations
 * @var string $pageTitle
 */

// Settings réutilisés
require_once dirname(__DIR__, 3) . '/includes/db.php';
require_once dirname(__DIR__, 3) . '/includes/settings.php';

$h1      = getSetting('realisations_h1',       'Nos réalisations en Alsace');
$intro   = getSetting('realisations_intro',    '');
$ctaText = getSetting('realisations_cta_text', 'Demander un devis');
$ctaLink = getSetting('realisations_cta_link', '/contact');

$baEnabled    = (int)getSetting('realisations_before_after_enabled', 1) === 1;
$baTitle      = getSetting('realisations_before_after_title',    'Avant / Après');
$baSub        = getSetting('realisations_before_after_subtitle', 'La différence se voit dans les détails.');
$baLabel      = getSetting('realisations_before_after_label',    'Transformation complète');
$baBlock1T    = getSetting('realisations_before_after_block1_title', 'Préparation minutieuse');
$baBlock1Tx   = getSetting('realisations_before_after_block1_text',  '');
$baBlock2T    = getSetting('realisations_before_after_block2_title', 'Finition propre');
$baBlock2Tx   = getSetting('realisations_before_after_block2_text',  '');
$beforeImg    = getSetting('realisations_before_after_image_before', '');
$afterImg     = getSetting('realisations_before_after_image_after',  '');
?>

<main>

  <!-- EN-TÊTE -->
  <section class="section">
    <div class="container">
      <span class="kicker"><span class="dot"></span><b>Nos réalisations</b> en Alsace</span>
      <h1 style="font-size:var(--h1);margin:18px 0 12px;"><?= htmlspecialchars($h1) ?></h1>
      <?php if ($intro): ?>
        <p class="muted" style="max-width:800px;font-size:18px;line-height:1.75;"><?= htmlspecialchars($intro) ?></p>
      <?php endif; ?>
      <?php if ($ctaLink): ?>
        <div style="margin-top:14px;">
          <a class="btn btn-primary" href="<?= BASE_URL . htmlspecialchars($ctaLink) ?>"><?= htmlspecialchars($ctaText) ?></a>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- GRILLE -->
  <section class="section">
    <div class="container">
      <div class="grid-3">

        <?php foreach ($realisations as $it): ?>
        <?php
        $cover = null;
        if (!empty($it['cover_thumb'])) {
            $cover = BASE_URL . '/' . ltrim($it['cover_thumb'], '/');
        } elseif (!empty($it['cover_image'])) {
            $cover = BASE_URL . '/' . ltrim($it['cover_image'], '/');
        }
        ?>
        <article class="card">
          <?php if ($cover): ?>
            <a href="<?= BASE_URL ?>/realisations/<?= (int)$it['id'] ?>">
              <img src="<?= htmlspecialchars($cover) ?>"
                   loading="lazy"
                   alt="<?= htmlspecialchars($it['title']) ?>"
                   style="width:100%;height:220px;object-fit:cover;border-radius:16px;">
            </a>
          <?php else: ?>
            <div style="height:220px;border-radius:16px;background:#1a1a1a;"></div>
          <?php endif; ?>

          <h3 style="margin-top:14px;">
            <a href="<?= BASE_URL ?>/realisations/<?= (int)$it['id'] ?>" style="text-decoration:none;color:inherit;">
              <?= htmlspecialchars($it['title']) ?>
              <?= !empty($it['city']) ? ' — ' . htmlspecialchars($it['city']) : '' ?>
            </a>
          </h3>
          <?php if (!empty($it['description'])): ?>
            <p><?= htmlspecialchars($it['description']) ?></p>
          <?php endif; ?>
          <?php if (!empty($it['type'])): ?>
            <div class="muted small"><?= htmlspecialchars($it['type']) ?></div>
          <?php endif; ?>
        </article>
        <?php endforeach; ?>

        <?php if (empty($realisations)): ?>
          <div class="muted">Aucune réalisation pour le moment.</div>
        <?php endif; ?>

      </div>
    </div>
  </section>

  <!-- AVANT / APRÈS -->
  <?php if ($baEnabled && ($beforeImg || $afterImg)): ?>
  <section class="section" id="avant-apres">
    <div class="container">
      <div class="section-title">
        <div>
          <h2><?= htmlspecialchars($baTitle) ?></h2>
          <p><?= htmlspecialchars($baSub) ?></p>
        </div>
      </div>

      <div class="ba-wrap">
        <div class="ba-compare" data-ba>
          <div class="ba-media ba-after"
               style="<?= $afterImg  ? "background-image:url('".htmlspecialchars(BASE_URL.'/'.ltrim($afterImg,'/'), ENT_QUOTES)."')" : '' ?>">
          </div>
          <div class="ba-media ba-before" data-ba-before
               style="<?= $beforeImg ? "background-image:url('".htmlspecialchars(BASE_URL.'/'.ltrim($beforeImg,'/'), ENT_QUOTES)."')" : '' ?>">
          </div>
          <div class="ba-label ba-label-left">Avant</div>
          <div class="ba-label ba-label-right">Après</div>
          <div class="ba-overlay">
            <span class="ba-kicker"><span class="dot"></span><b><?= htmlspecialchars($baLabel) ?></b></span>
          </div>
          <button class="ba-handle" type="button" aria-label="Comparer avant et après" data-ba-handle>
            <span class="ba-line"></span>
            <span class="ba-knob" aria-hidden="true"><span class="ba-arrows" aria-hidden="true">‹ ›</span></span>
          </button>
          <input class="ba-range" type="range" min="0" max="100" value="50" aria-label="Pourcentage avant/après" data-ba-range>
        </div>

        <div class="ba-side">
          <div class="ba-card">
            <b><?= htmlspecialchars($baBlock1T) ?></b>
            <?php if ($baBlock1Tx): ?><p class="muted" style="margin-top:8px;"><?= htmlspecialchars($baBlock1Tx) ?></p><?php endif; ?>
          </div>
          <div class="ba-card">
            <b><?= htmlspecialchars($baBlock2T) ?></b>
            <?php if ($baBlock2Tx): ?><p class="muted" style="margin-top:8px;"><?= htmlspecialchars($baBlock2Tx) ?></p><?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- SEO LOCAL -->
  <section class="section">
    <div class="container">
      <div class="local">
        <span class="kicker"><span class="dot"></span><b>Chantiers en Alsace</b></span>
        <h2 style="margin:14px 0 8px;">Projets réalisés dans le Bas-Rhin et le Haut-Rhin</h2>
        <p class="muted" style="line-height:1.75;">
          Joker Peintre intervient dans toute l'Alsace pour des travaux
          de peinture intérieure, extérieure et rénovation façade.
        </p>
        <ul>
          <li>Strasbourg</li><li>Colmar</li><li>Mulhouse</li>
          <li>Haguenau</li><li>Sélestat</li><li>Saint-Louis</li>
        </ul>
      </div>
    </div>
  </section>

  <!-- CTA FINAL -->
  <section class="section">
    <div class="container" style="text-align:center">
      <h2>Vous avez un projet similaire ?</h2>
      <p class="muted" style="max-width:650px;margin:0 auto 20px;">
        Contactez Joker Peintre pour un devis gratuit et une estimation adaptée.
      </p>
      <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;">
        <a class="btn btn-primary" href="<?= BASE_URL ?>/contact">Demander un devis</a>
      </div>
    </div>
  </section>

</main>
