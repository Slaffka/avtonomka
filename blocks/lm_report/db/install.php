<?php

function xmldb_block_lm_report_install() {
        $sql = "CREATE TABLE {lm_statistics} (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `userid` bigint(10) NOT NULL,
        `date` date NOT NULL,
        `page` varchar(50) NOT NULL,
        `subpage` varchar(50) NOT NULL,
        `time` int(10) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `userid` (`userid`)
        ) ENGINE=InnoDB ";

    $DB->execute($sql);

    return true;
}
