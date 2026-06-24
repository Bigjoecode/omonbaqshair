<?php
/** Admin bootstrap: config, db, helpers, auth guard. */
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';

function admin_user(): ?array { return $_SESSION['admin'] ?? null; }

/** Token helper for destructive GET links: ...&<?= csrf_param() ?> */
function csrf_param(): string { return '_t=' . urlencode(csrf_token()); }

function csrf_fail(): void
{
    http_response_code(403);
    die('<div style="font-family:system-ui,sans-serif;max-width:480px;margin:80px auto;text-align:center;color:#333">'
      . '<h2>Security check failed</h2><p>Your session may have expired. Please go back and try again.</p>'
      . '<a href="' . admin_url('index.php') . '" style="color:#a9842a">Return to dashboard</a></div>');
}

/**
 * CSRF guard for state-changing admin requests.
 * - All POSTs require a valid token (csrf_field()).
 * - Destructive GET links (delete/toggle/delimg/read/action) require ?_t=<token>.
 */
function admin_csrf_guard(): void
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_verify()) csrf_fail();
        return;
    }
    $destructive = isset($_GET['delete']) || isset($_GET['toggle']) || isset($_GET['delimg']) || isset($_GET['read'])
        || (isset($_GET['action']) && in_array($_GET['action'], ['delete', 'toggle'], true));
    if ($destructive) {
        $t = $_GET['_t'] ?? '';
        if (!hash_equals($_SESSION[CSRF_TOKEN_NAME] ?? '', (string)$t)) csrf_fail();
    }
}

// Enforce on every admin request (config.php already started the session).
admin_csrf_guard();

function require_admin(): void
{
    if (!admin_user()) {
        redirect('admin/login.php');
    }
}

function admin_url(string $p = ''): string { return url('admin/' . ltrim($p, '/')); }

/** Simple flash for admin */
function a_flash(?string $msg = null): ?string
{
    if ($msg !== null) { $_SESSION['admin_flash'] = $msg; return null; }
    $m = $_SESSION['admin_flash'] ?? null; unset($_SESSION['admin_flash']); return $m;
}

/** Handle product/category image upload, returns stored filename or null */
function handle_upload(string $field, string $subdir = 'products'): ?string
{
    if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) return null;
    $tmp = $_FILES[$field]['tmp_name'];
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg','jpeg','png','webp','gif'];
    if (!in_array($ext, $allowed, true)) return null;
    $dir = UPLOAD_PATH . '/' . $subdir;
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $name = $subdir . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3)) . '.' . $ext;
    if (move_uploaded_file($tmp, $dir . '/' . $name)) return $name;
    return null;
}

/** Handle a multi-file (`name="field[]"`) image upload. Returns stored filenames. */
function handle_uploads(string $field, string $subdir = 'pages'): array
{
    if (empty($_FILES[$field]['name']) || !is_array($_FILES[$field]['name'])) return [];
    $allowed = ['jpg','jpeg','png','webp','gif'];
    $dir = UPLOAD_PATH . '/' . $subdir;
    if (!is_dir($dir)) @mkdir($dir, 0775, true);
    $out = [];
    foreach ($_FILES[$field]['name'] as $i => $orig) {
        if (($_FILES[$field]['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
        $ext = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) continue;
        $name = $subdir . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        if (move_uploaded_file($_FILES[$field]['tmp_name'][$i], $dir . '/' . $name)) $out[] = $name;
    }
    return $out;
}
