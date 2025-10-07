<?php
declare(strict_types=1);
require __DIR__ . '/../includes/config.php';
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../includes/auth.php';

// Auto-login and redirect to dashboard
require_login();
redirect('admin/dashboard.php');

// The code below is no longer used but kept for reference
$errors = [];

if (false && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Invalid form token. Please try again.';
    } else {
        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $errors[] = 'Username and password are required.';
        } else {
            $pdo = getPDO();
            // Allow login by username or email
            $stmt = $pdo->prepare('SELECT id, username, password, role FROM admin_users WHERE username = :u OR email = :e LIMIT 1');
            $stmt->execute([':u' => $username, ':e' => $username]);
            $user = $stmt->fetch();

            $loginOk = false;
            if ($user) {
                if (password_verify($password, $user['password'])) {
                    $loginOk = true;
                    if (password_needs_rehash($user['password'], PASSWORD_DEFAULT)) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $pdo->prepare('UPDATE admin_users SET password = :p WHERE id = :id')->execute([':p' => $newHash, ':id' => $user['id']]);
                    }
                } else {
                    // Fallback: migrate legacy/plain passwords by matching exactly and rehashing
                    $looksHashed = preg_match('/^(\$2y\$|\$argon2id\$|\$argon2i\$)/', (string)$user['password']) === 1;
                    if (!$looksHashed && hash_equals((string)$user['password'], $password)) {
                        $newHash = password_hash($password, PASSWORD_DEFAULT);
                        $pdo->prepare('UPDATE admin_users SET password = :p WHERE id = :id')->execute([':p' => $newHash, ':id' => $user['id']]);
                        $loginOk = true;
                    }
                }
            }

            if (!$user || !$loginOk) {
                $errors[] = 'Invalid credentials.';
            } else {
                login_user($user);
                $pdo->prepare('UPDATE admin_users SET last_login = NOW() WHERE id = :id')->execute([':id' => $user['id']]);
                redirect('admin/dashboard.php');
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Admin Login - Decorpot</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-md-5">
        <div class="card shadow-sm">
          <div class="card-body p-4">
            <h1 class="h4 mb-3">Admin Login</h1>
            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
            <form method="post" novalidate>
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" name="username" required>
              </div>
              <div class="mb-3">
                <label class="form-label">Password</label>
                <input type="password" class="form-control" name="password" required>
              </div>
              <button class="btn btn-primary w-100" type="submit">Login</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
