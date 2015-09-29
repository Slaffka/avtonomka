<?php

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('lib/func.php');
$PAGE->requires->js('/blocks/lm_report/js/custom.js', true);
echo $OUTPUT->header();

$time = !empty($_GET['date']) ? $_GET['date'] : time();
$res = $DB->get_records('statistics', array('date' => date("Y:m:d", $time)));
$res = getSortData($res);
$html = "<div class='stat-page'><h1 style='text-align:center; margin:20px 0'>Отчет о посещении сотрудниками модулей</h1>";
$html .= "<div style='width:100%'>" . getDays($time) . "<div style='clear:both'></div></div>";
$html .= "<div class='table-responsive'><table class='table current-rating table-myteam'><tr style='background-color: #ddf;'><th></th><th   style='text-align:center; font-weight:bold; vertical-align:middle'>ФИО</th>
                        <th style='text-align:center; font-weight:bold; vertical-align:middle'>Руководитель</th><th style='text-align:center; font-weight:bold; vertical-align:middle'>Город</th><th style='text-align:center; font-weight:bold; vertical-align:middle'>Регион</th><th style='text-align:center; font-weight:bold; vertical-align:middle'>Название модуля</th>
                        <th style='text-align:center; font-weight:bold; vertical-align:middle'>Время в модуле</th></tr>";

foreach ($res as $key => $val) {
    $name = $DB->get_record('user', array('id' => $val['userid']));
    $time = changeTime($val['time_total']);
    $city = $DB->get_record_sql("SELECT r.name, r.parentid FROM mdl_lm_region as r INNER JOIN (mdl_lm_position as p INNER JOIN (mdl_user as u INNER JOIN mdl_lm_position_xref as px ON u.id = px.userid) ON p.id = px.posid) ON p.cityid = r.id and u.id = " . $val['userid']);
    $cheifData = $DB->get_record_sql("SELECT CONCAT_WS(' ', u.lastname, u.firstname) as name, u.id FROM mdl_user as u2 INNER JOIN (mdl_lm_position_xref as px2 INNER JOIN (mdl_lm_position as p INNER JOIN (mdl_user as u INNER JOIN mdl_lm_position_xref as px ON u.id = px.userid) ON p.parentid = px.posid) ON px2.id = p.id) ON u2.id = px2.userid AND u2.id = " . $val['userid']);
    $cheif = enterEmpty($cheifData, 'name', '23');
    $region = enterEmpty($DB->get_record('lm_region', array('id' => $city->parentid)));
    $city = enterEmpty($city);

    $html .= <<<HTML
                <tr>
                        <td><img value="$key" class="parent" src="images/plus.png"></td>
                        <td style="text-align:left">
                                <a href="/user/profile.php?id={$val['userid']}">
                                        <img src="/theme/image.php/cherkizovo/core/1442238974/u/f1" class="userpicture defaultuserpic">
                                </a> 
                                <a class="table-user" href="/blocks/manage/?_p=profile&subpage=index&details=lm_profile_mini&id={$val['userid']}">
                                   {$name->lastname} {$name->firstname}
                                </a>
                        </td>        
                        <td>{$cheif}</td>
                        <td>{$city}</td>
                        <td>{$region}</td>
                        <td>{$val['module_name']}</td>                     
                        <td>{$time}</td>
                </tr>
HTML;
    foreach ($val['detail'] as $v){
        $time = changeTime($v['time']);
        $html .= "<tr class='child child".$key."'><td colspan='5'></td><td>{$v['section']}</td><td>{$time}</td></tr>";
        if(count($val['detail'])%2){
           $html .= "<tr class='child child".$key."'><td colspan='7'></td></tr>";
        }
    }
}
$html .= "</table></div><div>";

echo $html;
admin_externalpage_setup('phpinfo');
echo $OUTPUT->footer();