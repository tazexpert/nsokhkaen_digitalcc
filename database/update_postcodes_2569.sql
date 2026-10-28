-- เติมรหัสไปรษณีย์ 12 ศูนย์ (รหัส -SC/-OT) ที่ไม่มีในไฟล์ Excel
-- อ้างอิงตามตำบล/อำเภอจากฐานข้อมูลรหัสไปรษณีย์ไทย (thai-address-database)
-- ใช้กับฐานข้อมูลที่นำเข้า import_centers_2569.sql ไปแล้ว: mysql -u root -p < database/update_postcodes_2569.sql

SET NAMES utf8mb4;
USE digitalcc_khonkaen;

UPDATE centers SET postcode = '40350' WHERE code = '005-SC';
UPDATE centers SET postcode = '40290' WHERE code = '006-SC';
UPDATE centers SET postcode = '40110' WHERE code = '007-OT';
UPDATE centers SET postcode = '40340' WHERE code = '008-SC';
UPDATE centers SET postcode = '40150' WHERE code = '009-SC';
UPDATE centers SET postcode = '40150' WHERE code = '010-SC';
UPDATE centers SET postcode = '40230' WHERE code = '012-SC';
UPDATE centers SET postcode = '40160' WHERE code = '013-SC';
UPDATE centers SET postcode = '40160' WHERE code = '014-SC';
UPDATE centers SET postcode = '40240' WHERE code = '015-SC';
UPDATE centers SET postcode = '40240' WHERE code = '016-SC';
UPDATE centers SET postcode = '40150' WHERE code = '355-OT';
