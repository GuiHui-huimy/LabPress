-- LabPress v1.1.1 数据库升级脚本
-- 作用：把动态多语言插件的 record_id 从 VARCHAR(100) 扩到 VARCHAR(255)，
--       避免较长的自定义文案在写入时被截断或触发唯一键冲突。
-- 影响：仅扩列，不改语义、不删数据；重复执行安全。
-- 执行前请先备份数据库（见文档 2-1）。

ALTER TABLE `ml_translations`
    MODIFY COLUMN `record_id` VARCHAR(255) NOT NULL;

-- 校验（执行后应为 varchar(255)）
-- SHOW COLUMNS FROM `ml_translations` LIKE 'record_id';
