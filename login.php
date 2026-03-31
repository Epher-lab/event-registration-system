<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Login — ' . SITE_NAME;

if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) { $error = 'Invalid request.'; }
    else {
        $email = trim($_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM attendees WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($pass, $user['password_hash'])) {
            $_SESSION['attendee_id'] = $user['id'];
            $_SESSION['first_name']  = $user['first_name'];
            $_SESSION['email']       = $user['email'];
            $_SESSION['role']        = $user['role'];
            $redirect = $_GET['redirect'] ?? 'index.php';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}

include 'includes/header.php';
?>
<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:2rem;">
  <div style="width:100%;max-width:420px;">
    <div style="text-align:center;margin-bottom:2rem;">
      <h1 style="font-size:2rem;margin-bottom:0.5rem;">Welcome Back</h1>
      <p style="color:var(--muted);">Sign in to your EventSys account</p>
    </div>
    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <div class="card">
      <div class="card-body">
        <form method="POST">
          <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" required autofocus>
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%">Sign In</button>
        </form>
      </div>
    </div>
    <div style="margin-top:1.25rem;background:rgba(124,58,237,0.1);border:1px solid rgba(124,58,237,0.3);border-radius:10px;padding:1rem;font-size:0.85rem;">
      <strong style="color:#a78bfa;">Demo Accounts</strong><br>
      <span style="color:var(--muted);">Admin: admin@eventsys.com / password</span><br>
      <span style="color:var(--muted);">Organizer: organizer@eventsys.com / password</span><br>
      <span style="color:var(--muted);">Attendee: john@example.com / password</span>
    </div>
    <p style="text-align:center;margin-top:1.25rem;color:var(--muted);font-size:0.9rem;">
      New here? <a href="register.php">Create an account →</a>
    </p>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
