<?php

use lm_companies;

require_once $CFG->libdir.'/formslib.php';
require_once($CFG->libdir.'/gradelib.php');

class sales_import_form extends moodleform {
    function definition (){
        $mform =& $this->_form;

        if (isset($this->_customdata)) {  // hardcoding plugin names here is hacky
            $features = $this->_customdata;
        } else {
            $features = array();
        }

        // course id needs to be passed for auth purposes
        $mform->addElement('hidden', 'id', optional_param('id', 0, PARAM_INT));
        $mform->setType('id', PARAM_INT);

        // Restrict the possible upload file types.
        if (!empty($features['acceptedtypes'])) {
            $acceptedtypes = $features['acceptedtypes'];
        } else {
            $acceptedtypes = '*';
        }

        // File upload.
        $mform->addElement('filepicker', 'userfile', 'Загрузите файл в формате csv', null, array('accepted_types' => $acceptedtypes));
        $mform->addRule('userfile', null, 'required');

        $encodings = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'grades'), $encodings);

        $this->add_action_buttons(false, 'Начать импорт');
    }
}


class block_manage_importsales_renderer extends lm_base_import {
    /**
     * TODO: В выгрузке не проработана возможность отслеживания изменений названий торговых точек и их адресов
     */

    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=importsales';
    public $pagename = 'Загрузка данных о продажах';
    public $type = 'manage_importsales';
    public $cvsiid = 0;


    protected static $start_from = 7;
    protected static $row_tm = 2;
    protected static $row_resp = 4;
    protected static $row_month = 7;
    protected static $row_decade = 9;
    protected static $row_company = 10;
    protected static $row_ttcode = 11;
    protected static $row_ttname = 14;
    protected static $row_city = 15;
    protected static $row_address = 17;
    protected static $row_plansales = 18;
    protected static $row_factsales = 19;
    protected static $row_returns = 20;
    protected static $row_stock = 21;

    public function init_page(){
        if(!lm_user::is_admin()){
            die();
        }

        $iid = optional_param('iid', null, PARAM_INT);
        $step = optional_param('step', 1, PARAM_INT);
        if($iid && $step){
            $this->pagename .= " - Шаг ".$step;
        }

        parent::init_page();
        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->js('/blocks/manage/yui/importsales.js');
    }

    public function main_content(){

        if(!lm_user::is_admin()) {
            return 'Ошибка доступа!';
        }

        $this->cvsiid = optional_param('iid', null, PARAM_INT);
        $step = optional_param('step', 1, PARAM_INT);

        $out = '';

        $mform = new sales_import_form($this->pageurl, array('acceptedtypes' => array('.csv', '.txt')));

        // Если CSV файл еще не был отправлен, проверяем наличие загрузки, либо отображаем начальную форму
        // для загрузки файлов
        if (!$this->cvsiid) {
            if ($formdata = $mform->get_data()) {

                // Large files are likely to take their time and memory. Let PHP know
                // that we'll take longer, and that the process should be recycled soon
                // to free up memory.
                @set_time_limit(0);
                raise_memory_limit(MEMORY_EXTRA);

                $text = $mform->get_file_content('userfile');
                $this->cvsiid = csv_import_reader::get_new_iid('sales');
                $csvimport = new csv_import_reader($this->cvsiid, 'sales');
                $csvimport->load_csv_content($text, $formdata->encoding, 'semicolon');
                $csvimport->init();

                $out .= $this->procced($csvimport, $step);

            } else {
                // Показываем стандартную форму загрузки файла
                $out .= $mform->render();
            }
        }else{
            $csvimport = new csv_import_reader($this->cvsiid, 'sales');
            $csvimport->init();
            $out .= $this->procced($csvimport, $step);
        }

        return $out;
    }

