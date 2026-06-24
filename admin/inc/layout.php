<?php
/** Admin layout header. Expects $title and $active (nav key). Call layout_foot() at end. */
require_admin();
$title = $title ?? 'Dashboard';
$active = $active ?? '';
$au = admin_user();

$newOrders = (int)(fetch("SELECT COUNT(*) c FROM orders WHERE status='new'")['c'] ?? 0);
$unread    = (int)(fetch("SELECT COUNT(*) c FROM contact_messages WHERE is_read=0")['c'] ?? 0);

function nav_item(string $key, string $active, string $href, string $icon, string $label, $badge = null): void {
    $cls = $key === $active ? 'active' : '';
    echo '<a href="' . admin_url($href) . '" class="' . $cls . '"><i class="fa-solid ' . $icon . '"></i> ' . $label;
    if ($badge) echo '<span class="badge">' . $badge . '</span>';
    echo '</a>';
}
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · Omonblaq Admin</title>
<link rel="icon" href="<?= asset('img/logo.png') ?>">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Jost:wght@300;400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="<?= admin_url('assets/admin.css') ?>?v=4">
</head><body>

<aside class="sidebar" id="sidebar">
  <div class="sb-brand">
    <img src="<?= asset('img/logo.png') ?>" alt="">
    <div style="flex:1"><b>Omonblaq</b><small>Admin</small></div>
    <button class="menu-close-btn" onclick="document.getElementById('sidebar').classList.remove('open')"><i class="fa-solid fa-xmark"></i></button>
  </div>
  <nav class="sb-nav">
    <div class="grp">Overview</div>
    <?php nav_item('dashboard',$active,'index.php','fa-gauge-high','Dashboard'); ?>
    <?php nav_item('orders',$active,'orders.php','fa-bag-shopping','Orders',$newOrders ?: null); ?>
    <?php nav_item('customers',$active,'customers.php','fa-users','Customers'); ?>
    <?php nav_item('royalty',$active,'royalty.php','fa-crown','Royalty'); ?>
    <div class="grp">Catalog</div>
    <?php nav_item('products',$active,'products.php','fa-gem','Products'); ?>
    <?php nav_item('categories',$active,'categories.php','fa-layer-group','Categories'); ?>
    <?php nav_item('coupons',$active,'coupons.php','fa-tags','Promotions'); ?>
    <div class="grp">Content</div>
    <?php nav_item('pages',$active,'pages.php','fa-file-lines','Pages'); ?>
    <?php nav_item('blog',$active,'blog.php','fa-feather','Blog'); ?>
    <?php nav_item('testimonials',$active,'testimonials.php','fa-quote-right','Testimonials'); ?>
    <?php nav_item('messages',$active,'messages.php','fa-envelope','Messages',$unread ?: null); ?>
    <div class="grp">System</div>
    <?php nav_item('settings',$active,'settings.php','fa-sliders','Settings'); ?>
  </nav>
  <div class="sb-foot">
    <a href="<?= url('index.php') ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Store</a>
  </div>
</aside>
<div class="sb-backdrop" onclick="document.getElementById('sidebar').classList.remove('open')"></div>

<div class="main">
  <div class="topbar">
    <div style="display:flex;align-items:center;gap:14px">
      <button class="menu-btn" onclick="document.getElementById('sidebar').classList.toggle('open')"><i class="fa-solid fa-bars"></i></button>
      <h1><?= e($title) ?></h1>
    </div>
    <div class="right">
      <span><?= e(date('l, M j')) ?></span>
      <div class="av"><?= e(strtoupper(substr($au['name'] ?? 'A', 0, 1))) ?></div>
      <a href="<?= admin_url('logout.php') ?>" class="btn-ghost" title="Logout"><i class="fa-solid fa-right-from-bracket"></i></a>
    </div>
  </div>
  <div class="content">
    <?php if ($f = a_flash()): ?><div class="alert alert-success"><?= e($f) ?></div><?php endif; ?>
