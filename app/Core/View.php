<?php

namespace App\Core;

/**
 * Moteur de rendu de vues.
 *
 * Les templates sont situés dans app/Views/.
 * Les variables du tableau $data sont extraites comme variables locales dans le template.
 * Un layout peut envelopper la vue (via $layout).
 */
class View
{
    private string $viewsPath;

    public function __construct()
    {
        $this->viewsPath = dirname(__DIR__) . '/Views';
    }

    /* ── Rendu ────────────────────────────────────────────────────────────── */

    /**
     * Affiche la vue $template avec les données $data.
     * Si la vue définit $layout, celle-ci est encapsulée dans le layout.
     *
     * @param string $template  Ex: 'admin/contacts/index'
     * @param array  $data      Variables à injecter
     */
    public function render(string $template, array $data = []): void
    {
        $file = $this->viewsPath . '/' . ltrim($template, '/') . '.php';

        if (!file_exists($file)) {
            throw new \RuntimeException("Vue introuvable : $file");
        }

        // Extrait les données comme variables locales
        extract($data, EXTR_SKIP);

        // Bufferiser la vue
        ob_start();
        include $file;
        $content = ob_get_clean();

        // Si un layout est défini dans la vue ou dans les données
        $layout = $data['layout'] ?? $layout ?? null;

        if ($layout) {
            $layoutFile = $this->viewsPath . '/layouts/' . $layout . '.php';
            if (!file_exists($layoutFile)) {
                throw new \RuntimeException("Layout introuvable : $layoutFile");
            }
            include $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Retourne le HTML d'une vue sans l'afficher.
     */
    public function fetch(string $template, array $data = []): string
    {
        ob_start();
        $this->render($template, $data);
        return ob_get_clean();
    }

    /**
     * Affiche un partial (fragment de vue réutilisable).
     *
     * @param string $partial  Ex: 'admin/partials/header'
     * @param array  $data
     */
    public function partial(string $partial, array $data = []): void
    {
        $this->render($partial, $data);
    }

    /* ── Helpers HTML ─────────────────────────────────────────────────────── */

    /**
     * Échappe une chaîne pour affichage HTML sécurisé.
     */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
