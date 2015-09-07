<?php
global $CFG;

require_once('thirdparty/smarty/Smarty.class.php');
require_once($CFG->libdir.'/tablelib.php');

if(empty($CFG->block_manage_studentroleid) || empty($CFG->block_manage_stafferroleid) ||
    empty($CFG->block_manage_pamroleid) || empty($CFG->block_manage_tmroleid) ||
    empty($CFG->block_manage_trainerroleid) || empty($CFG->block_manage_resproleid) ||
    empty($CFG->block_manage_reproleid)
){
    print_error('errorsinsettings', 'block_manage');
}




//////////////////////// ПОЛЬЗОВАТЕЛИ ///////////////////////////////////////
function get_userlist($q){
    global $DB;

    $sql = "SELECT u.*
                  FROM {user} u
                 WHERE (u.firstname LIKE '%{$q}%' OR u.lastname LIKE '%{$q}%') AND u.deleted=0 AND u.id != 1
                 ORDER BY u.lastname ASC
				 LIMIT 0, 50";

    return $DB->get_records_sql($sql);
}

/**
 * Вовращает список пользователей по id роли
 *
 * @param $q - слово по которому будет отфильтрован список
 * @param $roleid
 * @return array
 */
function get_userlist_by_role($q, $roleid){
    global $DB;

    $sql = "SELECT u.*
                  FROM {role_assignments} ra
                  JOIN {user} u ON u.id = ra.userid
                 WHERE ra.roleid = {$roleid} AND ra.contextid = 1 AND (u.firstname LIKE '{$q}%' OR u.lastname LIKE '{$q}%')
                 ORDER BY u.lastname ASC";


    return $DB->get_records_sql($sql);
}

// Будьте внимательны при рефакторинге функций, представленных ниже (get_***_list), т.к. они вызываются через
// переменные (variable function)

/**
 * Возвращает список менеджеров по работе с партнерами (ПАМ)
 *
 * @param $q - слово по которому будет отфильтрован список
 * @return array
 */
function get_pam_list($q){
    global $CFG;

    return get_userlist_by_role($q, $CFG->block_manage_pamroleid);
}

/**
 * Возвращает список территориальных менеджеров (ТМ)
 * @param $q - слово по которому будет отфильтрован список
 *
 * @return array
 */
function get_tm_list($q){
    global $CFG;

    return get_userlist_by_role($q, $CFG->block_manage_tmroleid);
}

/**
 * Возвращает список тренеров
 *
 * @param $q - слово по которому будет отфильтрован список
 * @return array
 */
function get_trainer_list($q){
    global $CFG;

    return get_userlist_by_role($q, $CFG->block_manage_trainerroleid);
}

/**
 * Возвращает список ответственных за партнера
 *
 * @param $q - слово по которому будет отфильтрован список
 * @return array
 */
function get_resp_list($q){
    global $CFG;

    return get_userlist_by_role($q, $CFG->block_manage_resproleid);
}

/**
 * Возвращает список контактных лиц партнера
 *
 * @param $q - слово по которому будет отфильтрован список
 * @param $partnerid
 * @return array
 */
function get_rep_list($q, $partnerid){
    return lm_partner::i($partnerid)->get_staffers(false, $q);
}



//////////////////////// ПАРТНЕРЫ //////////////////////////////////////////

/**
 * Возвращает массив партнеров из регионов, доступных пользователю.
 * В качестве ключей - id партнера, в качестве значений - название партнера.
 *
 * @return array
 */
function get_partners_menu(){
    global $DB, $USER;

    $result = array();

    if(lm_user::is_trainer() || lm_user::is_admin()){
        $regions = get_my_regions();
        $where = 'WHERE lp.companyid != 0';
        if($regions && $regions != 'all'){
            $where = " AND regionid IN({$regions})";
        }

        $sql = "SELECT lp.id, CONCAT_WS('', lc.name, ' (', lp.name, ')') as name
                      FROM {lm_partner} lp
                      LEFT JOIN {lm_company} lc ON lp.companyid=lc.id
                      $where
                      ORDER BY lc.name ASC, lp.name ASC";

        $result = $DB->get_records_sql_menu($sql);
    }

    if(lm_user::is_responsible()){
        $tmp = block_manage_responsible::i()->my_partners_menu();
        $result = array_merge_by_keys($result, $tmp);
    }

    /*if(is_rep()){
        $sql = "SELECT lp.id, CONCAT_WS('', lc.name, ' (', lp.name, ')') as name
                      FROM {lm_partner} lp
                      LEFT JOIN {lm_company} lc ON lp.companyid=lc.id
                      WHERE repid={$USER->id}";

        $tmp = $DB->get_records_sql_menu($sql);
        $result = array_merge_by_keys($result, $tmp);
    }*/

    if(lm_user::is_tm()){
        $sql = "SELECT lp.id, CONCAT_WS('', lc.name, ' (', lp.name, ')') as name
                      FROM {lm_partner} lp
                      JOIN {lm_place} lpl ON lp.id=lpl.partnerid
                      LEFT JOIN {lm_company} lc ON lp.companyid=lc.id
                      WHERE lpl.tmid={$USER->id}";

        $tmp = $DB->get_records_sql_menu($sql);
        $result = array_merge_by_keys($result, $tmp);
    }

    return $result;
}



