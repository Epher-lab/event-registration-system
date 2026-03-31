<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if (!isOrganizer()) { header('Location: ' . SITE_URL . '/index.php'); exit; }
$pageTitle = 'Admin Panel — ' . SITE_NAME;

$db = getDB();
$stats = $db->query("
    SELECT
        (SELECT COUNT(*) FROM events WHERE status='active') as active_events,
        (SELECT COUNT(*) FROM registrations WHERE status='confirmed') as confirmed_bookings,
        (SELECT COUNT(*) FROM attendees) as total_attendees,
        (SELECT COALESCE(SUM(amount),0) FROM payments WHERE payment_status='completed') as revenue
")->fetch();

$recentReg = $db->query("
    SELECT r.*, a.first_name, a.last_name, a.email, e.title as event_title, tt.name as ticket_name
    FROM registrations r
    JOIN attendees a ON a.id = r.attendee_id
    JOIN events e ON e.id = r.event_id
    JOIN ticket_types tt ON tt.id = r.ticket_type_id
    ORDER BY r.registered_at DESC LIMIT 10
")->fetchAll();

$events = $db->query("
    SELECT e.*, SUM(tt.quantity_sold) as sold, SUM(tt.quantity_total) as total_qty
    FROM events e LEFT JOIN ticket_types tt ON tt.event_id = e.id
    GROUP BY e.id ORDER BY e.event_date DESC
")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
    <div>
      <h1 style="margin-bottom:0.25rem;">Admin Panel</h1>
      <p style="color:var(--muted);">Manage events, tickets & attendees</p>
    </div>
    <div style="display:flex;gap:0.75rem;">
      <a href="create_event.php" class="btn btn-primary">+ New Event</a>
      <a href="attendees.php" class="btn btn-secondary">👥 Attendees</a>
    </div>
  </div>

  <!-- Stats -->
  <div class="stats-grid" style="margin-bottom:2.5rem;">
    <div class="stat-card">
      <div class="stat-value" style="color:var(--accent)"><?= $stats['active_events'] ?></div>
      <div class="stat-label">Active Events</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:var(--success)"><?= $stats['confirmed_bookings'] ?></div>
      <div class="stat-label">Confirmed Bookings</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:var(--gold)"><?= $stats['total_attendees'] ?></div>
      <div class="stat-label">Registered Attendees</div>
    </div>
    <div class="stat-card">
      <div class="stat-value" style="color:var(--accent2);font-size:1.4rem;"><?= formatPrice($stats['revenue']) ?></div>
      <div class="stat-label">Total Revenue</div>
    </div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">
    <!-- Events -->
    <div>
      <h2 style="margin-bottom:1rem;">📅 Events</h2>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Event</th><th>Date</th><th>Sold</th><th>Status</th><th></th></tr></thead>
            <tbody>
              <?php foreach ($events as $e):
                $pct = $e['total_qty'] > 0 ? round($e['sold']/$e['total_qty']*100) : 0;
              ?>
              <tr>
                <td style="font-weight:500;"><?= htmlspecialchars(substr($e['title'],0,30)) ?></td>
                <td style="font-size:0.8rem;color:var(--muted)"><?= date('M j', strtotime($e['event_date'])) ?></td>
                <td>
                  <div class="progress" style="width:60px;margin-bottom:2px;"><div class="progress-bar" style="width:<?= $pct ?>%"></div></div>
                  <small style="color:var(--muted)"><?= $e['sold'] ?>/<?= $e['total_qty'] ?></small>
                </td>
                <td><span class="badge <?= $e['status']==='active' ? 'badge-green' : 'badge-red' ?>"><?= $e['status'] ?></span></td>
                <td>
                  <a href="edit_event.php?id=<?= $e['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Recent registrations -->
    <div>
      <h2 style="margin-bottom:1rem;">🎟 Recent Bookings</h2>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Attendee</th><th>Event</th><th>Ref</th><th>Status</th></tr></thead>
            <tbody>
              <?php foreach ($recentReg as $r): ?>
              <tr>
                <td>
                  <strong><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></strong><br>
                  <small style="color:var(--muted)"><?= htmlspecialchars($r['email']) ?></small>
                </td>
                <td style="font-size:0.85rem"><?= htmlspecialchars(substr($r['event_title'],0,25)) ?></td>
                <td style="font-family:'Syne',sans-serif;font-size:0.8rem;color:var(--accent)"><?= $r['booking_ref'] ?></td>
                <td><span class="badge <?= $r['status']==='confirmed'?'badge-green':($r['status']==='pending'?'badge-gold':'badge-red') ?>"><?= $r['status'] ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
