<?php
/**
 * ตั้งค่าระบบบริหารจัดการศูนย์ดิจิทัลชุมชน จังหวัดขอนแก่น
 * แก้ไขค่าการเชื่อมต่อฐานข้อมูลให้ตรงกับเซิร์ฟเวอร์ของคุณ
 */

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'digitalcc_khonkaen');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');

define('SITE_NAME', 'ศูนย์ดิจิทัลชุมชน จังหวัดขอนแก่น');
define('PROVINCE', 'ขอนแก่น');

// โฟลเดอร์เก็บไฟล์รูปภาพ (ต้องเขียนได้)
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('UPLOAD_URL', 'uploads');           // path สัมพัทธ์จาก root ของเว็บ
define('UPLOAD_MAX_BYTES', 8 * 1024 * 1024); // 8 MB ต่อไฟล์
define('IMAGE_MAX_SIDE', 1600);             // ย่อรูปที่ใหญ่กว่านี้ (px)

date_default_timezone_set('Asia/Bangkok');
