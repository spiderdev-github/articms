<?php
require_once __DIR__ . '/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/settings.php';
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: crm-devis.php'); exit; }

$pdo = getPDO();

$st = $pdo->prepare("
    SELECT d.*,
        cl.name AS client_name, cl.company AS client_company,
        cl.email AS client_email, cl.phone AS client_phone,
        cl.address AS client_address, cl.city AS client_city,
        cl.zip AS client_zip, cl.type AS client_type, cl.ref AS client_ref
    FROM crm_devis d
    LEFT JOIN crm_clients cl ON cl.id = d.client_id
    WHERE d.id = ?
");
$st->execute([$id]);
$d = $st->fetch(PDO::FETCH_ASSOC);
if (!$d) { die('Document introuvable.'); }

$st2 = $pdo->prepare("SELECT * FROM crm_devis_lines WHERE devis_id = ? ORDER BY sort_order");
$st2->execute([$id]);
$lines = $st2->fetchAll(PDO::FETCH_ASSOC);

$companyName  = getSetting('company_name',  defined('COMPANY_NAME')  ? COMPANY_NAME  : '');
$companyPhone = getSetting('company_phone', defined('PHONE_DISPLAY')  ? PHONE_DISPLAY : '');
$companyEmail = getSetting('company_email', defined('EMAIL')          ? EMAIL         : '');
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
    'draft'=>'#888888','sent'=>'#0984e3','accepted'=>'#00b894',
    'refused'=>'#d63031','invoiced'=>'#fdcb6e','paid'=>'#00b894',
];

// ── Build HTML ─────────────────────────────────────────────────────────────
ob_start();
?><!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
@page { margin: 22mm 20mm; size: A4 portrait; }
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1a1a1e; padding: 6mm 8mm; }
a { color: inherit; text-decoration: none; }

