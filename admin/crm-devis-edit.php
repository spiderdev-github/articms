<?php
/* Bootstrap sans HTML pour permettre les redirections POST */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth.php';
requirePermission('crm');
$pdo  = getPDO();
$csrf = getCsrfToken();

$id       = (int)($_GET['id'] ?? 0);
$isNew    = ($id === 0);
$devis    = null;
$lines    = [];
$errors   = [];

/* Pre-select client */
$preClientId = (int)($_GET['client_id'] ?? 0);

/* Status options */
$statusOptions = [
    'draft'    => 'Brouillon',
    'sent'     => 'Envoyé',
    'accepted' => 'Accepté',
    'refused'  => 'Refusé',
    'invoiced' => 'Facturé',
    'paid'     => 'Payé',
];
$typeOptions = ['devis' => 'Devis', 'facture' => 'Facture'];

/* Load existing */
if (!$isNew) {
    $st = $pdo->prepare("SELECT * FROM crm_devis WHERE id = ?");
    $st->execute([$id]);
    $devis = $st->fetch(PDO::FETCH_ASSOC);
    if (!$devis) { $_SESSION['flash'] = ['type'=>'error','msg'=>'Document introuvable.']; header('Location: crm-devis.php'); exit; }

    $st2 = $pdo->prepare("SELECT * FROM crm_devis_lines WHERE devis_id = ? ORDER BY sort_order");
    $st2->execute([$id]);
    $lines = $st2->fetchAll(PDO::FETCH_ASSOC);
}

/* All clients for select */
$clients = $pdo->query("SELECT id, ref, name FROM crm_clients ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

/* Handle POST */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_POST['csrf'] ?? '') !== $csrf) { die('CSRF'); }

    $clientId = (int)($_POST['client_id'] ?? 0);
    $data = [
        'client_id'   => $clientId,
        'type'        => in_array($_POST['type']??'', ['devis','facture']) ? $_POST['type'] : 'devis',
        'status'      => array_key_exists($_POST['status']??'', $statusOptions) ? $_POST['status'] : 'draft',
        'title'       => trim($_POST['title'] ?? ''),
        'intro'       => trim($_POST['intro'] ?? ''),
        'footer_note' => trim($_POST['footer_note'] ?? ''),
        'issued_at'   => $_POST['issued_at'] ?: null,
        'valid_until' => $_POST['valid_until'] ?: null,
        'paid_at'     => $_POST['paid_at'] ?: null,
        'tva_rate'    => min(100, max(0, (float)str_replace(',','.',($_POST['tva_rate'] ?? '10')))),
    ];

    if (!$clientId) $errors[] = 'Veuillez sélectionner un client.';

    /* Build lines */
    $descs      = $_POST['line_desc']       ?? [];
    $qtys       = $_POST['line_qty']        ?? [];
    $units      = $_POST['line_unit']       ?? [];
    $prices     = $_POST['line_price']      ?? [];
    $newLines   = [];
    $totalHt    = 0;
    foreach ($descs as $i => $desc) {
        $desc  = trim($desc);
        if ($desc === '') continue;
        $qty   = max(0, (float)str_replace(',', '.', $qtys[$i] ?? 1));
        $price = max(0, (float)str_replace(',', '.', $prices[$i] ?? 0));
        $tot   = round($qty * $price, 2);
        $totalHt += $tot;
        $newLines[] = [
            'sort_order'  => $i,
            'description' => $desc,
            'qty'         => $qty,
            'unit'        => trim($units[$i] ?? ''),
            'unit_price'  => $price,
            'total'       => $tot,
        ];
    }

    $data['total_ht']  = round($totalHt, 2);
    $data['total_ttc'] = round($totalHt * (1 + $data['tva_rate'] / 100), 2);
    $data['updated_at'] = date('Y-m-d H:i:s');

    if (empty($errors)) {
        if ($isNew) {
            /* Generate ref: DEV-2026-001 or FAC-2026-001 */
            $prefix  = $data['type'] === 'facture' ? 'FAC' : 'DEV';
            $year    = date('Y');
            $lastRef = $pdo->query("SELECT ref FROM crm_devis WHERE ref LIKE '$prefix-$year-%' ORDER BY id DESC LIMIT 1")->fetchColumn();
            $nextNum = 1;
            if ($lastRef && preg_match("/$prefix-$year-(\d+)/", $lastRef, $m)) $nextNum = (int)$m[1] + 1;
            $data['ref']        = "$prefix-$year-" . str_pad($nextNum, 3, '0', STR_PAD_LEFT);
            $data['created_at'] = date('Y-m-d H:i:s');
            if (!$data['issued_at']) $data['issued_at'] = date('Y-m-d');

            $pdo->prepare("INSERT INTO crm_devis (client_id,ref,type,status,title,intro,footer_note,total_ht,tva_rate,total_ttc,issued_at,valid_until,paid_at,created_at,updated_at)
                VALUES (:client_id,:ref,:type,:status,:title,:intro,:footer_note,:total_ht,:tva_rate,:total_ttc,:issued_at,:valid_until,:paid_at,:created_at,:updated_at)")
                ->execute($data);
            $id = (int)$pdo->lastInsertId();
            $isNew = false;
        } else {
            $pdo->prepare("UPDATE crm_devis SET client_id=:client_id, type=:type, status=:status, title=:title, intro=:intro, footer_note=:footer_note, total_ht=:total_ht, tva_rate=:tva_rate, total_ttc=:total_ttc, issued_at=:issued_at, valid_until=:valid_until, paid_at=:paid_at, updated_at=:updated_at WHERE id=$id")
                ->execute($data);
        }

        /* Replace lines */
        $pdo->prepare("DELETE FROM crm_devis_lines WHERE devis_id = ?")->execute([$id]);
        $insLine = $pdo->prepare("INSERT INTO crm_devis_lines (devis_id, sort_order, description, qty, unit, unit_price, total) VALUES (?,?,?,?,?,?,?)");
        foreach ($newLines as $l) {
            $insLine->execute([$id, $l['sort_order'], $l['description'], $l['qty'], $l['unit'], $l['unit_price'], $l['total']]);
        }

        $_SESSION['flash'] = ['type'=>'success','msg'=>'Document enregistré.'];
        header("Location: crm-devis-edit.php?id=$id");
        exit;
    }
    /* on error, keep data */
    $devis = array_merge($devis ?? [], $data);
    $lines = $newLines;
}