    public function procced(csv_import_reader $csvimport, $step=1){
        global $CFG;

        $out = "";
        $error = false;
        if($step == 1 && $result = $this->step1($csvimport)){
            list($error, $out) = $result;
        }

        if( $step == 2 && $result = $this->step2($csvimport) ){
            list($error, $out) = $result;
        }

        if($step == 3 && $result = $this->step3($csvimport)){
            list($error, $out) = $result;
        }

        if( $step == 4 && $result = $this->step4($csvimport) ){
            list($error, $out) = $result;
        }

        if($step == 5 && $result = $this->step5($csvimport)){
            list($error, $out) = $result;
        }

        if($step == 6 && $result = $this->step6($csvimport)){
            list($error, $out) = $result;
        }


        $this->tpl->info = $out;

        $prevstep = $step - 1;
        if($prevstep) {
            $this->tpl->prevstephref = $this->pageurl . '&iid=' . $this->cvsiid . '&step=' . $prevstep;
        }

        $nextstep = $step + 1;

        if($step < 6) {
            $this->tpl->nextstephref = $this->pageurl . '&iid=' . $this->cvsiid . '&step=' . $nextstep;
        }else{
            $this->tpl->nextstephref = $CFG->wwwroot.'/blocks/manage/?_p=places';
        }

        $this->tpl->nextstepdisabled = $error;
        $this->tpl->nextstepname = $step == 6 ? "Завершить": "Далее";

        return $this->fetch('importsales/index.tpl');
    }

    /**
     * Проверка и корректировка городов
     *
     * @param csv_import_reader $csvimport
     * @return bool|string
     */
    public function step1(csv_import_reader $csvimport){

        $previewdata = array();
        $cities = array();
        $cantfind = array();
        $errors = false;


        $this->tpl->mainregions = html_writer::select(get_mainregions_menu(), '', '', 'Выберите округ...', array('class'=>'regionlist span6'));
        $this->tpl->regions = html_writer::select(get_regions_list(), '', '', 'Выберите город...', array('class'=>'citylist span6'));

        $n = 0;
        while ($rows = $csvimport->next()) {
            $n ++;

            if($n >= self::$start_from && !empty($rows[self::$row_city])){
                $cityname = $rows[self::$row_city];
                // Ищем в БД только один раз для каждого города
                if(!isset($cities[$cityname]) && !in_array($cityname, $cantfind)){
                    if($id = lm_city::recognize($cityname)){
                        $cities[$cityname] = $id;
                    }else{
                        $this->tpl->n = count($cantfind);
                        $cantfind[] = $cityname;
                        $row = array();
                        $row[] = count($cantfind);
                        $row[] = $cityname;
                        $row[] = $this->fetch('importsales/citychecker.tpl');;
                        $previewdata[] = $row;
                    }
                }
            }
        }

        $out = "";
        if($previewdata){
            $table = new html_table();
            $table->attributes['class'] = 'generaltable regionscorrector';
            $table->data = $previewdata;

            $out = '<h4>Города не найдены в системе</h4>';
            $out .= html_writer::table($table);
        }else{
            $out .= '<h4>Проверка городов завершилась успешно!</h4>';
        }

        return array($errors, $out);
    }

