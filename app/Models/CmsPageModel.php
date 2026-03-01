<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modèle CMS Page — pages statiques gérées via l'éditeur.
 */
class CmsPageModel extends Model
{
    protected string $table = 'cms_pages';

    /* ── Listes ──────────────────────────────────────────────────────────── */

    public function allPages(): array
    {
        return $this->query(
            "SELECT * FROM cms_pages ORDER BY created_at DESC"
        );
    }

    public function publishedPages(): array
    {
        return $this->where(['is_published' => 1], 'created_at DESC');
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->findWhere(['slug' => $slug]);
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        return $this->queryOne(
            "SELECT * FROM cms_pages WHERE slug = ? AND is_published = 1",
            [$slug]
        );
    }

    /**
     * Résolution hiérarchique parent/enfant.
     * Ex : /prestations/peinture-interieure-en-alsace
     */
    public function findPublishedByParentAndSlug(string $parentSlug, string $childSlug): ?array
    {
        return $this->queryOne(
            "SELECT c.* FROM cms_pages c
             INNER JOIN cms_pages p ON c.parent_id = p.id
             WHERE p.slug = ? AND c.slug = ? AND c.is_published = 1
             LIMIT 1",
            [$parentSlug, $childSlug]
        );
    }

    /* ── Stats ───────────────────────────────────────────────────────────── */

    public function countPublished(): int
    {
        return $this->count(['is_published' => 1]);
    }

    /* ── Slug unique ─────────────────────────────────────────────────────── */

    public function slugExists(string $slug, ?int $excludeId = null): bool
    {
        if ($excludeId) {
            return (bool)$this->queryValue(
                "SELECT COUNT(*) FROM cms_pages WHERE slug = ? AND id != ?",
                [$slug, $excludeId]
            );
        }
        return (bool)$this->queryValue(
            "SELECT COUNT(*) FROM cms_pages WHERE slug = ?",
            [$slug]
        );
    }

    public function generateUniqueSlug(string $base, ?int $excludeId = null): string
    {
        $slug = $base;
        $i    = 1;
        while ($this->slugExists($slug, $excludeId)) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
