<?php
// Не подключайте здесь config.php, т.к это приведет к ошибке (этот файл подключается только из config.php)


// Автозагрузка классов
spl_autoload_register(function ($class) {
    global $CFG;

    $filename = $class;
    if( strpos($class, 'block_manage') !== false ){
        $filename = str_replace('block_manage_', '', $class);
        $path = $CFG->dirroot.'/blocks/manage/classes/renderers/' . $filename . '.php';
        if(file_exists($path)) require_once($path);

    }else if(strpos($class, 'block_lm') !== false){
        $filename = str_replace('block_lm_', '', $class);
        $parts = explode('_', $filename);
        if( isset($parts[0]) ){
            $path = $CFG->dirroot.'/blocks/lm_'.$parts[0].'/classes/renderers/'. implode('_', $parts) .'.php';
            if(file_exists($path)){
                require_once($path);
            }
        }
    }else{
        $c = explode('_', $class);
        if ( isset($c[0]) && $c[0] === 'lm' ) {
            $path = $CFG->dirroot.'/blocks/manage/classes/' . $filename . '.php';
            if(file_exists($path)){
                require_once($path);
            }else{
                $i = 1;
                $path = $CFG->dirroot.'/blocks/lm_'.$c[$i++];
                while($i < count($c) && !file_exists($path . '/classes/' . $filename . '.php')) $path .= '_'.$c[$i++];
                if(file_exists($path)) {
                    require_once($path . '/classes/' . $filename . '.php');
                }
            }
        }
    }
});


$isadmin = false;
if($CFG->siteadmins){
    $admins = explode(',', $CFG->siteadmins);
    if(in_array($USER->id, $admins)){
        $isadmin = true;
    }
}


// Переключение темы оформления в зависимости от глобальной группы пользователя
global $DB, $USER, $CFG, $SESSION;
if( isset($DB) && isset($USER) && $USER->id != 0 && !isguestuser() ) {
    if( !isset($SESSION->cohort) ) {
        $sql = "SELECT c.idnumber
            FROM {cohort} c
            JOIN {cohort_members} cm ON c.id=cm.cohortid
            WHERE cm.userid={$USER->id}";
        $cohort = $DB->get_field_sql($sql);
        $SESSION->cohort = $cohort;
        if ($cohort) {
            $CFG->theme = $cohort;
        }
    }else if( $SESSION->cohort ){
        $CFG->theme = $SESSION->cohort;
    }

}


if(!empty($USER)) {
    global $USER, $SESSION, $CFG, $SCRIPT, $DB;

    $uri = "";
    if (isset($_SERVER['REQUEST_URI'])) $uri = $_SERVER['REQUEST_URI'];


    // Если пользователь авторизован под другим пользователем и нажал "выйти", возвращаем его сессию
    if( \core\session\manager::is_loggedinas() && $SCRIPT == "/course/loginas.php" && !isset($_GET["user"]) ) {
        if( $user = $DB->get_record('user', array('id' => $USER->realuser)) ) {
            \core\session\manager::set_user($user);
            redirect( $CFG->wwwroot.'/user/view.php?id='.$user->id );
        }
    }

    // Сразу после авторизации переадресуем на его профиль или страницу, которую он запрашивал.
    // При переходе на главную страницу, переадресуем в его профиль или на админку (если это админ).
    $justloggedin = isset($_SERVER['SCRIPT_NAME']) && $_SERVER['SCRIPT_NAME'] == '/login/index.php';
    if ( $USER->id > 1 && ($justloggedin || $uri == '/course/view.php?id=1' || $SCRIPT == '/index.php') ) {
        if( $isadmin ){
            if($CFG->dbname == 'qiwi'){// временная заглушка
                $urltogo = $CFG->wwwroot . '/blocks/manage/?_p=lm_qiwistart';
            }else {
                $urltogo = $CFG->wwwroot . "/blocks/manage/?_p=admin";
            }
        }else {
            if($CFG->dbname == 'qiwi'){// временная заглушка
                $urltogo = $CFG->wwwroot . '/blocks/manage/?_p=lm_qiwistart';
            }else{
                $urltogo = $CFG->wwwroot . '/user/view.php?id=' . $USER->id;
            }
            if (isset($SESSION->wantsurl) && (strpos($SESSION->wantsurl, $CFG->wwwroot) === 0
                    || strpos($SESSION->wantsurl, str_replace('http://', 'https://', $CFG->wwwroot)) === 0)
            ) { // Страница находится на этом же домене

                $tmp = parse_url($SESSION->wantsurl);
                if (!empty($tmp['path']) && $tmp['path'] != '/') {
                    // Переадресуем на любую страницу на этом сайте, кроме главной и авторизации
                    $urltogo = $SESSION->wantsurl;
                }
            }
        }

        unset($SESSION->wantsurl);
        redirect($urltogo);
    }


    $id = optional_param('id', $USER->id, PARAM_INT);

    // Никто, кроме самого юзера, не должен попадать на его дашбоард
    $_p = optional_param('_p', '', PARAM_TEXT);
    $details = optional_param('details', '', PARAM_TEXT);
    if ($_p === 'profile' && $details !== 'lm_personal' && $USER->id != $id) {
        header("Location: {$CFG->wwwroot}/blocks/manage/?_p=lm_personal&id={$id}");
    }

    if (strpos($uri, '/user/profile.php') !== false) {
        $params = "";
        if ($id > 0 && $USER->id !== $id) {
            $params = "&id=" . $id;
        }

        header("Location: {$CFG->wwwroot}/blocks/manage/?_p=profile{$params}");
    }


    // TODO: переадресовывать только если пользователь является сотрудником (убрать запрос, функция уже есть в blocks/manage/lib.php - is_staffer() )
    $isstaffer = $DB->record_exists('lm_partner_staff', array('userid'=>$USER->id));
    if( $isstaffer && !$isadmin ){
        $courseid = 0;

        $url = new moodle_url($uri);
        if( strpos($uri, '/course/') !== false ){
            $courseid = $url->get_param('id');
        }else if( preg_match('|\/mod\/(\w*)\/view.php|', $uri, $matches) ){
            $mods = array('quiz', 'feedback');
            $modname = $matches[1];
            $modid = (int)$url->get_param('id');
            if( $modid && in_array($modname, $mods) ) {
                if ($cm = get_coursemodule_from_id($modname, $modid)) {
                    $courseid = $cm->course;
                }
            }
        }else if( strpos($uri, '/mod/feedback/complete.php') !== false ){
            $modid = (int)$url->get_param('id');
            if(!$modid){
                include($CFG->dirroot.'/mod/feedback/complete.php');
                $courseid = optional_param('courseid', 0, PARAM_INT);
            }
        }

        if( $courseid ){
            header("Location: {$CFG->wwwroot}/blocks/manage/?_p=courseplayer&courseid={$courseid}");
            die();
        }
    }
}



