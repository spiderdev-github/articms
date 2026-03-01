<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
$pdo = getPDO();

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: crm-devis.php'); exit; }

$st = $pdo->prepare("SELECT d.*, cl.name AS client_name, cl.company AS client_company, cl.email AS client_email, cl.phone AS client_phone, cl.address AS client_address, cl.city AS client_city, cl.zip AS client_zip, cl.type AS client_type, cl.ref AS client_ref FROM crm_devis d LEFT JOIN crm_clients cl ON cl.id = d.client_id WHERE d.id = ?");
$st->execute([$id]);
$d = $st->fetch(PDO::FETCH_ASSOC);
if (!$d) { die('Document introuvable.'); }

$st2 = $pdo->prepare("SELECT * FROM crm_devis_lines WHERE devis_id = ? ORDER BY sort_order");
$st2->execute([$id]);
$lines = $st2->fetchAll(PDO::FETCH_ASSOC);

$companyName  = getSetting('company_name', COMPANY_NAME);
$companyPhone = getSetting('company_phone', PHONE_DISPLAY);
$companyEmail = getSetting('company_email', EMAIL);
$companyAddr  = getSetting('company_address', '');
$companyCity  = getSetting('company_city', '');
$companyZip   = getSetting('company_zip', '');
$companySiret = getSetting('company_siret', '');

$isFacture = ($d['type'] === 'facture');
$docLabel  = $isFacture ? 'FACTURE' : 'DEVIS';

