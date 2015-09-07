<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 6/26/2015
 * Time: 2:02 PM
 */

class lm_staff_import extends lm_base_import {

    /**
     * @var lm_unireader
     */
    public $reader = NULL;

    public $iid = 0;

    //public $assignprogramid = 107;
    public $assignprogramid = NULL;
    //public $addtoparterid = 660;
    public $addtoparterid = NULL;

    /**
     * Задайте пароль, который хотите установить для всех сотрудников.
     * После первого входа система попросить сотрудника изменить пароль.
     *
     * @var null
     */
    public static $forcepassword = NULL;

    public static $force_pswd_only_for_newcomer = true;

    public static $send_email = true;

    /**
     * Учитывать оргструктуру?
     *
     * @var bool
     */
    public $accept_org = false;


    protected static $start_from = 4;

    protected static $k_idnum = 1;
    protected static $k_fullname = 2;
    protected static $k_firstname = NULL;
    protected static $k_lastname = NULL;
    protected static $k_email = 6;
    protected static $k_post = 3; // id 60
    protected static $k_department = 4;
    protected static $k_hiredate = 5; // id 16
    protected static $k_dismissal = NULL;
    protected static $k_password = NULL;
    protected static $k_deleted = NULL;

    protected static $required = array();

    /**
     * @param stored_file|string $file file to upload
     */
    public function __construct($file) {
        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        @set_time_limit(0);
        raise_memory_limit(MEMORY_EXTRA);

        $this->reader = new lm_unireader($file);
        $this->iid = $this->reader->iid;
    }


    public function import($step = NULL) {
        $errors = array(array(), array());
        $out    = array('', '');

        if ($step === 1 || is_null($step)) {
            $this->reader->start();
            $this->k_settings();
            list($errors[0], $out[0]) = $this->step1();
        }

        if ($step === 2 || (is_null($step) && empty($errors[0]))) {
            $this->reader->start();
            $this->k_settings();
            list($errors[1], $out[1]) = $this->step2();
        }

        return array(array_merge($errors[0], $errors[1]), implode($out));
    }

    private function k_settings()
    {
        self::$required = array('k_idnum', 'k_email',
            'k_fullname'=> array('k_lastname', 'k_firstname') // значит k_fullname OR k_lastname AND k_firstname
        );

        if($this->reader && $this->reader->filetype == 'xml'){
            self::$start_from = 0;
            //self::$k_idnum = 'employeecode';
            self::$k_idnum = 'innercode';
            self::$k_email = 'email';
            self::$k_fullname = 'name';
            self::$k_firstname = 'firstname';
            self::$k_lastname = 'lastname';
            self::$k_hiredate = 'hiredate';
            self::$k_dismissal = NULL;

            self::$k_post = NULL;
            self::$k_password = NULL;
            self::$k_deleted = NULL;

            self::$forcepassword = 'Cherkizovo_2015';
            self::$send_email = false;
            $this->addtoparterid = 1;
            $this->assignprogramid = NULL;
            $this->accept_org = true;
        }


    }


    public function step1(){
        $errors = array();

        // Список email'ов
        $emails = array();

        // Список строк, в которых не указан email или имеет не верный формат
        $noemail = array();

        // Список табельных номеров
        $idnumbers = array();

        // Список дублирующих записей
        $dublicates = array();

        $n = 0;
        while ($item = $this->reader->next('employee')) {
            $n ++;

            if($n >= self::$start_from ){
                $email = strtolower(trim($item[self::$k_email]));
                $idnum = trim($item[self::$k_idnum]);

                if(strpos($email, '@') === false){
                    $noemail[$n] = $email;
                }else{
                    if(!isset($emails[$email])){
                        $emails[$email] = $n;
                    }else{
                        $dublicates[$n] = $email;
                    }
                }

                if(!isset($idnumbers[$idnum])){
                    $idnumbers[$idnum] = $n;
                }else{
                    $dublicates[$n] = $idnum;
                }
            }
        }

        $out = "";
        if(!empty($dublicates) || !empty($noemail)){
            if($dublicates){
                $out .= "<h4>В процессе было обнаружено несколько дублирующих строк:</h4><ul>";
                foreach($dublicates as $row=>$val){
                    $out .= "<li>(В строке:{$row}, значение: {$val})</li>";

                }
                $out .= "</ul><br>";
            }

            if($noemail){
                $out .= "<h4>В процессе были обнаружены неверные email адреса:</h4><ul>";
                foreach($noemail as $row=>$val){
                    $out .= "<li>(В строке:{$row}, значение: {$val})</li>";

                }
                $out .= "</ul>";
            }
        }else{
            $out .= '<h4>Проверка табельных номеров и email\'ов завершилась успешно!</h4>';
        }

        return array($errors, $out);
    }


