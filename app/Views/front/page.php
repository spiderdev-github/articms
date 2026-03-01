<?php
/**
 * Vue page CMS — rendu d'une page dynamique depuis le backoffice.
 * Variables injectées par PageController.
 *
 * @var array $page  Ligne de la table cms_pages
 */

// Raccourcis formulaires (form-shortcode)
require_once dirname(__DIR__, 3) . '/includes/form-renderer.php';

/**
 * Traite les shortcodes <figure class="form-shortcode" data-form-slug="...">
 */
function _viewRenderFormFigures(string $html): string
{
    return preg_replace_callback(
        '/<figure[^>]+class="form-shortcode"[^>]+data-form-slug="([a-z0-9\-]+)"[^>]*>.*?<\/figure>/si',
        function ($matches) {
            ob_start();
            renderForm($matches[1]);
            return ob_get_clean();
        },
        $html
    );
}

/**
 * Traite les shortcodes <figure class="gallery-shortcode" data-gallery-id="N">
 */
function _viewRenderGalleryShortcodes(string $html): string
{
    return preg_replace_callback(
        '/<figure[^>]+class="gallery-shortcode"[^>]+data-gallery-id="(\d+)"[^>]*>.*?<\/figure>/si',
        function ($matches) {
            $galId = (int)$matches[1];
            try {
                $pdo = \App\Core\Database::getInstance();
            } catch (\Exception $e) {
                return '<!-- gallery #' . $galId . ' unavailable -->';
            }

            $stmtG = $pdo->prepare("SELECT * FROM galleries WHERE id = ?");
            $stmtG->execute([$galId]);
            $gallery = $stmtG->fetch();
            if (!$gallery) return '<!-- galerie introuvable id=' . $galId . ' -->';

            $showLabels = (bool)($gallery['show_item_labels'] ?? 1);
            $perPage    = max(1, (int)($gallery['items_per_page'] ?? 6));

            $stmtR = $pdo->prepare("
                SELECT r.id AS real_id, r.title, r.city, r.type, r.cover_image, r.description,
                       ri.image_path, ri.alt_text
                FROM gallery_items gi
                JOIN realisations r  ON r.id  = gi.realisation_id
                LEFT JOIN realisation_images ri ON ri.realisation_id = r.id
                WHERE gi.gallery_id = ? AND r.is_published = 1
                ORDER BY gi.sort_order ASC, r.id ASC, ri.sort_order ASC, ri.id ASC
            ");
            $stmtR->execute([$galId]);
            $rows = $stmtR->fetchAll(\PDO::FETCH_ASSOC);

            $reals = [];
            foreach ($rows as $row) {
                $rid = $row['real_id'];
                if (!isset($reals[$rid])) {
                    $reals[$rid] = [
                        'title'       => $row['title'],
                        'city'        => $row['city'],
                        'type'        => $row['type'],
                        'description' => $row['description'],
                        'cover_image' => $row['cover_image'],
                        'images'      => [],
                    ];
                }
                if (!empty($row['image_path'])) {
                    $reals[$rid]['images'][] = ['image_path' => $row['image_path'], 'alt_text' => $row['alt_text']];
                }
            }

            if (empty($reals)) return '<!-- galerie vide id=' . $galId . ' -->';

            $baseUrl    = defined('BASE_URL') ? BASE_URL : '';
            $totalItems = count($reals);
            $totalPages = (int)ceil($totalItems / $perPage);
            $blockId    = 'cms-gal-' . $galId;
            $cardIdx    = 0;

            $out  = '<div class="cms-gallery" data-gallery-id="' . $galId . '" id="' . $blockId . '">';
            $out .= '<div class="grid-3" id="' . $blockId . '-grid">';

            foreach ($reals as $rid => $r) {
                $cardPage   = (int)floor($cardIdx / $perPage) + 1;
                $coverPath  = !empty($r['images']) ? $r['images'][0]['image_path'] : ($r['cover_image'] ?? '');
                $cover      = $coverPath ? $baseUrl . '/' . ltrim($coverPath, '/') : '';
                $galleryKey = 'gal-' . $galId . '-' . $rid;

                $out .= '<article class="card" data-gpage="' . $cardPage . '"' . ($cardPage > 1 ? ' style="display:none"' : '') . '>';
                if ($cover) {
                    $out .= '<a href="' . $cover . '" class="glightbox" data-gallery="' . $galleryKey . '">';
                    $out .= '<img src="' . $cover . '" loading="lazy" style="width:100%;height:220px;object-fit:cover;border-radius:16px;">';
                    $out .= '</a>';
                    foreach ($r['images'] as $img) {
                        $src = $baseUrl . '/' . ltrim($img['image_path'], '/');
                        $out .= '<a href="' . $src . '" class="glightbox" data-gallery="' . $galleryKey . '" style="display:none;"></a>';
                    }
                } else {
                    $out .= '<div style="height:220px;border-radius:16px;background:#1a1a1a;"></div>';
                }

                if ($showLabels) {
                    $title = htmlspecialchars($r['title']);
                    if (!empty($r['city'])) $title .= ' — ' . htmlspecialchars($r['city']);
                    $out .= '<h3 style="margin-top:14px;">' . $title . '</h3>';
                    if (!empty($r['description'])) $out .= '<p>' . htmlspecialchars($r['description']) . '</p>';
                    if (!empty($r['type'])) $out .= '<div class="muted small">' . htmlspecialchars($r['type']) . '</div>';
                }

                $out .= '</article>';
                $cardIdx++;
            }

            $out .= '</div>';

            if ($totalPages > 1) {
                $out .= '<div class="pagination-wrapper" style="display:flex;justify-content:center;gap:8px;flex-wrap:wrap;margin-top:24px;" id="' . $blockId . '-pages">';
                $out .= '<button class="btn btn-ghost cms-gal-prev" style="display:none" onclick="cmsGalPage(\'' . $blockId . '\',1,' . $totalPages . ',this,-1)">&larr; Précédent</button>';
                for ($p = 1; $p <= $totalPages; $p++) {
                    $active = $p === 1 ? 'btn-primary' : 'btn-ghost';
                    $out .= '<button class="btn ' . $active . '" data-page="' . $p . '" onclick="cmsGalPage(\'' . $blockId . '\',' . $p . ',' . $totalPages . ',this,0)">' . $p . '</button>';
                }
                $out .= '<button class="btn btn-ghost cms-gal-next" onclick="cmsGalPage(\'' . $blockId . '\',2,' . $totalPages . ',this,1)">Suivant &rarr;</button>';
                $out .= '</div>';
            }

            $out .= '</div>';
            $out .= '<script>(function(){if(window.cmsGalPage)return;window.cmsGalPage=function(blockId,targetPage,totalPages,btn,dir){var grid=document.getElementById(blockId+"-grid");if(!grid)return;if(dir!==0){var cur=parseInt(grid.querySelector(".card:not([style*=\'none\'])")?.dataset?.gpage||1);targetPage=cur+dir;}targetPage=Math.max(1,Math.min(totalPages,targetPage));grid.querySelectorAll(".card").forEach(function(c){c.style.display=(parseInt(c.dataset.gpage)===targetPage)?"":"none";});var pagesEl=document.getElementById(blockId+"-pages");if(pagesEl){pagesEl.querySelectorAll("button[data-page]").forEach(function(b){b.className=parseInt(b.dataset.page)===targetPage?"btn btn-primary":"btn btn-ghost";});var prevBtn=pagesEl.querySelector(".cms-gal-prev");var nextBtn=pagesEl.querySelector(".cms-gal-next");if(prevBtn)prevBtn.style.display=targetPage<=1?"none":"";if(nextBtn)nextBtn.style.display=targetPage>=totalPages?"none":"";}if(window.GLightbox){try{window._cmsGlb&&window._cmsGlb.destroy();window._cmsGlb=GLightbox();}catch(e){}}var block=document.getElementById(blockId);if(block)block.scrollIntoView({behavior:"smooth",block:"start"});};document.addEventListener("DOMContentLoaded",function(){if(window.GLightbox)window._cmsGlb=GLightbox();});})();</script>';

            return $out;
        },
        $html
    );
}

// Traitement du contenu (shortcodes → HTML)
$rawContent = $page['content'] ?? '';
$rawContent = _viewRenderFormFigures($rawContent);
$rawContent = _viewRenderGalleryShortcodes($rawContent);
?>

<main>

  <section class="section">
    <div class="container">
      <?php if (!empty($page['kicker'])): ?>
      <span class="kicker"><span class="dot"></span><b><?= htmlspecialchars($page['kicker']) ?></b></span>
      <?php endif; ?>

        
      <?php if (!empty($page['show_title']) || empty($page['hide_title'])): ?>
      <h1 style="margin-bottom:24px;"><?= htmlspecialchars($page['title']) ?></h1>
      <?php endif; ?>
        </div>
      <div class="cms-content page-content">
        <?= $rawContent ?>
      </div>

    
  </section>

</main>
