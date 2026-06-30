 <?php
// ============================================================
//  register.php  –  New-user registration with CAPTCHA
// ============================================================
session_start();
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/captcha/captcha.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error   = '';
$success = '';

// ── Generate CAPTCHA for GET or after failure ────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !empty($error)) {
    $captchaQuestion = captcha_generate('reg');
}

// ── Handle POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $fullname  = trim($_POST['fullname']  ?? '');
    $email     = trim($_POST['email']     ?? '');
    $password  = $_POST['password']       ?? '';
    $password2 = $_POST['password2']      ?? '';
    $captchaIn = trim($_POST['captcha']   ?? '');

    // — Validate CAPTCHA first —
    if (!captcha_validate('reg', $captchaIn)) {
        $error = 'Incorrect CAPTCHA answer. Please try again.';
        $captchaQuestion = captcha_generate('reg');
    }
    // — Basic field validation —
    elseif (empty($fullname) || empty($email) || empty($password)) {
        $error = 'All fields are required.';
        $captchaQuestion = captcha_generate('reg');
    }
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
        $captchaQuestion = captcha_generate('reg');
    }
    elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
        $captchaQuestion = captcha_generate('reg');
    }
    elseif ($password !== $password2) {
        $error = 'Passwords do not match.';
        $captchaQuestion = captcha_generate('reg');
    }
    else {
        $db = getDB();

        // Check duplicate email
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = 'An account with that email already exists.';
            $captchaQuestion = captcha_generate('reg');
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins  = $db->prepare(
                'INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)'
            );
            $ins->bind_param('sss', $fullname, $email, $hash);

            if ($ins->execute()) {
                $success = 'Account created! You can now log in.';
                $captchaQuestion = captcha_generate('reg');
            } else {
                $error = 'Registration failed. Please try again.';
                $captchaQuestion = captcha_generate('reg');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — F.I.R.E.W.A.T.C.H</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="auth-body">

  <div class="auth-card">
    <!-- Logo / Brand -->
    <div class="auth-brand">
      <div class="fire-icon-lg">🔥</div>
      <h1 class="brand-title">F.I.R.E.W.A.T.C.H</h1>
      <p class="brand-sub">Fire Intelligent Response &amp; Emergency Alert System</p>
    </div>

    <h2 class="auth-heading">Create Account</h2>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php" novalidate>

      <div class="form-group">
        <label for="fullname">Full Name</label>
        <input type="text" id="fullname" name="fullname"
               value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>"
               placeholder="Juan dela Cruz" required>
      </div>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" required>
      </div>

      <div class="form-group">
        <label for="password">Password <span class="hint">(min 6 chars)</span></label>
        <input type="password" id="password" name="password"
               placeholder="••••••••" required>
      </div>

      <div class="form-group">
        <label for="password2">Confirm Password</label>
        <input type="password" id="password2" name="password2"
               placeholder="••••••••" required>
      </div>

      <!-- CAPTCHA -->
      <div class="form-group captcha-group">
        <label>Security Check</label>
        <div class="captcha-box">
          <span class="captcha-question"><?= htmlspecialchars($captchaQuestion) ?></span>
        </div>
        <input type="number" name="captcha" placeholder="Enter answer" required>
      </div>

      <button type="submit" class="btn btn-primary btn-full">Create Account</button>
    </form>

    <p class="auth-footer">Already have an account? <a href="login.php">Sign in</a></p>
  </div>

</body>
</html>