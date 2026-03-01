<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/auth.php';
requirePermission('contacts');

$pdo = getPDO();
$csrf = getCsrfToken();

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) { header("Location: contacts.php"); exit; }

$stmt = $pdo->prepare("SELECT * FROM contacts WHERE id = :id LIMIT 1");
$stmt->execute([':id'=>$id]);
$contact = $stmt->fetch();
if (!$contact) { header("Location: contacts.php"); exit; }

// tags
$tags = $pdo->prepare("
  SELECT t.id, t.name
  FROM tags t
  INNER JOIN contact_tags ct ON ct.tag_id = t.id
  WHERE ct.contact_id = :id
  ORDER BY t.name ASC
");
$tags->execute([':id'=>$id]);
$tags = $tags->fetchAll();

// notes
$notes = $pdo->prepare("
  SELECT n.*, a.username
  FROM contact_notes n
  INNER JOIN admins a ON a.id = n.admin_id
  WHERE n.contact_id = :id
  ORDER BY n.created_at DESC
");
$notes->execute([':id'=>$id]);
$notes = $notes->fetchAll();

$pipeline = $contact['pipeline_status'] ?? 'new';
$archived = !empty($contact['archived_at']);

include __DIR__ . '/partials/header.php';
?>

<div class="row">
  <div class="col-lg-8">

    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title"><i class="fas fa-user mr-1"></i> <?= htmlspecialchars($contact['name']) ?></h3>
        <span class="badge <?= $archived ? 'badge-secondary' : 'badge-info' ?>">
          <?= $archived ? 'archived' : 'active' ?>
        </span>
      </div>

      <div class="card-body text-sm">
        <div class="mb-2"><b>Date:</b> <?= htmlspecialchars($contact['created_at']) ?></div>
        <div class="mb-2"><b>Email:</b> <?= htmlspecialchars($contact['email']) ?></div>
        <div class="mb-2"><b>Phone:</b> <?= htmlspecialchars($contact['phone'] ?? '') ?></div>
        <div class="mb-2"><b>City:</b> <?= htmlspecialchars($contact['city'] ?? '') ?></div>
        <div class="mb-2"><b>Service:</b> <?= htmlspecialchars($contact['service'] ?? '') ?></div>
        <div class="mb-2"><b>Surface:</b> <?= htmlspecialchars($contact['surface'] ?? '') ?></div>
        <div class="mb-2"><b>Captcha score:</b> <?= htmlspecialchars((string)$contact['captcha_score']) ?></div>

        <hr>
        <div><b>Message</b></div>
        <div class="mt-2"><?= nl2br(htmlspecialchars($contact['message'])) ?></div>
      </div>

      <div class="card-footer d-flex flex-wrap" style="gap:8px;">
        <a class="btn btn-primary btn-sm" href="mailto:<?= htmlspecialchars($contact['email']) ?>">
          <i class="fas fa-paper-plane"></i> Email
        </a>
        <?php if (!empty($contact['phone'])): ?>
        <a class="btn btn-success btn-sm" href="tel:<?= htmlspecialchars($contact['phone']) ?>">
          <i class="fas fa-phone"></i> Call
        </a>
        <?php endif; ?>

        <?php if (can('crm')): ?>
          <?php
            $alreadyClient = $pdo->prepare("SELECT id FROM crm_clients WHERE contact_id = ?");
            $alreadyClient->execute([$id]);
            $existingClientId = $alreadyClient->fetchColumn();
          ?>
          <?php if ($existingClientId): ?>
            <a href="crm-client-edit.php?id=<?= $existingClientId ?>" class="btn btn-outline-info btn-sm">
              <i class="fas fa-user-check"></i> Voir le client
            </a>
          <?php else: ?>
            <a href="actions/crm-convert-contact?contact_id=<?= $id ?>" class="btn btn-outline-warning btn-sm">
              <i class="fas fa-user-plus"></i> Convertir en client
            </a>
          <?php endif; ?>
        <?php endif; ?>

        <?php if (!$archived): ?>
          <form method="POST" action="actions/archive.php" class="m-0">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$id ?>">
            <button class="btn btn-secondary btn-sm" type="submit">
              <i class="fas fa-box-archive"></i> Archive
            </button>
          </form>
        <?php else: ?>
          <form method="POST" action="actions/restore.php" class="m-0">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="id" value="<?= (int)$id ?>">
            <button class="btn btn-info btn-sm" type="submit">
              <i class="fas fa-rotate-left"></i> Restore
            </button>
          </form>
        <?php endif; ?>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-note-sticky mr-1"></i> Notes internes</h3></div>
      <div class="card-body">

        <form method="POST" action="actions/add-note.php" class="mb-3">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="contact_id" value="<?= (int)$id ?>">
          <textarea name="note" class="form-control" rows="3" placeholder="Ajouter une note..." required></textarea>
          <button class="btn btn-primary btn-sm mt-2" type="submit"><i class="fas fa-plus"></i> Ajouter</button>
        </form>

        <?php if (empty($notes)): ?>
          <div class="text-muted">Aucune note.</div>
        <?php else: ?>
          <?php foreach ($notes as $n): ?>
            <div class="border rounded p-2 mb-2">
              <div class="text-muted text-xs"><?= htmlspecialchars($n['created_at']) ?> - <?= htmlspecialchars($n['username']) ?></div>
              <div class="mt-1"><?= nl2br(htmlspecialchars($n['note'])) ?></div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </div>

  </div>

  <div class="col-lg-4">

    <div class="card">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-diagram-project mr-1"></i> Pipeline</h3></div>
      <div class="card-body">

        <form method="POST" action="actions/update-pipeline.php">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)$id ?>">

          <select class="form-control" name="pipeline_status">
            <option value="new" <?= $pipeline==='new'?'selected':'' ?>>new</option>
            <option value="in_progress" <?= $pipeline==='in_progress'?'selected':'' ?>>in_progress</option>
            <option value="quoted" <?= $pipeline==='quoted'?'selected':'' ?>>quoted</option>
            <option value="won" <?= $pipeline==='won'?'selected':'' ?>>won</option>
            <option value="lost" <?= $pipeline==='lost'?'selected':'' ?>>lost</option>
          </select>

          <button class="btn btn-success btn-sm mt-2" type="submit">
            <i class="fas fa-save"></i> Update
          </button>
        </form>

      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-tags mr-1"></i> Tags</h3></div>
      <div class="card-body">

        <form method="POST" action="actions/add-tag.php" class="mb-2">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="contact_id" value="<?= (int)$id ?>">
          <div class="input-group input-group-sm">
            <input type="text" name="tag" class="form-control" placeholder="ex: urgent" required>
            <div class="input-group-append">
              <button class="btn btn-primary" type="submit"><i class="fas fa-plus"></i></button>
            </div>
          </div>
        </form>

        <div class="mt-2">
          <?php if (empty($tags)): ?>
            <span class="text-muted text-sm">Aucun tag.</span>
          <?php else: ?>
            <?php foreach ($tags as $t): ?>
              <span class="badge badge-info mr-1">
                <?= htmlspecialchars($t['name']) ?>
              </span>
              <form method="POST" action="actions/remove-tag.php" style="display:inline;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                <input type="hidden" name="contact_id" value="<?= (int)$id ?>">
                <input type="hidden" name="tag_id" value="<?= (int)$t['id'] ?>">
                <button class="btn btn-link btn-xs p-0" type="submit" title="remove">
                  <i class="fas fa-times text-danger"></i>
                </button>
              </form>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

      </div>
    </div>

    <div class="card">
      <div class="card-header"><h3 class="card-title"><i class="fas fa-phone-volume mr-1"></i> Relances</h3></div>
      <div class="card-body text-sm">
        <div><b>Next:</b> <?= htmlspecialchars($contact['next_followup_at'] ?? '-') ?></div>
        <div><b>Count:</b> <?= (int)($contact['followup_count'] ?? 0) ?></div>

        <form method="POST" action="actions/followup-done.php" class="mt-2">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="id" value="<?= (int)$id ?>">
          <label class="text-muted">Planifier prochaine relance</label>
          <input type="datetime-local" name="next_followup_at" class="form-control form-control-sm"
            value="">
          <button class="btn btn-warning btn-sm mt-2" type="submit">
            <i class="fas fa-check"></i> Relance faite
          </button>
        </form>

      </div>
    </div>

  </div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>