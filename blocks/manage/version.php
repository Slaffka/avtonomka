<?php

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2015080600;        // The current plugin version (Date: YYYYMMDDXX)
$plugin->requires  = 2013050100;        // Requires this Moodle version
$plugin->component = 'block_manage';     // Full name of the plugin (used for diagnostics)
$plugin->cron = 300;                    // Устанавливаем минимальное время между запусками крона (5 минут)