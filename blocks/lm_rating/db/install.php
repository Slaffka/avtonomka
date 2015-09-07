<?php

function xmldb_block_lm_rating_install() {
    global $DB, $CFG;

    $sql = "ALTER TABLE `{$CFG->prefix}lm_rating_metric_value` MODIFY `date` DATE";
    $DB->execute($sql);
}