$statusLabels = [
    'draft'=>'Brouillon','sent'=>'Envoyé','accepted'=>'Accepté',
    'refused'=>'Refusé','invoiced'=>'Facturé','paid'=>'Payé',
];
$statusColors = [
    'draft'=>'#888','sent'=>'#0984e3','accepted'=>'#00b894',
    'refused'=>'#d63031','invoiced'=>'#fdcb6e','paid'=>'#00b894',
];
?><!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= $docLabel ?> <?= htmlspecialchars($d['ref']) ?></title>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Inter',sans-serif;font-size:13px;color:#1a1a1e;background:#f0f0f0;padding:24px;}
    a{color:inherit;text-decoration:none;}

    .page{
      background:#fff;
      max-width:800px;
      margin:0 auto;
      padding:48px 48px 56px;
      box-shadow: 0 4px 40px rgba(0,0,0,.12);
      border-radius:4px;
    }

    /* Header band */
    .doc-header{
      display:flex;justify-content:space-between;align-items:flex-start;
      margin-bottom:40px;
      padding-bottom:28px;
      border-bottom:2px solid #d94f1e;
    }
    .company-block h1{font-size:22px;font-weight:800;color:#1a2d5a;letter-spacing:-.02em;}
    .company-block .tagline{font-size:11px;color:#888;margin-top:2px;text-transform:uppercase;letter-spacing:.06em;}
    .company-block .infos{margin-top:10px;font-size:12px;line-height:1.7;color:#444;}

    .doc-badge{text-align:right;}
    .doc-type{
      display:inline-block;
      font-size:26px;font-weight:900;
      letter-spacing:-.02em;
      color:#d94f1e;
      text-transform:uppercase;
    }
    .doc-ref{
      display:block;
      font-size:13px;font-weight:600;
      color:#1a2d5a;
      margin-top:4px;
    }
    .doc-status{
      display:inline-block;
      margin-top:6px;
      padding:3px 10px;
      border-radius:100px;
      font-size:11px;font-weight:600;
      color:#fff;
      background: <?= $statusColors[$d['status']] ?? '#888' ?>;
    }

    /* Parties */
    .parties{
      display:grid;grid-template-columns:1fr 1fr;gap:32px;
      margin-bottom:32px;
    }
    .party-box{
      background:#fafafa;
      border:1px solid #eee;
      border-radius:6px;
      padding:16px;
    }
    .party-box .party-label{
      font-size:10px;font-weight:700;
      text-transform:uppercase;letter-spacing:.1em;
      color:#888;margin-bottom:8px;
    }
    .party-box .party-name{font-size:15px;font-weight:700;color:#1a1a1e;}
    .party-box .party-detail{font-size:12px;color:#555;line-height:1.7;margin-top:4px;}

    /* Dates row */
    .dates-row{
      display:flex;gap:24px;
      margin-bottom:28px;
      font-size:12px;
    }
    .date-cell{background:#fafafa;border:1px solid #eee;border-radius:6px;padding:10px 16px;}
    .date-cell strong{display:block;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:#888;margin-bottom:2px;}

    /* Title / intro */
    .doc-title{font-size:16px;font-weight:700;color:#1a2d5a;margin-bottom:6px;}
    .doc-intro{font-size:12px;color:#555;line-height:1.65;margin-bottom:24px;}

    /* Lines table */
    table{width:100%;border-collapse:collapse;margin-bottom:0;}
    thead tr{background:#1a2d5a;color:#fff;}
    thead th{padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;}
    thead th:last-child{text-align:right;}
    tbody tr{border-bottom:1px solid #eee;}
    tbody tr:nth-child(even){background:#fafafa;}
    tbody td{padding:10px 12px;font-size:13px;vertical-align:top;}
    .col-desc{width:50%;}
    .col-qty{width:8%;text-align:center;}
    .col-unit{width:10%;text-align:center;color:#888;}
    .col-price{width:14%;text-align:right;}
    .col-total{width:14%;text-align:right;font-weight:600;}

    /* Totals */
    .totals{margin-top:0;display:flex;justify-content:flex-end;}
    .totals-box{
      width:260px;
      border:1px solid #eee;
      border-radius:6px;
      overflow:hidden;
      margin-top:16px;
    }
    .totals-row{display:flex;justify-content:space-between;align-items:center;padding:8px 14px;font-size:13px;}
    .totals-row+.totals-row{border-top:1px solid #eee;}
    .totals-ttc{background:#1a2d5a;color:#fff;padding:11px 14px;font-size:15px;font-weight:800;display:flex;justify-content:space-between;}

    /* Footer note */
    .footer-note{
      margin-top:36px;
      padding-top:16px;
      border-top:1px solid #eee;
      font-size:11px;color:#888;line-height:1.7;
    }

    /* Signature block */
    .sig-block{
      display:grid;grid-template-columns:1fr 1fr;gap:32px;
      margin-top:36px;
    }
    .sig-box{
      border:1px solid #eee;border-radius:6px;padding:16px;
      min-height:80px;
    }
    .sig-box .sig-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#888;margin-bottom:6px;}

    /* Print CTA */
    .print-bar{
      text-align:center;
      margin-bottom:20px;
    }
    .print-bar button, .print-bar a{
      padding:8px 20px;border-radius:6px;font-size:13px;font-weight:600;cursor:pointer;
      border:none;margin:0 4px;
    }
    .btn-print{background:#d94f1e;color:#fff;}
    .btn-back{background:#eee;color:#333;text-decoration:none;display:inline-block;}
    .btn-pdf{background:#1a2d5a;color:#fff;text-decoration:none;display:inline-block;}
    .btn-dl{background:#0056b3;}

    @media print{
      body{background:#fff;padding:0;}
      .page{box-shadow:none;border-radius:0;padding:20mm 18mm;}
      .print-bar{display:none;}
    }
  </style>
</head>
<body>

<div class="print-bar">
  <a class="btn-back" href="crm-devis-edit.php?id=<?= $id ?>"><i>←</i> Retour</a>
  <button class="btn-print" onclick="window.print()">🖨 Imprimer</button>
  <a class="btn-pdf" href="crm-devis-pdf.php?id=<?= $id ?>" target="_blank">📄 Voir PDF</a>
  <a class="btn-pdf btn-dl" href="crm-devis-pdf.php?id=<?= $id ?>&dl=1">⬇ Télécharger PDF</a>
</div>

<div class="page">

  <!-- En-tête -->
  <div class="doc-header">
    <div class="company-block">
      <h1><?= htmlspecialchars($companyName) ?></h1>
      <div class="tagline">Peinture &amp; Décoration</div>
      <div class="infos">
        <?php if ($companyAddr): ?><?= htmlspecialchars($companyAddr) ?><br><?php endif; ?>
        <?php if ($companyZip || $companyCity): ?><?= htmlspecialchars($companyZip) ?> <?= htmlspecialchars($companyCity) ?><br><?php endif; ?>
        <?php if ($companyPhone): ?>Tél : <?= htmlspecialchars($companyPhone) ?><br><?php endif; ?>
        <?php if ($companyEmail): ?><?= htmlspecialchars($companyEmail) ?><br><?php endif; ?>
        <?php if ($companySiret): ?>SIRET : <?= htmlspecialchars($companySiret) ?><?php endif; ?>
      </div>
    </div>
    <div class="doc-badge">
      <span class="doc-type"><?= $docLabel ?></span>
      <span class="doc-ref"><?= htmlspecialchars($d['ref']) ?></span>
      <span class="doc-status"><?= htmlspecialchars($statusLabels[$d['status']] ?? $d['status']) ?></span>
    </div>
  </div>

  <!-- Émetteur / Destinataire -->
  <div class="parties">
    <div class="party-box">
      <div class="party-label">Émetteur</div>
      <div class="party-name"><?= htmlspecialchars($companyName) ?></div>
      <div class="party-detail">
        <?php if ($companyAddr): ?><?= htmlspecialchars($companyAddr) ?><br><?php endif; ?>
        <?php if ($companyZip || $companyCity): ?><?= htmlspecialchars($companyZip) ?> <?= htmlspecialchars($companyCity) ?><br><?php endif; ?>
        <?php if ($companySiret): ?>SIRET : <?= htmlspecialchars($companySiret) ?><?php endif; ?>
      </div>
    </div>
    <div class="party-box">
      <div class="party-label">Destinataire — <?= $d['client_ref'] ? htmlspecialchars($d['client_ref']) : '' ?></div>
      <div class="party-name">
        <?= htmlspecialchars($d['client_name']) ?>
        <?php if ($d['client_company']): ?><br><span style="font-size:13px;font-weight:500"><?= htmlspecialchars($d['client_company']) ?></span><?php endif; ?>
      </div>
      <div class="party-detail">
        <?php if ($d['client_address']): ?><?= htmlspecialchars($d['client_address']) ?><br><?php endif; ?>
        <?php if ($d['client_zip'] || $d['client_city']): ?><?= htmlspecialchars($d['client_zip']) ?> <?= htmlspecialchars($d['client_city']) ?><br><?php endif; ?>
        <?php if ($d['client_phone']): ?>Tél : <?= htmlspecialchars($d['client_phone']) ?><br><?php endif; ?>
        <?php if ($d['client_email']): ?><?= htmlspecialchars($d['client_email']) ?><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Dates -->
  <div class="dates-row">
    <div class="date-cell">
      <strong>Date d'émission</strong>
      <?= $d['issued_at'] ? date('d/m/Y', strtotime($d['issued_at'])) : '—' ?>
    </div>
    <?php if (!$isFacture): ?>
    <div class="date-cell">
      <strong>Valable jusqu'au</strong>
      <?= $d['valid_until'] ? date('d/m/Y', strtotime($d['valid_until'])) : '—' ?>
    </div>
    <?php endif; ?>
    <?php if ($d['paid_at']): ?>
    <div class="date-cell" style="border-color:#00b894;">
      <strong>Payé le</strong>
      <?= date('d/m/Y', strtotime($d['paid_at'])) ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Titre / Intro -->
  <?php if ($d['title']): ?>
  <div class="doc-title"><?= htmlspecialchars($d['title']) ?></div>
  <?php endif; ?>
  <?php if ($d['intro']): ?>
  <div class="doc-intro"><?= nl2br(htmlspecialchars($d['intro'])) ?></div>
  <?php endif; ?>

  <!-- Lignes -->
  <table>
    <thead>
      <tr>
        <th class="col-desc">Description</th>
        <th class="col-qty">Qté</th>
        <th class="col-unit">Unité</th>
        <th class="col-price">P.U. HT</th>
        <th class="col-total">Total HT</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($lines as $l): ?>
      <tr>
        <td class="col-desc"><?= nl2br(htmlspecialchars($l['description'])) ?></td>
        <td class="col-qty"><?= rtrim(rtrim(number_format((float)$l['qty'], 2, ',', ''), '0'), ',') ?></td>
        <td class="col-unit"><?= htmlspecialchars($l['unit'] ?? '') ?></td>
        <td class="col-price"><?= number_format((float)$l['unit_price'], 2, ',', ' ') ?> €</td>
        <td class="col-total"><?= number_format((float)$l['total'], 2, ',', ' ') ?> €</td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- Totaux -->
  <div class="totals">
    <div class="totals-box">
      <div class="totals-row">
        <span>Total HT</span>
        <strong><?= number_format((float)$d['total_ht'], 2, ',', ' ') ?> €</strong>
      </div>
      <div class="totals-row">
        <span>TVA (<?= rtrim(rtrim(number_format((float)$d['tva_rate'],2,',',''),'0'),',') ?> %)</span>
        <span><?= number_format((float)($d['total_ttc'] - $d['total_ht']), 2, ',', ' ') ?> €</span>
      </div>
      <div class="totals-ttc">
        <span>TOTAL TTC</span>
        <strong><?= number_format((float)$d['total_ttc'], 2, ',', ' ') ?> €</strong>
      </div>
    </div>
  </div>

  <!-- Note de bas -->
  <?php if ($d['footer_note']): ?>
  <div class="footer-note"><?= nl2br(htmlspecialchars($d['footer_note'])) ?></div>
  <?php endif; ?>

  <!-- Signature -->
  <?php if (!$isFacture): ?>
  <div class="sig-block">
    <div class="sig-box">
      <div class="sig-label">Bon pour accord — Signature client</div>
    </div>
    <div class="sig-box">
      <div class="sig-label">Cachet &amp; signature entreprise</div>
    </div>
  </div>
  <?php endif; ?>

</div><!-- /.page -->
</body>
</html>
