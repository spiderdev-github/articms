<?php
/**
 * Layout front-end — enveloppe le header/footer du thème actif.
 * $content est généré par View::render() avant l'inclusion de ce layout.
 */
require_once dirname(__DIR__, 3) . '/includes/header.php';
?>
<?= $content ?>
<?php require_once dirname(__DIR__, 3) . '/includes/footer.php'; ?>