    public function step2(){
        global $CFG, $DB, $USER;

        require_once($CFG->dirroot.'/user/lib.php');

        $previewdata = array();
        $multiusers = array();
        $nodata = array();

        $users = array();
        $errors = array();

        $n = 0;
        $created = 0;
        $added = 0;
        $assigned = 0;
        $activityid = 0;

        while ($rows = $this->reader->next('employee')) {
            $n ++;

            $fullname = trim($rows[self::$k_fullname]);
            $firstname = trim($rows[self::$k_firstname]);
            $lastname = trim($rows[self::$k_lastname]);

            $email = strtr(
                strtolower(trim($rows[self::$k_email], " \t\n\r\0\x0B\xC2\xA0")),
                array(
                    ','       => '.',
                    '/'       => '.',
                    'mailto:' => ''
                )
            );
            if(strpos($email, '@') === false) $email = "";
            $idnum = trim($rows[self::$k_idnum]);

            $hasdata = (!empty($fullname) || !empty($firstname) && !empty($lastname)) && !empty($idnum);


            if( $n >= self::$start_from && $hasdata && !$this->is_user_deleted($rows) ){

                $user = $DB->get_record('user', array("idnumber"=>$idnum), 'id, username, email');

                //TODO: Вынести этот код в отдельный скрипт импорта из csv
                /*if( ! $userid)) {
                    if( ! $userid = $DB->get_field('user', 'id', array("email" => $email))) {
                        $userid = $this->guess_user_by_fullname($fullname);
                    }
                }*/


                $positions = false;
                if($idnum && $this->accept_org){
                    $positions = lm_position::get_staffer_xrefs($idnum);
                }

                $newcomer = false;

                // Пользователя создаем только в том случае, если он есть в оргструктуре
                // т.к. в выгрузке может быть много пользователей не привязанных к оргструктуре
                $has_user_positions = $positions && $this->accept_org;

                if( $user === false ){

                    if( !$this->accept_org || $has_user_positions ) {
                        $new_user = $this->user_data($rows);
                        //TODO: write to log about this
                        if (empty($new_user->email)) continue;
                        $new_user->username = $new_user->email;
                        try {
                            $user = lm_user::create($new_user);
                        } catch (Exception $e) {
                            // TODO: Write to log. can't update user. Usually because of username/email duplicate
                        }
                        if ($user && $user->id) {
                            $newcomer = true;
                            $created++;
                        }
                    }
                }

                if( $user && is_numeric($user->id) ){
                    if( $this->addtoparterid ) {
                        $partner = lm_partner::i($this->addtoparterid);
                        $partner->add_staffer($user->id, self::$send_email, false);
                        $added ++;
                    }

                    try {
                        $this->update_userdata($rows, $user, $newcomer);
                        $users[] = $user->id;
                    } catch(dml_exception $e) {
                        // TODO: Write to log. catnt update user. Usually because of username/email duplicate
                    }
                }

                // Обновляем оргструктуру
                if( $has_user_positions && $user){
                    foreach($positions as $pos_xref){
                        if( !$pos_xref->userid ){
                            $pos_xref->userid = $user->id;
                            $DB->update_record('lm_position_xref', $pos_xref);
                        }
                    }
                }

                if( is_array($user) ){
                    $multiusers[$n] = $user->id;
                }
            }else if($n >= self::$start_from && !$hasdata){
                $nodata[$n] = $fullname;
            }
        }

        lm_position::correct_assigned_roles();

        if($users && $this->assignprogramid && $this->addtoparterid){
            // Создаем активность только один раз за время импорта и только если пользователь разрешил
            $dataobj = new StdClass();
            $dataobj->trainerid = $USER->id;
            $dataobj->type = 'online';
            $dataobj->auditory = 'own';
            $dataobj->programid = $this->assignprogramid;
            $dataobj->maxmembers = 1000;

            $activityid = $DB->insert_record('lm_activity', $dataobj);
            $activity = lm_activity::i($activityid);

            foreach($users as $userid){
                $isrecalculate = false;
                if($assigned >= count($users)-1){
                    $isrecalculate = true;
                }

                $activity->add_member($userid, $this->addtoparterid, $isrecalculate);
                $assigned ++;
            }
        }


        $out = "";
        if($previewdata){
            $table = new html_table();
            $table->attributes['class'] = 'generaltable';
            $table->data = $previewdata;

            $out = '<h4>Не найдено</h4>';
            $out .= html_writer::table($table);
        }else{
            $out .= "<h4>Зарегистрировано пользователей - {$created} <br>";

            if($this->addtoparterid) {
                $out .= " Назначено в сотрудники - {$added}<br>";
            }

            if($this->assignprogramid) {
                $link = '<a href="'.$CFG->wwwroot.'/blocks/manage/?_p=activities&id='.$activityid.'" target="_blank">активность</a>';
                $out .= "Записано на {$link} - {$assigned} сотрудников!<br>";
            }
            $out .= "</h4>";

            if($multiusers){
                $out .= "<h4>В процессе было обнаружено несколько аккаунтов для некоторых пользователей (они не были записаны на активность!):</h4><ul>";
                foreach($multiusers as $row=>$user){
                    $out .= "<li>(Строка:{$row})</li>";
                }
                $out .= "</ul>";
            }

            if($nodata){
                $out .= "<h4>В процессе возникли проблемы с данными в строках:</h4><ul>";
                foreach($nodata as $row=>$userfullname){
                    $out .= "<li>Строка:{$row}</li>";
                }
                $out .= "</ul>";
            }
        }

        return array($errors, $out);
    }

