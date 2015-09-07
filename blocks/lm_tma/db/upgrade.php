<?php
/**
 * Handles upgrading instances feedback block.
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_lm_tma_upgrade($oldversion, $block)
{
    global $DB, $CFG;

    /* if ( $oldversion < 2015071000 ) {
        $sql = "UPDATE mdl_block SET name = 'lm_tma' WHERE id = 44";
        $DB->execute($sql);
    }*/

    if ($oldversion < 2015071303) {
        $sql = 'ALTER TABLE {lm_tma} ADD code VARCHAR(32) NOT NULL AFTER id, ADD INDEX code (code)';
        $DB->execute($sql);

        $sql = 'ALTER TABLE {lm_tma_results} ADD UNIQUE result (tmaid, posxrefid)';
        $DB->execute($sql);
    }

    if ( $oldversion < 2015071307 ) {

        $DB->execute("DROP TABLE {lm_tma}");

        $DB->execute("CREATE TABLE IF NOT EXISTS {lm_tma} (
            `id` bigint(10) NOT NULL,
            `code` varchar(32) COLLATE utf8_unicode_ci NOT NULL,
            `reward` bigint(10) NOT NULL DEFAULT '0',
            `start` date DEFAULT NULL,
            `end` date DEFAULT NULL,
            `descr` longtext COLLATE utf8_unicode_ci NOT NULL,
            `title` varchar(255) COLLATE utf8_unicode_ci NOT NULL DEFAULT '0'
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=5 ");
        $DB->execute("INSERT INTO {lm_tma} (`id`, `code`, `reward`, `start`, `end`, `descr`, `title`) VALUES
            (1, '', 50, '2015-07-13', '2015-08-13', 'При единовременной покупке всех 3 SKU акционного ассортимента -  участник получает подарок  - колбаса Докторская Оригинальная, 500 г', 'Покупает три – получает четыре'),
            (2, '', 80, '2015-06-13', '2015-07-20', 'Описание Акции 2', 'Акция 2'),
            (3, '', 40, '2015-07-14', '2015-07-25', 'Описание Акции 3', 'Акция 3'),
            (4, '', 55, '2015-06-04', '2015-07-02', 'Описание Акции 4', 'Акция 4')");
        $DB->execute("ALTER TABLE {lm_tma} ADD PRIMARY KEY (`id`), ADD KEY `code` (`code`)");


        $DB->execute("DROP TABLE {lm_tma_area}");

        $DB->execute("CREATE TABLE IF NOT EXISTS `mdl_lm_tma_area` (
            `tmaid` int(10) NOT NULL DEFAULT '0',
            `areaid` bigint(10) NOT NULL,
            `toid` bigint(10) NOT NULL DEFAULT '0'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci");
        $DB->execute("INSERT INTO `mdl_lm_tma_area` (`tmaid`, `areaid`, `toid`) VALUES
            (1, 17, 17),
            (2, 17, 17),
            (3, 17, 17),
            (4, 17, 16),
            (4, 17, 17)");
        $DB->execute("ALTER TABLE `mdl_lm_tma_area`  ADD PRIMARY KEY (`tmaid`,`toid`,`areaid`), ADD KEY `areaid` (`areaid`), ADD KEY `toid` (`toid`)");


        $DB->execute("DROP TABLE {lm_tma_results}");
        $DB->execute("CREATE TABLE IF NOT EXISTS `mdl_lm_tma_results` (
            `id` bigint(10) NOT NULL,
            `tmaid` bigint(10) NOT NULL,
            `posxrefid` bigint(10) NOT NULL DEFAULT '0',
            `plan` bigint(10) NOT NULL DEFAULT '0',
            `fact` bigint(10) DEFAULT '0'
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=3") ;

        $DB->execute("INSERT INTO `mdl_lm_tma_results` (`id`, `tmaid`, `posxrefid`, `plan`, `fact`) VALUES
            (1, 1, 80, 10, 7),
            (2, 2, 80, 10, 2)");
        $DB->execute("ALTER TABLE `mdl_lm_tma_results`  ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `result` (`tmaid`,`posxrefid`)");

    }

    if ( $oldversion < 2015071308 ) {

        $DB->execute("ALTER TABLE {lm_tma} CHANGE `id` `id` BIGINT(10) NOT NULL AUTO_INCREMENT");
        $DB->execute("ALTER TABLE {lm_tma_results} CHANGE `id` `id` BIGINT(10) NOT NULL AUTO_INCREMENT");
    }

    if ( $oldversion < 2015071309 ) {

        $DB->execute("INSERT INTO {lm_bank_channel} (`id`, `code`, `blockid`) VALUES (NULL, 'tma', '77')");

    }

    return true;
}