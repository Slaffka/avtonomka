<?php
/**
 * Handles upgrading instances rating block.
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_lm_notifications_upgrade($oldversion, $block)
{
    global $DB;

    $dbman = $DB->get_manager();

    if($oldversion < 2015050500) {
        $sql = 'CREATE TABLE IF NOT EXISTS {lm_notification} (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `block` varchar(255) NOT NULL,
            `userid` int(11) NOT NULL,
            `type` int(11) NOT NULL,
            `message` text NOT NULL,
            PRIMARY KEY (`id`),
            KEY `block` (`block`,`userid`)
        )';
        $DB->execute($sql);
    }

    if($oldversion < 2015050501) {
        $sql = 'ALTER TABLE {lm_notification} ADD INDEX `user` (`userid`)';
        $DB->execute($sql);
    }

    if($oldversion < 2015050502) {
        $sql = 'ALTER TABLE {lm_notification} ADD `href` VARCHAR(255) NOT NULL AFTER `type`';
        $DB->execute($sql);
    }

    if($oldversion < 2015050700) {
        $sql = 'TRUNCATE {lm_notification}';
        $DB->execute($sql);

        $sql = 'ALTER TABLE {lm_notification} CHANGE `block` `blockid` INT NOT NULL';
        $DB->execute($sql);

        $sql = 'ALTER TABLE {lm_notification} DROP INDEX `block`, ADD UNIQUE `notification` (`blockid`, `userid`)';
        $DB->execute($sql);
    }

    if($oldversion < 2015052200) {
        $table = new xmldb_table('lm_notification');

        $field = new xmldb_field('event', XMLDB_TYPE_CHAR, '32', null, true, null, '', 'blockid');
        if( ! $dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('type', XMLDB_TYPE_INTEGER, '11', null, true, null, '', 'userid');
        if($dbman->field_exists($table, $field)){
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('href', XMLDB_TYPE_CHAR, '255', null, true, null, '', 'userid');
        if($dbman->field_exists($table, $field)){
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('message', XMLDB_TYPE_TEXT, null, null, true, null, null, 'userid');
        if($dbman->field_exists($table, $field)){
            $dbman->drop_field($table, $field);
        }

        $field = new xmldb_field('timestamp', XMLDB_TYPE_INTEGER, '11', null, true, null, 0, 'userid');
        if( ! $dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('data', XMLDB_TYPE_TEXT, null, null, false, null, null, 'timestamp');
        if( ! $dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $sql = 'ALTER TABLE {lm_notification} DROP INDEX `notification`, ADD UNIQUE `notification` (`blockid`, `event`, `userid`)';
        $DB->execute($sql);
    }

    if ($oldversion < 2015061100) {
        $sql = 'ALTER TABLE {lm_notification} ADD `alert` BOOLEAN NOT NULL DEFAULT FALSE  AFTER `userid`';
        $DB->execute($sql);
    }

    if ($oldversion < 2015061200) {
        global $USER;
        lm_notification::add('lm_notifications:update', TRUE, $USER->id, 50);
    }

    if ($oldversion < 2015080700) {
        $sql = 'ALTER TABLE {lm_notification} ADD `instanceid` INTEGER NOT NULL DEFAULT 0 AFTER `event`';
        $DB->execute($sql);
    }

    if ($oldversion < 2015080701) {
        $sql = 'ALTER TABLE {lm_notification} DROP INDEX `notification`, ADD UNIQUE `notification` (`blockid`, `event`, `instanceid`, `userid`)';
        $DB->execute($sql);
    }

    return true;
}