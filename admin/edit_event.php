<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if (!isOrganizer()) { header('Location: ' . SITE_URL); exit; }
$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$stmt = $db->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$id]);
$event = $stmt->fetch();
if (!$event) { header('Location: index.php'); exit; }

$tt_stmt = $db->prepare("SELECT * FROM ticket_types WHERE event_id = ?");
$tt_stmt->execute([$id]);
$ticket_types = $tt_stmt->fetchAll();

$pageTitle = 'Edit Event — ' . SITE_NAME;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) { $errors[] = 'Invalid token.'; }
    $title = trim($_POST['title'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $evt_date = $_POST['event_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $status = $_POST['status'] ?? 'active';

    if (!$title) $errors[] = 'Title required.';

    if (empty($errors)) {
        $upd = $db->prepare("UPDATE events SET title=?,description=?,venue=?,event_date=?,end_date=?,status=? WHERE id=?");
        $upd->execute([$title,$desc,$venue,$evt_date,$end_date?:null,$status,$id]);
        // Update ticket types
        foreach ($ticket_types as $tt) {
            $i = $tt['id'];
            if (isset($_POST["tt_name_$i"])) {
                $ttu = $db->prepare("UPDATE ticket_types SET name=?,description=?,price=?,quantity_total=? WHERE id=?");
                $ttu->execute([
                    trim($_POST["tt_name_$i"]),
                    trim($_POST["tt_desc_$i"] ?? ''),
                    (float)$_POST["tt_price_$i"],
                    max((int)$_POST["tt_qty_$i"], $tt['quantity_sold']),
                    $i
                ]);
            }
        }
        header('Location: index.php?updated=1');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="container" style="max-width:800px;">
  <a href="index.php" style="color:var(--muted);font-size:0.85rem;">← Back to Admin</a>
  <h1 style="margin:1rem 0 2rem;">Edit Event</h1>
  <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-body">
        <h3 style="margin-bottom:1.25rem;">Event Details</h3>
        <div class="form-group">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($event['title']) ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control"><?= htmlspecialchars($event['description']) ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Venue</label>
          <input type="text" name="venue" class="form-control" value="<?= htmlspecialchars($event['venue']) ?>">
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label class="form-label">Start Date & Time</label>
            <input type="datetime-local" name="event_date" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($event['event_date'])) ?>">
          </div>
          <div class="form-group">
            <label class="form-label">End Date & Time</label>
            <input type="datetime-local" name="end_date" class="form-control" value="<?= $event['end_date'] ? date('Y-m-d\TH:i', strtotime($event['end_date'])) : '' ?>">
          </div>
        </div>
        <div class="form-group">
          <label class="form-label">Status</label>
          <select name="status" class="form-control">
            <option value="active" <?= $event['status']==='active' ? 'selected' : '' ?>>Active</option>
            <option value="cancelled" <?= $event['status']==='cancelled' ? 'selected' : '' ?>>Cancelled</option>
            <option value="completed" <?= $event['status']==='completed' ? 'selected' : '' ?>>Completed</option>
          </select>
        </div>
      </div>
    </div>
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-body">
        <h3 style="margin-bottom:1.25rem;">Ticket Types</h3>
        <?php foreach ($ticket_types as $tt): ?>
        <div style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1rem;margin-bottom:0.75rem;">
          <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
            <div>
              <label class="form-label">Ticket Name</label>
              <input type="text" name="tt_name_<?= $tt['id'] ?>" class="form-control" value="<?= htmlspecialchars($tt['name']) ?>">
            </div>
            <div>
              <label class="form-label">Price (KES)</label>
              <input type="number" name="tt_price_<?= $tt['id'] ?>" class="form-control" value="<?= $tt['price'] ?>" min="0" step="0.01">
            </div>
            <div>
              <label class="form-label">Total Qty (sold: <?= $tt['quantity_sold'] ?>)</label>
              <input type="number" name="tt_qty_<?= $tt['id'] ?>" class="form-control" value="<?= $tt['quantity_total'] ?>" min="<?= $tt['quantity_sold'] ?>">
            </div>
          </div>
          <div>
            <label class="form-label">Description</label>
            <input type="text" name="tt_desc_<?= $tt['id'] ?>" class="form-control" value="<?= htmlspecialchars($tt['description'] ?? '') ?>">
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%">💾 Save Changes</button>
  </form>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
