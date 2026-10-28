<?php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/districts.php';

/* ---------- ทั่วไป ---------- */

function h($s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

function post(string $key, string $default = ''): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function int_param(string $key, int $default = 0): int
{
    $v = $_GET[$key] ?? $_POST[$key] ?? $default;
    return is_numeric($v) ? (int)$v : $default;
}

/** แปลงค่าพิกัดจากฟอร์ม คืนค่า null ถ้าว่างหรือไม่ถูกต้อง */
function coord(string $v, float $min, float $max): ?float
{
    $v = trim($v);
    if ($v === '' || !is_numeric($v)) {
        return null;
    }
    $f = (float)$v;
    return ($f >= $min && $f <= $max) ? $f : null;
}

function thai_date(?string $date): string
{
    if (!$date) {
        return '-';
    }
    $ts = strtotime($date);
    if ($ts === false) {
        return '-';
    }
    $months = ['', 'ม.ค.', 'ก.พ.', 'มี.ค.', 'เม.ย.', 'พ.ค.', 'มิ.ย.', 'ก.ค.', 'ส.ค.', 'ก.ย.', 'ต.ค.', 'พ.ย.', 'ธ.ค.'];
    return (int)date('j', $ts) . ' ' . $months[(int)date('n', $ts)] . ' ' . ((int)date('Y', $ts) + 543);
}

function upload_url(?string $file): string
{
    return $file ? UPLOAD_URL . '/' . $file : '';
}

/* ---------- Session / Auth / CSRF ---------- */

function start_session(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_name('DCCSESSID');
        session_start();
    }
}

function current_user(): ?array
{
    start_session();
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        redirect('login.php');
    }
    return $u;
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function check_csrf(): void
{
    start_session();
    $t = $_POST['csrf'] ?? '';
    if (!is_string($t) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $t)) {
        http_response_code(400);
        exit('คำขอไม่ถูกต้อง (CSRF token ไม่ตรงกัน) กรุณาโหลดหน้าใหม่แล้วลองอีกครั้ง');
    }
}

function flash(string $msg, string $type = 'success'): void
{
    start_session();
    $_SESSION['flash'][] = ['msg' => $msg, 'type' => $type];
}

function take_flash(): array
{
    start_session();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- อัปโหลดรูปภาพ ---------- */

/**
 * จัดรูป $_FILES['x'] ที่เป็น multiple ให้เป็น array ของไฟล์เดี่ยว
 */
function files_array(string $field): array
{
    if (empty($_FILES[$field])) {
        return [];
    }
    $f = $_FILES[$field];
    if (!is_array($f['name'])) {
        return [$f];
    }
    $out = [];
    foreach ($f['name'] as $i => $name) {
        $out[] = [
            'name'     => $name,
            'type'     => $f['type'][$i],
            'tmp_name' => $f['tmp_name'][$i],
            'error'    => $f['error'][$i],
            'size'     => $f['size'][$i],
        ];
    }
    return $out;
}

/**
 * บันทึกรูปที่อัปโหลด คืนค่า path สัมพัทธ์ (เช่น centers/abc.jpg) หรือ null ถ้าไม่มีไฟล์
 * โยน RuntimeException หากไฟล์ไม่ถูกต้อง
 */
function save_upload(array $file, string $subdir): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('อัปโหลดไฟล์ "' . $file['name'] . '" ไม่สำเร็จ (รหัส ' . $file['error'] . ')');
    }
    if ($file['size'] > UPLOAD_MAX_BYTES) {
        throw new RuntimeException('ไฟล์ "' . $file['name'] . '" มีขนาดใหญ่เกิน ' . (UPLOAD_MAX_BYTES / 1048576) . ' MB');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('ไฟล์ไม่ถูกต้อง');
    }
    $info = @getimagesize($file['tmp_name']);
    $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!$info || !isset($allowed[$info[2]])) {
        throw new RuntimeException('ไฟล์ "' . $file['name'] . '" ไม่ใช่รูปภาพที่รองรับ (JPG, PNG, WEBP, GIF)');
    }
    $ext = $allowed[$info[2]];
    $dir = UPLOAD_DIR . '/' . $subdir;
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        throw new RuntimeException('ไม่สามารถสร้างโฟลเดอร์ ' . $subdir);
    }
    $name = date('Ymd') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $dest = $dir . '/' . $name;

    if (!resize_image($file['tmp_name'], $dest, $info, IMAGE_MAX_SIDE)) {
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new RuntimeException('บันทึกไฟล์ไม่สำเร็จ ตรวจสอบสิทธิ์การเขียนโฟลเดอร์ uploads');
        }
    }
    return $subdir . '/' . $name;
}

/** ย่อรูปให้ด้านยาวไม่เกิน $max (ต้องมี GD) คืน false ถ้าไม่ได้ย่อ */
function resize_image(string $src, string $dest, array $info, int $max): bool
{
    [$w, $h, $type] = $info;
    if (!function_exists('imagecreatetruecolor') || ($w <= $max && $h <= $max) || $type === IMAGETYPE_GIF) {
        return false;
    }
    $img = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
        IMAGETYPE_PNG  => @imagecreatefrompng($src),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false,
        default        => false,
    };
    if (!$img) {
        return false;
    }
    $ratio = min($max / $w, $max / $h);
    $nw = (int)round($w * $ratio);
    $nh = (int)round($h * $ratio);
    $out = imagecreatetruecolor($nw, $nh);
    if ($type !== IMAGETYPE_JPEG) {
        imagealphablending($out, false);
        imagesavealpha($out, true);
    }
    imagecopyresampled($out, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
    $ok = match ($type) {
        IMAGETYPE_JPEG => imagejpeg($out, $dest, 85),
        IMAGETYPE_PNG  => imagepng($out, $dest, 6),
        IMAGETYPE_WEBP => imagewebp($out, $dest, 85),
    };
    imagedestroy($img);
    imagedestroy($out);
    return $ok;
}

function delete_upload(?string $rel): void
{
    if (!$rel || str_contains($rel, '..')) {
        return;
    }
    $path = UPLOAD_DIR . '/' . $rel;
    if (is_file($path)) {
        @unlink($path);
    }
}
