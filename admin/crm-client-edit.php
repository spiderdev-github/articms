<?php
/* Bootstrap sans HTML pour permettre les redirections POST */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth.php';
requirePermission('crm');
$pdo  = getPDO();
$csrf = getCsrfToken();

$id     = (int)($_GET['id'] ?? 0);
$isNew  = ($id === 0);
$client = null;
$errors = [];

/* Pre-fill from contact if converting */
$fromContact = null;
$contactId   = (int)($_GET['contact_id'] ?? 0);
if ($isNew && $contactId > 0) {
    $st = $pdo->prepare("SELECT * FROM contacts WHERE id = ?");
    $st->execute([$contactId]);
    $fromContact = $st->fetch(PDO::FETCH_ASSOC);
}

/* Load existing client */
if (!$isNew) {
    $st = $pdo->prepare("SELECT * FROM crm_clients WHERE id = ?");
    $st->execute([$id]);
    $client = $st->fetch(PDO::FETCH_ASSOC);
    if (!$client) { $_SESSION['flash'] = ['type'=>'error','msg'=>'Client introuvable.']; header('Location: crm-clients.php'); exit; }
}

/* Handle POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['csrf'] ?? '') !== $csrf) { die('CSRF'); }

    $data = [
        'type'    => in_array($_POST['type']??'', ['particulier','professionnel']) ? $_POST['type'] : 'particulier',
        'name'    => trim($_POST['name'] ?? ''),
        'company' => trim($_POST['company'] ?? ''),
        'email'   => trim($_POST['email'] ?? ''),
        'phone'   => trim($_POST['phone'] ?? ''),
        'address' => trim($_POST['address'] ?? ''),
        'city'    => trim($_POST['city'] ?? ''),
        'zip'     => trim($_POST['zip'] ?? ''),
        'notes'   => trim($_POST['notes'] ?? ''),
        'contact_id' => (int)($_POST['contact_id'] ?? 0) ?: null,
    ];

    if ($data['name'] === '') $errors[] = 'Le nom est obligatoire.';

    if (empty($errors)) {
        if ($isNew) {
            /* Generate ref: CLI-0001 */
            $lastRef = $pdo->query("SELECT ref FROM crm_clients ORDER BY id DESC LIMIT 1")->fetchColumn();
            $nextNum = 1;
            if ($lastRef && preg_match('/CLI-(\d+)/', $lastRef, $m)) $nextNum = (int)$m[1] + 1;
            $data['ref'] = 'CLI-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            $pdo->prepare("INSERT INTO crm_clients (contact_id, ref, type, name, company, email, phone, address, city, zip, notes, created_at, updated_at)
                VALUES (:contact_id,:ref,:type,:name,:company,:email,:phone,:address,:city,:zip,:notes,:created_at,:updated_at)")
                ->execute($data);
            $newId = (int)$pdo->lastInsertId();

            /* Mark contact as converted if applicable */
            if ($data['contact_id']) {
                $pdo->prepare("UPDATE contacts SET pipeline_status='won', status='treated' WHERE id=?")->execute([$data['contact_id']]);
            }

            $_SESSION['flash'] = ['type'=>'success','msg'=>'Client '.$data['ref'].' créé.'];
            header("Location: crm-client-edit.php?id=$newId");
            exit;
        } else {
            $data['updated_at'] = date('Y-m-d H:i:s');
            $pdo->prepare("UPDATE crm_clients SET type=:type, name=:name, company=:company, email=:email, phone=:phone, address=:address, city=:city, zip=:zip, notes=:notes, updated_at=:updated_at WHERE id=:id")
                ->execute([
                    'type'       => $data['type'],
                    'name'       => $data['name'],
                    'company'    => $data['company'],
                    'email'      => $data['email'],
                    'phone'      => $data['phone'],
                    'address'    => $data['address'],
                    'city'       => $data['city'],
                    'zip'        => $data['zip'],
                    'notes'      => $data['notes'],
                    'updated_at' => $data['updated_at'],
                    'id'         => $id,
                ]);
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Client mis à jour.'];
            header("Location: crm-client-edit.php?id=$id");
            exit;
        }
    }
    /* On error: re-populate form */
    $client = array_merge($client ?? [], $data);
}

/* Default values */
$v = [
    'type'      => $client['type']    ?? $fromContact['type'] ?? 'particulier',
    'name'      => $client['name']    ?? $fromContact['name'] ?? '',
    'company'   => $client['company'] ?? '',
    'email'     => $client['email']   ?? $fromContact['email'] ?? '',
    'phone'     => $client['phone']   ?? $fromContact['phone'] ?? '',
    'address'   => $client['address'] ?? '',
    'city'      => $client['city']    ?? $fromContact['city'] ?? '',
    'zip'       => $client['zip']     ?? '',
    'notes'     => $client['notes']   ?? ($fromContact ? 'Converti depuis le contact #'.$contactId.' — '.$fromContact['message'] : ''),
    'contact_id'=> $client['contact_id'] ?? ($contactId ?: ''),
];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ── HTML output begins here ──────────────────────────────── */
require_once __DIR__ . '/partials/header.php';

