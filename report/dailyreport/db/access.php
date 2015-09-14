<?php
/**
 * Capabilities
 *
 * Defines capablities related to dailyreport
 *
 * @package    report_dailyreport
 * @copyright  2015 Pinta webware
 * @license    All rights reserved
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'report/dailyreport:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),

        'clonepermissionsfrom' => 'coursereport/log:view',
    ),
);