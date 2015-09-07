<?php
/**
 * Handles upgrading instances feedback block.
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_lm_feedback_upgrade($oldversion, $block)
{
    global $DB, $CFG;

    if ( $oldversion < 2015062400 ) {
        $sql = "CREATE TABLE {lm_feedback_messages} (
              `id` INT(11) NOT NULL AUTO_INCREMENT,
              `feedbackid` int(11) NOT NULL DEFAULT '0',
              `message` text NOT NULL,
              `userid` int(11) NOT NULL DEFAULT '0',
              `time` int(11) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        )";
        $DB->execute($sql);
    }

    if ( $oldversion < 2015070700 ) {
        $subjs = new StdClass(); // Системный канал - сгорание
        $subjs->name = 'Прочее';
        $DB->insert_record("lm_feedback_subjects", $subjs);
    }



    return true;
}