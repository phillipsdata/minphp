CREATE TABLE `acl_acl` (
`aro_id` INT NOT NULL ,
`aco_id` INT NOT NULL ,
`action` VARCHAR( 255 ) NOT NULL ,
`permission` ENUM( 'allow', 'deny' ) NOT NULL ,
PRIMARY KEY ( `aro_id` , `aco_id` , `action` )
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;
 
CREATE TABLE `acl_aro` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`parent_id` INT NULL DEFAULT NULL,
`alias` VARCHAR( 255 ) NOT NULL ,
`lineage` VARCHAR( 255) NOT NULL DEFAULT '/',
INDEX ( `parent_id`),
UNIQUE ( `alias` )
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;
 
CREATE TABLE `acl_aco` (
`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ,
`alias` VARCHAR( 255 ) NOT NULL ,
UNIQUE ( `alias` )
) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_unicode_ci;