function get_partners($q, $sortby='', $pagenum=0, $perpage=0){
    global $DB;

    $select = get_partners_select($q);

    $sql = "SELECT lp.*, lc.name as companyname, lc.type, lc.hide
                      FROM {lm_partner} lp
                      LEFT JOIN {lm_company} lc ON lp.companyid=lc.id
                      $select
                      {$sortby}";

    return $DB->get_records_sql($sql, array(), $pagenum*$perpage, $perpage);
}

function count_partners($q){
    global $DB;

    $select = get_partners_select($q);

    $sqlcount = "SELECT COUNT(lp.id)
                      FROM {lm_partner} lp
                      LEFT JOIN {lm_company} lc ON lp.companyid=lc.id
                      $select";

    return $DB->count_records_sql($sqlcount, array());
}

function get_partners_select($q){
    $select='';
    if($q){
        $select = "lp.name LIKE '%$q%' OR lc.name LIKE '%$q%'";
    }

    if($regions = get_my_regions()) {
        if ($regions != 'all') {
            if ($select) {
                $select .= " AND ";
            }

            $select .= " lp.regionid IN ({$regions})";
        }
    }else if(lm_user::is_responsible()){
        if($partners = block_manage_responsible::i()->my_partners_menu()){
            $partnerids = "";
            foreach($partners as $partnerid=>$name){
                if($partnerids) $partnerids .= ",";
                $partnerids .= $partnerid;
            }

            if ($select) {
                $select .= " AND ";
            }

            $select .= "lp.id IN({$partnerids})";
        }
    }

    if($select){
        $select = 'WHERE '.$select;
    }

    return $select;
}

/**
 * Возвращает список территориальных менеджеров (ТМов)
 *
 * @return array
 */
function get_tm_menu($regions=array()){
    global $DB;

    $where = $regions ? 'lp.regionid IN('.$regions.')' : "1";

    /*$sql = "SELECT u.id, CONCAT_WS('', u.lastname, ' ', u.firstname) as name
                  FROM {lm_partner} lp
                  JOIN {lm_place} lpl ON lp.id=lpl.partnerid
                  JOIN {lm_company} lc ON lp.companyid=lc.id AND lc.type='own'
                  JOIN {user} u ON u.id=lpl.tmid
                  WHERE $where
                  GROUP BY u.id
                  ORDER BY u.lastname";*/

    $sql = "SELECT u.id, CONCAT_WS('', u.lastname, ' ', u.firstname) as name
                  FROM {lm_partner} lp
                  JOIN {lm_place} lpl ON lp.id=lpl.partnerid
                  JOIN {user} u ON u.id=lpl.tmid
                  WHERE $where
                  GROUP BY u.id
                  ORDER BY u.lastname";

    return $DB->get_records_sql_menu($sql);
}


//////////////////////// ТРЕНЕРЫ //////////////////////////////////////////

/**
 * Возвращает массив тренеров. В качестве ключей - id тренера, в качестве значений - ФИО
 *
 * @return array
 */
function get_trainers_menu(){
    global $DB;

    $sql = "SELECT u.id, CONCAT(u.lastname, ' ', u.firstname) as fullname
                  FROM {role_assignments} ra
                  JOIN {user} u ON u.id = ra.userid
                 WHERE ra.roleid = 9 AND ra.contextid = 1
                 ORDER BY u.lastname ASC";

    return $DB->get_records_sql_menu($sql);
}



//////////////////////// АКТИВНОСТИ //////////////////////////////////////////

