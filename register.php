<?php
require_once __DIR__ . '/includes/config.php';
$pageTitle = 'Create Account — ' . SITE_NAME;

if (isLoggedIn()) { header('Location: index.php'); exit; }

$errors = [];
$captchaQ = generateCaptcha();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf'] ?? '')) { $errors[] = 'Invalid CSRF token.'; }
    if (!verifyCaptcha($_POST['captcha'] ?? '')) { $errors[] = 'Incorrect CAPTCHA answer.'; }

    $fname = trim($_POST['first_name'] ?? '');
    $lname = trim($_POST['last_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (!$fname) $errors[] = 'First name is required.';
    if (!$lname) $errors[] = 'Last name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (strlen($pass) < 8) $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2) $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $db = getDB();
        $chk = $db->prepare("SELECT id FROM attendees WHERE email = ?");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $ins = $db->prepare("INSERT INTO attendees (first_name,last_name,email,phone,password_hash) VALUES (?,?,?,?,?)");
            $ins->execute([$fname,$lname,$email,$phone,$hash]);
            $newId = $db->lastInsertId();
            $_SESSION['attendee_id'] = $newId;
            $_SESSION['first_name']  = $fname;
            $_SESSION['email']       = $email;
            $_SESSION['role']        = 'attendee';
            header('Location: index.php');
            exit;
        }
    }
    $captchaQ = generateCaptcha(); // regenerate on fail
}

include 'includes/header.php';
?>

<div style="min-height:80vh;display:flex;align-items:center;justify-content:center;padding:2rem;">
  <div style="width:100%;max-width:480px;">
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
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
            <div class="form-group">
              <label class="form-label">First Name</label>
              <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
              <label class="form-label">Last Name</label>
              <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label class="form-label">Phone (optional)</label>
            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+254...">
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" required minlength="8">
          </div>
          <div class="form-group">
            <label class="form-label">Confirm Password</label>
            <input type="password" name="password2" class="form-control" required>
          </div>
          <!-- CAPTCHA -->
          <div class="form-group" style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:1rem;">
            <label class="form-label">🔐 CAPTCHA — What is <strong style="color:var(--accent)"><?= $captchaQ ?></strong>?</label>
            <input type="number" name="captcha" class="form-control" placeholder="Your answer" required>
            <small style="color:var(--muted)">Please solve the math problem above to verify you're human.</small>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;margin-top:0.5rem;">Create Account</button>
        </form>
      </div>
    </div>
    <p style="text-align:center;margin-top:1.25rem;color:var(--muted);font-size:0.9rem;">
      Already have an account? <a href="login.php">Sign in →</a>
    </p>
  </div>
</div>

<?php include 'includes/footer.php'; ?>
