<?php
require_once __DIR__ . '/inc/bootstrap.php';
if (admin_user()) redirect('admin/index.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $u = fetch('SELECT * FROM admin_users WHERE email = ?', [$email]);
    if ($u && password_verify($pass, $u['password_hash'])) {
        $_SESSION['admin'] = ['id' => $u['id'], 'name' => $u['name'], 'email' => $u['email'], 'role' => $u['role']];
        session_regenerate_id(true);
        redirect('admin/index.php');
    }
    $err = 'Invalid email or password.';
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Admin Login · Omonblaq's Hair</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= admin_url('assets/admin.css') ?>?v=2">
</head><body>
<div class="login-wrap"><div class="login-card">
  <img src="<?= asset('img/logo.png') ?>" alt="Omonblaq's Hair">
  <h1>Welcome Back</h1>
  <p>Sign in to manage your store</p>
  <?php if ($err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="fg"><label>Email</label><input type="email" name="email" required autofocus></div>
    <div class="fg"><label>Password</label><input type="password" name="password" required></div>
    <button class="btn btn-gold" style="width:100%;justify-content:center"><i class="fa-solid fa-lock"></i> Sign In</button>
  </form>
</div></div>
</body></html>
