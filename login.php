<?php
// ============================================================
//  login.php  –  Email + password login (Step 1)
// ============================================================
session_start();
require_once __DIR__ . '/config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if (isset($_SESSION['temp_user'])) {
    header('Location: humanoid.php');
    exit;
}

$error = '';

// ── Handle form submission (POST) ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = $_POST['password']      ?? '';

    // Basic input check
    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } 
    // Authenticate with Database
    else {
        $db = getDB();
        
        // Fetch user from the database
        $stmt = $db->prepare('SELECT id, fullname, password FROM users WHERE email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password hash matches
            if (password_verify($password, $user['password'])) {
                
                // STAGE 1 SUCCESS! Save profile in holding state
                $_SESSION['temp_user'] = [
                    'id'       => $user['id'],
                    'fullname' => $user['fullname']
                ];
                
                // Whisk them away to the standalone security check page
                header('Location: humanoid.php');
                exit;
                
            } else {
                $error = 'Incorrect email or password.';
            }
        } else {
            $error = 'Incorrect email or password.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — F.I.R.E.W.A.T.C.H</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="auth-body">

  <div class="auth-card">
    <div class="auth-brand">
      <div class="fire-icon-lg">🔥</div>
      <h1 class="brand-title">F.I.R.E.W.A.T.C.H</h1>
      <p class="brand-sub">Fire Intelligent Response &amp; Emergency Alert System</p>
    </div>

    <h2 class="auth-heading">Sign In</h2>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="login.php" novalidate>

      <div class="form-group">
        <label for="email">Email Address</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" required>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password"
               placeholder="••••••••" required>
      </div>

      <button type="submit" class="btn btn-primary btn-full">Sign In</button>
    </form>

    <p class="auth-footer">No account yet? <a href="register.php">Register here</a></p>
  </div>

</body>
</html>