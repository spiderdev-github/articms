<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modèle Media — métadonnées des fichiers médias (images assets).
 */
class MediaModel extends Model
{
    protected string $table = 'media_meta';

    /* ── Alt text ────────────────────────────────────────────────────────── */

    /**
     * Retourne un tableau indexé par 'rel' => ['alt' => '...'].
     */
    public function getAllMeta(): array
    {
        $meta = [];
        $rows = $this->query("SELECT rel, alt_text FROM media_meta");
        foreach ($rows as $row) {
            $meta[$row['rel']] = ['alt' => $row['alt_text']];
        }
        return $meta;
    }

    public function saveAlt(string $rel, string $altText): void
    {
        $this->execute(
            "INSERT INTO media_meta (rel, alt_text)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE alt_text = VALUES(alt_text)",
            [$rel, $altText]
        );
    }

    public function deleteByRel(string $rel): void
    {
        $this->execute("DELETE FROM media_meta WHERE rel = ?", [$rel]);
    }

    public function countMedia(): int
    {
        return $this->count();
    }
}
