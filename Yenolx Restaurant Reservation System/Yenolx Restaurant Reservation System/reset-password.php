<?php
require_once __DIR__ . '/includes/auth-check.php';
if (empty($_SESSION['reset_password_nonce'])) {
    $_SESSION['reset_password_nonce'] = bin2hex(random_bytes(32));
}
$nonce = $_SESSION['reset_password_nonce'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
  <div class="row justify-content-center">
    <div class="col-md-5">
      <div class="card shadow">
        <div class="card-body">
          <h4 class="mb-3">Reset Password</h4>
          <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Password changed successfully.</div>
          <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($_GET['error']) ?></div>
          <?php endif; ?>
          <form method="POST" action="process-reset-password.php">
            <input type="hidden" name="nonce" value="<?= htmlspecialchars($nonce) ?>">
            <div class="mb-3">
              <label>Current Password</label>
              <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>New Password</label>
              <input type="password" name="new_password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label>Confirm New Password</label>
              <input type="password" name="confirm_password" class="form-control" required>
            </div>
            <button class="btn btn-primary w-100">Change Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
