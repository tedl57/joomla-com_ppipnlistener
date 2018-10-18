CREATE TABLE IF NOT EXISTS `#__ppipnlistener_log` (
`id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,

`entry_time` DATETIME NOT NULL ,
`entry_text` TEXT NOT NULL ,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT COLLATE=utf8mb4_unicode_ci;

