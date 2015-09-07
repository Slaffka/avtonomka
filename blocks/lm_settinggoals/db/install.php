<?php

function xmldb_block_lm_settinggoals_install()
{
    global $DB;

    $sql = "CREATE TABLE {lm_settinggoals_plan} (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `positionid` INT UNSIGNED NOT NULL,
              `placeid` INT UNSIGNED NOT NULL,
              `date` INT UNSIGNED NOT NULL,
              `state` TINYINT(127) UNSIGNED NOT NULL DEFAULT '0',
              `comment` MEDIUMTEXT NOT NULL,
              PRIMARY KEY (`id`),
              INDEX `positionidx` (`positionid` ASC),
              INDEX `placeidx` (`placeid` ASC)
            )
            ENGINE = InnoDB";
    $DB->execute($sql);
    $sql = "CREATE TABLE {lm_settinggoals_plan_kpi} (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `planid` INT UNSIGNED NOT NULL,
              `kpiid` INT UNSIGNED NOT NULL,
              `stage` ENUM('plan','correct','fact','old') NOT NULL,
              `value` DECIMAL(20,4) UNSIGNED NOT NULL DEFAULT '0',
              `comment` TEXT(65535) NOT NULL,
              PRIMARY KEY (`id`),
              /*INDEX `plankpiidx` (`planid` ASC, `kpiid` ASC)*/
              INDEX `planidx` (`planid` ASC),
              INDEX `kpiididx` (`kpiid` ASC)
            )
            ENGINE = InnoDB";
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
    $sql = "CREATE TABLE {lm_settinggoals_phase} (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `status` TINYINT UNSIGNED NOT NULL,
            `phase` TINYINT UNSIGNED NOT NULL,
            `deadline` INT UNSIGNED NOT NULL,
            `date` INT UNSIGNED NOT NULL,
            `svposid` INT UNSIGNED NOT NULL,
            `comment` MEDIUMTEXT NOT NULL,
            PRIMARY KEY (`id`))";
    $DB->execute($sql);

    $sql = "CREATE TABLE {lm_settinggoals_plan_comments} (
              `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
              `phaseid` INT UNSIGNED NOT NULL,
              `tpposid` INT UNSIGNED NOT NULL,
              `posxrefid` INT UNSIGNED NOT NULL,
              `timestamp` TIMESTAMP on update CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `text` TEXT(65535) NOT NULL,
              PRIMARY KEY (`id`),
              INDEX `planidx` (`phaseid`, `tpposid`)
            )
            ENGINE = InnoDB";
    $DB->execute($sql);

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

    $sql = "CREATE TABLE {lm_goalsetting_delay} (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `posid` INT UNSIGNED NOT NULL,
        `date` INT UNSIGNED NOT NULL,
        PRIMARY KEY (`id`))";
    $DB->execute($sql);


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

    return true;
}