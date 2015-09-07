<?php

function xmldb_block_lm_bank_install() {
    global $DB;


    $sql = "ALTER TABLE {lm_bank_account} CHANGE amount amount DECIMAL(9,2) NOT NULL";
    $DB->execute($sql);

    $sql = "ALTER TABLE {lm_bank_account} CHANGE balance balance DECIMAL(9,2) NOT NULL";
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
    $DB->insert_record("lm_bank_channel", $channels);

    return true;
}