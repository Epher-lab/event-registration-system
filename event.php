<?php
require_once __DIR__ . '/includes/config.php';

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT * FROM events WHERE id = ? AND status = 'active'");
$stmt->execute([$id]);
$event = $stmt->fetch();
if (!$event) { header('Location: index.php'); exit; }

$stmt2 = $db->prepare("SELECT * FROM ticket_types WHERE event_id = ? ORDER BY price ASC");
$stmt2->execute([$id]);
$ticket_types = $stmt2->fetchAll();

// Handle add to cart
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    $tt_id = (int)$_POST['ticket_type_id'];
    $qty   = (int)$_POST['quantity'];
    if ($qty < 1 || $qty > 10) { $error = 'Invalid quantity.'; }
    else {
        // Check availability
        $chk = $db->prepare("SELECT * FROM ticket_types WHERE id = ? AND event_id = ?");
        $chk->execute([$tt_id, $id]);
        $tt = $chk->fetch();
        if (!$tt) { $error = 'Invalid ticket type.'; }
        elseif (($tt['quantity_sold'] + $qty) > $tt['quantity_total']) {
            $error = 'Not enough tickets available.';
        } else {
            addToCart($tt_id, $id, $qty);
            $success = "Added to cart! <a href='cart.php' style='color:var(--accent)'>View cart →</a>";
        }
    }
}

$pageTitle = htmlspecialchars($event['title']) . ' — ' . SITE_NAME;
include 'includes/header.php';

$emojis = ['🎵','🎤','💻','🚀','🌿','🎭','🏆','🎨'];
$emoji = $emojis[($event['id'] - 1) % count($emojis)];
?>

<div style="background:linear-gradient(to bottom,var(--surface),var(--bg));border-bottom:1px solid var(--border);padding:3rem 1.5rem;">
  <div style="max-width:900px;margin:0 auto;display:grid;grid-template-columns:1fr 320px;gap:2rem;align-items:start;">
    <div>
      <a href="index.php" style="color:var(--muted);font-size:0.85rem;display:inline-flex;align-items:center;gap:0.3rem;margin-bottom:1.5rem;">← Back to Events</a>
      <div style="font-size:4rem;margin-bottom:1rem;"><?= $emoji ?></div>
      <h1 style="font-size:2.2rem;margin-bottom:0.75rem;"><?= htmlspecialchars($event['title']) ?></h1>
      <div style="display:flex;flex-wrap:wrap;gap:1rem;color:var(--muted);font-size:0.9rem;margin-bottom:1.5rem;">
        <span>📅 <?= date('D, M j Y \a\t g:i A', strtotime($event['event_date'])) ?></span>
        <span>📍 <?= htmlspecialchars($event['venue']) ?></span>
        <?php if ($event['end_date']): ?>
          <span>⏰ Ends <?= date('M j, g:i A', strtotime($event['end_date'])) ?></span>
        <?php endif; ?>
      </div>
      <p style="color:var(--muted);line-height:1.8;"><?= nl2br(htmlspecialchars($event['description'])) ?></p>
    </div>

    <!-- Ticket selector -->
    <div class="card" style="position:sticky;top:80px;">
      <div class="card-body">
        <h3 style="margin-bottom:1.25rem;">Select Tickets</h3>
        <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
        <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>

        <form method="POST">
          <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
          <div class="form-group">
            <label class="form-label">Ticket Type</label>
            <select name="ticket_type_id" class="form-control" id="ttSelect">
              <?php foreach ($ticket_types as $tt):
                $avail = $tt['quantity_total'] - $tt['quantity_sold'];
              ?>
              <option value="<?= $tt['id'] ?>" data-price="<?= $tt['price'] ?>" <?= $avail <= 0 ? 'disabled' : '' ?>>
                <?= htmlspecialchars($tt['name']) ?> — <?= formatPrice($tt['price']) ?>
                <?= $avail <= 0 ? '(Sold Out)' : "($avail left)" ?>
              </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Quantity</label>
            <input type="number" name="quantity" class="form-control" value="1" min="1" max="10" id="qtyInput">
          </div>
          <div style="background:var(--surface);border-radius:10px;padding:1rem;margin-bottom:1.25rem;">
            <div style="display:flex;justify-content:space-between;color:var(--muted);font-size:0.85rem;margin-bottom:0.4rem;">
              <span>Subtotal</span>
              <span id="subtotal">—</span>
            </div>
          </div>
          <button type="submit" name="add_to_cart" class="btn btn-primary" style="width:100%">🛒 Add to Cart</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Ticket types table -->
<div class="container" style="max-width:900px;">
  <h2 style="margin-bottom:1.25rem;">Ticket Options</h2>
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Type</th><th>Price</th><th>Available</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($ticket_types as $tt):
            $avail = $tt['quantity_total'] - $tt['quantity_sold'];
            $pct = round($tt['quantity_sold'] / max($tt['quantity_total'],1) * 100);
          ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($tt['name']) ?></strong><br>
              <small style="color:var(--muted)"><?= htmlspecialchars($tt['description'] ?? '') ?></small>
            </td>
            <td style="font-family:'Syne',sans-serif;color:var(--accent);"><?= formatPrice($tt['price']) ?></td>
            <td>
              <div class="progress" style="margin-bottom:4px;width:100px;">
                <div class="progress-bar" style="width:<?= $pct ?>%"></div>
              </div>
              <small style="color:var(--muted)"><?= $avail ?> / <?= $tt['quantity_total'] ?></small>
            </td>
            <td>
              <?php if ($avail <= 0): ?>
                <span class="badge badge-red">Sold Out</span>
              <?php elseif ($pct >= 80): ?>
                <span class="badge badge-gold">Almost Gone</span>
              <?php else: ?>
                <span class="badge badge-green">Available</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
const sel = document.getElementById('ttSelect');
const qty = document.getElementById('qtyInput');
const sub = document.getElementById('subtotal');
function update() {
  const opt = sel.options[sel.selectedIndex];
  const price = parseFloat(opt.dataset.price) || 0;
  const q = parseInt(qty.value) || 1;
  sub.textContent = 'KES ' + (price * q).toLocaleString('en-KE', {minimumFractionDigits:2});
}
sel.addEventListener('change', update);
qty.addEventListener('input', update);
update();
</script>

<?php include 'includes/footer.php'; ?>
