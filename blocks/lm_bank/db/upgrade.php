<?php
/**
 * Handles upgrading instances rating block.
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_lm_bank_upgrade($oldversion, $block)
{
    global $DB, $CFG;

    $dbman = $DB->get_manager();

    if ( $oldversion < 2015061000 ) {
        if ( $dbman->table_exists('lm_payment_account') )
            $DB->execute("DROP TABLE {lm_payment_account}");

        if ( $dbman->table_exists('lm_payment_chanel') )
            $DB->execute("DROP TABLE {lm_payment_chanel}");

        if ( $dbman->table_exists('lm_bank_account') )
            $DB->execute("DROP TABLE {lm_bank_account}");

        if ( $dbman->table_exists('lm_bank_channel') )
            $DB->execute("DROP TABLE {lm_bank_channel}");

        $sql = "DELETE FROM {block_instances} WHERE blockname = 'lm_bank'";
        $DB->execute($sql);

        $sql = "UPDATE {block_instances} SET blockname = 'lm_bank' WHERE blockname = 'lm_coins'";
        $DB->execute($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `{$CFG->prefix}lm_bank_channel` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `code` varchar(30) NOT NULL DEFAULT '',
            `blockid` INT(10) NOT NULL DEFAULT '0',
            PRIMARY KEY (`id`)
        )";
        $DB->execute($sql);

        $sql = "CREATE TABLE IF NOT EXISTS `{$CFG->prefix}lm_bank_account` (
            `id` INT(10) NOT NULL AUTO_INCREMENT,
            `channelid` INT(10) NOT NULL DEFAULT '0',
            `operatorid` INT(10) NOT NULL DEFAULT '0',
            `correspondent` INT(10) NOT NULL DEFAULT '0',
            `userid` INT(10) NOT NULL DEFAULT '0',
            `instanceid` INT(10) NOT NULL DEFAULT '0',
            `date` DATE NOT NULL,
            `amount` decimal(9,2) NOT NULL,
            `balance` decimal(9,2) NOT NULL,
            `comment` varchar(255) NOT NULL DEFAULT '',
             PRIMARY KEY (`id`)
        )";
        $DB->execute($sql);

        $sql = "ALTER TABLE {lm_bank_channel} ADD UNIQUE(`code`)";
        $DB->execute($sql);

        $channels = new StdClass(); // Системный канал - сгорание
        $channels->code = 'burn';
        $DB->insert_record("lm_bank_channel", $channels);

        $channels = new StdClass();
        $channels->code = 'system';
        $DB->insert_record("lm_bank_channel", $channels);

        $channels = new StdClass();
        $channels->code = 'feedback';
        $channels->blockid = 74;
        $DB->insert_record("lm_bank_channel", $channels);

        $channels = new StdClass();
        $channels->code = 'program';
        $channels->blockid = 73;
        $DB->insert_record("lm_bank_channel", $channels);

        $channels = new StdClass();
        $channels->code = 'transfer';
        $channels->blockid = 0;
        $DB->insert_record("lm_bank_channel", $channels);

    }

    if ( $oldversion < 2015061400 ) {
        $sql = "ALTER TABLE {lm_bank_account} CHANGE `date` `date` DATETIME NOT NULL";
        $DB->execute($sql);
    }


    return true;
}