function get_activities($type='', $state='', $q='', $startdate=0, $enddate=0, $sortby='', $pagenum=0, $perpage=0){
    global $DB;

    list($select, $join) = get_activities_sqlparams($type, $state, $q, $startdate, $enddate);

    $sql = "SELECT la.*, lp.courseid, CONCAT(u.lastname, ' ', u.firstname) as trainerfio, lp.name
                      FROM {lm_activity} la
                      LEFT JOIN {lm_program} lp ON la.programid=lp.id
                      LEFT JOIN {user} u ON la.trainerid=u.id
                      $join
                      WHERE $select
                      $sortby";

    return $DB->get_records_sql($sql, array(), $pagenum*$perpage, $perpage);
}

function get_activities_count($type='', $state='', $q='', $startdate=0, $enddate=0){
    global $DB;

    list($select, $join) = get_activities_sqlparams($type, $state, $q, $startdate, $enddate);

    $sql = "SELECT la.id
                      FROM {lm_activity} la
                      LEFT JOIN {lm_program} lp ON la.programid=lp.id
                      LEFT JOIN {user} u ON la.trainerid=u.id
                      $join
                      WHERE $select";

    return count($DB->get_records_sql($sql));
}

function get_activities_sqlparams($type='', $state='', $q='', $startdate=0, $enddate=0){
    $conditions = array();
    $select = $join = '';
    if(!$state && !$type){
        $type = 'auditory';
    }

    if($type){
        $conditions[] = "la.type LIKE '".$type."' ";
    }

    if($q){
        $conditions[] = "(u.lastname LIKE '%$q%' OR u.firstname LIKE '%$q%' OR lp.name LIKE '%$q%' ) ";
    }

    if($startdate){
        $conditions[] = "la.startdate > {$startdate}";
    }

    if($enddate){
        $conditions[] = "la.enddate < {$enddate}";
    }

    //Если текущий пользователь - контактное лицо партнера
    $currenttime = time();
    $isrep = lm_user::is_rep();

    // ... и просматривает предстоящие тренинги
    if($isrep && $state == 'planned'){
        // показываем только те тренинги, программа в которых соотв. назначенным партнеру
        $partnerid = get_my_company_id();
        $ids = lm_partner::i($partnerid)->get_appointed_programs_ids();
        $ids = implode(',', $ids);
        if($ids){
            $conditions[] = "la.programid IN({$ids})";
        }
        $conditions[] = "la.startdate > {$currenttime}";

        // ... и просматривает завершенные тренинги
    }else if($isrep && $state == 'finished'){
        // показываем только те тренинги, в которых участвовали его сотрудники
        $conditions[] = "la.startdate < {$currenttime}";
        $partnerid = get_my_company_id();
        $join = "JOIN (
                        SELECT activityid
                              FROM {lm_activity_request}
                              WHERE partnerid={$partnerid}
                              GROUP BY activityid
                     )lar ON la.id=lar.activityid";
    }

    if($conditions){
        foreach($conditions as $part){
            if($select){
                $select .= ' AND ';
            }
            $select .= $part;
        }
    }

    return array($select, $join);
}

//////////////////////// ПРОГРАММЫ //////////////////////////////////////////

/**
 * Возвращает массив с полным списоком программ. В качестве значений массива - Std-класс со св-ми:
 * id  - Идентификатор программы
 * period  - Период обучения в днях
 * name  - Название программы (курса)
 * courseid  - Идентификатор курса
 *
 *
 * @return array
 */
function get_programs_tree(){
    $result = array();

    if($categories = lm_programs::get_categories_menu()){

        foreach($categories as $categoryid=>$categoryname){
            $object = new StdClass();
            $object->id = $categoryid;
            $object->name = $categoryname;
            $object->programs = array();

            if($programs = get_programs($categoryid)){
                foreach($programs as $program){
                    $program->matrix = array();

                    $object->programs[$program->id] = $program;
                }
            }

            $result[$categoryid] = $object;
        }
    }

    return $result;
}

/**
 * Возвращает массив с полным списком программ. В качестве ключей - идентификатор программы,
 * в качестве значений - название программ (курсов)
 *
 * @return array
 */
function get_programs_list(){
    global $DB;

    $result = array();

    if($categories = $DB->get_records('lm_program', array('parent'=>0), 'name ASC')){

        foreach($categories as $category){
            if($programs = $DB->get_records('lm_program', array('parent'=>$category->id), 'name ASC')){
                $array = array();
                foreach($programs as $program){
                    $array[$category->name][$program->id] = $program->name;
                }
                if($array) $result[] = $array;
            }
        }

    }

    return $result;
}


