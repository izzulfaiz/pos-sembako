<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Username dan password wajib diisi.';
    } else {
        $pdo  = getDB();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND aktif = 1 LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_nama'] = $user['nama'];
            $_SESSION['user_role'] = $user['role'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — POS Sembako</title>
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: system-ui, sans-serif;
      background: #f5f5f0;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #1a1a18;
    }
    .card {
      background: #fff;
      border: 0.5px solid #ddddd5;
      border-radius: 14px;
      padding: 2rem 2.5rem;
      width: 100%;
      max-width: 360px;
    }
    .store-name {
      font-size: 18px;
      font-weight: 500;
      margin-bottom: 4px;
    }
    .store-sub {
      font-size: 13px;
      color: #888780;
      margin-bottom: 1.8rem;
    }
    label {
      display: block;
      font-size: 13px;
      color: #5f5e5a;
      margin-bottom: 4px;
    }
    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 9px 12px;
      font-size: 14px;
      border: 0.5px solid #ccc;
      border-radius: 8px;
      background: #fafaf8;
      color: #1a1a18;
      outline: none;
      margin-bottom: 1rem;
      transition: border-color 0.15s;
    }
    input:focus {
      border-color: #888;
      background: #fff;
    }
    .error {
      font-size: 13px;
      color: #a32d2d;
      background: #fcebeb;
      border: 0.5px solid #f09595;
      border-radius: 8px;
      padding: 8px 12px;
      margin-bottom: 1rem;
    }
    button[type="submit"] {
      width: 100%;
      padding: 10px;
      font-size: 14px;
      font-weight: 500;
      border: none;
      border-radius: 8px;
      background: #1D9E75;
      color: #E1F5EE;
      cursor: pointer;
      transition: opacity 0.15s;
    }
    button[type="submit"]:hover { opacity: 0.88; }
    .hint {
      font-size: 12px;
      color: #b4b2a9;
      text-align: center;
      margin-top: 1rem;
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="store-name">Toko Sembako Mujiati</div>
    <div class="store-sub">Sistem Kasir</div>

    <?php if ($error): ?>
      <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <label for="username">Username</label>
      <input type="text" id="username" name="username"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
             autocomplete="username" required>

      <label for="password">Password</label>
      <input type="password" id="password" name="password"
             autocomplete="current-password" required>

      <button type="submit">Masuk</button>
    </form>

</body>
</html>
