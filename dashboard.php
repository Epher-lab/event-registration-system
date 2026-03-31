<?php
require_once __DIR__ . '/includes/config.php';
requireLogin();
$pageTitle = 'My Dashboard — ' . SITE_NAME;
$db = getDB();

$booked = $_GET['booked'] ?? '';

$stmt = $db->prepare("
    SELECT r.*, e.title as event_title, e.venue, e.event_date,
           tt.name as ticket_name, tt.price as ticket_price,
           p.payment_status, p.payment_method, p.transaction_ref
    FROM registrations r
    JOIN events e ON e.id = r.event_id
    JOIN ticket_types tt ON tt.id = r.ticket_type_id
    LEFT JOIN payments p ON p.registration_id = r.id
    WHERE r.attendee_id = ?
    ORDER BY r.registered_at DESC
");
$stmt->execute([$_SESSION['attendee_id']]);
$registrations = $stmt->fetchAll();

$upcoming = array_filter($registrations, fn($r) => strtotime($r['event_date']) >= time() && $r['status'] === 'confirmed');
$past = array_filter($registrations, fn($r) => strtotime($r['event_date']) < time());
$totalSpent = array_sum(array_column(array_filter($registrations, fn($r) => $r['status'] === 'confirmed'), 'total_amount'));

include 'includes/header.php';
?>
<div class="container">
  <?php if ($booked): ?>
  <div class="alert alert-success" style="margin-bottom:1.5rem;">
    🎉 Booking confirmed! Reference(s): <strong><?= htmlspecialchars($booked) ?></strong>
    <br><small>Check your tickets below.</small>
  </div>
  <?php endif; ?>

  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 style="margin-bottom:0.25rem;">My Dashboard</h1>
      <p style="color:var(--muted);">Welcome back, <?= htmlspecialchars($_SESSION['first_name']) ?>!</p>
    </div>
    <a href="index.php" class="btn btn-primary">Browse More Events</a>
  </div>

  <!-- Stats -->
  <div class="stats-grid" style="margin-bottom:2.5rem;">
    <div class="stat-card">
      <div class="stat-value" style="color:var(--accent)"><?= count($registrations) ?></div>
      <div class="stat-label">Total Bookings</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:var(--success)"><?= count($upcoming) ?></div>
      <div class="stat-label">Upcoming Events</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:var(--gold)"><?= formatPrice($totalSpent) ?></div>
      <div class="stat-label">Total Spent</div>
    </div>
  </div>

  <!-- Upcoming -->
  <h2 style="margin-bottom:1rem;">🎟 Upcoming Tickets</h2>
  <?php if (empty($upcoming)): ?>
    <div style="color:var(--muted);margin-bottom:2rem;">No upcoming events. <a href="index.php">Browse events →</a></div>
  <?php else: ?>
  <div class="events-grid" style="margin-bottom:2.5rem;">
    <?php foreach ($upcoming as $reg): ?>
    <div class="card">
      <div class="card-body">
        <div style="display:flex;justify-content:space-between;align-items:start;margin-bottom:0.75rem;">
          <h3 style="font-size:1rem;"><?= htmlspecialchars($reg['event_title']) ?></h3>
          <span class="badge badge-green"><?= ucfirst($reg['status']) ?></span>
        </div>
        <div style="color:var(--muted);font-size:0.85rem;margin-bottom:0.75rem;">
          <div>📅 <?= date('D, M j Y · g:i A', strtotime($reg['event_date'])) ?></div>
          <div>📍 <?= htmlspecialchars($reg['venue']) ?></div>
          <div>🎫 <?= htmlspecialchars($reg['ticket_name']) ?> × <?= $reg['quantity'] ?></div>
        </div>
        <div style="background:var(--surface);border-radius:10px;padding:0.75rem;margin-bottom:0.75rem;">
          <div style="font-size:0.75rem;color:var(--muted);margin-bottom:0.25rem;">Booking Reference</div>
          <div style="font-family:'Syne',sans-serif;font-weight:700;color:var(--accent);font-size:1.1rem;letter-spacing:0.05em;"><?= $reg['booking_ref'] ?></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:0.85rem;color:var(--muted);">
          <span>💳 <?= ucfirst($reg['payment_method'] ?? 'card') ?></span>
          <span style="color:var(--text);font-weight:600"><?= formatPrice($reg['total_amount']) ?></span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- All history -->
  <h2 style="margin-bottom:1rem;">📋 Booking History</h2>
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Booking Ref</th><th>Event</th><th>Ticket</th><th>Date</th><th>Amount</th><th>Status</th></tr></thead>
        <tbody>
          <?php if (empty($registrations)): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:2rem;">No bookings yet.</td></tr>
          <?php else: foreach ($registrations as $reg): ?>
          <tr>
            <td style="font-family:'Syne',sans-serif;font-weight:700;color:var(--accent);"><?= $reg['booking_ref'] ?></td>
            <td><?= htmlspecialchars($reg['event_title']) ?></td>
            <td><?= htmlspecialchars($reg['ticket_name']) ?> × <?= $reg['quantity'] ?></td>
            <td style="color:var(--muted);font-size:0.85rem;"><?= date('M j, Y', strtotime($reg['registered_at'])) ?></td>
            <td><?= formatPrice($reg['total_amount']) ?></td>
            <td>
              <?php $sc = ['confirmed'=>'badge-green','pending'=>'badge-gold','cancelled'=>'badge-red']; ?>
              <span class="badge <?= $sc[$reg['status']] ?? 'badge-orange' ?>"><?= ucfirst($reg['status']) ?></span>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
