<?php
require __DIR__ . '/config_mysqli.php';

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

$errors = [];
$success = false;

function csrf_check($token) {
    return true;
}
function csrf_token() {
    return bin2hex(random_bytes(16));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   
    if (!csrf_check($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form token. Please try again.';
    } else {
      
        $email = trim($_POST['email'] ?? '');
        $name  = trim($_POST['display_name'] ?? '');
        $pass1 = $_POST['password'] ?? '';
        $pass2 = $_POST['password_confirm'] ?? '';

       
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if ($name === '') {
            $errors[] = 'Please enter a display name.';
        }
        if ($pass1 === '' || strlen($pass1) < 8) {
            $errors[] = 'Password must be at least 8 characters.';
        }
        if ($pass1 !== $pass2) {
            $errors[] = 'Passwords do not match.';
        }

       
        if (!$errors) {
            $stmt = $mysqli->prepare('SELECT 1 FROM users WHERE email = ? LIMIT 1');
            if (!$stmt) {
                $errors[] = 'Database error (prepare failed).';
            } else {
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $errors[] = 'This email is already registered.';
                }
                $stmt->close();
            }
        }

        
        if (!$errors) {
            $hash = password_hash($pass1, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare('INSERT INTO users (email, display_name, password_hash) VALUES (?, ?, ?)');
            if (!$stmt) {
                $errors[] = 'Database error (prepare insert failed).';
            } else {
                $stmt->bind_param('sss', $email, $name, $hash);
                if ($stmt->execute()) {
                    $success = true;
                } else {
                    $errors[] = 'Database error: failed to create user.';
                }
                $stmt->close();
            }
        }
    }
}


$token = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Create account</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { min-height: 100vh; display:flex; align-items:center; }
    .card-wrap { max-width:520px; width:100%; }
  </style>
</head>
<body class="bg-light">
  <main class="container d-flex justify-content-center py-5">
    <div class="card shadow-sm card-wrap">
      <div class="card-body p-4">
        <h1 class="h4 mb-3">Create your account</h1>

        <?php if ($success): ?>
          <div class="alert alert-success">
            Registration successful. You can now <a href="login.php" class="alert-link">sign in</a>.
          </div>
        <?php endif; ?>

        <?php if ($errors): ?>
          <div class="alert alert-danger">
            <ul class="mb-0">
              <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="post" novalidate>
          <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label for="display_name" class="form-label">Display name</label>
            <input type="text" class="form-control" id="display_name" name="display_name" required value="<?= e($_POST['display_name'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required minlength="8">
            <div class="form-text">At least 8 characters.</div>
          </div>
          <div class="mb-3">
            <label for="password_confirm" class="form-label">Confirm password</label>
            <input type="password" class="form-control" id="password_confirm" name="password_confirm" required minlength="8">
          </div>
          <div class="d-grid gap-2">
            <button type="submit" class="btn btn-primary">Create account</button>
            <a href="login.php" class="btn btn-outline-secondary">Back to sign in</a>
          </div>
        </form>
      </div>
    </div>
  </main>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
