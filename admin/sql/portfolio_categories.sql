-- إضافة عمود category_id إلى جدول portfolio
ALTER TABLE `portfolio` 
ADD COLUMN `category_id` int(11) NULL AFTER `category`,
ADD CONSTRAINT `fk_portfolio_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE SET NULL ON UPDATE CASCADE;

-- نقل التصنيفات الموجودة إلى الجدول الجديد
INSERT IGNORE INTO `categories` (`name`)
SELECT DISTINCT `category` FROM `portfolio` WHERE `category` IS NOT NULL AND `category` != '';

-- تحديث category_id في جدول portfolio
UPDATE `portfolio` p
JOIN `categories` c ON p.category = c.name
SET p.category_id = c.category_id;

-- إزالة عمود category القديم من جدول portfolio
ALTER TABLE `portfolio` DROP COLUMN `category`; 