/* Defaults */
$v = [
    'client_id'   => $devis['client_id']   ?? $preClientId,
    'type'        => $devis['type']        ?? 'devis',
    'status'      => $devis['status']      ?? 'draft',
    'title'       => $devis['title']       ?? '',
    'intro'       => $devis['intro']       ?? '',
    'footer_note' => $devis['footer_note'] ?? 'Devis valable 30 jours. TVA 10 % (travaux de rénovation). Paiement à réception de facture.',
    'issued_at'   => $devis['issued_at']   ?? date('Y-m-d'),
    'valid_until' => $devis['valid_until'] ?? date('Y-m-d', strtotime('+30 days')),
    'paid_at'     => $devis['paid_at']     ?? '',
    'tva_rate'    => $devis['tva_rate']    ?? 10,
    'total_ht'    => $devis['total_ht']    ?? 0,
    'total_ttc'   => $devis['total_ttc']  ?? 0,
];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ── HTML output begins here ──────────────────────────────── */
require_once __DIR__ . '/partials/header.php';

$statusColors = ['draft'=>'secondary','sent'=>'info','accepted'=>'success','refused'=>'danger','invoiced'=>'warning','paid'=>'success'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="m-0">
    <?php if ($isNew): ?>
      <i class="fas fa-file-invoice mr-2"></i>Nouveau devis
    <?php else: ?>
      <i class="fas fa-file-invoice mr-2"></i><?= htmlspecialchars($devis['ref']) ?>
      <span class="badge badge-<?= $statusColors[$devis['status']] ?? 'secondary' ?> ml-2">
        <?= $statusOptions[$devis['status']] ?? $devis['status'] ?>
      </span>
    <?php endif; ?>
  </h4>
  <div>
    <?php if (!$isNew): ?>
    <a href="crm-devis-print.php?id=<?= $id ?>" target="_blank" class="btn btn-sm btn-outline-secondary mr-2">
      <i class="fas fa-print mr-1"></i>Aperçu / Imprimer
    </a>
    <?php endif; ?>
    <a href="crm-devis.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Retour</a>
  </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-<?= $flash['type'] === 'success' ? 'success' : 'danger' ?> alert-dismissible">
  <?= htmlspecialchars($flash['msg']) ?>
  <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
</div>
<?php endif; ?>
<?php if ($errors): ?>
<div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
<?php endif; ?>

<form method="post" id="devisForm">
<input type="hidden" name="csrf" value="<?= $csrf ?>">

<div class="row">
  <!-- Colonne gauche -->
  <div class="col-md-8">

    <!-- En-tête du document -->
    <div class="card mb-3">
      <div class="card-header"><h5 class="m-0">En-tête</h5></div>
      <div class="card-body">
        <div class="form-row">
          <div class="form-group col-md-4">
            <label>Type *</label>
            <select name="type" class="form-control">
              <?php foreach ($typeOptions as $k => $lbl): ?>
              <option value="<?= $k ?>" <?= $v['type'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-md-8">
            <label>Client *</label>
            <select name="client_id" class="form-control" required>
              <option value="">— Sélectionner un client —</option>
              <?php foreach ($clients as $cl): ?>
              <option value="<?= $cl['id'] ?>" <?= (int)$v['client_id'] === (int)$cl['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cl['ref']) ?> — <?= htmlspecialchars($cl['name']) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <small><a href="crm-client-edit.php" target="_blank"><i class="fas fa-plus mr-1"></i>Créer un client</a></small>
          </div>
        </div>

        <div class="form-group">
          <label>Titre / Objet</label>
          <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($v['title']) ?>" placeholder="Ex : Ravalement de façade — 12 rue des Acacias, Colmar">
        </div>

        <div class="form-group">
          <label>Texte d'introduction</label>
          <textarea name="intro" class="form-control" rows="3" placeholder="Description générale du projet…"><?= htmlspecialchars($v['intro']) ?></textarea>
        </div>
      </div>
    </div>

    <!-- Lignes de prestation -->
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="m-0">Prestations</h5>
        <button type="button" class="btn btn-sm btn-success" id="addLine">
          <i class="fas fa-plus mr-1"></i>Ajouter une ligne
        </button>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table m-0" id="linesTable">
          <thead class="thead-dark">
            <tr>
              <th style="width:40%">Description *</th>
              <th style="width:10%">Qté</th>
              <th style="width:12%">Unité</th>
              <th style="width:14%">P.U. HT (€)</th>
              <th style="width:14%" class="text-right">Total HT</th>
              <th style="width:4%"></th>
            </tr>
          </thead>
          <tbody id="linesBody">
            <?php if (empty($lines)): ?>
            <!-- Default empty line -->
            <tr class="line-row">
              <td><input type="text" name="line_desc[]" class="form-control form-control-sm" placeholder="Ex : Peinture façade nord, 2 couches" required></td>
              <td><input type="number" name="line_qty[]" class="form-control form-control-sm line-qty" value="1" step="0.01" min="0"></td>
              <td><input type="text" name="line_unit[]" class="form-control form-control-sm" placeholder="m²"></td>
              <td><input type="number" name="line_price[]" class="form-control form-control-sm line-price" value="0" step="0.01" min="0"></td>
              <td class="text-right align-middle"><span class="line-total fw-bold">0,00</span></td>
              <td class="align-middle"><button type="button" class="btn btn-xs btn-outline-danger remove-line"><i class="fas fa-times"></i></button></td>
            </tr>
            <?php else: ?>
            <?php foreach ($lines as $l): ?>
            <tr class="line-row">
              <td><input type="text" name="line_desc[]" class="form-control form-control-sm" value="<?= htmlspecialchars($l['description']) ?>" required></td>
              <td><input type="number" name="line_qty[]" class="form-control form-control-sm line-qty" value="<?= htmlspecialchars($l['qty']) ?>" step="0.01" min="0"></td>
              <td><input type="text" name="line_unit[]" class="form-control form-control-sm" value="<?= htmlspecialchars($l['unit'] ?? '') ?>"></td>
              <td><input type="number" name="line_price[]" class="form-control form-control-sm line-price" value="<?= htmlspecialchars($l['unit_price']) ?>" step="0.01" min="0"></td>
              <td class="text-right align-middle"><span class="line-total"><?= number_format((float)$l['total'], 2, ',', ' ') ?></span></td>
              <td class="align-middle"><button type="button" class="btn btn-xs btn-outline-danger remove-line"><i class="fas fa-times"></i></button></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
          <tfoot>
            <tr class="table-light">
              <td colspan="4" class="text-right font-weight-bold">Total HT</td>
              <td class="text-right font-weight-bold" id="sumHt"><?= number_format((float)$v['total_ht'], 2, ',', ' ') ?></td>
              <td></td>
            </tr>
            <tr class="table-light">
              <td colspan="3" class="text-right">
                TVA
              </td>
              <td>
                <div class="input-group input-group-sm">
                  <input type="number" name="tva_rate" id="tvaRate" class="form-control form-control-sm" value="<?= htmlspecialchars($v['tva_rate']) ?>" step="0.5" min="0" max="100" style="max-width:70px;">
                  <div class="input-group-append"><span class="input-group-text">%</span></div>
                </div>
              </td>
              <td class="text-right font-weight-bold" id="sumTva">—</td>
              <td></td>
            </tr>
            <tr class="table-dark">
              <td colspan="4" class="text-right font-weight-bold">TOTAL TTC</td>
              <td class="text-right font-weight-bold" id="sumTtc"><?= number_format((float)$v['total_ttc'], 2, ',', ' ') ?></td>
              <td></td>
            </tr>
          </tfoot>
        </table>
        </div>
      </div>
    </div>

    <!-- Note de bas de page -->
    <div class="card mb-3">
      <div class="card-header"><h5 class="m-0">Note de bas de page</h5></div>
      <div class="card-body">
        <textarea name="footer_note" class="form-control" rows="3"><?= htmlspecialchars($v['footer_note']) ?></textarea>
      </div>
    </div>

  </div>

  <!-- Colonne droite -->
  <div class="col-md-4">

    <!-- Statut et dates -->
    <div class="card mb-3">
      <div class="card-header"><h5 class="m-0">Statut &amp; Dates</h5></div>
      <div class="card-body">
        <div class="form-group">
          <label>Statut</label>
          <select name="status" class="form-control">
            <?php foreach ($statusOptions as $k => $lbl): ?>
            <option value="<?= $k ?>" <?= $v['status'] === $k ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Date d'émission</label>
          <input type="date" name="issued_at" class="form-control" value="<?= htmlspecialchars($v['issued_at'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Valable jusqu'au</label>
          <input type="date" name="valid_until" class="form-control" value="<?= htmlspecialchars($v['valid_until'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label>Date de paiement</label>
          <input type="date" name="paid_at" class="form-control" value="<?= htmlspecialchars($v['paid_at'] ?? '') ?>">
        </div>
      </div>
    </div>

    <!-- Actions -->
    <div class="card mb-3">
      <div class="card-body">
        <button type="submit" class="btn btn-primary btn-block">
          <i class="fas fa-save mr-1"></i>Enregistrer
        </button>
        <?php if (!$isNew): ?>
        <a href="crm-devis-print.php?id=<?= $id ?>" target="_blank" class="btn btn-outline-secondary btn-block mt-2">
          <i class="fas fa-print mr-1"></i>Aperçu / Imprimer
        </a>
        <?php endif; ?>
      </div>
    </div>

    <?php if (!$isNew): ?>
    <div class="card">
      <div class="card-body">
        <small class="text-muted">
          Réf. : <strong><?= htmlspecialchars($devis['ref']) ?></strong><br>
          Créé le : <?= date('d/m/Y H:i', strtotime($devis['created_at'])) ?>
        </small>
      </div>
    </div>
    <?php endif; ?>
  </div>
</div>

</form>

<!-- Template for new line (hidden) -->
<template id="lineTemplate">
  <tr class="line-row">
    <td><input type="text" name="line_desc[]" class="form-control form-control-sm" placeholder="Description de la prestation" required></td>
    <td><input type="number" name="line_qty[]" class="form-control form-control-sm line-qty" value="1" step="0.01" min="0"></td>
    <td><input type="text" name="line_unit[]" class="form-control form-control-sm" placeholder="m², h, u…"></td>
    <td><input type="number" name="line_price[]" class="form-control form-control-sm line-price" value="0" step="0.01" min="0"></td>
    <td class="text-right align-middle"><span class="line-total">0,00</span></td>
    <td class="align-middle"><button type="button" class="btn btn-xs btn-outline-danger remove-line"><i class="fas fa-times"></i></button></td>
  </tr>
</template>

<script>
(function(){
  const tbody  = document.getElementById('linesBody');
  const tpl    = document.getElementById('lineTemplate');
  const sumHt  = document.getElementById('sumHt');
  const sumTva = document.getElementById('sumTva');
  const sumTtc = document.getElementById('sumTtc');
  const tvaIn  = document.getElementById('tvaRate');

  function fmt(n){ return n.toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,' '); }

  function recalc(){
    let ht = 0;
    tbody.querySelectorAll('.line-row').forEach(function(row){
      const qty   = parseFloat(row.querySelector('.line-qty').value)   || 0;
      const price = parseFloat(row.querySelector('.line-price').value) || 0;
      const tot   = qty * price;
      ht += tot;
      row.querySelector('.line-total').textContent = fmt(tot);
    });
    const tva = parseFloat(tvaIn.value) || 0;
    const ttc = ht * (1 + tva / 100);
    sumHt.textContent  = fmt(ht);
    sumTva.textContent = fmt(ttc - ht);
    sumTtc.textContent = fmt(ttc);
  }

  /* Delegation for qty/price changes */
  tbody.addEventListener('input', function(e){
    if(e.target.matches('.line-qty,.line-price')) recalc();
  });
  tvaIn.addEventListener('input', recalc);

  /* Add line */
  document.getElementById('addLine').addEventListener('click', function(){
    const clone = tpl.content.cloneNode(true);
    tbody.appendChild(clone);
    recalc();
  });

  /* Remove line */
  tbody.addEventListener('click', function(e){
    const btn = e.target.closest('.remove-line');
    if(!btn) return;
    const rows = tbody.querySelectorAll('.line-row');
    if(rows.length <= 1){ alert('Un devis doit avoir au moins une ligne.'); return; }
    btn.closest('tr').remove();
    recalc();
  });

  /* Initial calc */
  recalc();
})();
</script>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
