<?php
require_once __DIR__.'/../auth.php';
requireAdmin();
require_once __DIR__.'/../../includes/db.php';

$pdo = getPDO();

$data = json_decode(file_get_contents("php://input"), true);
if(!is_array($data)) exit;

foreach($data as $row){
  $pdo->prepare("
    UPDATE realisation_images
    SET sort_order = ?
    WHERE id = ?
  ")->execute([(int)$row['position'], (int)$row['id']]);
}