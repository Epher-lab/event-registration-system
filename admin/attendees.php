<?php
require_once __DIR__ . '/../includes/config.php';
requireLogin();
if (!isOrganizer()) { header('Location: ' . SITE_URL); exit; }
$pageTitle = 'Attendees — ' . SITE_NAME;
$db = getDB();

$search = trim($_GET['search'] ?? '');
$where = ''; $params = [];
if ($search) {
    $where = "WHERE a.first_name LIKE :s OR a.last_name LIKE :s2 OR a.email LIKE :s3";
    $params = [':s'=>"%$search%",':s2'=>"%$search%",':s3'=>"%$search%"];
}

$stmt = $db->prepare("
    SELECT a.*, COUNT(r.id) as booking_count, COALESCE(SUM(r.total_amount),0) as total_spent
    FROM attendees a
    LEFT JOIN registrations r ON r.attendee_id = a.id AND r.status='confirmed'
    $where
    GROUP BY a.id ORDER BY a.created_at DESC
");
$stmt->execute($params);
$attendees = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem;flex-wrap:wrap;gap:1rem;">
    <h1>👥 Attendees</h1>
    <a href="index.php" class="btn btn-secondary">← Admin Home</a>
  </div>
  <form method="GET" style="margin-bottom:1.5rem;display:flex;gap:0.75rem;max-width:400px;">
    <input type="text" name="search" class="form-control" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn btn-primary">Search</button>
    <?php if ($search): ?><a href="attendees.php" class="btn btn-secondary">Clear</a><?php endif; ?>
  </form>
  <div class="card">
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Bookings</th><th>Spent</th><th>Joined</th></tr></thead>
        <tbody>
          <?php foreach ($attendees as $a): ?>
          <tr>
            <td><strong><?= htmlspecialchars($a['first_name'].' '.$a['last_name']) ?></strong></td>
            <td style="color:var(--muted);font-size:0.85rem"><?= htmlspecialchars($a['email']) ?></td>
            <td style="color:var(--muted);font-size:0.85rem"><?= htmlspecialchars($a['phone'] ?? '—') ?></td>
            <td>
              <?php $rc = ['admin'=>'badge-purple','organizer'=>'badge-orange','attendee'=>'badge-green']; ?>
              <span class="badge <?= $rc[$a['role']] ?? 'badge-green' ?>"><?= $a['role'] ?></span>
            </td>
            <td><?= $a['booking_count'] ?></td>
            <td><?= formatPrice($a['total_spent']) ?></td>
            <td style="color:var(--muted);font-size:0.85rem"><?= date('M j, Y', strtotime($a['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
