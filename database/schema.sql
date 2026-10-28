-- ฐานข้อมูลระบบบริหารจัดการศูนย์ดิจิทัลชุมชน จังหวัดขอนแก่น
-- นำเข้า: mysql -u root -p < database/schema.sql

SET NAMES utf8mb4;

CREATE DATABASE IF NOT EXISTS digitalcc_khonkaen
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE digitalcc_khonkaen;

-- ผู้ใช้งานระบบหลังบ้าน
CREATE TABLE IF NOT EXISTS users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username      VARCHAR(50)  NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  fullname      VARCHAR(150) NOT NULL DEFAULT '',
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ศูนย์ดิจิทัลชุมชน
CREATE TABLE IF NOT EXISTS centers (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  code          VARCHAR(30)  NULL,                  -- รหัสศูนย์ เช่น ONDE0728
  name          VARCHAR(255) NOT NULL,
  center_type   VARCHAR(100) NOT NULL DEFAULT '',   -- เช่น ศูนย์ในโรงเรียน / อบต. / กศน.
  address       VARCHAR(255) NOT NULL DEFAULT '',
  village       VARCHAR(100) NOT NULL DEFAULT '',
  subdistrict   VARCHAR(100) NOT NULL DEFAULT '',
  district      VARCHAR(100) NOT NULL DEFAULT '',
  province      VARCHAR(100) NOT NULL DEFAULT 'ขอนแก่น',
  postcode      VARCHAR(10)  NOT NULL DEFAULT '',
  lat           DECIMAL(10,7) NULL,
  lng           DECIMAL(10,7) NULL,
  phone         VARCHAR(50)  NOT NULL DEFAULT '',
  email         VARCHAR(150) NOT NULL DEFAULT '',
  facebook      VARCHAR(255) NOT NULL DEFAULT '',
  open_hours    VARCHAR(150) NOT NULL DEFAULT '',
  services      TEXT NULL,                          -- บริการ/อุปกรณ์ (1 บรรทัดต่อ 1 รายการ)
  description   TEXT NULL,
  cover_image   VARCHAR(255) NOT NULL DEFAULT '',
  is_active     TINYINT(1)   NOT NULL DEFAULT 1,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_code (code),
  KEY idx_district (district)
) ENGINE=InnoDB;

-- ภาพศูนย์ฯ (แกลเลอรี)
CREATE TABLE IF NOT EXISTS center_images (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  center_id   INT UNSIGNED NOT NULL,
  file        VARCHAR(255) NOT NULL,
  caption     VARCHAR(255) NOT NULL DEFAULT '',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_cimg_center FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ผู้ดูแลศูนย์
CREATE TABLE IF NOT EXISTS center_staff (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  center_id   INT UNSIGNED NOT NULL,
  name        VARCHAR(150) NOT NULL,
  position    VARCHAR(150) NOT NULL DEFAULT '',
  phone       VARCHAR(50)  NOT NULL DEFAULT '',
  email       VARCHAR(150) NOT NULL DEFAULT '',
  line_id     VARCHAR(100) NOT NULL DEFAULT '',
  bio         TEXT NULL,
  photo       VARCHAR(255) NOT NULL DEFAULT '',
  sort_order  INT NOT NULL DEFAULT 0,
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_staff_center FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- กิจกรรมที่จัดร่วมกับศูนย์ฯ
CREATE TABLE IF NOT EXISTS activities (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  center_id     INT UNSIGNED NOT NULL,
  title         VARCHAR(255) NOT NULL,
  activity_date DATE NULL,
  organizer     VARCHAR(255) NOT NULL DEFAULT '',
  participants  INT UNSIGNED NOT NULL DEFAULT 0,
  description   TEXT NULL,
  cover_image   VARCHAR(255) NOT NULL DEFAULT '',
  created_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_center_date (center_id, activity_date),
  CONSTRAINT fk_act_center FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ภาพกิจกรรม
CREATE TABLE IF NOT EXISTS activity_images (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  activity_id INT UNSIGNED NOT NULL,
  file        VARCHAR(255) NOT NULL,
  caption     VARCHAR(255) NOT NULL DEFAULT '',
  created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_aimg_act FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ผู้ดูแลระบบเริ่มต้น: admin / admin1234  (เปลี่ยนรหัสผ่านทันทีหลังเข้าสู่ระบบ)
INSERT IGNORE INTO users (username, password_hash, fullname) VALUES
('admin', '$2y$10$0p/y0WrhxRL/AfM0d6Hhmub2FORJC2B2TsIaaTQ04v2EJ363sGjBq', 'ผู้ดูแลระบบ');