/* Devis liés si existant */
$linkedDevis = [];
if (!$isNew) {
    $st = $pdo->prepare("SELECT * FROM crm_devis WHERE client_id = ? ORDER BY created_at DESC");
    $st->execute([$id]);
    $linkedDevis = $st->fetchAll(PDO::FETCH_ASSOC);
}
$statusLabels = [
    'draft'=>'Brouillon','sent'=>'Envoyé','accepted'=>'Accepté',
    'refused'=>'Refusé','invoiced'=>'Facturé','paid'=>'Payé',
];
$statusColors = [
    'draft'=>'secondary','sent'=>'info','accepted'=>'success',
    'refused'=>'danger','invoiced'=>'warning','paid'=>'success',
];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0">
    <?php if ($isNew): ?>
      <i class="fas fa-user-plus mr-2"></i>Nouveau client
    <?php else: ?>
      <i class="fas fa-user-edit mr-2"></i><?= htmlspecialchars($client['name']) ?>
      <small class="text-muted ml-2"><?= htmlspecialchars($client['ref']) ?></small>
    <?php endif; ?>
  </h4>
  <a href="crm-clients.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Retour</a>
</div>

<?php if ($fromContact): ?>
<div class="alert alert-info">
  <i class="fas fa-info-circle mr-1"></i>
  Pré-rempli depuis la demande de contact de <strong><?= htmlspecialchars($fromContact['name']) ?></strong>
  (<?= htmlspecialchars($fromContact['service']) ?>).
</div>
<?php endif; ?>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible">
  <?= htmlspecialchars($flash['msg']) ?>
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>

<?php if ($errors): ?>
<div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<div class="row">
  <!-- Formulaire -->
  <div class="col-md-8">
    <div class="card">
      <div class="card-header"><h5 class="m-0">Informations client</h5></div>
      <div class="card-body">
        <form method="post">
          <input type="hidden" name="csrf" value="<?= $csrf ?>">
          <input type="hidden" name="contact_id" value="<?= (int)$v['contact_id'] ?>">

          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Type *</label>
              <select name="type" class="form-control">
                <option value="particulier" <?= $v['type'] === 'particulier' ? 'selected' : '' ?>>Particulier</option>
                <option value="professionnel" <?= $v['type'] === 'professionnel' ? 'selected' : '' ?>>Professionnel</option>
              </select>
            </div>
            <div class="form-group col-md-8">
              <label>Nom / Prénom *</label>
              <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($v['name']) ?>" placeholder="Ex : Dupont Jean">
            </div>
          </div>

          <div class="form-group">
            <label>Société <small class="text-muted">(si professionnel)</small></label>
            <input type="text" name="company" class="form-control" value="<?= htmlspecialchars($v['company']) ?>" placeholder="Raison sociale">
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Email</label>
              <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($v['email']) ?>">
            </div>
            <div class="form-group col-md-6">
              <label>Téléphone</label>
              <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($v['phone']) ?>">
            </div>
          </div>

          <div class="form-group">
            <label>Adresse</label>
            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($v['address']) ?>" placeholder="N° et nom de rue">
          </div>

          <div class="form-row">
            <div class="form-group col-md-4">
              <label>Code postal</label>
              <input type="text" name="zip" class="form-control" value="<?= htmlspecialchars($v['zip']) ?>">
            </div>
            <div class="form-group col-md-8">
              <label>Ville</label>
              <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($v['city']) ?>">
            </div>
          </div>

          <div class="form-group">
            <label>Notes internes</label>
            <textarea name="notes" class="form-control" rows="4"><?= htmlspecialchars($v['notes']) ?></textarea>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i>Enregistrer</button>
            <?php if (!$isNew): ?>
            <a href="crm-devis-edit.php?client_id=<?= $id ?>" class="btn btn-success ml-2">
              <i class="fas fa-file-invoice mr-1"></i>Nouveau devis
            </a>
            <?php endif; ?>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Sidebar infos -->
  <div class="col-md-4">
    <?php if (!$isNew): ?>
    <!-- Devis liés -->
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="m-0">Devis / Factures</h5>
        <a href="crm-devis-edit.php?client_id=<?= $id ?>" class="btn btn-xs btn-success"><i class="fas fa-plus"></i></a>
      </div>
      <div class="card-body p-0">
        <?php if (empty($linkedDevis)): ?>
          <p class="text-muted p-3 m-0">Aucun document.</p>
        <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($linkedDevis as $d): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-2">
            <div>
              <a href="crm-devis-edit.php?id=<?= $d['id'] ?>"><code><?= htmlspecialchars($d['ref']) ?></code></a>
              <br><small class="text-muted"><?= number_format((float)$d['total_ttc'],2,',',' ') ?> € TTC</small>
            </div>
            <div class="text-right">
              <span class="badge badge-<?= $statusColors[$d['status']] ?? 'secondary' ?>"><?= $statusLabels[$d['status']] ?? $d['status'] ?></span>
              <br><small><?= $d['issued_at'] ? date('d/m/Y', strtotime($d['issued_at'])) : '—' ?></small>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
    <!-- Infos -->
    <div class="card mt-3">
      <div class="card-body">
        <small class="text-muted">
          Créé le : <?= date('d/m/Y H:i', strtotime($client['created_at'])) ?><br>
          Modifié le : <?= $client['updated_at'] ? date('d/m/Y H:i', strtotime($client['updated_at'])) : '—' ?>
        </small>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