    /**
     * Проверка и корректировка компаний
     *
     * @param csv_import_reader $csvimport
     * @return bool|string
     */
    public function step2(csv_import_reader $csvimport){
        global $DB;

        $tablecantfind = array();

        $tablemulticompanies = array();

        /**
         * Успешно распознанные компании
         */
        $companies = array();

        /**
         * Компании, которые не удалось найти в БД
         */
        $cantfind = array();

        /**
         * Компании, которые встречаются в БД более чем один раз. В нормальной ситуации такого не должно быть,
         * если такое обнаружили - это ошибка и дальше продолжить импорт не можем.
         */
        $multicompanies = array();

        /**
         * Обнаружены ли ошибки?
         */
        $errors = false;


        if($companies = get_companies_list()){
            foreach($companies as $dbcompany){
                $company = lm_company::i($dbcompany);
                if($company->id){
                    $nodepcompanies = array();
                    $depcompanies = array();

                    if($synonyms = $company->synonyms()){
                        foreach($synonyms as $synonym){
                            if(in_array($synonym, $multicompanies)){
                                continue;
                            }


                            if($conflicts = lm_companies::check_conflict_by_synonym($synonym)){
                                $conflictinfo = array();
                                foreach($conflicts as $conflictcompany) {
                                    $hasdependences = false;
                                    $partners = $DB->get_records("lm_partner", array("companyid"=>$conflictcompany->id));
                                    if($partners){

                                        foreach($partners as $partner){
                                            $partner = lm_partner::i($partner);
                                            if($partner->has_dependences()){
                                                $hasdependences = true;
                                            }
                                        }
                                    }

                                    if($hasdependences){
                                        $depcompanies[$conflictcompany->id] = $conflictcompany->name;
                                    }else{
                                        $nodepcompanies[$conflictcompany->id] = $conflictcompany->name;
                                    }
                                }

                                if(!empty($nodepcompanies)){
                                    if(empty($depcompanies)){
                                        $id = key($nodepcompanies);
                                        $depcompanies[$id] = $nodepcompanies[$id];
                                        unset($id);
                                    }

                                    foreach($nodepcompanies as $companyid=>$name){
                                        lm_company::i($companyid)->remove();
                                        $conflictinfo[] = '#'.$companyid.' '.$name;
                                    }
                                    $nodepcompanies = array();
                                }

                                if(count($depcompanies) > 1){
                                    foreach($depcompanies as $companyid=>$name){
                                        $conflictinfo[] = '#'.$companyid.' '.$name;
                                    }
                                }


                                if(!empty($conflictinfo)) {
                                    $multicompanies[] = $synonym;

                                    $row = array();
                                    $row[] = count($multicompanies);
                                    $row[] = implode(" | ", $conflictinfo);

                                    $tablemulticompanies[] = $row;
                                    $errors = true;
                                }
                            }
                        }
                    }

                }
            }
        }

        if(!$multicompanies) {
            $n = 0;
            $this->tpl->companies = html_writer::select(get_companies_menu(), '', '', 'Выберите компанию...', array('class' => 'companylist span6', 'id' => ''));
            while ($rows = $csvimport->next()) {
                $n++;

                if ($n >= self::$start_from && !empty($rows[self::$row_company])) {
                    $companyrawname = $rows[self::$row_company];
                    $cleanedname = lm_companies::clean_name($companyrawname);

                    $company = htmlspecialchars($cleanedname);

                    if (!isset($companies[$company]) && !in_array($company, $cantfind) && !in_array($company, $multicompanies)) {
                        $id = $this->guess_company($companyrawname);
                        if ($id) {
                            $companies[$company] = $id;
                        } elseif ($id === NULL) { // Такая компания не найдена
                            $this->tpl->n = count($cantfind);
                            $cantfind[] = $company;

                            $row = array();
                            $row[] = count($cantfind);
                            $row[] = $companyrawname;

                            $this->tpl->companyname = htmlspecialchars($companyrawname);
                            $row[] = $this->fetch('importsales/companychecker.tpl');
                            $tablecantfind[] = $row;
                        } elseif ($id === false) { // Найдено более одной компании
                            $multicompanies[] = $company;

                            $row = array();
                            $row[] = count($multicompanies);
                            $row[] = $companyrawname;

                            $tablemulticompanies[] = $row;
                            $errors = true;
                        }
                    }
                }
            }
        }

        $out = "";
        if($tablemulticompanies) {
            $table = new html_table();
            $table->attributes['class'] = 'generaltable companycorrector';
            $table->data = $tablemulticompanies;

            $out .= '<h4>В базе данных обнаружены компании с одинаковыми названиями, дальнейший импорт невозможен.
                    Обратитесь к администратору для решения проблемы!</h4>';
            $out .= html_writer::table($table);
        }elseif($tablecantfind){
            $table = new html_table();
            $table->attributes['class'] = 'generaltable companycorrector';
            $table->data = $tablecantfind;

            $out .= '<h4>Компании не найдены в системе</h4>';
            $out .= html_writer::table($table);
        }else{
            $out .= '<h4>Проверка компаний завершилась успешно!</h4>';
        }

        return array($errors, $out);
    }

