<?php
session_start();
if (isset($_SESSION['admin_logged_in'])) {
  header("Location: views/admin/dashboard.php");
  exit;
}

// Prepare a CSRF token using a WordPress nonce when available.
if (function_exists('wp_create_nonce')) {
  $yenolx_nonce = wp_create_nonce('yenolx_admin_login');
} else {
  if (empty($_SESSION['yenolx_login_nonce'])) {
    $_SESSION['yenolx_login_nonce'] = bin2hex(random_bytes(32));
  }
  $yenolx_nonce = $_SESSION['yenolx_login_nonce'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login - Yenolx</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-4">
      <div class="card shadow">
        <div class="card-body">
          <h4 class="mb-3">Admin Login</h4>
          <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">Invalid credentials</div>
          <?php endif; ?>
          <form method="POST" action="process-login.php">
            <input type="hidden" name="yenolx_login_nonce" value="<?php echo htmlspecialchars($yenolx_nonce, ENT_QUOTES, 'UTF-8'); ?>">
            <div class="mb-3">
              <label class="form-label">Username</label>
              <input type="text" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Login</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
