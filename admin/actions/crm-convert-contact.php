<?php
/**
 * Convert a contact request into a CRM client.
 * GET: contact_id=X  (shows confirmation)
 * POST: csrf + contact_id  (performs conversion)
 */
require_once __DIR__ . '/../auth.php';
requirePermission('crm');
require_once __DIR__ . '/../../includes/db.php';
$pdo  = getPDO();
$csrf = getCsrfToken();

$contactId = (int)($_GET['contact_id'] ?? $_POST['contact_id'] ?? 0);
if (!$contactId) { header('Location: ../contacts.php'); exit; }

$st = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
$st->execute([$contactId]);
$contact = $st->fetch(PDO::FETCH_ASSOC);
if (!$contact) { header('Location: ../contacts.php'); exit; }

/* Check if already converted */
$existing = $pdo->prepare("SELECT id FROM crm_clients WHERE contact_id = ?");
$existing->execute([$contactId]);
$existingId = $existing->fetchColumn();
if ($existingId) {
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Ce contact est déjà un client.'];
    header("Location: ../crm-client-edit.php?id=$existingId");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['csrf'] ?? '') === $csrf) {
    /* Generate ref */
    $lastRef = $pdo->query("SELECT ref FROM crm_clients ORDER BY id DESC LIMIT 1")->fetchColumn();
    $nextNum = 1;
    if ($lastRef && preg_match('/CLI-(\d+)/', $lastRef, $m)) $nextNum = (int)$m[1] + 1;
    $ref = 'CLI-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

    $now = date('Y-m-d H:i:s');
    $pdo->prepare("INSERT INTO crm_clients (contact_id, ref, type, name, email, phone, city, notes, created_at, updated_at)
        VALUES (?,?,?,?,?,?,?,?,?,?)")
        ->execute([
            $contactId,
            $ref,
            'particulier',
            $contact['name'],
            $contact['email'],
            $contact['phone'],
            $contact['city'],
            'Converti depuis contact #'.$contactId."\nService : ".($contact['service']??'')."\nMessage : ".($contact['message']??''),
            $now, $now,
        ]);
    $newClientId = (int)$pdo->lastInsertId();

    /* Update contact pipeline */
    $pdo->prepare("UPDATE contacts SET pipeline_status='won', status='treated', updated_at=? WHERE id=?")->execute([$now, $contactId]);

    $_SESSION['flash'] = ['type'=>'success','msg'=>'Contact converti en client '.$ref.'.'];
    header("Location: ../crm-client-edit.php?id=$newClientId");
    exit;
}

/* GET: confirmation page */
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Convertir en client</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">
  <style>body{background:#0f1116;display:flex;align-items:center;justify-content:center;min-height:100vh;}</style>
</head>
<body class="hold-transition dark-mode">
<div class="card" style="max-width:480px;width:100%;margin:24px;">
  <div class="card-header bg-primary text-white">
    <h5 class="m-0"><i class="fas fa-user-plus mr-2"></i>Convertir en client</h5>
  </div>
  <div class="card-body">
    <p>Voulez-vous créer un client CRM à partir de la demande de contact de :</p>
    <div class="alert alert-light">
      <strong><?= htmlspecialchars($contact['name']) ?></strong><br>
      <?= htmlspecialchars($contact['email']) ?><?= $contact['phone'] ? ' · '.$contact['phone'] : '' ?><br>
      <?= $contact['service'] ? '<small>'.htmlspecialchars($contact['service']).'</small>' : '' ?>
    </div>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= $csrf ?>">
      <input type="hidden" name="contact_id" value="<?= $contactId ?>">
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-1"></i>Confirmer</button>
        <a href="../contact-view.php?id=<?= $contactId ?>" class="btn btn-outline-secondary ml-2">Annuler</a>
      </div>
    </form>
  </div>
</div>
</body>
</html>
