<?php
require_once __DIR__ . '/db.php';

/**
 * Charge tous les settings en une seule requête (cache statique).
 * Invalider en appelant _settingsCacheClear().
 */
function &_settingsCache(): array {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            $rows = getPDO()->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $cache[$r['setting_key']] = $r['setting_value'];
            }
        } catch (Throwable $e) {
            // BDD pas encore migrée : cache vide, aucune exception fatale
        }
    }
    return $cache;
}

function _settingsCacheClear(): void {
    $cache = &_settingsCache();
    $cache = [];
    // Force reload au prochain appel
    static $reset = false;
    $reset = true;
}

function getSetting(string $key, string $default = ''): string {
    $cache = &_settingsCache();
    return array_key_exists($key, $cache) ? (string)$cache[$key] : $default;
}

function setSetting(string $key, $value): void {
    $pdo = getPDO();
    $pdo->prepare("
        INSERT INTO settings (setting_key, setting_value, updated_at)
        VALUES (:k, :v, NOW())
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
    ")->execute([':k' => $key, ':v' => (string)$value]);

    // Invalider le cache
    $cache = &_settingsCache();
    $cache[$key] = (string)$value;
}

/**
 * Raccourci : getSetting() avec htmlspecialchars pour affichage HTML.
 */
function gs(string $key, string $default = ''): string {
    return htmlspecialchars(getSetting($key, $default), ENT_QUOTES, 'UTF-8');
}
