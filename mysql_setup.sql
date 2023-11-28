CREATE TABLE `buddytest` (
	`ornull` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_danish_ci',
	`strornull` VARCHAR(50) NULL DEFAULT NULL COLLATE 'utf8mb4_danish_ci',
	`intornull` INT(11) NULL DEFAULT NULL,
	`dateornull` DATE NULL DEFAULT NULL,
	`datetimeornull` DATETIME NULL DEFAULT NULL,
	`str` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_danish_ci',
	`string` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_danish_ci',
	`text` TEXT NOT NULL COLLATE 'utf8mb4_danish_ci',
	`autostring` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_danish_ci',
	`int` INT(11) NOT NULL,
	`tinyint` TINYINT(4) NOT NULL,
	`float` FLOAT NOT NULL,
	`dec` DECIMAL(20,6) NOT NULL,
	`decimal` DECIMAL(20,6) NOT NULL,
	`date` DATE NOT NULL,
	`time` TIME NOT NULL,
	`datetime` DATETIME NOT NULL,
	`raw` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_danish_ci',
	`boolean` TINYINT(4) NOT NULL,
	`email` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_danish_ci',
	`col` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_danish_ci',
	`column` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_danish_ci'
)
COLLATE='utf8mb4_danish_ci' ENGINE=InnoDB;