/* Header band */
.doc-header { display: table; width: 100%; border-bottom: 2px solid #d94f1e; padding-bottom: 20px; margin-bottom: 28px; }
.col-left  { display: table-cell; width: 60%; vertical-align: top; }
.col-right { display: table-cell; width: 40%; vertical-align: top; text-align: right; }
.company-name  { font-size: 18px; font-weight: bold; color: #1a2d5a; }
.company-tag   { font-size: 9px; color: #888; text-transform: uppercase; letter-spacing: .08em; margin-top: 2px; }
.company-infos { font-size: 10px; color: #444; line-height: 1.7; margin-top: 8px; }
.doc-type  { font-size: 24px; font-weight: bold; color: #d94f1e; text-transform: uppercase; }
.doc-ref   { font-size: 11px; font-weight: bold; color: #1a2d5a; display: block; margin-top: 4px; }
.doc-status{ display: inline-block; margin-top: 5px; padding: 2px 9px; border-radius: 100px;
             font-size: 9px; font-weight: bold; color: #fff;
             background: <?= $statusColors[$d['status']] ?? '#888' ?>; }

/* Parties */
.parties { display: table; width: 100%; margin-bottom: 20px; border-spacing: 12px 0; }
.party-cell { display: table-cell; width: 50%; background: #fafafa; border: 1px solid #eee;
              border-radius: 4px; padding: 12px; vertical-align: top; }
.party-label { font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: .1em; color: #888; margin-bottom: 6px; }
.party-name  { font-size: 13px; font-weight: bold; color: #1a1a1e; }
.party-detail{ font-size: 10px; color: #555; line-height: 1.7; margin-top: 3px; }

/* Dates */
.dates-row { display: table; width: 100%; margin-bottom: 20px; }
.date-cell { display: table-cell; padding: 8px 14px; background: #fafafa; border: 1px solid #eee;
             border-radius: 4px; margin-right: 10px; font-size: 11px; }
.date-cell strong { display: block; font-size: 9px; font-weight: bold; text-transform: uppercase;
                    letter-spacing: .06em; color: #888; margin-bottom: 2px; }
.date-gap { display: table-cell; width: 12px; }

/* Title / intro */
.doc-title { font-size: 14px; font-weight: bold; color: #1a2d5a; margin-bottom: 4px; }
.doc-intro { font-size: 11px; color: #555; line-height: 1.6; margin-bottom: 18px; }

/* Lines table */
table { width: 100%; border-collapse: collapse; margin-bottom: 0; }
thead tr { background: #1a2d5a; color: #fff; }
thead th { padding: 8px 10px; font-size: 9px; font-weight: bold; text-transform: uppercase; letter-spacing: .04em; }
thead th.right { text-align: right; }
thead th.center { text-align: center; }
tbody tr { border-bottom: 1px solid #eee; }
tbody tr.even { background: #fafafa; }
tbody td { padding: 8px 10px; font-size: 11px; vertical-align: top; }
.col-qty   { text-align: center; }
.col-unit  { text-align: center; color: #888; }
.col-price { text-align: right; }
.col-total { text-align: right; font-weight: bold; }

/* Totals */
.totals-wrap { text-align: right; margin-top: 14px; }
.totals-box  { display: inline-block; width: 230px; border: 1px solid #eee; border-radius: 4px; overflow: hidden; }
.totals-row  { display: table; width: 100%; padding: 7px 12px; border-top: 1px solid #eee; font-size: 12px; }
.totals-row:first-child { border-top: none; }
.t-label { display: table-cell; }
.t-value { display: table-cell; text-align: right; font-weight: bold; }
.totals-ttc { display: table; width: 100%; background: #1a2d5a; color: #fff;
              padding: 9px 12px; font-size: 13px; font-weight: bold; }

/* Footer note */
.footer-note { margin-top: 28px; padding-top: 12px; border-top: 1px solid #eee;
               font-size: 10px; color: #888; line-height: 1.7; }

/* Signature */
.sig-block { display: table; width: 100%; margin-top: 28px; }
.sig-cell  { display: table-cell; width: 50%; border: 1px solid #eee; border-radius: 4px;
             padding: 12px; height: 70px; vertical-align: top; }
.sig-gap   { display: table-cell; width: 24px; }
.sig-label { font-size: 8px; font-weight: bold; text-transform: uppercase;
             letter-spacing: .07em; color: #888; margin-bottom: 4px; }
</style>
</head>
<body>

<!-- En-tête -->
<div class="doc-header">
  <div class="col-left">
    <div class="company-name"><?= htmlspecialchars($companyName) ?></div>
    <div class="company-tag">Peinture &amp; Décoration</div>
    <div class="company-infos">
      <?php if ($companyAddr): ?><?= htmlspecialchars($companyAddr) ?><br><?php endif; ?>
      <?php if ($companyZip || $companyCity): ?><?= htmlspecialchars($companyZip) ?> <?= htmlspecialchars($companyCity) ?><br><?php endif; ?>
      <?php if ($companyPhone): ?>Tél : <?= htmlspecialchars($companyPhone) ?><br><?php endif; ?>
      <?php if ($companyEmail): ?><?= htmlspecialchars($companyEmail) ?><br><?php endif; ?>
      <?php if ($companySiret): ?>SIRET : <?= htmlspecialchars($companySiret) ?><?php endif; ?>
    </div>
  </div>
  <div class="col-right">
    <div class="doc-type"><?= $docLabel ?></div>
    <span class="doc-ref"><?= htmlspecialchars($d['ref']) ?></span>
    <div><span class="doc-status"><?= htmlspecialchars($statusLabels[$d['status']] ?? $d['status']) ?></span></div>
  </div>
</div>

<!-- Parties -->
<div class="parties">
  <div class="party-cell">
    <div class="party-label">Émetteur</div>
    <div class="party-name"><?= htmlspecialchars($companyName) ?></div>
    <div class="party-detail">
      <?php if ($companyAddr): ?><?= htmlspecialchars($companyAddr) ?><br><?php endif; ?>
      <?php if ($companyZip || $companyCity): ?><?= htmlspecialchars($companyZip) ?> <?= htmlspecialchars($companyCity) ?><br><?php endif; ?>
      <?php if ($companySiret): ?>SIRET : <?= htmlspecialchars($companySiret) ?><?php endif; ?>
    </div>
  </div>
  <div style="display:table-cell;width:16px;"></div>
  <div class="party-cell">
    <div class="party-label">Destinataire<?= $d['client_ref'] ? ' — ' . htmlspecialchars($d['client_ref']) : '' ?></div>
    <div class="party-name">
      <?= htmlspecialchars($d['client_name']) ?>
      <?php if ($d['client_company']): ?><br><span style="font-size:11px;font-weight:normal"><?= htmlspecialchars($d['client_company']) ?></span><?php endif; ?>
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
  <?php if (!$isFacture && $d['valid_until']): ?>
  <div class="date-gap"></div>
  <div class="date-cell">
    <strong>Valable jusqu'au</strong>
    <?= date('d/m/Y', strtotime($d['valid_until'])) ?>
  </div>
  <?php endif; ?>
  <?php if ($d['paid_at']): ?>
  <div class="date-gap"></div>
  <div class="date-cell" style="border-color:#00b894;">
    <strong>Payé le</strong>
    <?= date('d/m/Y', strtotime($d['paid_at'])) ?>
  </div>
  <?php endif; ?>
</div>

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
      <th style="width:50%">Description</th>
      <th class="center" style="width:8%">Qté</th>
      <th class="center" style="width:10%">Unité</th>
      <th class="right"  style="width:15%">P.U. HT</th>
      <th class="right"  style="width:15%">Total HT</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($lines as $i => $l): ?>
    <tr class="<?= $i % 2 === 1 ? 'even' : '' ?>">
      <td><?= nl2br(htmlspecialchars($l['description'])) ?></td>
      <td class="col-qty"><?= rtrim(rtrim(number_format((float)$l['qty'], 2, ',', ''), '0'), ',') ?></td>
      <td class="col-unit"><?= htmlspecialchars($l['unit'] ?? '') ?></td>
      <td class="col-price"><?= number_format((float)$l['unit_price'], 2, ',', ' ') ?> €</td>
      <td class="col-total"><?= number_format((float)$l['total'], 2, ',', ' ') ?> €</td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<!-- Totaux -->
<div class="totals-wrap">
  <div class="totals-box">
    <div class="totals-row">
      <span class="t-label">Total HT</span>
      <span class="t-value"><?= number_format((float)$d['total_ht'], 2, ',', ' ') ?> €</span>
    </div>
    <div class="totals-row">
      <span class="t-label">TVA (<?= rtrim(rtrim(number_format((float)$d['tva_rate'],2,',',''),'0'),',') ?> %)</span>
      <span class="t-value"><?= number_format((float)($d['total_ttc'] - $d['total_ht']), 2, ',', ' ') ?> €</span>
    </div>
    <div class="totals-ttc">
      <span>TOTAL TTC</span>
      <strong><?= number_format((float)$d['total_ttc'], 2, ',', ' ') ?> €</strong>
    </div>
  </div>
</div>

<?php if ($d['footer_note']): ?>
<div class="footer-note"><?= nl2br(htmlspecialchars($d['footer_note'])) ?></div>
<?php endif; ?>

<?php if (!$isFacture): ?>
<div class="sig-block">
  <div class="sig-cell">
    <div class="sig-label">Bon pour accord — Signature client</div>
  </div>
  <div class="sig-gap"></div>
  <div class="sig-cell">
    <div class="sig-label">Cachet &amp; signature entreprise</div>
  </div>
</div>
<?php endif; ?>

</body>
</html>
<?php
$html = ob_get_clean();

// ── Generate PDF ───────────────────────────────────────────────────────────
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Sans');
$options->set('fontHeightRatio', 1.0);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$filename = strtolower($docLabel) . '_' . preg_replace('/[^A-Za-z0-9\-]/', '-', $d['ref']) . '.pdf';

$dompdf->stream($filename, [
    'Attachment' => isset($_GET['dl']) ? 1 : 0,  // ?dl=1 → force download, sinon inline
]);
