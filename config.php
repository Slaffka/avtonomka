<?php  // Moodle configuration file

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = 'mysqli';
$CFG->dblibrary = 'native';
$CFG->dbhost    = '127.0.0.1';
$CFG->dbname    = 'dev_moodle_pixy_pro';
$CFG->dbuser    = 'moodle';//'cherkizovo';
$CFG->dbpass    = '1N8r1U1o';//'cherkizovo';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '3306',
  'dbsocket' => '',
);

$CFG->wwwroot   = 'http://moodle.pixy.pro';//'http://192.168.0.101:2222';

$CFG->dataroot  = __DIR__ . '/../moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;


require_once(dirname(__FILE__) . '/lib/setup.php');

require_once(dirname(__FILE__) . '/blocks/manage/route.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
