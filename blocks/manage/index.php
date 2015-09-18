<?php
require_once('../../config.php');
require_once('lib.php');
//var_dump(setcookie ('title', lm_renderer::get($page)->pagename));



global $OUTPUT, $PAGE, $DB;

$PAGE->requires->js('/blocks/lm_report/js/statistics_include.js', true);

///var/www/moodle.loc/cherkizovo/blocks/manage/classes/renderers/courseplayer_renderer.php
lm_ajaxrouter::try_route();

if($do = optional_param('_do', '', PARAM_TEXT)){
    switch($do){
        case 'exel_export_partners':
            $search = optional_param('search', '', PARAM_TEXT);
            lm_exel_export::i()->partners($search);
            break;
        case 'exel_export_activities':
            $q = optional_param('search', '', PARAM_TEXT);
            $startdate = optional_param('startdate', '', PARAM_TEXT);
            $enddate = optional_param('enddate', '', PARAM_TEXT);
            $type = optional_param('type', '', PARAM_TEXT);
            $state = optional_param('state', '', PARAM_TEXT);
            $startdate = strtotime($startdate);
            $enddate = strtotime($enddate);

            lm_exel_export::i()->activities($type, $state, $q, $startdate, $enddate);
            break;
        case 'exel_export_report':
            $type = optional_param('type', '', PARAM_TEXT);
            $datefrom = strtotime(optional_param('datefrom', '', PARAM_TEXT));
            $dateto = strtotime(optional_param('dateto', '', PARAM_TEXT));
            $filter = optional_param('filter', 0, PARAM_RAW);

            if($type && $type == 'partner'){
                if(isset($filter['partner']) && $filter['partner']) {
                    lm_exel_export::i()->report_partner($filter, $datefrom, $dateto);
                }
            }else if($type && $type == 'trainer'){
                if(isset($filter['trainer']) && $filter['trainer']) {
                    lm_exel_export::i()->report_trainer($filter, $datefrom, $dateto);
                }
            }else if($type && $type == 'tm'){
                $tm = isset($filter['tm']) && $filter['t'] ? $filter['tm']: 0;
                $regions = optional_param('regions', 0, PARAM_SEQUENCE);
                lm_exel_export::i()->report_tm($filter['tm'], $regions);

            }else if($type && $type == 'staffer'){
                $partnerid = isset($filter['partner']) ? $filter['partner']: 0;
                $userid = isset($filter['user']) ? $filter['user']: 0;

                if($partnerid && $userid) {
                    lm_exel_export::i()->report_staffer($partnerid, $userid);
                }
            }
            break;
    }

    die();
}


$page = optional_param('_p', 'activities', PARAM_TEXT);
$PAGE->set_pagelayout('standard');
lm_renderer::get($page)->display();

//var_dump(lm_renderer::get($page)->pagename);



