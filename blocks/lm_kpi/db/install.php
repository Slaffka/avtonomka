<?php

function xmldb_block_lm_kpi_install() {
    global $DB, $CFG;

    $sql = "ALTER TABLE `{$CFG->prefix}lm_kpi_value` MODIFY `date` DATE";
    $DB->execute($sql);
}