    /**
     * Проверка существования пользователей
     *
     * @param csv_import_reader $csvimport
     * @return array
     */
    public function step3(csv_import_reader $csvimport){

        $exists = array();
        $notexists = array();
        $multiexists = array();
        $junk = array();
        $errors = false;

        $n = 0;
        while ($rows = $csvimport->next()) {
            $n++;

            if ($n >= self::$start_from) {
                $users = array($rows[self::$row_resp], $rows[self::$row_tm]);
                foreach($users as $fn) { // $fn - fullname
                    if ($fn) {
                        /** Если ФИО похоже на название торгового центра, то отсеиваем такое )
                         * Примечание: [ТT][ОO_] - здесь используется первая русская Т, а вторая английская, аналогично с О
                         *                          т.к. в выгрузке присутствует алфавитная мешанина
                         */
                        if (!in_array($fn, $junk) && preg_match("/ТЦ[_ ]|ТРЦ[_ ]|СТЦ[_ ]|ЦРТ[_ ]|ТК[_ ]|ТРК[_ ]|[ТT][ОO_] |интернет|магазин/ui", $fn)) {
                            $junk[] = $fn;
                        } else if (!in_array($fn, $exists) && !in_array($fn, $notexists) && !isset($multiexists[$fn]) && !in_array($fn, $junk)) {
                            $user = $this->guess_user_by_fullname($fn);
                            if(is_array($user)){
                                $multiexists[$fn] = array("sourcename"=>$fn, "userlist"=>$user);
                            }else if ($user) {
                                $exists[$user] = $fn;
                            } else {
                                $notexists[] = $fn;
                            }
                        }
                    }
                }
            }
        }

        $out = "";

        if($multiexists){
            $tabledata = array();
            foreach($multiexists as $data) {
                $row = array();
                $row[] = count($tabledata) + 1;
                $row[] = $data["sourcename"] /*. ' - ' . ru2lat($data["sourcename"])*/;

                $userlist = array();
                foreach ($data['userlist'] as $user) {
                    if(!$user->city) $user->city = " - ";
                    $userlist[$user->id] = 'id'.$user->id .' - ' . $user->firstname . ' ' . $user->lastname . ' - '.$user->email. ' ('.$user->city . ')';
                }
                $row[] = html_writer::select($userlist, "userlist", '', 'Выберите соответствие...');
                $tabledata[] = $row;
            }

            $table = new html_table();
            $table->head = array("№", "ФИО");
            $table->attributes['class'] = 'generaltable multiexistscorrector';
            $table->data = $tabledata;

            $out .= '<h4>Найдено несколько учетных записей для этих пользователей</h4>';
            $out .= html_writer::table($table);
        }


        if($notexists){
            $tabledata = array();
            foreach($notexists as $fn) {
                $row = array();
                $row[] = count($tabledata)+1;
                $row[] = $fn.' - ' . ru2lat($fn);
                $tabledata[] = $row;
            }

            $table = new html_table();
            $table->head = array("№", "ФИО");
            $table->attributes['class'] = 'generaltable notexistscorrector';
            $table->data = $tabledata;

            $out .= '<h4>Пользователи не найдены в lms</h4>';
            $out .= html_writer::table($table);
        }


        if($junk){
            $tabledata = array();
            foreach($junk as $fn) {
                $row = array();
                $row[] = count($tabledata)+1;
                $row[] = $fn.' - ' . ru2lat($fn);
                $tabledata[] = $row;
            }

            $table = new html_table();
            $table->head = array("№", "ФИО");
            $table->attributes['class'] = 'generaltable junkcorrector';
            $table->data = $tabledata;

            $out .= '<h4>Возможно, это не пользователи</h4>';
            $out .= html_writer::table($table);
        }

        if(!$out){
            $out .= '<h4>Проверка пользователей завершилась успешно, можно продолжать импорт!</h4>';
        }

        return array($errors, $out);
    }



