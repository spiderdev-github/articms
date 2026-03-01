<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modèle Galerie — albums photos.
 */
class GalleryModel extends Model
{
    protected string $table = 'galleries';

    public function allWithItemCount(): array
    {
        return $this->query(
            "SELECT g.*, COUNT(gi.id) AS item_count
             FROM galleries g
             LEFT JOIN gallery_items gi ON gi.gallery_id = g.id
             GROUP BY g.id
             ORDER BY g.created_at DESC"
        );
    }

    public function findWithItems(int $id): ?array
    {
        $gallery = $this->find($id);
        if (!$gallery) return null;

        $gallery['items'] = $this->getItems($id);
        return $gallery;
    }

    /* ── Items ───────────────────────────────────────────────────────────── */

    public function getItems(int $galleryId): array
    {
        return $this->query(
            "SELECT * FROM gallery_items
             WHERE gallery_id = ?
             ORDER BY sort_order ASC, id ASC",
            [$galleryId]
        );
    }

    public function addItem(int $galleryId, string $imagePath, string $thumbPath = '', string $caption = ''): int
    {
        $this->execute(
            "INSERT INTO gallery_items (gallery_id, image_path, thumb_path, caption, sort_order, created_at)
             VALUES (?, ?, ?, ?, (SELECT COALESCE(MAX(sort_order),0)+1 FROM gallery_items gi WHERE gi.gallery_id=?), NOW())",
            [$galleryId, $imagePath, $thumbPath, $caption, $galleryId]
        );
        return (int)$this->pdo->lastInsertId();
    }

    public function deleteItem(int $itemId): ?array
    {
        $item = $this->queryOne("SELECT * FROM gallery_items WHERE id = ?", [$itemId]);
        if ($item) {
            $this->execute("DELETE FROM gallery_items WHERE id = ?", [$itemId]);
        }
        return $item;
    }

    public function countItems(): int
    {
        return (int)$this->queryValue("SELECT COUNT(*) FROM gallery_items");
    }

    public function countGalleries(): int
    {
        return $this->count();
    }
}
