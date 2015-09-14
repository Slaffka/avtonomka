<?php
/**
 * Daily report
 *
 * @package    report
 * @subpackage dailyreport
 * @copyright  2015 Pinta webware
 * @license    All rights reserved
 */
require_once __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config.php';
require_once($CFG->libdir.'/adminlib.php');
$PAGE->set_pagelayout('admin');
$PAGE->set_title('Page title');
$PAGE->set_heading('Heading');
echo $OUTPUT->header();