    /**
     * Проверка и корректировка партнеров
     *
     * @param csv_import_reader $csvimport
     * @return bool|string
     */
    public function step4(csv_import_reader $csvimport){
        $previewdata = array();
        $partners = array();
        $errors = false;

        $n = 0;
        $autocreatecount = 0;
        while ($rows = $csvimport->next()) {
            $n++;

            if ($n >= self::$start_from && !empty($rows[self::$row_company]) && !empty($rows[self::$row_city])) {
                $companyname = $rows[self::$row_company];
                $cityname = $rows[self::$row_city];
                $companyid = $this->guess_company($companyname);
                $cityid = lm_city::recognize($cityname);

                if($companyid && $cityid){
                    if(!isset($partners[$companyid][$cityid])) {
                        if ($partnerid = $this->guess_partner($companyid, $cityid)) {
                            $partners[$companyid][$cityid] = $partnerid;
                        } else {
                            $partner = lm_partner::i(0);
                            $partner->companyid = $companyid;
                            $partner->regionid = $cityid;
                            if(!$partner->id = $partner->create()){
                                $errors = true;
                            }else{
                                $autocreatecount ++;
                                $partners[$companyid][$cityid] = $partner->id;
                            }


                        }
                    }
                }else{
                    // Прерываем обработку ошибкой, если не найдена компания или город в системе, т.к.
                    // это должно было быть исправлено в предыдущих шагах
                    $errortext = '<h4 class="process-warning">В процессе импорта возникла ошибка: не найдет город или компания! Попробуйте импортировать данные заново.</h4>';
                    return array(true, $errortext);
                }
            }
        }

        $out = "";
        if($autocreatecount){
            $out .= "<h3>Создано партнеров в автоматическом режиме: {$autocreatecount} шт.</h3><hr>";
        }

        if($previewdata){
            $table = new html_table();
            $table->head = array("№", "Партнер", "ТМ", "");
            $table->attributes['class'] = 'generaltable partnercorrector';
            $table->data = $previewdata;

            $out .= '<h4>Партнеры не найдены в системе</h4>';
            $out .= html_writer::table($table);
        }else{
            $out .= '<h4>Проверка партнеров завершилась успешно, можно продолжать импорт!</h4>';
        }

        return array($errors, $out);
    }

