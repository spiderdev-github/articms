<?php
/**
 * Vue détail d'une réalisation.
 * Variables injectées par Front\RealisationController::show()
 *
 * @var array  $realisation
 * @var array  $images
 * @var string $pageTitle
 */

$cover = null;
if (!empty($realisation['cover_thumb'])) {
    $cover = BASE_URL . '/' . ltrim($realisation['cover_thumb'], '/');
} elseif (!empty($realisation['cover_image'])) {
    $cover = BASE_URL . '/' . ltrim($realisation['cover_image'], '/');
}
?>

<main>

  <section class="section">
    <div class="container">

      <a href="<?= BASE_URL ?>/realisations" class="btn btn-ghost" style="margin-bottom:24px;display:inline-flex;align-items:center;gap:8px;">
        ← Retour aux réalisations
      </a>

      <div style="display:grid;grid-template-columns:1fr 320px;gap:40px;align-items:start;">

        <!-- Galerie photos -->
        <div>
          <?php if ($cover): ?>
            <a href="<?= htmlspecialchars($cover) ?>" class="glightbox" data-gallery="realisation-<?= (int)$realisation['id'] ?>">
              <img src="<?= htmlspecialchars($cover) ?>"
                   alt="<?= htmlspecialchars($realisation['title']) ?>"
                   style="width:100%;border-radius:16px;object-fit:cover;max-height:500px;">
            </a>
          <?php endif; ?>

          <!-- Images supplémentaires -->
          <?php if (!empty($images)): ?>
          <div class="grid-3" style="margin-top:16px;">
            <?php foreach ($images as $img): ?>
            <?php $src = BASE_URL . '/' . ltrim($img['image_path'], '/'); ?>
            <a href="<?= htmlspecialchars($src) ?>"
               class="glightbox"
               data-gallery="realisation-<?= (int)$realisation['id'] ?>">
              <img src="<?= htmlspecialchars($src) ?>"
                   loading="lazy"
                   alt="<?= htmlspecialchars($img['alt_text'] ?? $realisation['title']) ?>"
                   style="width:100%;height:180px;object-fit:cover;border-radius:12px;">
            </a>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Infos réalisation -->
        <aside>
          <h1 style="font-size:clamp(1.4rem,2.5vw,2rem);line-height:1.3;margin-bottom:12px;">
            <?= htmlspecialchars($realisation['title']) ?>
            <?php if (!empty($realisation['city'])): ?>
              <span style="font-weight:400;color:var(--muted,#888)"> — <?= htmlspecialchars($realisation['city']) ?></span>
            <?php endif; ?>
          </h1>

          <?php if (!empty($realisation['type'])): ?>
            <span class="chip" style="margin-bottom:12px;display:inline-block;"><?= htmlspecialchars($realisation['type']) ?></span>
          <?php endif; ?>

          <?php if (!empty($realisation['description'])): ?>
            <p style="line-height:1.75;color:var(--muted,#888);margin-bottom:20px;">
              <?= htmlspecialchars($realisation['description']) ?>
            </p>
          <?php endif; ?>

          <?php if (!empty($realisation['surface'])): ?>
            <div style="margin-bottom:8px;"><b>Surface :</b> <?= htmlspecialchars($realisation['surface']) ?> m²</div>
          <?php endif; ?>

          <?php if (!empty($realisation['duration'])): ?>
            <div style="margin-bottom:8px;"><b>Durée :</b> <?= htmlspecialchars($realisation['duration']) ?></div>
          <?php endif; ?>

          <div style="margin-top:24px;display:flex;flex-direction:column;gap:10px;">
            <a class="btn btn-primary" href="<?= BASE_URL ?>/contact">Demander un devis similaire</a>
            <a class="btn btn-ghost"   href="<?= BASE_URL ?>/realisations">Voir toutes les réalisations</a>
          </div>
        </aside>
        
        
      </div>

    </div>
  </section>
  
    <!-- Bloc Avant / Apres -->
<?php
$baTitle = getSetting('realisations_before_after_title', 'Avant / Apres');
$baSub = getSetting('realisations_before_after_subtitle', 'La difference se voit dans les details.');
$baLabel = getSetting('realisations_before_after_label', 'Transformation complete');

$baBlock1Title = getSetting('realisations_before_after_block1_title', 'Preparation minutieuse');
$baBlock1Text = getSetting('realisations_before_after_block1_text', '');
$baBlock2Title = getSetting('realisations_before_after_block2_title', 'Finition propre');
$baBlock2Text = getSetting('realisations_before_after_block2_text', '');

$beforeImg = getSetting('realisations_before_after_image_before', '');
$afterImg  = getSetting('realisations_before_after_image_after', '');
?>

<!--section class="section" id="avant-apres">
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
             style="background-image:url('<?= $afterImg ? (BASE_URL . '/' . ltrim($afterImg,'/')) : '' ?>');">
        </div>

        <div class="ba-media ba-before"
             data-ba-before
             style="background-image:url('<?= $beforeImg ? (BASE_URL . '/' . ltrim($beforeImg,'/')) : '' ?>');">
        </div>

        <div class="ba-label ba-label-left">Avant</div>
        <div class="ba-label ba-label-right">Apres</div>

        <div class="ba-overlay">
          <span class="ba-kicker">
            <span class="dot"></span>
            <b><?= htmlspecialchars($baLabel) ?></b>
          </span>
        </div>

        <button class="ba-handle" type="button" aria-label="Comparer avant et apres" data-ba-handle>
          <span class="ba-line"></span>
          <span class="ba-knob" aria-hidden="true">
            <span class="ba-arrows" aria-hidden="true">‹ ›</span>
          </span>
        </button>

        <input class="ba-range" type="range" min="0" max="100" value="50" aria-label="Pourcentage avant/apres" data-ba-range>
      </div>

      <div class="ba-side">
        <div class="ba-card">
          <b><?= htmlspecialchars($baBlock1Title) ?></b>
          <p class="muted" style="margin-top:8px;"><?= htmlspecialchars($baBlock1Text) ?></p>
        </div>
        <div class="ba-card">
          <b><?= htmlspecialchars($baBlock2Title) ?></b>
          <p class="muted" style="margin-top:8px;"><?= htmlspecialchars($baBlock2Text) ?></p>
        </div>
      </div>
    </div>

  </div>
</section-->

</main>
