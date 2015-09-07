<?php
/**
 * Handles upgrading instances rating block.
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_lm_kpi_upgrade($oldversion, $block)
{
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if($oldversion < 2015040804) {
        $sql = "DROP TABLE IF EXISTS `{$CFG->prefix}lm_kpi_value`";
        $DB->execute($sql);

        $sql = "DROP TABLE IF EXISTS `{$CFG->prefix}lm_kpi`";
        $DB->execute($sql);


        $sql = "CREATE TABLE `{$CFG->prefix}lm_kpi` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `code` VARCHAR(32) NOT NULL,
            `postid` INT(10) NOT NULL,
            `name` VARCHAR (255) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `kpi` (`code`),
            KEY `post` (`postid`)
        )";
        $DB->execute($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `{$CFG->prefix}lm_kpi_value` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `kpiid` INT(10) NOT NULL,
            `posid` INT(10) NOT NULL,
            `userid` INT(10) NOT NULL,
            `date` DATE NOT NULL,
            `plan` DECIMAL(16, 2) NOT NULL,
            `fact` DECIMAL(16, 2) NOT NULL,
            `predict` DECIMAL(16, 2) NOT NULL,
            `dailyplan` DECIMAL(16, 2) NOT NULL,
            `dayilyplan_to_fit` DECIMAL(16, 2) NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `value` (`kpiid`, `posid`, `date`),
            KEY `kpi` (`kpiid`)
        )";
        $DB->execute($sql);
    }

    if($oldversion < 2015040900){
        $sql = "ALTER TABLE {lm_kpi_value} CHANGE dayilyplan_to_fit dailyplan_to_fit DECIMAL(16,2) NOT NULL";
        $DB->execute($sql);
    }

    if( $oldversion < 2015042300 ){
        $DB->execute("TRUNCATE {lm_kpi}");
        $DB->execute("TRUNCATE {lm_kpi_value}");
    }

    if( $oldversion < 2015042301 ) {
        $sql = "ALTER TABLE {lm_kpi_value} DROP INDEX `value`, ADD UNIQUE `value` (`kpiid`, `posid`, `userid`, `date`) COMMENT ''";
        $DB->execute($sql);
    }

    if( $oldversion < 2015051300 ){
        $table = new xmldb_table('lm_kpi');
        $field = new xmldb_field('uom', XMLDB_TYPE_CHAR, '10', null, true, null, '', 'name');
        if(!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);
    }

    if( $oldversion < 2015051303 && $CFG->dbname == 'cherkizovo' ){
        $sql = "UPDATE {lm_kpi} SET uom = 'руб' WHERE id = 1";
        $DB->execute($sql);

        $sql = "UPDATE {lm_kpi} SET uom = 'кг' WHERE id = 2";
        $DB->execute($sql);

        $sql = "UPDATE {lm_kpi} SET uom = 'шт' WHERE id = 3";
        $DB->execute($sql);

        $sql = "UPDATE {lm_kpi} SET uom = 'шт' WHERE id = 4";
        $DB->execute($sql);


        $sql = "UPDATE {lm_kpi} SET uom = 'руб' WHERE id = 6";
        $DB->execute($sql);

        $sql = "UPDATE {lm_kpi} SET uom = 'кг' WHERE id = 7";
        $DB->execute($sql);

        $sql = "UPDATE {lm_kpi} SET uom = 'шт' WHERE id = 8";
        $DB->execute($sql);

        $sql = "UPDATE {lm_kpi} SET uom = 'шт' WHERE id = 9";
        $DB->execute($sql);
    }

    if( $oldversion < 2015072100 ){
        $table = new xmldb_table('lm_kpi');
        $field = new xmldb_field('active', XMLDB_TYPE_INTEGER, '1', null, true, null, 1, 'uom');
        if(!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);
    }

    if( $oldversion < 2015072100 && $CFG->dbname == 'cherkizovo' ){
        $sql = "UPDATE {lm_kpi} SET active = 0 WHERE id = 3 OR id=8";
        $DB->execute($sql);
    }

    return true;
}