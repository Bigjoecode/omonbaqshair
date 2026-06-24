<?php
require_once __DIR__ . '/inc/bootstrap.php';
unset($_SESSION['admin']);
redirect('admin/login.php');