/**
 * Возвращает полный список програм
 *
 * @param $categoryid
 * @return array
 */
function get_programs($categoryid=0){
    global $DB;

    $condition = "lp.parent != 0";
    if($categoryid) {
        $condition = "lp.parent={$categoryid}";
    }

    $sql = "SELECT lp.*, c.id as courseid
                  FROM {lm_program} lp
                  LEFT JOIN {course} c ON c.id=lp.courseid
                  WHERE {$condition}
                  ORDER BY lp.name ASC";
    return $DB->get_records_sql($sql);
}



//////////////////////// КОМПАНИИ //////////////////////////////////////////

/**
 * Возвращает массив с полным списком компаний. В качестве значений - StdClass.
 *
 * @return array
 */
function get_companies_list(){
    global $DB;

    return $DB->get_records('lm_company', array(), 'name ASC');
}

/**
 *
 * @return array
 */
function get_companies_menu(){
    global $DB;

    return $DB->get_records_menu('lm_company', array(), 'name ASC');
}


//////////////////////// МЕСТА ПРОВЕДЕНИЯ, ТОРГОВЫЕ ТОЧКИ //////////////////////////////////////////
function get_places_menu(){
    global $DB;

    $sql = "SELECT lp.id, CONCAT(lp.name, lp.code, ' (', lr.name, ')') as name
                  FROM {lm_place} lp
                  LEFT JOIN {lm_region} lr ON lr.id=lp.cityid
                  WHERE lp.name != '' OR lp.code != ''
                  ORDER BY lr.name ASC";

    return $DB->get_records_sql_menu($sql);
}

function get_places($type='class', $q='', $partnerid=0){
    global $DB;

    $where = "";
    if($q){
        $where = " AND (lpl.name LIKE '{$q}%' OR lpl.code LIKE '{$q}%' OR lr.name LIKE '{$q}%' OR lc.name LIKE '{$q}%')";
    }

    if($partnerid){
        $where .= " AND lpl.partnerid={$partnerid}";
    }

    $orderby = "lr.name ASC, lpl.name ASC";
    if($type == 'tt'){
        $orderby = "lpl.code ASC";
    }

    $sql = "SELECT lpl.*, lr.name as city, lp.name as partnername, lc.name as companyname
                  FROM {lm_place} lpl
                  LEFT JOIN {lm_region} lr ON lr.id=lpl.cityid
                  LEFT JOIN {lm_partner} lp ON lp.id=lpl.partnerid
                  LEFT JOIN {lm_company} lc ON lc.id=lp.companyid
                  WHERE lpl.type='{$type}' {$where}
                  ORDER BY $orderby";

    return $DB->get_records_sql($sql);
}

//////////////////////// РЕГИОНЫ //////////////////////////////////////////

function get_cityname($cityid){
    global $DB;

    return $DB->get_field('lm_region', 'name', array('id'=>$cityid));
}

function get_regions(){
    global $DB;

    $result = array();

   if($regions = $DB->get_records('lm_region', array('parentid'=>0), 'name ASC')){
       foreach($regions as $region){
           $object = new StdClass();
           $object->name = $region->name;
           $object->cities = array();

           if($cities = $DB->get_records('lm_region', array('parentid'=>$region->id), 'name ASC')){
                foreach($cities as $city){
                    $object->cities[$city->id] = $city->name;
                }
           }

           $result[$region->id] = $object;
       }
   }

    return $result;
}

function get_regions_list(){
    global $DB;

    $result = array();

    if($regions = $DB->get_records('lm_region', array('parentid'=>0), 'name ASC')){
        foreach($regions as $region){
            $array = array();
            if($cities = $DB->get_records('lm_region', array('parentid'=>$region->id), 'name ASC')){
                foreach($cities as $city){
                    if(is_my_region($city->id))
                        $array[$region->name][$city->id] = $city->name;
                }
            }

            if($array)
                $result[] = $array;
        }
    }

    return $result;
}

function get_regions_menu(){
    global $DB;

    return $DB->get_records_menu('lm_region');
}

function get_mainregions_menu(){
    global $DB;

    return $DB->get_records_menu('lm_region', array('parentid'=>0), 'name ASC');
}

/**
 * Возвращает идентификаторы регионов через запятую, в которых текущий пользователь является трениром.
 *
 */
