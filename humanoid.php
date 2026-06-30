<?php
// ============================================================
//  humanoid.php  –  Standalone Security Wall Check (Step 2)
// ============================================================
session_start();
require_once __DIR__ . '/captcha/captcha.php';

// If they already passed completely, send them straight through
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

// Redirect back to login if they haven't submitted correct credentials yet
if (!isset($_SESSION['temp_user'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$captchaQuestion = '';

// Generate CAPTCHA problem string
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $captchaQuestion = captcha_generate('humanoid');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $captchaIn = trim($_POST['captcha'] ?? '');

    if (!captcha_validate('humanoid', $captchaIn)) {
        $error = 'Incorrect security check answer. Please try again.';
        $captchaQuestion = captcha_generate('humanoid');
    } else {
        // SUCCESS! Promote temporary session data into full user access status
        $_SESSION['user_id']  = $_SESSION['temp_user']['id'];
        $_SESSION['fullname'] = $_SESSION['temp_user']['fullname'];

        // Clean up temporary tokens
        unset($_SESSION['temp_user']);

        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Security Check — F.I.R.E.W.A.T.C.H</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
    .security-shield-container {
      display: flex;
      justify-content: center;
      margin-bottom: 16px;
    }
    .security-shield-icon {
      background: var(--safe-dim);
      border: 1px solid var(--safe);
      color: #fff;
      width: 64px;
      height: 64px;
      border-radius: var(--radius-lg);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2rem;
      box-shadow: 0 0 20px var(--safe-glow);
    }
    .humanoid-box {
      background: var(--bg-surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 24px;
      text-align: center;
      margin-bottom: 20px;
    }
    .humanoid-question {
      font-family: var(--font-display);
      font-size: 2.5rem;
      font-weight: 700;
      letter-spacing: 0.05em;
      color: var(--ember-bright);
      text-shadow: 0 0 16px var(--ember-glow);
      user-select: none;
    }
    .secure-footer-stamp {
      text-align: center;
      font-size: 0.75rem;
      color: var(--text-dim);
      margin-top: 24px;
      letter-spacing: 0.05em;
    }
  </style>
</head>
<body class="auth-body">

  <div class="auth-card">
    <div class="security-shield-container">
      <div class="security-shield-icon">🛡️</div>
    </div>

    <h1 class="brand-title" style="text-align:center; margin-bottom: 4px;">Security Check</h1>
    <p class="brand-sub" style="text-align:center; margin-bottom: 24px;">Prove to us that you are a human</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="humanoid.php" autocomplete="off">
      
      <div class="form-group">
        <label style="text-align:center;">Complete the simple math challenge below</label>
        <div class="humanoid-box">
          <span class="humanoid-question"><?= htmlspecialchars($captchaQuestion) ?></span>
        </div>
        <input type="number" name="captcha" placeholder="Enter your answer" required autofocus>
      </div>

      <button type="submit" class="btn btn-primary btn-full">✓ Verify and continue</button>
    </form>

    <div class="secure-footer-stamp">🔒 SECURE VERIFICATION SYSTEM</div>
  </div>

</body>
</html>