<?php

function xmldb_block_lm_bestpractices_install()
{
    global $DB;
    $sql = "CREATE TABLE IF NOT EXISTS {lm_bestpractices_practice} (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `parentid` BIGINT UNSIGNED NOT NULL DEFAULT 0,
              `authorid` BIGINT NOT NULL,
              `regionid` INT NOT NULL,
              `parentuserid` BIGINT NOT NULL,
              `name` VARCHAR(255) NOT NULL,
              `goal` TEXT NOT NULL,
              `description` TEXT NOT NULL,
              `resourcesfinance` TEXT NOT NULL,
              `resourcesother` TEXT NOT NULL,
              `datestart` DATE NOT NULL,
              `datefinish` DATE NOT NULL,
              `profit` FLOAT UNSIGNED NOT NULL,
              `state` TINYINT UNSIGNED NOT NULL DEFAULT 0,
              `embedded` INT UNSIGNED NOT NULL,
              `respects` INT NOT NULL,
              `comment` TEXT NOT NULL,
              `created` INT UNSIGNED NOT NULL,
              PRIMARY KEY (`id`))
            ENGINE = InnoDB";
    $DB->execute($sql);

    $sql = "CREATE TABLE IF NOT EXISTS {lm_bestpractices_practice_comment} (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `practiceid` BIGINT UNSIGNED NOT NULL,
              `userid` BIGINT NOT NULL,
              `text` TEXT NOT NULL,
              PRIMARY KEY (`id`))
            ENGINE = InnoDB";
    $DB->execute($sql);

    $sql = "CREATE TABLE IF NOT EXISTS {lm_bestpractices_practice_type} (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `practiceid` BIGINT UNSIGNED NOT NULL,
              `typeid` BIGINT NOT NULL,
              PRIMARY KEY (`id`))
            ENGINE = InnoDB";
    $DB->execute($sql);

    $sql = "CREATE TABLE IF NOT EXISTS {lm_bestpractices_practice_types} (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(255) NOT NULL,
              PRIMARY KEY (`id`))
            ENGINE = InnoDB";
    $DB->execute($sql);

    $sql = "TRUNCATE {lm_bestpractices_practice_types}";
    $DB->execute($sql);

    $sql = "INSERT INTO {lm_bestpractices_practice_types} (`name`) VALUES ('Разработка доп. Материалов'),
            ('Общение, договоренности'), ('Развитие профессиональных навыков'), ('Мерчендайзинг'),
            ('Работа с командой'), ('Оптимизация работы')";
    $DB->execute($sql);

    $sql = "CREATE TABLE IF NOT EXISTS {lm_bestpractices_practice_file} (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `practiceid` BIGINT UNSIGNED NOT NULL,
              `type` TINYINT UNSIGNED NOT NULL,
              `path` VARCHAR(255) NOT NULL,
              `contenttype` VARCHAR(255) NOT NULL,
              `filename` VARCHAR(255) NOT NULL,
              PRIMARY KEY (`id`))
            ENGINE = InnoDB";
    $DB->execute($sql);

    $sql = "CREATE TABLE IF NOT EXISTS {lm_bestpractices_practice_tag} (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `practiceid` BIGINT UNSIGNED NOT NULL,
              `hashtag` VARCHAR(255) NOT NULL,
              PRIMARY KEY (`id`))
            ENGINE = InnoDB";
    $DB->execute($sql);

    $sql = "CREATE TABLE IF NOT EXISTS {lm_bestpractices_practice_trade_outlets} (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `practiceid` BIGINT UNSIGNED NOT NULL,
              `outletid` INT NOT NULL,
              PRIMARY KEY (`id`))
            ENGINE = InnoDB";
    $DB->execute($sql);

    $sql = "CREATE TABLE IF NOT EXISTS {lm_bestpractices_practice_favorite} (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `practiceid` BIGINT UNSIGNED NOT NULL,
              `userid` BIGINT NOT NULL,
              `created` INT UNSIGNED NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE INDEX `userpractice` (`practiceid` ASC, `userid` ASC))
            ENGINE = InnoDB";
    $DB->execute($sql);

    $sql = "CREATE TABLE IF NOT EXISTS {lm_bestpractices_practice_roles} (
              `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(255) NOT NULL,
              `access` TEXT NOT NULL,
              PRIMARY KEY (`id`))";
    $DB->execute($sql);

    $sql = "TRUNCATE {lm_bestpractices_practice_roles}";
    $DB->execute($sql);

    $sql = "INSERT INTO {lm_bestpractices_practice_roles} (`name`, `access`) VALUES
        ('Участник', 'index,my_practices,today_results,hall_of_fame'),
        ('Член Управляющего совета', 'index,today_results,hall_of_fame,council'),
        ('Модератор', 'index,today_results,hall_of_fame,moderate'),
        ('Наблюдатель', 'index,today_results,hall_of_fame')";
    $DB->execute($sql);

    $sql = "CREATE TABLE IF NOT EXISTS {lm_bestpractices_practice_user_roles} (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `userid` BIGINT NOT NULL,
            `rolesid` BIGINT UNSIGNED NOT NULL,
            PRIMARY KEY (`id`))";
    $DB->execute($sql);

    $sql = "CREATE TABLE IF NOT EXISTS {lm_bestpractices_practice_history} (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `practiceid` BIGINT NOT NULL,
            `date` INT UNSIGNED NOT NULL,
            `state` TINYINT UNSIGNED NOT NULL,
            `comment` TEXT NOT NULL,
            `data` TEXT NOT NULL,
            PRIMARY KEY (`id`))";
    $DB->execute($sql);

    return true;
}