    /**
     * Проверка и корректировка торговых точек
     *
     * @param csv_import_reader $csvimport
     * @return bool|string
     */
    public function step5(csv_import_reader $csvimport){
        global $DB;

        $tts = array();
        $cantfind = array();
        $errors = false;


        $n = $createdcount = $tmcorrectcount = $respcorrectcount = 0;
        while ($rows = $csvimport->next()) {
            $n ++;

            if($n >= self::$start_from && !empty($rows[self::$row_ttcode])){
                $code = $rows[self::$row_ttcode];
                $cityname = $rows[self::$row_city];
                $respfullname = $rows[self::$row_resp];
                $tmfullname = $rows[self::$row_tm];
                $ttname = $rows[self::$row_ttname];
                $companyname = $rows[self::$row_company];
                $rawaddress = $rows[self::$row_address];

                // Ищем в БД каждую торговую точку только один раз!
                if(!isset($tts[$code]) && !in_array($code, $cantfind)){
                    $respid = $this->guess_user_by_fullname($respfullname);
                    $tmid = $this->guess_user_by_fullname($tmfullname);

                    if( $tt = lm_place::get_by_code($code) ){
                        $tts[$code] = $tt->id;

                        if(is_int($respid) && $tt->respid != $respid){
                            $tt->set('respid', $respid);
                            $respcorrectcount ++;
                        }

                        if(is_int($tmid) && $tt->tmid != $tmid){
                            $tt->set('tmid', $tmid);
                            $tmcorrectcount ++;
                        }

                        $tt->update();

                    }else{
                        $cantfind[] = $code;
                        $this->tpl->n = count($cantfind);

                        $partnerid = 0;
                        $cityid = (int) lm_city::recognize($cityname);
                        if($companyid = $this->guess_company($companyname)) {
                            $partnerid = (int) $this->guess_partner($companyid, $cityid);
                        }

                        if($partnerid) {
                            if(is_array($respid)) $respid = 0;
                            if(is_array($tmid)) $tmid = 0;

                            $dataobj = new StdClass();
                            $dataobj->code = $code;
                            $dataobj->type = 'tt';
                            $dataobj->partnerid = $partnerid;
                            $dataobj->name = $ttname;
                            $dataobj->cityid = $cityid;
                            $dataobj->respid = $respid;
                            $dataobj->tmid = $tmid;
                            $dataobj->rawaddress = $rawaddress;
                            lm_place::i(0)->set_group($dataobj)->create();

                            $createdcount ++;
                        }else{
                            // Прерываем обработку закончив ее ошибкой, если не найдено в системе такого партнера
                            // т.к. это должно было быть исправлено в предыдущих шагах
                            $errors = true;

                        }
                    }
                }
            }
        }

        $out = "";
        if($createdcount){
            $out .= "<h4>Создано торговых точек в автоматическом режиме: {$createdcount} шт.</h4>";
        }

        if($respcorrectcount){
            $out .= "<h4>Скорректировано назначений ответственных за торговую точку: {$respcorrectcount} шт.</h4>";
        }

        if($tmcorrectcount){
            $out .= "<h4>Скорректировано назначений ТМ: {$tmcorrectcount} шт.</h4>";
        }

        if($errors){
            $out .= '<h4 class="process-warning">Импорт завершился ошибкой, т.к. в одной или более торговых точках невозможно определить к какому партнеру они относятся!</h4>';
        }else{
            $out .= '<h4>Проверка торговых точек завершилась успешно, можно продолжать импорт!</h4>';
        }


        return array($errors, $out);
    }

    /**
     * Финальный шаг - загрузка данных о продажах
     *
     * @param csv_import_reader $csvimport
     * @return bool|string
     */
    public function step6(csv_import_reader $csvimport){
        global $DB;

        $errors = false;
        $count = (object) array('success'=>0, 'esixts'=>0, 'error'=>0);

        $n = 0;
        while ($rows = $csvimport->next()) {
            $n++;

            if ($n >= self::$start_from && !empty($rows[self::$row_decade]) && !empty($rows[self::$row_month])) {
                $companyname = $rows[self::$row_company];
                $cityname = $rows[self::$row_city];
                $ttcode = $rows[self::$row_ttcode];
                $decade = $rows[self::$row_decade];
                $date = explode('.', $rows[self::$row_month]);
                $month = !empty($date[1]) ? $date[1]: 0;
                $year = !empty($date[2]) ? $date[2]: 0;

                $companyid = $this->guess_company($companyname);
                $cityid = lm_city::recognize($cityname);
                $partnerid = $this->guess_partner($companyid, $cityid);
                $ttid = $this->guess_tt($ttcode);
                if($partnerid && $ttid && $decade && $month && $year){
                    $expression = array('partnerid'=>$partnerid, 'ttid'=>$ttid, 'decade'=>$decade, 'month'=>$month, 'year'=>$year);
                    if(!$DB->record_exists('lm_stat', $expression)){
                        $dataobj = new StdClass();
                        $dataobj->partnerid = $partnerid;
                        $dataobj->ttid = $ttid;
                        $dataobj->period = make_period($year, $month, $decade);
                        $dataobj->decade = $decade;
                        $dataobj->month = $month;
                        $dataobj->year = $year;
                        $dataobj->plansales = ceil($rows[self::$row_plansales]);
                        $dataobj->factsales = ceil($rows[self::$row_factsales]);
                        $dataobj->returns = ceil($rows[self::$row_returns]);
                        $dataobj->stock = ceil($rows[self::$row_stock]);

                        if($DB->insert_record('lm_stat', $dataobj)) {
                            $count->success ++;
                        }else{
                            $count->error ++;
                            $errors = true;
                            break;
                        }
                    }
                }else{
                    // Прерываем обработку закончив ее ошибкой, если не найдено в системе такого партнера или ТТ
                    // т.к. это должно было быть исправлено в предыдущих шагах
                    $count->error ++;
                    $errors = true;
                    break;
                }
            }
        }

        $out = "";
        if($errors){
            $out .= '<h4 class="process-warning">Импорт завершился ошибкой, т.к. не были найдены некоторые торговые точки или партнеры, либо некорректно задан период!</h4>';
        }else{
            $out .= "<h4>Готово! Успешно обновлена информация о продажах, загружено {$count->success} записей.</h4>";
        }

        return array($errors, $out);
    }



