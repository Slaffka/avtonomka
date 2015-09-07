<?php
/**
 * Handles upgrading instances setting goeals block.
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_lm_settinggoals_upgrade($oldversion, $block)
{
    global $DB;

    if($oldversion < 2015070700) {
        $sql = "DROP TABLE IF EXISTS {lm_settinggoals_plan}";
        $DB->execute($sql);
        $sql = "CREATE TABLE {lm_settinggoals_plan} (
                  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `positionid` INT UNSIGNED NOT NULL,
                  `outletid` INT UNSIGNED NOT NULL,
                  `date` INT UNSIGNED NOT NULL,
                  PRIMARY KEY (`id`),
                  INDEX `positionidx` (`positionid` ASC),
                  INDEX `outletidx` (`outletid` ASC)
                )
                ENGINE = InnoDB";
        $DB->execute($sql);
        $sql = "DROP TABLE IF EXISTS {lm_settinggoals_plan_kpi}";
        $DB->execute($sql);
        $sql = "CREATE TABLE {lm_settinggoals_plan_kpi} (
                  `id` INT UNSIGNED NOT NULL,
                  `planid` INT UNSIGNED NOT NULL,
                  `kpiid` INT UNSIGNED NOT NULL,
                  `value` DECIMAL(20,4) UNSIGNED NOT NULL,
                  `correct_value` DECIMAL(20,4) UNSIGNED NOT NULL,
                  `fact_value` DECIMAL(20,4) UNSIGNED NOT NULL,
                  `comment` TEXT(65535) NOT NULL,
                  `state` TINYINT(127) UNSIGNED NOT NULL,
                  PRIMARY KEY (`id`),
                  INDEX `planidx` (`planid` ASC),
                  INDEX `kpiididx` (`kpiid` ASC)
                )
                ENGINE = InnoDB";
        $DB->execute($sql);
    }

    if($oldversion < 2015071200) {
        $sql = "DROP TABLE IF EXISTS {lm_settinggoals_top}";
        $DB->execute($sql);
        $sql = "CREATE TABLE {lm_settinggoals_top} (
                  `userid` int(11) NOT NULL,
                  `count` int(10) unsigned NOT NULL DEFAULT '0',
                  `current` int(10) unsigned NOT NULL DEFAULT '0',
                  `next` tinyint(127) unsigned NOT NULL DEFAULT '0',
                  PRIMARY KEY (`userid`)
                ) ENGINE=InnoDB";
        $DB->execute($sql);
        $sql = "ALTER TABLE {lm_settinggoals_plan}
                CHANGE COLUMN `outletid` `placeid` INT(10) UNSIGNED NOT NULL,
                DROP INDEX `outletidx` ,
                ADD INDEX `placeidx` (`placeid` ASC)";
        $DB->execute($sql);
    }

    /*if($oldversion < 2015071400) {
        $sql = "ALTER TABLE {lm_settinggoals_plan_kpi}
                DROP INDEX `kpiididx`,
                DROP INDEX `planidx`,
                ADD INDEX `plankpiidx` (`planid` ASC, `kpiid` ASC)";
        $DB->execute($sql);
    }*/

    if($oldversion < 2015071500) {
        $sql = "ALTER TABLE {lm_settinggoals_plan_kpi}
                CHANGE `id` `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT";
        $DB->execute($sql);
    }

    if($oldversion < 2015071501) {
        $sql = "
            ALTER TABLE {lm_settinggoals_plan_kpi}
            CHANGE `state` `state` TINYINT(127) UNSIGNED NOT NULL DEFAULT '0',
            CHANGE `value` `value` DECIMAL(20,4) UNSIGNED NOT NULL DEFAULT '0',
            CHANGE `correct_value` `correct_value` DECIMAL(20,4) UNSIGNED NOT NULL DEFAULT '0',
            CHANGE `fact_value` `fact_value` DECIMAL(20,4) UNSIGNED NOT NULL DEFAULT '0',
            CHANGE `comment` `comment` MEDIUMTEXT NULL DEFAULT NULL
        ";
        $DB->execute($sql);
    }

    if($oldversion < 2015071601) {
        $sql = "ALTER TABLE {lm_settinggoals_plan}
        ADD COLUMN `state` TINYINT(127) UNSIGNED NOT NULL AFTER `date`";
        $DB->execute($sql);
    }

    if($oldversion < 2015071602) {
        $sql = "CREATE TABLE {lm_settinggoals_phase} (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `status` TINYINT UNSIGNED NOT NULL,
                `phase` TINYINT UNSIGNED NOT NULL,
                `deadline` INT UNSIGNED NOT NULL,
                `date` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`id`))";
        $DB->execute($sql);
      }

    if($oldversion < 2015071700) {
        $sql = "
            ALTER TABLE {lm_settinggoals_plan_kpi}
            DROP COLUMN `correct_value`,
            DROP COLUMN `fact_value`,
            ADD COLUMN `stage` ENUM('plan','correct','fact') NOT NULL AFTER `kpiid`
        ";
        $DB->execute($sql);
    }

    if($oldversion < 2015071701) {
        $sql = "ALTER TABLE {lm_settinggoals_plan}
        CHANGE COLUMN `state` `state` TINYINT(127) UNSIGNED NOT NULL DEFAULT '0' AFTER `date`";
        $DB->execute($sql);
    }
    if($oldversion < 2015071901) {
        $sql = "ALTER TABLE {lm_settinggoals_plan_kpi}
                CHANGE COLUMN `stage` `stage` ENUM('plan', 'correct', 'fact', 'old') NOT NULL";
        $DB->execute($sql);
    }

    if($oldversion < 2015071902) {
        $sql = "ALTER TABLE {lm_settinggoals_plan_kpi}
                DROP COLUMN `state`";
        $DB->execute($sql);
        $sql = "ALTER TABLE {lm_settinggoals_plan}
                ADD COLUMN `comment` MEDIUMTEXT NOT NULL AFTER `state`";
        $DB->execute($sql);
    }

    if($oldversion < 2015072101) {
        $sql = "ALTER TABLE {lm_settinggoals_phase}
                ADD COLUMN `svposid` INT UNSIGNED NOT NULL DEFAULT 0 AFTER `date`";
        $DB->execute($sql);
    }
    if($oldversion < 2015072102) {
        $sql = "CREATE TABLE {lm_settinggoals_plan_comments} (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `phaseid` INT UNSIGNED NOT NULL,
              `tpposid` INT UNSIGNED NOT NULL,
              `posxrefid` INT UNSIGNED NOT NULL,
              `timestamp` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `text` TEXT(65535) NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE `planidx` (`phaseid`, `tpposid`)
            )
            ENGINE = InnoDB";
        $DB->execute($sql);
    }

    if($oldversion < 2015072103) {
        $sql = "ALTER TABLE {lm_settinggoals_plan}
                CHANGE COLUMN `comment` `comment` MEDIUMTEXT DEFAULT NULL";
        $DB->execute($sql);
    }

    if($oldversion < 2015072200) {
        $sql = "ALTER TABLE {lm_settinggoals_plan_comments}
                DROP INDEX `planidx`, ADD INDEX `planidx` (`phaseid`, `tpposid`)";
        $DB->execute($sql);
    }
    if($oldversion < 2015072300) {
        $sql = "ALTER TABLE {lm_settinggoals_phase}
                ADD COLUMN `comment` MEDIUMTEXT NOT NULL AFTER `svposid`";
        $DB->execute($sql);
    }

    if($oldversion < 2015072400) {
        $sql = "
            CREATE TABLE {lm_settinggoals_averagekgcost} (
                `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                `positionid` INT UNSIGNED NOT NULL,
                `date` INT UNSIGNED NOT NULL,
                `value` DECIMAL(16,8) NOT NULL DEFAULT '0',
                PRIMARY KEY (`id`),
                UNIQUE `averagekgcostidx` (`positionid`, `date`)
            ) ENGINE = InnoDB";
        $DB->execute($sql);
    }


    if($oldversion < 2015072402) {
        $sql = "CREATE TABLE {lm_goalsetting_delay} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `posid` INT UNSIGNED NOT NULL,
            `date` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`))";
        $DB->execute($sql);
    }
    if($oldversion < 2015072600) {
        $sql = "CREATE TABLE {lm_goalsetting_exports} (
                  `id` INT NOT NULL AUTO_INCREMENT,
                  `time` INT UNSIGNED NOT NULL,
                  PRIMARY KEY (`id`))
                ";
        $DB->execute($sql);
        $sql = "CREATE TABLE {lm_goalsetting_top_list} (
                  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `posid` INT UNSIGNED NOT NULL,
                  `time` INT UNSIGNED NOT NULL,
                  PRIMARY KEY (`id`))
                ";
        $DB->execute($sql);
    }
    if($oldversion < 2015072601) {
        $sql = "DROP TABLE {lm_settinggoals_top}";
        $DB->execute($sql);
            $sql = "CREATE TABLE {lm_settinggoals_top} (
              `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
              `posid` int(11) unsigned NOT NULL,
              `count` int(10) unsigned NOT NULL DEFAULT '0',
              `current` int(10) unsigned NOT NULL DEFAULT '0',
              `next` tinyint(127) unsigned NOT NULL DEFAULT '0',
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB";
        $DB->execute($sql);
    }

    return true;
}