<?php

function xmldb_block_lm_tma_install() {
    global $DB, $CFG;

    $DB->execute('ALTER TABLE {lm_tma} ADD INDEX code (code)');

    $DB->execute('ALTER TABLE {lm_tma} MODIFY `start` DATE');
    $DB->execute('ALTER TABLE {lm_tma} MODIFY `end` DATE');


}