<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['reservation_csrf_token'] = bin2hex(random_bytes(32));
require_once __DIR__ . '/includes/class-database.php';
require_once __DIR__ . '/includes/class-slot-helper.php';

$db = new Database();
$pdo = $db->connect();

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reserve a Table | Yenolx</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
  <div class="container mt-5 mb-5">
    <div class="card shadow-lg">
      <div class="card-body">
        <h3 class="mb-4">Make a Reservation</h3>

        <form method="POST" action="submit-reservation.php">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($_SESSION['reservation_csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
          <div class="mb-3">
            <label for="res_name" class="form-label">Full Name</label>
            <input type="text" class="form-control" id="res_name" name="name" required>
          </div>

          <div class="mb-3">
            <label for="res_email" class="form-label">Email</label>
            <input type="email" class="form-control" id="res_email" name="email" required>
          </div>

          <div class="mb-3">
            <label for="res_phone" class="form-label">Phone</label>
            <input type="text" class="form-control" id="res_phone" name="phone" required>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="res_date" class="form-label">Date</label>
              <input type="date" class="form-control" id="res_date" name="date" min="<?= $today ?>" required>
            </div>
            <div class="col-md-6">
              <label for="res_time" class="form-label">Time</label>
              <select class="form-select" name="time" id="res_time" required>
                <option value="">Select a time</option>
              </select>
            </div>
          </div>

          <div class="mb-3">
            <label for="res_guests" class="form-label">Party Size</label>
            <input type="number" class="form-control" id="res_guests" name="guests" min="1" required>
          </div>

          <button type="submit" class="btn btn-primary">Book Now</button>
        </form>
      </div>
    </div>
  </div>

<script>
document.getElementById('res_date').addEventListener('change', function() {
  const date = this.value;
  const timeDropdown = document.getElementById('res_time');
  timeDropdown.innerHTML = '<option>Loading...</option>';

  fetch('api/get-available-times.php?date=' + date)
    .then(res => res.json())
    .then(data => {
      timeDropdown.innerHTML = '';
      if (data.length === 0) {
        timeDropdown.innerHTML = '<option>No slots available</option>';
      } else {
        data.forEach(time => {
          const opt = document.createElement('option');
          opt.value = time;
          opt.textContent = time;
          timeDropdown.appendChild(opt);
        });
      }
    });
});
</script>
</body>
</html>