function get_my_regions(){
    global $DB, $USER;


    if(!has_capability('block/manage:allregions', context_system::instance()) ){
        if(!defined('MY_REGIONS')){
            $regions = $DB->get_records_menu('lm_region_trainer', array('trainerid'=>$USER->id), '', 'id, regionid');
            define('MY_REGIONS', $regions);
        }else{
            $regions = MY_REGIONS;
        }

        if(!$regions){
            return '';
        }

        $regions = implode(',', $regions);
    }else{
        $regions = 'all';
    }

    return $regions;
}

/**
 * Возвращает true, если текущий пользователь назначен на регион $regionid
 *
 * @param $regionid
 * @return bool
 */
function is_my_region($regionid){
    global $USER;

    if(lm_user::is_admin()){
        return true;
    }

    if(!defined('MY_REGIONS')){
        $regions = get_my_regions();
        $regions = explode(',', $regions);
    }else{
        $regions = MY_REGIONS;
    }

    return in_array($regionid, $regions);
}

/**
 * Возвращает id партнера, в котором текущий пользователь является представителем (контактным лицом)
 *
 * @return mixed
 */
function get_my_company_id(){
    global $DB, $USER;

    return (int) $DB->get_field('lm_place', 'partnerid', array('type'=>'tt', 'repid'=>$USER->id));
}





//////////////////////// ДРУГОЕ //////////////////////////////////////////
function ru2lat($string){
    $tr = array(
        "ий", "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D","Е"=>"E", "Ё"=> "YO", "Ж"=>"ZH","З"=>"Z","И"=>"I","Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"Kh","Ц"=>"Ts","Ч"=>"Ch","Ш"=>"Sh","Щ"=>"Sch","Ъ"=>"","Ы"=>"Y","Ь"=>"", "Э"=>"E","Ю"=>"Yu","Я"=>"Ya",
        "y", "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=> "yo","ж"=>"zh","з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"kh","ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"", "ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
    );

    return strtr($string, $tr);
}

/**
 * Работает аналогично стандартной функции php array_merge, но числовые значения ключей не сбрасываются
 *
 * @param $array1
 * @param $array2
 * @return mixed
 */
function array_merge_by_keys($array1, $array2){
    if(!empty($array2)) {
        foreach($array2 as $key=>$elem){
            $array1[$key] = $elem;
        }
    }

    return $array1;
}


function calc_date($count){
    $curmonth = (int)date("m");

    $month = $curmonth - $count;
    $year = date("Y") + floor($month/12);
    $month = $month < 0 ? 12+$month: $month%12;

    if( strlen($month) < 2) $month = "0".$month;

    return array($year, $month);
}

/**
 * Преобразует год, месяц, декаду в одно число, характерезующее период
 *
 * @param $year
 * @param $month
 * @param $decade
 * @return string
 */
function make_period($year, $month, $decade){
    $month = strlen($month) < 2 ? "0".$month: $month;
    return $year.$month.$decade;
}


function get_records_array($table, $fields, $conditions=array(), $sort='')
{
    global $DB;
    $result = array();

    $field = (explode(',', str_replace(' ', '', $fields)));
    $fieldscount = count($field);
    if($fieldscount > 4){
        return false;
    }

    if($records = $DB->get_records($table, $conditions, $sort, $fields)){
        foreach($records as $record){
            switch($fieldscount){
                case 1:
                    $result[] = $record->$field[0];
                    break;
                case 2:
                    $result[$record->{$field[1]}] = $record->{$field[0]};
                    break;
                case 3:
                    $result[$record->{$field[1]}][$record->{$field[2]}] = $record->{$field[0]};
                    break;
                case 4:
                    $result[$record->{$field[1]}][$record->{$field[2]}][$record->{$field[3]}] = $record->{$field[0]};

                    break;
            }

        }
    }


    return $result;
}

/**
 * Функция возвращает окончание для множественного числа слова на основании числа и массива окончаний
 * @param int $number Число на основе которого нужно сформировать окончание
 * @param array $endingArray Массив слов или окончаний для чисел (1, 4, 5),
 *        например ['яблоко', 'яблока', 'яблок']
 * @return string
 */
function get_num_ending($number, $endingArray) {
    $number = $number % 100;
    if ($number>=11 && $number<=19) {
        $ending=$endingArray[2];
    }
    else {
        $i = $number % 10;
        switch ($i)
        {
            case (1): $ending = $endingArray[0]; break;
            case (2):
            case (3):
            case (4): $ending = $endingArray[1]; break;
            default: $ending=$endingArray[2];
        }
    }
    return $ending;
}
