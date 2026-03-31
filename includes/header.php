<?php
// includes/header.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle ?? SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
<style>
:root {
  --bg: #0a0a0f;
  --surface: #13131a;
  --card: #1a1a24;
  --border: #2a2a38;
  --accent: #ff6b35;
  --accent2: #7c3aed;
  --gold: #f59e0b;
  --text: #e8e8f0;
  --muted: #8888aa;
  --success: #10b981;
  --danger: #ef4444;
  --radius: 12px;
  --shadow: 0 8px 32px rgba(0,0,0,0.5);
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
  background: var(--bg);
  color: var(--text);
  font-family: 'DM Sans', sans-serif;
  font-size: 15px;
  line-height: 1.6;
  min-height: 100vh;
}
h1,h2,h3,h4,h5 { font-family: 'Syne', sans-serif; }
a { color: var(--accent); text-decoration: none; }
a:hover { opacity: 0.85; }

/* NAV */
nav {
  background: rgba(10,10,15,0.9);
  backdrop-filter: blur(16px);
  border-bottom: 1px solid var(--border);
  position: sticky; top: 0; z-index: 100;
  padding: 0 1.5rem;
}
.nav-inner {
  max-width: 1200px; margin: 0 auto;
  display: flex; align-items: center; gap: 2rem;
  height: 64px;
}
.nav-logo {
  font-family: 'Syne', sans-serif;
  font-size: 1.4rem; font-weight: 800;
  color: var(--text);
  display: flex; align-items: center; gap: 0.4rem;
}
.nav-logo span { color: var(--accent); }
.nav-links { display: flex; gap: 0.25rem; flex: 1; }
.nav-links a {
  color: var(--muted); padding: 0.4rem 0.9rem;
  border-radius: 8px; font-size: 0.9rem;
  transition: all 0.2s;
}
.nav-links a:hover, .nav-links a.active {
  color: var(--text); background: var(--card);
}
.nav-right { display: flex; align-items: center; gap: 0.75rem; margin-left: auto; }
.cart-btn {
  position: relative; background: var(--card);
  border: 1px solid var(--border); border-radius: 10px;
  padding: 0.4rem 0.9rem; color: var(--text);
  font-family: 'DM Sans', sans-serif; cursor: pointer;
  display: flex; align-items: center; gap: 0.4rem;
  font-size: 0.9rem; transition: border-color 0.2s;
}
.cart-btn:hover { border-color: var(--accent); }
.cart-count {
  background: var(--accent); color: white;
  border-radius: 50%; width: 18px; height: 18px;
  font-size: 0.7rem; display: flex; align-items: center; justify-content: center;
}
.btn {
  display: inline-flex; align-items: center; gap: 0.4rem;
  padding: 0.5rem 1.25rem; border-radius: 10px;
  font-family: 'DM Sans', sans-serif; font-weight: 500;
  font-size: 0.9rem; cursor: pointer; border: none;
  transition: all 0.2s; text-decoration: none;
}
.btn-primary { background: var(--accent); color: white; }
.btn-primary:hover { background: #ff8555; opacity: 1; }
.btn-secondary { background: var(--card); color: var(--text); border: 1px solid var(--border); }
.btn-secondary:hover { border-color: var(--accent); }
.btn-sm { padding: 0.35rem 0.85rem; font-size: 0.8rem; }
.btn-danger { background: var(--danger); color: white; }
.btn-success { background: var(--success); color: white; }

/* LAYOUT */
.container { max-width: 1200px; margin: 0 auto; padding: 2rem 1.5rem; }
.page-hero {
  background: linear-gradient(135deg, var(--surface), var(--card));
  border-bottom: 1px solid var(--border);
  padding: 3rem 1.5rem;
  text-align: center;
}
.page-hero h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }
.page-hero p { color: var(--muted); font-size: 1.1rem; }

/* CARDS */
.card {
  background: var(--card); border: 1px solid var(--border);
  border-radius: var(--radius); overflow: hidden;
}
.card-body { padding: 1.5rem; }

