<?php
/**
 * Handles upgrading instances rating block.
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_lm_rating_upgrade($oldversion, $block)
{
    global $DB, $CFG;


    if($oldversion < 2015040600) {
        $sql = "DROP TABLE IF EXISTS `{$CFG->prefix}lm_rating_metric_param_value`";
        $DB->execute($sql);

        $sql = "DROP TABLE IF EXISTS `{$CFG->prefix}lm_rating_metric_param`";
        $DB->execute($sql);

        $sql = "DROP TABLE IF EXISTS `{$CFG->prefix}lm_rating_metric`";
        $DB->execute($sql);

        $sql = "CREATE TABLE `{$CFG->prefix}lm_rating_metric` (
                `id` INT(10) NOT NULL AUTO_INCREMENT,
                `code` VARCHAR(32) NOT NULL,
                `post` INT(10) NOT NULL,
                `weight` FLOAT NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `metric` (`code`),
                KEY `post` (`post`)
        )";
        $DB->execute($sql);

        $sql = "CREATE TABLE `{$CFG->prefix}lm_rating_metric_value` (
                `id` INT(10) NOT NULL AUTO_INCREMENT,
                `metric` INT(10) NOT NULL,
                `pos` INT(10) NOT NULL,
                `user` INT(10) NOT NULL,
                `date` DATE NOT NULL,
                `rating` FLOAT NOT NULL,
                `bal` FLOAT NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `value` (`metric`, `pos`, `date`),
                KEY `metric` (`metric`)
        )";
        $DB->execute($sql);

        $sql = "CREATE TABLE `{$CFG->prefix}lm_rating_param` (
                `id` INT(10) NOT NULL AUTO_INCREMENT,
                `metric` INT(10) NOT NULL,
                `code` VARCHAR(32) NOT NULL,
                `name` VARCHAR(255) NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `param` (`code`),
                KEY `metric` (`metric`)
        )";
        $DB->execute($sql);

        $sql = "CREATE TABLE `{$CFG->prefix}lm_rating_param_value` (
                `id` INT(10) NOT NULL AUTO_INCREMENT,
                `param` INT(10) NOT NULL,
                `metric_value` INT(10) NOT NULL,
                `value` FLOAT NOT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `value` (`param`, `metric_value`),
                KEY `metric_value` (`metric_value`)
        )";
        $DB->execute($sql);

    }

    if($oldversion < 2015040601) {
        $sql = "ALTER TABLE `{$CFG->prefix}lm_rating_metric` ADD `name` VARCHAR(255) NOT NULL AFTER `post`";
        $DB->execute($sql);
    }

    if($oldversion < 2015040602) {
        $sql = "ALTER TABLE `{$CFG->prefix}lm_rating_metric_value` CHANGE `rating` `value` FLOAT NOT NULL";
        $DB->execute($sql);
    }

    if($oldversion < 2015040603) {
        // lm_rating_metric
        $sql = "ALTER TABLE `{$CFG->prefix}lm_rating_metric` CHANGE `post` `postid` INT(10) NOT NULL";
        $DB->execute($sql);

        // lm_rating_metric_value
        $sql = "ALTER TABLE `{$CFG->prefix}lm_rating_metric_value` CHANGE `metric` `metricid` INT(10) NOT NULL";
        $DB->execute($sql);

        $sql = "ALTER TABLE `{$CFG->prefix}lm_rating_metric_value` CHANGE `pos` `posid` INT(10) NOT NULL";
        $DB->execute($sql);

        $sql = "ALTER TABLE `{$CFG->prefix}lm_rating_metric_value` CHANGE `user` `userid` INT(10) NOT NULL";
        $DB->execute($sql);

        // lm_rating_param
        $sql = "ALTER TABLE `{$CFG->prefix}lm_rating_param` CHANGE `metric` `metricid` INT(10) NOT NULL";
        $DB->execute($sql);

        // lm_rating_param_value
        $sql = "ALTER TABLE `{$CFG->prefix}lm_rating_param_value` CHANGE `param` `paramid` INT(10) NOT NULL";
        $DB->execute($sql);

        $sql = "ALTER TABLE `{$CFG->prefix}lm_rating_param_value` CHANGE `metric_value` `metric_value_id` INT(10) NOT NULL";
        $DB->execute($sql);

    }

    if($oldversion < 2015041007) {
        $sql = "DROP TABLE IF EXISTS `{$CFG->prefix}lm_rating_cash`";
        $DB->execute($sql);

        $sql = "DROP TABLE IF EXISTS `{$CFG->prefix}_lm_rating_cash`";
        $DB->execute($sql);
    }

    if( $oldversion < 2015042300 ){
        $DB->execute("TRUNCATE {lm_rating_metric}");
        $DB->execute("TRUNCATE {lm_rating_metric_value}");
        $DB->execute("TRUNCATE {lm_rating_param}");
        $DB->execute("TRUNCATE {lm_rating_param_value}");
    }

    if( $oldversion < 2015042301 ) {
        $sql = "ALTER TABLE {lm_rating_metric_value} DROP INDEX `value`, ADD UNIQUE `value` (`metricid`, `posid`, `userid`, `date`) COMMENT ''";
        $DB->execute($sql);
    }

    if ( $oldversion < 2015061400 ) {
        $sql = "UPDATE {lm_rating_metric} SET name = 'Эффективность визитов' WHERE id = 13";
        $DB->execute($sql);

        $sql = "UPDATE {lm_rating_metric} SET name = 'Соблюдение маршрута' WHERE id = 14";
        $DB->execute($sql);

    }


    return true;
}