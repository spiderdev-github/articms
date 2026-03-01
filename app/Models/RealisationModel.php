<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modèle Réalisation — gestion des chantiers/projets publiés.
 */
class RealisationModel extends Model
{
    protected string $table = 'realisations';

    /* ── Listes ──────────────────────────────────────────────────────────── */

    public function allOrdered(): array
    {
        return $this->query(
            "SELECT * FROM realisations ORDER BY sort_order ASC, created_at DESC"
        );
    }

    public function published(int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM realisations WHERE is_published = 1
                ORDER BY is_featured DESC, sort_order ASC, created_at DESC";
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        return $this->query($sql);
    }

    public function recent(int $limit = 5): array
    {
        return $this->query(
            "SELECT id, title, city, type, is_published, created_at
             FROM realisations ORDER BY created_at DESC LIMIT $limit"
        );
    }

    /* ── Stats ───────────────────────────────────────────────────────────── */

    public function stats(): array
    {
        return [
            'total'    => $this->count(['is_published' => 1]),
            'draft'    => $this->count(['is_published' => 0]),
        ];
    }

    /* ── Featured ────────────────────────────────────────────────────────── */

    public function featured(): ?array
    {
        return $this->queryOne(
            "SELECT id, title, city, type, cover_thumb, cover_image
             FROM realisations
             WHERE is_published = 1
             ORDER BY is_featured DESC, sort_order ASC, created_at DESC
             LIMIT 1"
        );
    }

    public function findPublished(int $id): ?array
    {
        return $this->queryOne(
            "SELECT * FROM realisations WHERE id = ? AND is_published = 1",
            [$id]
        );
    }

    /* ── Images ──────────────────────────────────────────────────────────── */

    public function getImages(int $realisationId): array
    {
        return $this->query(
            "SELECT * FROM realisation_images
             WHERE realisation_id = ?
             ORDER BY sort_order ASC, id ASC",
            [$realisationId]
        );
    }

    public function addImage(int $realisationId, string $path, string $thumb = ''): int
    {
        $this->execute(
            "INSERT INTO realisation_images (realisation_id, image_path, thumb_path, sort_order, created_at)
             VALUES (?, ?, ?, (SELECT COALESCE(MAX(sort_order),0)+1 FROM realisation_images ri WHERE ri.realisation_id=?), NOW())",
            [$realisationId, $path, $thumb, $realisationId]
        );
        return (int)$this->pdo->lastInsertId();
    }

    public function deleteImage(int $imageId): ?array
    {
        $img = $this->queryOne("SELECT * FROM realisation_images WHERE id = ?", [$imageId]);
        if ($img) {
            $this->execute("DELETE FROM realisation_images WHERE id = ?", [$imageId]);
        }
        return $img;
    }

    public function sortImages(array $orderedIds): void
    {
        foreach ($orderedIds as $pos => $id) {
            $this->execute(
                "UPDATE realisation_images SET sort_order = ? WHERE id = ?",
                [$pos, (int)$id]
            );
        }
    }

    public function setCover(int $realisationId, string $imagePath, string $thumbPath): void
    {
        $this->execute(
            "UPDATE realisations SET cover_image = ?, cover_thumb = ? WHERE id = ?",
            [$imagePath, $thumbPath, $realisationId]
        );
    }

    /* ── Toggle ──────────────────────────────────────────────────────────── */

    public function toggle(int $id, string $field): void
    {
        $allowed = ['is_published', 'is_featured'];
        if (!in_array($field, $allowed, true)) return;
        $this->execute(
            "UPDATE realisations SET `$field` = NOT `$field` WHERE id = ?",
            [$id]
        );
    }

    /* ── Sort ────────────────────────────────────────────────────────────── */

    public function updateSortOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $pos => $id) {
            $this->execute(
                "UPDATE realisations SET sort_order = ? WHERE id = ?",
                [$pos, (int)$id]
            );
        }
    }
}