/* GRID */
.events-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
}
.event-card {
  background: var(--card); border: 1px solid var(--border);
  border-radius: var(--radius); overflow: hidden;
  transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
}
.event-card:hover {
  transform: translateY(-4px);
  border-color: var(--accent);
  box-shadow: 0 12px 40px rgba(255,107,53,0.15);
}
.event-img {
  width: 100%; height: 180px; object-fit: cover;
  background: linear-gradient(135deg, #1e1e2e, #2a1a3e);
  display: flex; align-items: center; justify-content: center;
  font-size: 3rem;
}
.event-card-body { padding: 1.25rem; }
.event-meta { display: flex; gap: 0.75rem; flex-wrap: wrap; margin: 0.75rem 0; }
.badge {
  padding: 0.2rem 0.65rem; border-radius: 20px; font-size: 0.75rem; font-weight: 500;
}
.badge-orange { background: rgba(255,107,53,0.15); color: var(--accent); }
.badge-purple { background: rgba(124,58,237,0.15); color: #a78bfa; }
.badge-gold { background: rgba(245,158,11,0.15); color: var(--gold); }
.badge-green { background: rgba(16,185,129,0.15); color: var(--success); }
.badge-red { background: rgba(239,68,68,0.15); color: var(--danger); }

/* FORMS */
.form-group { margin-bottom: 1.25rem; }
.form-label { display: block; margin-bottom: 0.4rem; font-weight: 500; font-size: 0.9rem; color: var(--muted); }
.form-control {
  width: 100%; padding: 0.65rem 1rem;
  background: var(--surface); border: 1px solid var(--border);
  border-radius: 10px; color: var(--text);
  font-family: 'DM Sans', sans-serif; font-size: 0.95rem;
  transition: border-color 0.2s;
}
.form-control:focus { outline: none; border-color: var(--accent); }
.form-control::placeholder { color: var(--muted); }
select.form-control option { background: var(--surface); }
textarea.form-control { resize: vertical; min-height: 100px; }

/* ALERTS */
.alert { padding: 1rem 1.25rem; border-radius: var(--radius); margin-bottom: 1rem; font-size: 0.9rem; }
.alert-success { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); color: #34d399; }
.alert-danger { background: rgba(239,68,68,0.1); border: 1px solid rgba(239,68,68,0.3); color: #f87171; }
.alert-info { background: rgba(124,58,237,0.1); border: 1px solid rgba(124,58,237,0.3); color: #a78bfa; }

/* TABLE */
.table-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; }
th { padding: 0.75rem 1rem; text-align: left; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted); border-bottom: 1px solid var(--border); }
td { padding: 0.85rem 1rem; border-bottom: 1px solid rgba(42,42,56,0.5); vertical-align: middle; font-size: 0.9rem; }
tr:last-child td { border-bottom: none; }
tr:hover td { background: rgba(255,107,53,0.03); }

/* STATS */
.stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem; }
.stat-card {
  background: var(--card); border: 1px solid var(--border);
  border-radius: var(--radius); padding: 1.25rem;
}
.stat-value { font-size: 2rem; font-weight: 800; font-family: 'Syne', sans-serif; }
.stat-label { color: var(--muted); font-size: 0.85rem; margin-top: 0.2rem; }

/* PROGRESS BAR */
.progress { background: var(--surface); border-radius: 4px; height: 6px; overflow: hidden; }
.progress-bar { height: 100%; background: var(--accent); border-radius: 4px; transition: width 0.3s; }

/* FOOTER */
footer {
  margin-top: 4rem; padding: 2rem 1.5rem;
  border-top: 1px solid var(--border);
  text-align: center; color: var(--muted); font-size: 0.85rem;
}

/* RESPONSIVE */
@media (max-width: 768px) {
  .nav-links { display: none; }
  .page-hero h1 { font-size: 1.75rem; }
  .events-grid { grid-template-columns: 1fr; }
}
</style>
</head>
<body>
<nav>
  <div class="nav-inner">
    <a class="nav-logo" href="<?= SITE_URL ?>/index.php">Event<span>Sys</span></a>
    <div class="nav-links">
      <a href="<?= SITE_URL ?>/index.php" <?= (basename($_SERVER['PHP_SELF']) === 'index.php') ? 'class="active"' : '' ?>>Events</a>
      <?php if (isLoggedIn()): ?>
        <a href="<?= SITE_URL ?>/dashboard.php" <?= (basename($_SERVER['PHP_SELF']) === 'dashboard.php') ? 'class="active"' : '' ?>>My Tickets</a>
        <?php if (isOrganizer()): ?>
          <a href="<?= ADMIN_URL ?>/index.php">Admin Panel</a>
        <?php endif; ?>
      <?php endif; ?>
    </div>
    <div class="nav-right">
      <?php
        $cartCount = array_sum(array_column(getCart(), 'quantity'));
      ?>
      <a href="<?= SITE_URL ?>/cart.php" class="cart-btn">
        🛒 Cart <?php if ($cartCount > 0): ?><span class="cart-count"><?= $cartCount ?></span><?php endif; ?>
      </a>
      <?php if (isLoggedIn()): ?>
        <span style="color:var(--muted);font-size:0.85rem">Hi, <?= htmlspecialchars($_SESSION['first_name']) ?></span>
        <a href="<?= SITE_URL ?>/logout.php" class="btn btn-secondary btn-sm">Logout</a>
      <?php else: ?>
        <a href="<?= SITE_URL ?>/login.php" class="btn btn-secondary btn-sm">Login</a>
        <a href="<?= SITE_URL ?>/register.php" class="btn btn-primary btn-sm">Register</a>
      <?php endif; ?>
    </div>
  </div>
</nav>
