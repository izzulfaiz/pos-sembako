<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
$_SESSION['user_nama'] = $user['nama'];
$_SESSION['user_role'] = $user['role'];
        header('Location: index.php'); exit;
    } else {
        $error = 'Username atau password salah';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
  <title>Login — POS Sembako</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: system-ui, sans-serif; background: #f5f5f0;
      color: #1a1a18; font-size: 14px;
      min-height: 100vh; display: flex; align-items: center; justify-content: center;
      padding: 16px;
    }
    .card {
      background: #fff; border: 0.5px solid #ddddd5;
      border-radius: 14px; padding: 2rem 1.5rem;
      width: 100%; max-width: 360px;
    }
    .logo { text-align: center; margin-bottom: 1.5rem; }
    .logo-icon {
      width: 52px; height: 52px; background: #E1F5EE;
      border-radius: 14px; display: flex; align-items: center; justify-content: center;
      font-size: 24px; margin: 0 auto 10px;
    }
    .logo-title { font-size: 16px; font-weight: 600; }
    .logo-sub { font-size: 12px; color: #888780; margin-top: 2px; }
    .form-group { margin-bottom: 14px; }
    .form-label { display: block; font-size: 12px; color: #5f5e5a; margin-bottom: 4px; }
    .form-control {
      width: 100%; padding: 11px 14px; font-size: 15px;
      border: 0.5px solid #ccc; border-radius: 10px;
      background: #fafaf8; color: #1a1a18; outline: none;
    }
    .form-control:focus { border-color: #1D9E75; background: #fff; }
    .error-box {
      background: #FCEBEB; border: 0.5px solid #F7C1C1;
      border-radius: 8px; padding: 10px 14px;
      font-size: 13px; color: #791F1F; margin-bottom: 14px;
    }
    .btn-login {
      width: 100%; padding: 13px; font-size: 15px; font-weight: 600;
      border: none; border-radius: 10px;
      background: #1D9E75; color: #fff; cursor: pointer;
      -webkit-tap-highlight-color: transparent;
    }
    .btn-login:active { opacity: 0.88; }
  </style>
</head>
<body>
<div class="card">
  <div class="logo">
    <div class="logo-icon">🛒</div>
    <div class="logo-title">Toko Sembako Mujiati</div>
    <div class="logo-sub">Point of Sale</div>
  </div>
  <?php if ($error): ?>
    <div class="error-box"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label class="form-label">Username</label>
      <input class="form-control" type="text" name="username" autocomplete="username"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus>
    </div>
    <div class="form-group">
      <label class="form-label">Password</label>
      <input class="form-control" type="password" name="password" autocomplete="current-password" required>
    </div>
    <button class="btn-login" type="submit">Masuk</button>
  </form>
</div>
</body>
</html>
