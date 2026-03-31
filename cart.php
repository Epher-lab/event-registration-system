<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Cart — ' . SITE_NAME;

$db = getDB();
$cart = getCart();
$error = $success = '';

// Remove item
if (isset($_GET['remove'])) {
    $ttId = (int)$_GET['remove'];
    unset($_SESSION['cart'][$ttId]);
    header('Location: cart.php');
    exit;
}

// Enrich cart with DB data
$cartItems = [];
$total = 0;
foreach ($cart as $ttId => $item) {
    $stmt = $db->prepare("SELECT tt.*, e.title as event_title, e.event_date, e.venue FROM ticket_types tt JOIN events e ON e.id = tt.event_id WHERE tt.id = ?");
    $stmt->execute([$ttId]);
    $tt = $stmt->fetch();
    if ($tt) {
        $tt['cart_quantity'] = $item['quantity'];
        $tt['line_total'] = $tt['price'] * $item['quantity'];
        $total += $tt['line_total'];
        $cartItems[] = $tt;
    }
}

// Checkout submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    requireLogin();
    if (!verifyCsrf($_POST['csrf'] ?? '')) { $error = 'Invalid request.'; }
    elseif (empty($cartItems)) { $error = 'Your cart is empty.'; }
    else {
        try {
            $db->beginTransaction();
            $bookingRefs = [];
            foreach ($cartItems as $item) {
                // Lock & check inventory
                $lock = $db->prepare("SELECT quantity_total, quantity_sold FROM ticket_types WHERE id = ? FOR UPDATE");
                $lock->execute([$item['id']]);
                $tt = $lock->fetch();
                $avail = $tt['quantity_total'] - $tt['quantity_sold'];
                if ($avail < $item['cart_quantity']) {
                    $db->rollBack();
                    $error = "Not enough tickets for \"{$item['name']}\" ({$item['event_title']}). Only $avail left.";
                    break;
                }
                // Create registration
                $ref = generateBookingRef();
                $ins = $db->prepare("INSERT INTO registrations (attendee_id,event_id,ticket_type_id,quantity,total_amount,status,booking_ref) VALUES (?,?,?,?,?,'pending',?)");
                $ins->execute([$_SESSION['attendee_id'], $item['event_id'], $item['id'], $item['cart_quantity'], $item['line_total'], $ref]);
                $regId = $db->lastInsertId();
                // Simulate payment
                $transRef = 'SIM-' . rand(100000, 999999);
                $pm = $db->prepare("INSERT INTO payments (registration_id,amount,payment_method,payment_status,transaction_ref,paid_at) VALUES (?,?,?,?,?,NOW())");
                $pm->execute([$regId, $item['line_total'], $_POST['payment_method'] ?? 'card', 'completed', $transRef]);
                // Update ticket count
                $upd = $db->prepare("UPDATE ticket_types SET quantity_sold = quantity_sold + ? WHERE id = ?");
                $upd->execute([$item['cart_quantity'], $item['id']]);
                // Confirm registration
                $conf = $db->prepare("UPDATE registrations SET status = 'confirmed' WHERE id = ?");
                $conf->execute([$regId]);
                $bookingRefs[] = $ref;
            }
            if (empty($error)) {
                $db->commit();
                clearCart();
                $refsStr = implode(', ', $bookingRefs);
                header('Location: dashboard.php?booked=' . urlencode($refsStr));
                exit;
            }
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Booking failed: ' . $e->getMessage();
        }
    }
}

include 'includes/header.php';
?>
<div class="container" style="max-width:900px;">
  <h1 style="margin-bottom:1.5rem;">🛒 Your Cart</h1>
  <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <?php if (empty($cartItems)): ?>
    <div style="text-align:center;padding:4rem;color:var(--muted);">
      <div style="font-size:3rem;margin-bottom:1rem;">🛒</div>
      <p>Your cart is empty.</p>
      <a href="index.php" class="btn btn-primary" style="margin-top:1rem;">Browse Events</a>
    </div>
  <?php else: ?>
  <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;">
    <!-- Cart items -->
    <div>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Event / Ticket</th><th>Qty</th><th>Price</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($cartItems as $item): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($item['event_title']) ?></strong><br>
                  <small style="color:var(--muted)"><?= htmlspecialchars($item['name']) ?> &middot; <?= date('M j', strtotime($item['event_date'])) ?> &middot; <?= htmlspecialchars($item['venue']) ?></small>
                </td>
                <td><?= $item['cart_quantity'] ?></td>
                <td style="color:var(--accent);font-family:'Syne',sans-serif;"><?= formatPrice($item['line_total']) ?></td>
                <td><a href="cart.php?remove=<?= $item['id'] ?>" class="btn btn-danger btn-sm">✕</a></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <!-- Order summary & payment -->
    <div>
      <div class="card">
        <div class="card-body">
          <h3 style="margin-bottom:1.25rem;">Order Summary</h3>
          <?php foreach ($cartItems as $item): ?>
          <div style="display:flex;justify-content:space-between;font-size:0.85rem;margin-bottom:0.5rem;">
            <span style="color:var(--muted)"><?= htmlspecialchars($item['name']) ?> × <?= $item['cart_quantity'] ?></span>
            <span><?= formatPrice($item['line_total']) ?></span>
          </div>
          <?php endforeach; ?>
          <div style="border-top:1px solid var(--border);margin:1rem 0;"></div>
          <div style="display:flex;justify-content:space-between;font-family:'Syne',sans-serif;font-size:1.2rem;font-weight:700;margin-bottom:1.5rem;">
            <span>Total</span>
            <span style="color:var(--accent)"><?= formatPrice($total) ?></span>
          </div>
          <?php if (!isLoggedIn()): ?>
            <a href="login.php?redirect=<?= urlencode('cart.php') ?>" class="btn btn-primary" style="width:100%">Login to Checkout</a>
          <?php else: ?>
          <form method="POST">
            <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
            <div class="form-group">
              <label class="form-label">Payment Method</label>
              <select name="payment_method" class="form-control">
                <option value="card">💳 Credit / Debit Card</option>
                <option value="mpesa">📱 M-Pesa</option>
                <option value="paypal">🅿 PayPal</option>
              </select>
            </div>
            <div style="background:rgba(16,185,129,0.08);border:1px solid rgba(16,185,129,0.2);border-radius:10px;padding:0.75rem;font-size:0.8rem;color:#34d399;margin-bottom:1rem;">
              🔒 This is a simulated payment. No real charges will occur.
            </div>
            <button type="submit" name="checkout" class="btn btn-success" style="width:100%">✅ Confirm & Pay <?= formatPrice($total) ?></button>
          </form>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
