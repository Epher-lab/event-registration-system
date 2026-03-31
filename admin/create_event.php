<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if (!isOrganizer()) { header('Location: ' . SITE_URL); exit; }
$pageTitle = 'Create Event — ' . SITE_NAME;
$db = getDB();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_event'])) {
    if (!verifyCsrf($_POST['csrf'] ?? '')) { $errors[] = 'Invalid token.'; }
    $title     = trim($_POST['title'] ?? '');
    $desc      = trim($_POST['description'] ?? '');
    $venue     = trim($_POST['venue'] ?? '');
    $evt_date  = $_POST['event_date'] ?? '';
    $end_date  = $_POST['end_date'] ?? '';

    if (!$title) $errors[] = 'Title required.';
    if (!$venue) $errors[] = 'Venue required.';
    if (!$evt_date) $errors[] = 'Event date required.';

    // Ticket types
    $tt_names  = $_POST['tt_name'] ?? [];
    $tt_prices = $_POST['tt_price'] ?? [];
    $tt_qtys   = $_POST['tt_qty'] ?? [];
    $tt_descs  = $_POST['tt_desc'] ?? [];

    if (empty(array_filter($tt_names))) $errors[] = 'At least one ticket type required.';

    if (empty($errors)) {
        $ins = $db->prepare("INSERT INTO events (title,description,venue,event_date,end_date,status,organizer_id) VALUES (?,?,?,?,?,?,?)");
        $ins->execute([$title,$desc,$venue,$evt_date,$end_date ?: null,'active',$_SESSION['attendee_id']]);
        $eid = $db->lastInsertId();
        foreach ($tt_names as $i => $name) {
            if (!trim($name)) continue;
            $tinit = $db->prepare("INSERT INTO ticket_types (event_id,name,description,price,quantity_total) VALUES (?,?,?,?,?)");
            $tinit->execute([$eid, trim($name), trim($tt_descs[$i] ?? ''), (float)($tt_prices[$i] ?? 0), max(1,(int)($tt_qtys[$i] ?? 0))]);
        }
        header('Location: index.php?created=1');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="container" style="max-width:800px;">
  <a href="index.php" style="color:var(--muted);font-size:0.85rem;">← Back to Admin</a>
  <h1 style="margin:1rem 0 2rem;">Create New Event</h1>
  <?php foreach ($errors as $e): ?><div class="alert alert-danger"><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
  <form method="POST">
    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-body">
        <h3 style="margin-bottom:1.25rem;">Event Details</h3>
        <div class="form-group">
          <label class="form-label">Event Title</label>
          <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
        </div>
        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="form-group">
          <label class="form-label">Venue</label>
          <input type="text" name="venue" class="form-control" value="<?= htmlspecialchars($_POST['venue'] ?? '') ?>" required>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
          <div class="form-group">
            <label class="form-label">Start Date & Time</label>
            <input type="datetime-local" name="event_date" class="form-control" value="<?= htmlspecialchars($_POST['event_date'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">End Date & Time (optional)</label>
            <input type="datetime-local" name="end_date" class="form-control" value="<?= htmlspecialchars($_POST['end_date'] ?? '') ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="card" style="margin-bottom:1.5rem;">
      <div class="card-body">
        <h3 style="margin-bottom:1.25rem;">Ticket Types</h3>
        <div id="ttContainer">
          <?php
          $prevNames = $_POST['tt_name'] ?? ['General','VIP'];
          $prevPrices = $_POST['tt_price'] ?? ['1000','5000'];
          $prevQtys = $_POST['tt_qty'] ?? ['200','50'];
          $prevDescs = $_POST['tt_desc'] ?? ['',''];
          for ($i = 0; $i < max(2, count($prevNames)); $i++):
          ?>
          <div class="tt-row" style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1rem;margin-bottom:0.75rem;">
            <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
              <div>
                <label class="form-label">Ticket Name</label>
                <input type="text" name="tt_name[]" class="form-control" value="<?= htmlspecialchars($prevNames[$i] ?? '') ?>">
              </div>
              <div>
                <label class="form-label">Price (KES)</label>
                <input type="number" name="tt_price[]" class="form-control" value="<?= htmlspecialchars($prevPrices[$i] ?? '0') ?>" min="0" step="0.01">
              </div>
              <div>
                <label class="form-label">Quantity</label>
                <input type="number" name="tt_qty[]" class="form-control" value="<?= htmlspecialchars($prevQtys[$i] ?? '100') ?>" min="1">
              </div>
            </div>
            <div>
              <label class="form-label">Description (optional)</label>
              <input type="text" name="tt_desc[]" class="form-control" value="<?= htmlspecialchars($prevDescs[$i] ?? '') ?>">
            </div>
          </div>
          <?php endfor; ?>
        </div>
        <button type="button" class="btn btn-secondary btn-sm" onclick="addTicketRow()">+ Add Ticket Type</button>
      </div>
    </div>
    <button type="submit" name="save_event" class="btn btn-primary" style="width:100%">🚀 Publish Event</button>
  </form>
</div>
<script>
function addTicketRow() {
  const html = `<div class="tt-row" style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1rem;margin-bottom:0.75rem;">
    <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:0.75rem;margin-bottom:0.75rem;">
      <div><label class="form-label">Ticket Name</label><input type="text" name="tt_name[]" class="form-control"></div>
      <div><label class="form-label">Price (KES)</label><input type="number" name="tt_price[]" class="form-control" value="0" min="0" step="0.01"></div>
      <div><label class="form-label">Quantity</label><input type="number" name="tt_qty[]" class="form-control" value="100" min="1"></div>
    </div>
    <div><label class="form-label">Description (optional)</label><input type="text" name="tt_desc[]" class="form-control"></div>
  </div>`;
  document.getElementById('ttContainer').insertAdjacentHTML('beforeend', html);
}
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>
