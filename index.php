<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Events — ' . SITE_NAME;

$db = getDB();
$search = trim($_GET['search'] ?? '');
$filter = $_GET['filter'] ?? 'upcoming';

$where = "WHERE e.status = 'active'";
$params = [];

if ($search) {
    $where .= " AND (e.title LIKE :s OR e.venue LIKE :s2 OR e.description LIKE :s3)";
    $params[':s'] = $params[':s2'] = $params[':s3'] = "%$search%";
}
if ($filter === 'upcoming') {
    $where .= " AND e.event_date >= NOW()";
} elseif ($filter === 'past') {
    $where .= " AND e.event_date < NOW()";
}

$stmt = $db->prepare("
    SELECT e.*,
        COUNT(DISTINCT tt.id) as ticket_type_count,
        SUM(tt.quantity_total) as total_tickets,
        SUM(tt.quantity_sold) as sold_tickets,
        MIN(tt.price) as min_price
    FROM events e
    LEFT JOIN ticket_types tt ON tt.event_id = e.id
    $where
    GROUP BY e.id
    ORDER BY e.event_date ASC
");
$stmt->execute($params);
$events = $stmt->fetchAll();

include 'includes/header.php';
?>

<!-- HERO -->
<div style="background: linear-gradient(135deg, #0a0a0f 0%, #1a0a2e 50%, #0a1020 100%); padding: 5rem 1.5rem 4rem; text-align: center; position:relative; overflow:hidden; border-bottom:1px solid var(--border);">
  <div style="position:absolute;inset:0;background:radial-gradient(ellipse at 60% 40%, rgba(255,107,53,0.08) 0%, transparent 70%);pointer-events:none;"></div>
  <div style="max-width:700px;margin:0 auto;position:relative;">
    <div style="display:inline-block;background:rgba(255,107,53,0.1);border:1px solid rgba(255,107,53,0.3);border-radius:20px;padding:0.3rem 1rem;font-size:0.8rem;color:var(--accent);margin-bottom:1.5rem;letter-spacing:0.1em;text-transform:uppercase;">
      🎟 Your Ticket to Every Experience
    </div>
    <h1 style="font-size:3.5rem;line-height:1.1;margin-bottom:1rem;">
      Discover & Book<br><span style="color:var(--accent)">Unforgettable Events</span>
    </h1>
    <p style="color:var(--muted);font-size:1.1rem;margin-bottom:2rem;">Concerts, conferences, retreats, and more — all in one place.</p>
    <form method="GET" style="display:flex;gap:0.75rem;max-width:500px;margin:0 auto;">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search events, venues..." class="form-control" style="flex:1;">
      <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
      <button type="submit" class="btn btn-primary">Search</button>
    </form>
  </div>
</div>

<div class="container">
  <!-- Filter tabs -->
  <div style="display:flex;gap:0.5rem;margin-bottom:2rem;flex-wrap:wrap;">
    <?php foreach(['all' => 'All Events', 'upcoming' => 'Upcoming', 'past' => 'Past'] as $val => $label): ?>
      <a href="?filter=<?= $val ?>&search=<?= urlencode($search) ?>"
         class="btn <?= $filter === $val ? 'btn-primary' : 'btn-secondary' ?> btn-sm">
        <?= $label ?>
      </a>
    <?php endforeach; ?>
    <span style="margin-left:auto;color:var(--muted);font-size:0.9rem;align-self:center;"><?= count($events) ?> event(s) found</span>
  </div>

  <?php if (empty($events)): ?>
    <div style="text-align:center;padding:4rem;color:var(--muted);">
      <div style="font-size:3rem;margin-bottom:1rem;">🎪</div>
      <p>No events found. Try adjusting your filters.</p>
    </div>
  <?php else: ?>
  <div class="events-grid">
    <?php foreach ($events as $event):
      $pct = $event['total_tickets'] > 0 ? round($event['sold_tickets'] / $event['total_tickets'] * 100) : 0;
      $emojis = ['🎵','🎤','💻','🚀','🌿','🎭','🏆','🎨'];
      $emoji = $emojis[($event['id'] - 1) % count($emojis)];
    ?>
    <a href="event.php?id=<?= $event['id'] ?>" style="text-decoration:none;color:inherit;">
      <div class="event-card">
        <div class="event-img"><?= $emoji ?></div>
        <div class="event-card-body">
          <div class="event-meta">
            <span class="badge badge-orange">📅 <?= date('M j, Y', strtotime($event['event_date'])) ?></span>
            <?php if ($pct >= 90): ?>
              <span class="badge badge-red">🔥 Almost Sold Out</span>
            <?php elseif ($pct >= 60): ?>
              <span class="badge badge-gold">⚡ Selling Fast</span>
            <?php else: ?>
              <span class="badge badge-green">✅ Available</span>
            <?php endif; ?>
          </div>
          <h3 style="font-size:1.1rem;margin-bottom:0.4rem;"><?= htmlspecialchars($event['title']) ?></h3>
          <p style="color:var(--muted);font-size:0.85rem;margin-bottom:1rem;">📍 <?= htmlspecialchars($event['venue']) ?></p>
          <div class="progress" style="margin-bottom:0.4rem;">
            <div class="progress-bar" style="width:<?= $pct ?>%"></div>
          </div>
          <div style="display:flex;justify-content:space-between;font-size:0.8rem;color:var(--muted);margin-bottom:1rem;">
            <span><?= $event['sold_tickets'] ?>/<?= $event['total_tickets'] ?> sold</span>
            <span><?= $pct ?>% full</span>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:center;">
            <span style="font-size:0.85rem;color:var(--muted);">From</span>
            <span style="font-family:'Syne',sans-serif;font-size:1.1rem;font-weight:700;color:var(--accent);"><?= formatPrice($event['min_price'] ?? 0) ?></span>
          </div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
