-- إنشاء جدول التصنيفات
CREATE TABLE IF NOT EXISTS `categories` (
  `category_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`category_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إضافة عمود category_id إلى جدول الخدمات
ALTER TABLE `services` 
ADD COLUMN `category_id` int(11) NULL AFTER `category`,
ADD CONSTRAINT `fk_services_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- نقل التصنيفات الموجودة إلى الجدول الجديد
INSERT IGNORE INTO `categories` (`name`)
SELECT DISTINCT `category` FROM `services` WHERE `category` IS NOT NULL AND `category` != '';

-- تحديث category_id في جدول الخدمات
UPDATE `services` s
JOIN `categories` c ON s.category = c.name
SET s.category_id = c.category_id;

-- إزالة عمود category القديم من جدول الخدمات
ALTER TABLE `services` DROP COLUMN `category`; 