    public function ajax_regionscorrector($p){
        if(empty($p->items)){
            return false;
        }

        $items = json_decode($p->items);
        if(is_array($items)){
            foreach($items as $item){
                switch($item->mode){
                    case "new":
                        lm_city::i()->setName($item->cityname)->setSynonyms(array($item->sourcename))->create($item->region);
                        break;

                    case "exists":
                        lm_city::i($item->linkedcity)->setSynonyms(array($item->sourcename))->update();
                        break;

                    default:break;
                }
            }
        }

        return true;
    }

    public function ajax_companycorrector($p){
        if(empty($p->items)){
            return false;
        }

        $items = json_decode($p->items);
        if(is_array($items)){
            foreach($items as $item){
                switch($item->mode){
                    case "new":
                        lm_company::i(0)->setName($item->companyname)->setSynonyms(array($item->sourcename))->create();
                        break;

                    case "exists":
                        lm_company::i($item->linkedcompany)->setSynonyms(array($item->sourcename))->update();
                        break;

                    default:break;
                }
            }
        }

        return true;
    }

    public function ajax_multiexistscorrector($p){
        global $DB;

        if(empty($p->items)){
            return false;
        }

        $items = json_decode($p->items);
        if(is_array($items)){
            foreach($items as $item){
                // Синоним для пользователя уже существует, обновляем
                if($user = $DB->get_record('lm_user', array('userid'=>$item->linkeduser))){
                    $user->synonyms = trim($user->synonyms);
                    if($user->synonyms) {
                        $user->synonyms = explode(',', $user->synonyms);
                    }

                    $synonym = $this->cleanname($item->sourcename);

                    if(!in_array($synonym, $user->synonyms)){
                        $user->synonyms[] = $synonym;
                    }

                    $user->synonyms = implode(',', $user->synonyms);

                    $DB->update_record('lm_user', $user);
                }else{
                    $user = new StdClass();
                    $user->userid = $item->linkeduser;
                    $user->synonyms = $this->cleanname($item->sourcename);
                    $DB->insert_record('lm_user', $user);
                }
            }
        }

        return true;
    }

    public function ajax_staffcorrector($p){
        global $DB;

        $p = json_decode($p->items);
        if(!isset($p->tms) || !isset($p->resps)){
            return false;
        }

        // TODO: исправить обновление записей в lm_partner
        if(is_array($p->tms)){
            foreach($p->tms as $data){
                $dataobj = new StdClass();
                $dataobj->id = $data->partnerid;
                $dataobj->tmid = $data->userid;
                $DB->update_record('lm_partner', $dataobj);
            }
        }

        if(is_array($p->resps)){
            foreach($p->resps as $data){
                $dataobj = new StdClass();
                $dataobj->id = $data->partnerid;
                $dataobj->respid = $data->userid;
                $DB->update_record('lm_partner', $dataobj);
            }
        }
    }
}