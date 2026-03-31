<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Create Account — ' . SITE_NAME;

if (isLoggedIn()) { header('Location: index.php'); exit; }

define('ORGANIZER_CODE', 'ORG2025');
define('ADMIN_CODE',     'ADMIN9999');

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) { $errors[] = 'Invalid CSRF token.'; }

    // Checkbox CAPTCHA check
    if (!verifyCaptcha($_POST['not_a_robot'] ?? '')) {
        $errors[] = 'Please confirm you are not a robot.';
    }

    $fname       = trim($_POST['first_name'] ?? '');
    $lname       = trim($_POST['last_name'] ?? '');
    $email       = trim($_POST['email'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $pass        = $_POST['password'] ?? '';
    $pass2       = $_POST['password2'] ?? '';
    $role        = $_POST['role'] ?? 'attendee';
    $access_code = trim($_POST['access_code'] ?? '');

    if (!$fname) $errors[] = 'First name is required.';
    if (!$lname) $errors[] = 'Last name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2) $errors[] = 'Passwords do not match.';

    if (!in_array($role, ['attendee', 'organizer', 'admin'])) {
        $role = 'attendee';
    }

    if ($role === 'organizer' && strtoupper($access_code) !== ORGANIZER_CODE) {
        $errors[] = 'Invalid organizer access code.';
    }
    if ($role === 'admin' && strtoupper($access_code) !== ADMIN_CODE) {
        $errors[] = 'Invalid admin access code.';
    }

    if (empty($errors)) {
        $db = getDB();
        $chk = $db->prepare("SELECT id FROM attendees WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins = $db->prepare("INSERT INTO attendees (first_name,last_name,email,phone,password_hash,role) VALUES (?,?,?,?,?,?)");
            $ins->execute([$fname, $lname, $email, $phone, $hash, $role]);
            $newId = $db->lastInsertId();
            $_SESSION['attendee_id'] = $newId;
            $_SESSION['first_name']  = $fname;
            $_SESSION['email']       = $email;
            $_SESSION['role']        = $role;

            if ($role === 'admin' || $role === 'organizer') {
                header('Location: ' . ADMIN_URL . '/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        }
    }
}

include 'includes/header.php';
?>

<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:2rem;">
  <div style="width:100%;max-width:520px;">
    <div style="text-align:center;margin-bottom:2rem;">
      <h1 style="font-size:2rem;margin-bottom:0.5rem;">Create Account</h1>
      <p style="color:var(--muted);">Join EventSys to book tickets and manage registrations</p>
    </div>

    <?php foreach ($errors as $e): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($e) ?></div>
    <?php endforeach; ?>

    <div class="card">
      <div class="card-body">
        <form method="POST" novalidate>
          <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
          <!-- Honeypot — hidden from humans, bots fill this in -->
          <input type="text" name="website" value="" style="display:none;" tabindex="-1" autocomplete="off">

          <!-- ROLE SELECTOR -->
          <div class="form-group">
            <label class="form-label">I am registering as a...</label>
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:0.75rem;margin-top:0.5rem;">

              <label style="cursor:pointer;">
                <input type="radio" name="role" value="attendee"
                  <?= (($_POST['role'] ?? 'attendee') === 'attendee') ? 'checked' : '' ?>
                  style="display:none;" onchange="toggleAccessCode()">
                <div class="role-card">
                  <div style="font-size:1.75rem;margin-bottom:0.4rem;">🎟</div>
                  <div style="font-weight:600;font-size:0.9rem;">Attendee</div>
                  <div style="font-size:0.72rem;color:var(--muted);margin-top:0.25rem;">Buy & attend events</div>
                </div>
              </label>

              <label style="cursor:pointer;">
                <input type="radio" name="role" value="organizer"
                  <?= (($_POST['role'] ?? '') === 'organizer') ? 'checked' : '' ?>
                  style="display:none;" onchange="toggleAccessCode()">
                <div class="role-card">
                  <div style="font-size:1.75rem;margin-bottom:0.4rem;">🎪</div>
                  <div style="font-weight:600;font-size:0.9rem;">Organizer</div>
                  <div style="font-size:0.72rem;color:var(--muted);margin-top:0.25rem;">Create & manage events</div>
                </div>
              </label>

              <label style="cursor:pointer;">
                <input type="radio" name="role" value="admin"
                  <?= (($_POST['role'] ?? '') === 'admin') ? 'checked' : '' ?>
                  style="display:none;" onchange="toggleAccessCode()">
                <div class="role-card">
                  <div style="font-size:1.75rem;margin-bottom:0.4rem;">🛡</div>
                  <div style="font-weight:600;font-size:0.9rem;">Admin</div>
                  <div style="font-size:0.72rem;color:var(--muted);margin-top:0.25rem;">Full system access</div>
                </div>
              </label>

            </div>
          </div>

          <!-- ACCESS CODE -->
          <div id="accessCodeSection" style="display:none;margin-bottom:1.25rem;">
            <div style="background:rgba(124,58,237,0.08);border:1px solid rgba(124,58,237,0.3);border-radius:10px;padding:1rem;">
              <label class="form-label" style="color:#a78bfa;">🔑 Access Code Required</label>
              <input type="text" name="access_code" id="accessCodeInput" class="form-control"
                placeholder="Enter your access code..."
                value="<?= htmlspecialchars($_POST['access_code'] ?? '') ?>"
                style="letter-spacing:0.12em;text-transform:uppercase;">
              <small style="color:var(--muted);margin-top:0.5rem;display:block;">
                Contact your system administrator to get an access code.
              </small>
            </div>
          </div>

          <!-- PERSONAL DETAILS -->
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" class="form-control"
                value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-control"
                value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>

          <div class="form-group">
            <label class="form-label">Phone (optional)</label>
            <input type="tel" name="phone" class="form-control"
              value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+254...">
          </div>

          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required minlength="8">
          </div>

          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="password2" class="form-control" required>
          </div>

          <!-- CAPTCHA — simple checkbox, no math -->
          <div class="form-group">
            <label style="display:flex;align-items:center;gap:0.75rem;background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1rem;cursor:pointer;">
              <input type="checkbox" name="not_a_robot" value="1" id="notRobot"
                style="width:20px;height:20px;accent-color:var(--accent);cursor:pointer;flex-shrink:0;"
                <?= isset($_POST['not_a_robot']) ? 'checked' : '' ?>>
              <span>
                <strong style="font-size:0.95rem;">🔐 I am not a robot</strong><br>
                <small style="color:var(--muted);">Please confirm you are a real person</small>
              </span>
            </label>
          </div>

          <button type="submit" class="btn btn-primary" style="width:100%;padding:0.75rem;font-size:1rem;">
            Create Account
          </button>
        </form>
      </div>
    </div>

    <!-- Demo access codes -->
    <div style="margin-top:1.25rem;background:rgba(245,158,11,0.07);border:1px solid rgba(245,158,11,0.25);border-radius:10px;padding:1rem;font-size:0.82rem;">
      <strong style="color:var(--gold);">📋 Demo Access Codes (for testing)</strong><br>
      <span style="color:var(--muted);">Organizer code: </span><strong style="color:var(--text);letter-spacing:0.08em;">ORG2025</strong><br>
      <span style="color:var(--muted);">Admin code: </span><strong style="color:var(--text);letter-spacing:0.08em;">ADMIN9999</strong>
    </div>

    <p style="text-align:center;margin-top:1.25rem;color:var(--muted);font-size:0.9rem;">
      Already have an account? <a href="login.php">Sign in →</a>
    </p>
  </div>
</div>

<style>
.role-card {
  background: var(--surface);
  border: 2px solid var(--border);
  border-radius: 12px;
  padding: 1rem 0.5rem;
  text-align: center;
  transition: all 0.2s;
  user-select: none;
}
.role-card:hover {
  border-color: var(--accent);
  background: rgba(255,107,53,0.05);
}
input[value="attendee"]:checked + .role-card {
  border-color: var(--accent);
  background: rgba(255,107,53,0.1);
  box-shadow: 0 0 0 3px rgba(255,107,53,0.15);
}
input[value="organizer"]:checked + .role-card {
  border-color: var(--gold);
  background: rgba(245,158,11,0.1);
  box-shadow: 0 0 0 3px rgba(245,158,11,0.15);
}
input[value="admin"]:checked + .role-card {
  border-color: #a78bfa;
  background: rgba(124,58,237,0.1);
  box-shadow: 0 0 0 3px rgba(124,58,237,0.15);
}
</style>

<script>
function toggleAccessCode() {
  const selected = document.querySelector('input[name="role"]:checked');
  const role = selected ? selected.value : 'attendee';
  const section = document.getElementById('accessCodeSection');
  const input   = document.getElementById('accessCodeInput');
  if (role === 'organizer' || role === 'admin') {
    section.style.display = 'block';
    input.required = true;
  } else {
    section.style.display = 'none';
    input.required = false;
    input.value = '';
  }
}
document.addEventListener('DOMContentLoaded', toggleAccessCode);
document.getElementById('accessCodeInput').addEventListener('input', function () {
  this.value = this.value.toUpperCase();
});
</script>

<?php include 'includes/footer.php'; ?>
