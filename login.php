<?php
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header("Location: dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Silakan isi username dan password.';
    } else {
        $user = authenticateUser($username, $password);
        if ($user) {
            $_SESSION['user'] = $user;
            header("Location: dashboard.php");
            exit;
        } else {
            $error = 'Username atau password salah!';
        }
    }
}
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Login Admin - Dynamic Template Parser System</title>
    <link rel="stylesheet" href="styles.css" />
  </head>
  <body class="login-body">
    <div class="login-wrapper">
      <div class="login-card">
        <div class="login-header">
          <div class="brand-mark large">D</div>
          <h2>Dynamic Parser</h2>
          <p>Portal Login Administrator</p>
        </div>

        <?php if (!empty($error)): ?>
          <div class="alert alert-danger">
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" action="login.php" class="login-form">
          <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required placeholder="Masukkan username admin" value="admin" autofocus />
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required placeholder="Masukkan password admin" value="admin123" />
          </div>

          <button type="submit" class="primary-button login-btn">Masuk Admin</button>
        </form>

        <div class="login-actions-footer" style="margin-top: 20px; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border); padding-top: 16px; font-size: 13px;">
          <a href="index.php" style="color: var(--muted); text-decoration: none;">&larr; Kembali ke Workspace</a>
          <button type="button" onclick="fillDemo()" style="background: none; border: none; color: var(--primary); font-weight: 600; cursor: pointer; padding: 0;">Isi Akun Demo</button>
        </div>

        <div class="login-demo-info" style="margin-top: 16px;">
          <p><strong>Akun Demo Bawaan:</strong></p>
          <p style="margin:0;">Username: <code>admin</code> | Password: <code>admin123</code></p>
        </div>
      </div>
    </div>

    <script>
      function fillDemo() {
        document.getElementById('username').value = 'admin';
        document.getElementById('password').value = 'admin123';
      }
    </script>
  </body>
</html>
