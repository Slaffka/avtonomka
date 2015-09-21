<?php

require_once('../../config.php');
require_once($CFG->libdir . '/adminlib.php');
require_once('lib/func.php');

echo $OUTPUT->header();

$time = !empty($_GET['date']) ? $_GET['date'] : time();
$res = $DB->get_records('statistics', array('date' => date("Y:m:d", $time)));

$html = "<div class='stat-page'><h1 style='text-align:center; margin:20px 0'>Отчет о посещении сотрудниками модулей</h1>";
$html .= "<div style='width:100%'>" . getDays($time) . "<div style='clear:both'></div></div>";
$html .= "<div class='table-responsive'><table class='table current-rating table-myteam'><tr style='background-color: #ddf;'><th style='text-align:center; font-weight:bold; vertical-align:middle'>№</th><th   style='text-align:center; font-weight:bold; vertical-align:middle'>ФИО</th>
                        <th style='text-align:center; font-weight:bold; vertical-align:middle'>Руководитель</th><th style='text-align:center; font-weight:bold; vertical-align:middle'>Город</th><th style='text-align:center; font-weight:bold; vertical-align:middle'>Регион</th><th style='text-align:center; font-weight:bold; vertical-align:middle'>Название модуля</th>
                        <th style='text-align:center; font-weight:bold; vertical-align:middle'>Раздел модуля</th><th style='text-align:center; font-weight:bold; vertical-align:middle'>Время в модуле</th></tr>";
$key = 0;

foreach ($res as $val) {
    //  var_dump($val);
    $name = $DB->get_record('user', array('id' => $val->userid));
    $time = changeTime($val->time);
    $city = enterEmpty($DB->get_record_sql("SELECT r.name, r.parentid FROM mdl_lm_region as r INNER JOIN (mdl_lm_position as p INNER JOIN (mdl_user as u INNER JOIN mdl_lm_position_xref as px ON u.id = px.userid) ON p.id = px.posid) ON p.cityid = r.id and u.id = " . $val->userid));
    $cheif = enterEmpty($DB->get_record_sql("SELECT CONCAT_WS(' ', u2.lastname, u2.firstname) as name FROM mdl_user as u2 INNER JOIN (mdl_lm_position as p INNER JOIN (mdl_user as u INNER JOIN mdl_lm_position_xref as px ON u.id = px.userid) ON p.id = px.posid) ON p.parentid = u2.id and u.id = " . $val->userid));
// определяем город
    if ($city->parentid) {
        $region = $DB->get_record('lm_region', array('id' => $city->parentid))->name;
    } else {
        $region = '---';
    }
// определяем название курсов
    if (intval($val->page) != 0) {
        $page = $DB->get_record('course', array('id' => $val->page))->fullname;
    } else {
        $page = $val->page;
    }

    $key++;
    $html .= <<<HTML
                <tr>
                        <td>$key</td>
                        <td style="text-align:left">
                                <a href="/user/profile.php?id=1785">
                                        <img src="/theme/image.php/cherkizovo/core/1442238974/u/f1" class="userpicture defaultuserpic">
                                </a> 
                                <a class="table-user" href="/blocks/manage/?_p=profile&subpage=index&details=lm_profile_mini&id={$val->userid}">
                                   {$name->lastname} {$name->firstname}
                                </a>
                        </td>        
                        <td>{$cheif}</td>
                        <td>{$city}</td>
                        <td>{$region}</td>
                        <td>{$page}</td>
                        <td>{$val->subpage}</td>
                        <td>{$time}</td>
                </tr>
HTML;
}
$html .= "</table></div><div>";

echo $html;
admin_externalpage_setup('phpinfo');

echo $OUTPUT->footer();
