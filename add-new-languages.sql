-- Yeni diller için veritabanı kolonlarını ekle
-- Fransızca (fr), İspanyolca (es), Arapça (ar), Lehçe (pl)

ALTER TABLE `urun` 
ADD COLUMN IF NOT EXISTS `baslik_fr` VARCHAR(255) NULL AFTER `baslik_ru`,
ADD COLUMN IF NOT EXISTS `baslik_es` VARCHAR(255) NULL AFTER `baslik_fr`,
ADD COLUMN IF NOT EXISTS `baslik_ar` VARCHAR(255) NULL AFTER `baslik_es`,
ADD COLUMN IF NOT EXISTS `baslik_pl` VARCHAR(255) NULL AFTER `baslik_ar`,
ADD COLUMN IF NOT EXISTS `kisa_aciklama_fr` TEXT NULL AFTER `kisa_aciklama_ru`,
ADD COLUMN IF NOT EXISTS `kisa_aciklama_es` TEXT NULL AFTER `kisa_aciklama_fr`,
ADD COLUMN IF NOT EXISTS `kisa_aciklama_ar` TEXT NULL AFTER `kisa_aciklama_es`,
ADD COLUMN IF NOT EXISTS `kisa_aciklama_pl` TEXT NULL AFTER `kisa_aciklama_ar`,
ADD COLUMN IF NOT EXISTS `aciklama_fr` TEXT NULL AFTER `aciklama_ru`,
ADD COLUMN IF NOT EXISTS `aciklama_es` TEXT NULL AFTER `aciklama_fr`,
ADD COLUMN IF NOT EXISTS `aciklama_ar` TEXT NULL AFTER `aciklama_es`,
ADD COLUMN IF NOT EXISTS `aciklama_pl` TEXT NULL AFTER `aciklama_ar`;

-- Eğer IF NOT EXISTS desteklenmiyorsa, aşağıdaki komutları kullanın:
-- ALTER TABLE `urun` ADD COLUMN `baslik_fr` VARCHAR(255) NULL;
-- ALTER TABLE `urun` ADD COLUMN `baslik_es` VARCHAR(255) NULL;
-- ALTER TABLE `urun` ADD COLUMN `baslik_ar` VARCHAR(255) NULL;
-- ALTER TABLE `urun` ADD COLUMN `baslik_pl` VARCHAR(255) NULL;
-- ALTER TABLE `urun` ADD COLUMN `kisa_aciklama_fr` TEXT NULL;
-- ALTER TABLE `urun` ADD COLUMN `kisa_aciklama_es` TEXT NULL;
-- ALTER TABLE `urun` ADD COLUMN `kisa_aciklama_ar` TEXT NULL;
-- ALTER TABLE `urun` ADD COLUMN `kisa_aciklama_pl` TEXT NULL;
-- ALTER TABLE `urun` ADD COLUMN `aciklama_fr` TEXT NULL;
-- ALTER TABLE `urun` ADD COLUMN `aciklama_es` TEXT NULL;
-- ALTER TABLE `urun` ADD COLUMN `aciklama_ar` TEXT NULL;
-- ALTER TABLE `urun` ADD COLUMN `aciklama_pl` TEXT NULL;

