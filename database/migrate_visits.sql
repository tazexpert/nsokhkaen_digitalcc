-- อัปเดตฐานข้อมูลเดิมให้รองรับแบบฟอร์มการตรวจเยี่ยมศูนย์ (รันซ้ำได้)
-- mysql -u root -p < database/migrate_visits.sql

SET NAMES utf8mb4;
USE digitalcc_khonkaen;

-- เพิ่มคอลัมน์ เลขที่ / หมู่ที่ ให้ตาราง centers (ถ้ายังไม่มี)
SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'centers' AND COLUMN_NAME = 'house_no');
SET @sql := IF(@has = 0, "ALTER TABLE centers ADD COLUMN house_no VARCHAR(30) NOT NULL DEFAULT '' AFTER center_type", 'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;
SET @has := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'centers' AND COLUMN_NAME = 'moo');
SET @sql := IF(@has = 0, "ALTER TABLE centers ADD COLUMN moo VARCHAR(10) NOT NULL DEFAULT '' AFTER house_no", 'DO 0');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- การตรวจเยี่ยมศูนย์ (แบบฟอร์มการตรวจเยี่ยม) — แต่ละครั้งสร้างรายการกิจกรรมคู่กันเพื่อเก็บภาพ
CREATE TABLE IF NOT EXISTS visits (
  id                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  center_id          INT UNSIGNED NOT NULL,
  activity_id        INT UNSIGNED NULL,
  visit_date         DATE NOT NULL,
  visit_time         TIME NULL,
  data               LONGTEXT NULL,                 -- คำตอบส่วนที่ 1–3 (JSON)
  problems           TEXT NULL,                     -- 4.1 ปัญหา/อุปสรรค
  suggestions        TEXT NULL,                     -- 4.2 ความต้องการ/ข้อเสนอแนะ
  informant_name     VARCHAR(150) NOT NULL DEFAULT '',
  informant_position VARCHAR(150) NOT NULL DEFAULT '',
  inspector_name     VARCHAR(150) NOT NULL DEFAULT '',
  inspector_position VARCHAR(150) NOT NULL DEFAULT '',
  lat                DECIMAL(10,7) NULL,            -- พิกัดที่บันทึกขณะตรวจเยี่ยม
  lng                DECIMAL(10,7) NULL,
  created_by         INT UNSIGNED NULL,
  created_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_center_date (center_id, visit_date),
  CONSTRAINT fk_visit_center FOREIGN KEY (center_id) REFERENCES centers(id) ON DELETE CASCADE,
  CONSTRAINT fk_visit_act FOREIGN KEY (activity_id) REFERENCES activities(id) ON DELETE SET NULL
) ENGINE=InnoDB;