    protected function user_data($row)
    {
        $user = new StdClass();

        if(self::$k_firstname && $row[self::$k_firstname] && self::$k_lastname && $row[self::$k_lastname]){
            $user->firstname = trim($row[self::$k_firstname]);
            $user->lastname = trim($row[self::$k_lastname]);
        }else{
            $fullname = trim($row[self::$k_fullname]);
            $tmp = explode(" ", $fullname);
            $user->lastname = array_shift($tmp);
            $user->firstname = implode(" ", $tmp);
        }

        if( self::$forcepassword ) {
            $user->password = self::$forcepassword;
        }else if( isset($row[self::$k_password]) && $row[self::$k_password] ){
            $user->password = $row[self::$k_password];
        }else{
            $user->password = generate_password(6);
        }

        $user->email = strtr(
            strtolower(trim($row[self::$k_email], " \t\n\r\0\x0B\xC2\xA0")),
            array(
                ','       => '.',
                '/'       => '.',
                'mailto:' => ''
            )
        );

        return $user;
    }

    protected function is_user_deleted($row)
    {
        $deleted = 0;
        if( isset($row[self::$k_deleted]) && strtolower(trim($row[self::$k_deleted])) == 'true' ) {
            $deleted = 1;
        }

        return $deleted;
    }

    protected function update_userdata($row, $user, $isnewcomer=false){
        global $DB;

        date_default_timezone_set("Europe/Moscow");

        $dataobj = $this->user_data($row);

        if (empty($dataobj->email)) return FALSE;

        // меняем логин только если логин совпадает с email
        if ($user->username === $user->email) $dataobj->username = $dataobj->email;
        unset($dataobj->password);
        $dataobj->id = $user->id;
        $dataobj->idnumber = $idnum = trim($row[self::$k_idnum]);

        if( isset($row[self::$k_department]) && $row[self::$k_department] ){
            $dataobj->department = $row[self::$k_department];
        }


        if ( self::$forcepassword && !self::$force_pswd_only_for_newcomer || self::$forcepassword && $isnewcomer ) {
            if( $usernew = $DB->get_record('user', array('id'=>$user->id)) ) {
                //if(self::$send_email) setnew_password_and_mail($usernew);
                $dataobj->password = hash_internal_user_password(self::$forcepassword);
                unset_user_preference('create_password', $user->id);
                set_user_preference('auth_forcepasswordchange', 1, $user->id);
            }
        }

        $dataobj->deleted = $this->is_user_deleted($row);

        $DB->update_record('user', $dataobj);


        $hiredate = NULL;
        if( isset($row[self::$k_hiredate]) ) $hiredate = strtotime(trim($row[self::$k_hiredate]));
        if(  $hiredate ) {
            if ($hiredate) $hiredate = date( "Y-m-d", $hiredate );
            else $hiredate = NULL;

            if ( $lm_user = $DB->get_record('lm_user', array("userid" => $user->id)) ) {
                $lm_user->hiredate = $hiredate;
                $DB->update_record('lm_user', $lm_user);
            } else {
                $dataobj = new StdClass();
                $dataobj->userid = $user->id;
                $dataobj->hiredate = $hiredate;
                $DB->insert_record('lm_user', $dataobj);
            }
        }

        if( isset($row[self::$k_post]) && $row[self::$k_post] ) {
            if ($positiondata = $DB->get_records('user_info_data', array("userid" => $user->id, "fieldid" => 60), 'sequence DESC')) {
                $positiondata = array_shift($positiondata);
                $positiondata->data = $row[self::$k_post];
                $DB->update_record('user_info_data', $positiondata);
            } else {
                $dataobj = new StdClass();
                $dataobj->userid = $user->id;
                $dataobj->fieldid = 60;
                $dataobj->data = $row[self::$k_post];
                $dataobj->sequence = 1;
            }
        }
    }

}