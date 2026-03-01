<?php

namespace App\Models;

use App\Core\Model;

/**
 * Modèle Settings — lecture/écriture des paramètres du CMS.
 * Synchronisé avec les helpers getSetting()/setSetting() de includes/settings.php.
 */
class SettingModel extends Model
{
    protected string $table      = 'settings';
    protected string $primaryKey = 'setting_key';

    private array $cache = [];
    private bool  $loaded = false;

    /* ── Lecture ─────────────────────────────────────────────────────────── */

    /**
     * Charge tous les settings en une seule requête (cache interne).
     */
    public function loadAll(): array
    {
        if (!$this->loaded) {
            $rows = $this->query("SELECT setting_key, setting_value FROM settings");
            foreach ($rows as $row) {
                $this->cache[$row['setting_key']] = $row['setting_value'];
            }
            $this->loaded = true;
        }
        return $this->cache;
    }

    public function get(string $key, string $default = ''): string
    {
        $this->loadAll();
        return array_key_exists($key, $this->cache) ? (string)$this->cache[$key] : $default;
    }

    /* ── Écriture ────────────────────────────────────────────────────────── */

    public function set(string $key, mixed $value): void
    {
        $this->execute(
            "INSERT INTO settings (setting_key, setting_value, updated_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()",
            [$key, (string)$value]
        );
        $this->cache[$key] = (string)$value;
    }

    /**
     * Sauvegarde un tableau de clés/valeurs en batch.
     */
    public function saveMany(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }

    /* ── Helpers spécifiques ─────────────────────────────────────────────── */

    /** Retourne les settings filtrés par préfixe (ex: 'home_'). */
    public function getByPrefix(string $prefix): array
    {
        $all = $this->loadAll();
        return array_filter($all, fn($k) => str_starts_with($k, $prefix), ARRAY_FILTER_USE_KEY);
    }
}
