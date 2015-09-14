<?php
/**
 * Links and settings
 *
 * Contains settings used by daily report.
 *
 * @package    report_dailyreport
 * @copyright  2015 Pinta webware
 * @license    All rights reserved
 */

defined('MOODLE_INTERNAL') || die;

// Just a link to course report.
$ADMIN->add('reports', new admin_externalpage('reportdailyreport', get_string('dailyreport', 'admin'),
    $CFG->wwwroot . "/report/dailyreport/index.php", 'report/dailyreport:view'));

// No report settings.
$settings = null;