<?php
class lm_partner{
    static private $i = NULL;

    /**
     * Идентификатор партнера
     * @var int
     */
    public $id = 0;

    /**
     * Идентификатор компании, к которой относится этот партнер
     * @var int
     */
    public $companyid = 0;

    /**
     * Название подразделения/филиала
     * @var string
     */
    public $name = 'Новая компания';


    public $synonyms = "";

    /**
     * Идентификатор региона местоположения подразделения
     * @var int
     */
    public $regionid = 0;

    /**
     * Идентификатор менеджера по работе с партнером
     *
     * @var int
     */
    public $pamid = 0;

    /**
     * Идентификатор территориального менеджера
     *
     * @var int
     */
    public $tmid_deprecated = 0;

    /**
     * Идентификатор тренера
     *
     * @var int
     */
    public $trainerid_deprecated = 0;

    /**
     * Идентификатор пользователя, который является представителем партнера
     * @var int
     */
    public $repid_deprecated = 0;

    /**
     * Идентификатор пользователя, ответственного за партнера
     * @var int
     */
    public $respid_deprecated = 0;

    /**
     * Комментарий о партнере
     * @var string
     */
    public $comment = '';

    /**
     * Название компании, к которой относится партнер
     * @var null
     */
    public $companyname = NULL;

    /**
     * Тип партнера. М.б. собственная розница или партнерская (own|partner)
     * @var string
     */
    public $type = 'partner';

    /**
     * Идентификатор глобальной группы moodle, в которую будем записывать пользователя при добавлении в сотрудники
     *
     * @var int
     */
    public $cohortid = 0;

    public $hide = 0;

    /**
     * Кол-во назначенных программ партнеру. Кэшируется в таблице lm_partner и используется для сортировки.
     *
     * @var int
     */
    public $programscount = 0;

    /**
     * Процент обученных сотрудников у партнера. Кэшируется в таблице lm_partner и используется для сортировки.
     *
     * @var int
     */
    public $trainedpercent = 0;

    /**
     * Процент обученных сотрудников у партнера по программе. Значения каждый раз не вычисляются, а
     * берутся из таблицы lm_partner_program.
     *
     * @var null
     */
    protected $trainedbyprogram = NULL;

    /**
     * Процент обученности сотрудников программам, назначенным партнеру.
     *
     * @var null
     */
    protected $staffersprogress = NULL;

    /**
     * Кол-во ошибок сотрудников программам, назначенным партнеру.
     *
     * @var null
     */
    protected $staffersmistakes = NULL;

    /**
     * Продолжительность прохождения программы (время, проведенное в SCORM-пакете)
     *
     * @var null
     */
    protected $staffersduration = NULL;

    /**
     * @param $partnerid
     * @return lm_partner
     */
    static public function i($partnerid){
        $partner = 0;
        if($partnerid && is_numeric($partnerid)) {
            $partner = $partnerid;
        }else if($partnerid && is_object($partnerid)){
            $partner = clone $partnerid;
            $partnerid = (integer) $partnerid->id;
        }

        if(!$partnerid || !isset(self::$i[$partnerid])){
            self::$i[$partnerid] = new lm_partner($partner);
        }

        return self::$i[$partnerid];
    }

    private function __construct($partnerid){
        global $DB;

        $partner = new StdClass();

        if($partnerid && is_numeric($partnerid)) {
            $sql = "SELECT lp.*, lc.name as companyname, lc.type, lc.hide
                      FROM {lm_partner} lp
                      LEFT JOIN {lm_company} lc ON lp.companyid=lc.id
                      WHERE lp.id=?";

            $partner = $DB->get_record_sql($sql, array('id' => $partnerid));

        }else if($partnerid && is_object($partnerid)){
            $partner = $partnerid;
        }

        if ($partner) {
            foreach ($partner as $field => $value) {
                $this->$field = $value;
            }
        }
    }



    /**
     * Возвращает название партнера
     * @return string
     */
    public function name(){
        return $this->name;
    }

    /**
     * Возвращает наименование организации, напр. ЙОТА
     *
     * @return null
     */
    public function company_name(){
        global $DB;

        if($this->companyname === NULL && $company = $DB->get_record('lm_company', array('id'=>$this->companyid))){
            $this->companyname = $company->name;
            $this->type = $company->type;
        }

        return $this->companyname;
    }

    /**
     * Возвращает полное наименование партнера
     *
     * @return string
     */
    public function fullname(){
        return $this->company_name().' ('.$this->name.')';
    }

    /**
     * Возвращает ссылку на текущего партнера
     *
     * @return string
     */
    public function url(){
        global $CFG;

        return $CFG->wwwroot.'/blocks/manage/?_p=partners&id='.$this->id;
    }

    public function link(){
        return html_writer::link($this->url(), $this->fullname(), array("target"=>"_blank"));
    }

    /**
     * Создать нового партнера
     */
    public function create(){
        global $DB, $USER;

        if(!$this->id){
            // Записываем любой регион из доступных тренеру, чтобы он имел возможность работать с ней.
            if(!$this->regionid) {
                $this->regionid = $DB->get_field('lm_region_trainer', 'regionid', array('trainerid' => $USER->id));
            }

            if($this->regionid){
                $this->name = get_cityname($this->regionid);
            }

            $this->id = $DB->insert_record('lm_partner', $this);
        }

        return $this->id;
    }

    public function has_dependences(){
        global $DB;

        /*if($DB->record_exists("lm_partner_program", array("partnerid"=>$this->id))){
            return true;
        }*/

        if($DB->record_exists("lm_activity_request", array("partnerid"=>$this->id))){
            return true;
        }

        if($DB->record_exists("lm_partner_staff_progress", array("partnerid"=>$this->id))){
            return true;
        }

        if($DB->record_exists("lm_place", array("partnerid"=>$this->id))){
            return true;
        }

        if($DB->record_exists("lm_stat", array("partnerid"=>$this->id))){
            return true;
        }

        return false;
    }

    /**
     * Удаляет партнера полностью без возможности восстановления!
     *
     * @return bool
     */
    public function remove(){
        global $DB;

        if(!$this->has_capability_edit()){
            return false;
        }

        $activities = $DB->get_records('lm_activity_request', array('partnerid'=>$this->id));
        $DB->delete_records('lm_activity_request', array('partnerid'=>$this->id));
        if($activities) {
            // Пересчитываем процент обученных по активностям
            foreach($activities as $activity) {
                $oactivity = lm_activity::i($activity);
                $oactivity->recalculate_trained_count();
            }
        }

        $DB->delete_records('lm_partner_staff', array('partnerid'=>$this->id));
        $DB->delete_records('lm_partner_program', array('partnerid'=>$this->id));
        $DB->delete_records("lm_stat", array("partnerid"=>$this->id));
        $DB->delete_records('lm_partner', array('id'=>$this->id));

        return true;
    }

    /**
     * Назначает пользователя $userid менеджером по работе с партнером (partner account manager)
     *
     * @param $userid
     * @return bool
     */
    public function appoint_pam($userid){
        global $DB;

        $dataobj = (object) array('id'=>$this->id, 'pamid'=>$userid);
        return $DB->update_record('lm_partner', $dataobj);
    }

    public function get_pam(){
        global $DB;

        return $DB->get_record('user', array('id'=>$this->pamid));
    }




    /**
     * Добавляет сотрудника партнеру, при этом регистрируя его в moodle-системе
     *
     * @param $usernew
     * @param $issendemail - Отправлять ли пользователю сообщение о том, что его добавили в качестве сотрудника?
     * @return int
     * @throws moodle_exception
     */
    public function create_staffer($usernew, $issendemail=true, $isrecalculate=true){
        $usernew->institution = $this->name;
        $usernew = lm_user::create($usernew);
        if($usernew === NULL){
            return 'already_exists';
        }

        $this->add_staffer($usernew, $issendemail, $isrecalculate);

        return $usernew->id;
    }

    /**
     * Добавлят уже зарегистрированного пользователя в сотрудники
     *
     * @param $userid
     * @param $issendemail - Отправлять ли пользователю сообщение о том, что его добавили в качестве сотрудника?
     * @param $isrecalculate
     * @return bool|int|string
     */
    public function add_staffer($userid, $issendemail=true, $isrecalculate=true){
        global $DB, $CFG;

        $rawpassword = '';
        if(is_object($userid) && isset($userid->id)){
            $rawpassword = $userid->rawpassword;
            $userid = $userid->id;
        }

        $ispartnerstaffer = $DB->record_exists('lm_partner_staff', array('partnerid'=>$this->id, 'userid'=>$userid));
        if(!$ispartnerstaffer && $userid){
            $dataobj = new StdClass();
            $dataobj->partnerid = $this->id;
            $dataobj->userid = $userid;

            // Записываем юзера на роль "Сотрудник партнера"
            if( $CFG->lm_org_enabled ) {
                lm_position::correct_assigned_roles_by_user($userid);
            }else{
                $context = context_system::instance();
                role_assign($CFG->block_manage_stafferroleid, $userid, $context->id);
            }

            // Добавляем пользователя в глобальную группу
            if($this->cohortid){
                lm_cohort::add_member($this->cohortid, $userid);
            }

            if($issendemail){
                // Отправляем пользователю нотификацию, что его назначили сотрудником
                $this->notify_staffer_assignment($userid, $rawpassword);
            }


            $result = $DB->insert_record('lm_partner_staff', $dataobj);

            // После добавления сотрудников изменяется информация об обученности
            // сотрудников партнера по программам, а также в целом, поэтому делаем пересчет
            if($isrecalculate) {
                $this->recalculateall_trained_percent();
            }

            return $result;
        }else{
            return 'already_partners_staffer';
        }
    }

    /**
     * Перемещает сотрудника из одной ТТ в другую (только в рамках одного партнера)
     *
     * @param $userid
     * @param $fromttid
     * @param $tottid
     * @return bool|int
     */
    public function relocate_staffer($userid, $fromttid, $tottid){
        global $DB;

        $staffer_xrefid = $DB->get_field('lm_partner_staff', 'id',
                                array('partnerid'=>$this->id, 'ttid'=>$fromttid, 'userid'=>$userid));
        $dataobj = new StdClass();
        $dataobj->ttid = $tottid;
        if($staffer_xrefid){
            $dataobj->id = $staffer_xrefid;

            return $DB->update_record('lm_partner_staff', $dataobj);
        }else{
            $dataobj->userid = $userid;
            $dataobj->partnerid = $this->id;

            return $DB->insert_record('lm_partner_staff', $dataobj);
        }
    }


    /**
     * Перенос пользователя в архив
     *
     * @param $userid
     * @return bool
     */
    public function archive_staffer($userid){
        global $DB;

        if(!$this->has_capability_edit()){
            return false;
        }

        $dataobj = new StdClass();
        $dataobj->id = $this->get_stafferid($userid);
        $dataobj->archive = time();

        if($dataobj->id){

            $result = $DB->update_record('lm_partner_staff', $dataobj);

            // После перемещения сотрудника в архив изменяется информация об обученности
            // сотрудников партнера по программам, а также в целом, поэтому делаем пересчет
            $this->recalculateall_trained_percent();

            return $result;
        }

        return false;
    }

    /**
     * Удаление пользователя из нашей lms
     *
     * @param $userid
     * @return bool
     */
    public function remove_staffer($userid){
        global $DB;

        if(!$this->has_capability_edit()){
            return false;
        }

        if(!$this->get_stafferid($userid)){
            return false;
        }

        $DB->delete_records('lm_partner_staff', array('partnerid'=>$this->id, 'userid'=>$userid));
        $DB->delete_records('lm_activity_request', array('partnerid'=>$this->id, 'userid'=>$userid));
        $DB->delete_records('lm_region_trainer', array('trainerid'=>$userid));

        // После удаления сотрудника изменяется информация об обученности
        // сотрудников партнера по программам, а также в целом, поэтому делаем пересчет
        $this->recalculateall_trained_percent();

        return true;
    }

    /**
     * Полное удаление пользователя из moodle
     *
     * @param $userid
     * @throws coding_exception
     */
    public function finaly_remove_staffer($userid){
        global $DB;

        if(!$this->has_capability_edit()){
            return false;
        }

        if($user = $DB->get_record('user', array('id'=>$userid))){
            $this->remove_staffer($userid);

            if (delete_user($user)) {
                \core\session\manager::gc(); // Remove stale sessions.
            }

            return true;
        }

        return false;
    }

    /**
     * Идентификатор сотрудника из таблицы lm_partner_staffer
     *
     * @param $userid
     * @return mixed
     */
    public function get_stafferid($userid){
        global $DB;

        return $DB->get_field('lm_partner_staff', 'id', array('partnerid'=>$this->id, 'userid'=>$userid));
    }

    /**
     * Уведомление о назначении в сотрудники партнеру
     *
     * @param $userid
     * @param string $rawpassword
     * @throws coding_exception
     */
    public function notify_staffer_assignment($userid, $rawpassword=''){
        global $DB, $CFG, $USER;

        $user = $DB->get_record('user', array('id'=>$userid));
        if($user){
            $emaildata = new StdClass();
            $emaildata->firstname = $user->firstname;
            $emaildata->companyname = $this->company_name();
            $emaildata->email = $user->email;
            $emaildata->link = $CFG->wwwroot;
            $emaildata->whoassignedfullname = fullname($USER);

            if($rawpassword){
                $emaildata->password = $rawpassword;
            }else{
                $emaildata->password = 'уточни у тренера, если забыл';
            }

            $msg = get_string('emailassigntopartner', 'block_manage', $emaildata);
            $msghtml = get_string('emailassigntopartnerhtml', 'block_manage', $emaildata);
            $subject = get_string('emailassigntopartnersubject', 'block_manage');
            email_to_user($user, core_user::get_support_user(), $subject, $msg, $msghtml);
        }
    }


    /**
     * Возвращает список сотрудников партнера
     *
     * @param $q - слово по которому будет отфильтрован список
     * @param bool $archive - Если true, то возвратит сотрудников только из архива, иначе только активных!
     * @param string $q - Слово, по которому будет отфильтрован список
     * @param int $tmid - Фильтр сотрудников по ТМу (териториальный менеджер)
     * @param int $tainerid - Фильтр сотрудников по Тренеру
     * @param int $ttid - Фильтр сотрудников по ТТ (торговой точке)
     *
     * @return array
     */
    public function get_staffers($archive=false, $q="", $tmid=0, $trainerid=0, $ttid=0){
        global $DB;

        $sql = $this->get_staffers_sql($archive, false, $q, $tmid, $trainerid, $ttid);
        return $DB->get_records_sql($sql);
    }

    /**
     * Возвращает список сотрудников партнера в виде ассоциативного массива. В качестве ключа id сотрудника,
     * в качестве значения ФИО
     *
     * @param bool $archive
     * @return array
     */
    public function get_staffers_menu($archive=false){
        global $DB;

        $sql = $this->get_staffers_sql($archive, true);
        return $DB->get_records_sql_menu($sql);
    }

    /**
     * Возвращает sql
     *
     * @param bool $archive
     * @param bool $menu
     * @param string $q  - слово, по которому будет отфильтрована выборка
     * @param int $tmid - Фильтр сотрудников по ТМу (териториальный менеджер)
     * @param int $tainerid - Фильтр сотрудников по Тренеру
     * @param int $ttid - Фильтр сотрудников по ТТ (торговой точке)
     *
     * @return string
     */
    protected function get_staffers_sql($archive=false, $menu=false, $q="", $tmid=0, $trainerid=0, $ttid=0){
        $where = " AND archive=0";
        if($archive) $where = " AND archive > 0";
        if($q) $where .= " AND  (u.firstname LIKE '{$q}%' OR u.lastname LIKE '{$q}%')";
        if($tmid) $where .= " AND lpl.tmid={$tmid}";
        if($trainerid) $where .= " AND lpl.trainerid={$trainerid}";
        if($ttid && $ttid > 0){
            $where .= " AND lpl.id={$ttid}";
        }else if($ttid && $ttid < 0){
            $where .= " AND lpl.id IS NULL";
        }


        $fields = 'u.id, u.email, u.username, u.picture, u.imagealt, u.firstname, u.lastname,
                   lpl.id as ttid, lpl.code as ttcode, lpl.name as ttname';
        if($menu){
            $fields = "u.id, CONCAT(u.lastname, ' ', u.firstname)";
        }

        $sql = "SELECT {$fields}
                      FROM {user} u
                      JOIN {lm_partner_staff} lps ON lps.userid=u.id
                      LEFT JOIN {lm_place} lpl ON lpl.id=lps.ttid
                      WHERE lps.partnerid={$this->id} $where
                      ORDER BY u.lastname ASC, u.firstname ASC
                      ";

        return $sql;
    }

    /**
     * Взять сотрудников партнера из архива
     *
     * @return array
     */
    public function get_archive_staffers(){
        return $this->get_staffers(true);
    }

    /**
     * Возвращает кол-во сотрудников у партнера
     *
     * @return int
     */
    public function count_staffers(){
        global $DB;

        return $DB->count_records('lm_partner_staff', array('partnerid'=>$this->id, 'archive'=>0));
    }

    /**
     * Является ли пользователь сотрудником этого партнера?
     *
     * @param $userid
     * @return bool
     */
    public function is_staffer($userid){
        global $DB;

        return $DB->record_exists_select('lm_partner_staff', "partnerid={$this->id} AND userid={$userid}");
    }

    /**
     * Пересчитывает процент обученности сотрудника и кэширует это значение в таблице lm_partner_staff_progress.
     * Эти данные в дальнейшем используются в сортировках, отчетах и т.п, чтобы увеличить скорость работы.
     *
     * @param $userid
     * @param $programid
     * @return bool
     * @throws coding_exception
     */
    public function recalculate_staffer_progress($userid, $programid=0){
        global $DB, $CFG;

        $progress = $this->calculate_staffer_progress($userid, $programid);
        $mistakes = $this->calculate_staffer_mistakes($userid, $programid);
        $duration = $this->calculate_staffer_duration($userid, $programid);

        $stageid = 0;
        if($CFG->lm_matrix_enabled) {
            $staffer = lm_staffer::i($this->id, $userid);
            $stageid = $staffer->get_stageid();
        }

        if($this->staffersprogress !== NULL){
            $this->staffersprogress[$userid][$programid][$stageid] = $progress;
        }

        $recordexists = $DB->get_record('lm_partner_staff_progress',
            array('partnerid'=>$this->id, 'userid'=>$userid, 'programid'=>$programid, 'stageid'=>$stageid),
            'id, progress, mistakes, duration'
        );

        if($recordexists){
            $recordexists->progress = $progress;
            $recordexists->mistakes = $mistakes;
            $recordexists->duration = $duration;
            return $DB->update_record('lm_partner_staff_progress', $recordexists);
        }else {
            $dataobj = new StdClass();
            $dataobj->partnerid = $this->id;
            $dataobj->userid    = $userid;
            $dataobj->programid = $programid;
            $dataobj->stageid   = $stageid;
            $dataobj->progress  = $progress;
            $dataobj->mistakes  = $mistakes;
            $dataobj->duration  = $duration;

            return $DB->insert_record('lm_partner_staff_progress', $dataobj);
        }
    }

    public function recalculate_staffer_progress_all_programs($userid){
        $programs = $this->get_appointed_programs_ids($userid);
        if($programs) {
            foreach ($programs as $programid) {
                $this->recalculate_staffer_progress($userid, $programid);
            }
        }
    }

    /**
     * Расчитывает процент обученности сотрудника по программе или в целом
     *
     * @param $userid
     * @param int $programid
     * @return float|int
     * @throws coding_exception
     */
    public function calculate_staffer_progress($userid, $programid=0){
        global $CFG, $DB;

        $progress = 0;

        if($CFG->lm_matrix_enabled) {
            $staffer = lm_staffer::i($this->id, $userid);
            $postid = $staffer->post()->get_id();
            $stageid = $staffer->get_stageid();
            if(!$programid) {
                if( $programs = lm_matrix::programs_menu($postid, $stageid) ){
                    $programscount = count($programs);
                    $successcount = 0;
                    foreach($programs as $programid){
                        $res = $this->staffer_progress($userid, $programid);
                        if( $res > 0 ){
                            $successcount ++;
                        }
                    }
                    $progress = round($successcount / $programscount * 100, 2);
                }
            }else{
                $progress = $staffer->calculate_program_result($programid);
            }
        }else {
            if (!$programid) {

                // Подсчитаем кол-во программ (из назначенных партнеру), по которым сотрудник прошел успешно обучение
                $sql = "SELECT COUNT(DISTINCT(la.programid))
                      FROM {lm_activity_request} lar
                      JOIN {lm_activity} la ON la.id=lar.activityid
                      JOIN {lm_partner_program} lpa ON lpa.partnerid=lar.partnerid
                      WHERE lar.userid={$userid} AND lar.partnerid={$this->id} AND lar.passed > 0";

                $successcount = $DB->count_records_sql($sql);
                if ($programscount = $this->count_appointed_programs()) {
                    $progress = round($successcount / $programscount * 100, 2);
                }
            } else {
                // Если смотрим прогресс по программе, то он может быть либо 0, либо 100 (прошел/не прошел)
                $sql = "SELECT lar.id
                          FROM {lm_activity_request} lar
                          JOIN {lm_activity} la ON la.id=lar.activityid
                          WHERE lar.partnerid={$this->id} AND lar.userid={$userid} AND
                                la.programid={$programid} AND lar.passed > 0";

                if ($DB->record_exists_sql($sql)) {
                    $progress = 100;
                }
            }
        }

        return $progress;
    }

    /**
     * Расчитывает кол-во ошибок сотрудника по программе или в целом
     *
     * @param $userid
     * @param int $programid (optional)
     * @return NULL|int
     * @throws coding_exception
     */
    public function calculate_staffer_mistakes($userid, $programid = 0){
        global $CFG;

        $mistakes = NULL;

        if($CFG->lm_matrix_enabled) {
            $staffer = lm_staffer::i($this->id, $userid);
            if(!$programid) {
                $postid = $staffer->post()->get_id();
                $stageid = $staffer->get_stageid();
                if( $programs = lm_matrix::programs_menu($postid, $stageid) ){
                    foreach($programs as $programid){
                        $res = $this->staffer_mistakes($userid, $programid);
                        if( (int) $res > 0 ) $mistakes += $res;
                    }
                }
            }else{
                $program = lm_program::i($programid);
                $mistakes = $program->get_mistakes($userid);
            }
        }else {
            $mistakes = 0;
        }

        return $mistakes;
    }

    /**
     * Расчитывает время, проведенное в программе
     *
     * @param $userid
     * @param int $programid (optional)
     * @return int|NULL
     * @throws coding_exception
     */
    public function calculate_staffer_duration($userid, $programid = 0){
        global $CFG;

        $duration = NULL;

        if($CFG->lm_matrix_enabled) {
            $staffer = lm_staffer::i($this->id, $userid);
            if(!$programid) {
                $postid = $staffer->post()->get_id();
                $stageid = $staffer->get_stageid();
                if( $programs = lm_matrix::programs_menu($postid, $stageid) ){
                    foreach($programs as $programid){
                        $res = $this->staffer_duration($userid, $programid);
                        if( (int) $res > 0 ) $duration += $res;
                    }
                }
            }else{
                $program = lm_program::i($programid);
                $duration = $program->get_duration($userid);
            }
        }else {
            $duration = 0;
        }

        return $duration;
    }

    private function _get_partner_staff_progress() {
        global $DB;

        if($this->staffersprogress === NULL){
            $staffers = $DB->get_records('lm_partner_staff_progress', array('partnerid'=>$this->id));
            if($staffers){
                foreach($staffers as $staffer){
                    $this->staffersprogress[$staffer->userid][$staffer->programid][$staffer->stageid] = $staffer->progress;
                    $this->staffersmistakes[$staffer->userid][$staffer->programid][$staffer->stageid] = $staffer->mistakes;
                    $this->staffersduration[$staffer->userid][$staffer->programid][$staffer->stageid] = $staffer->duration;
                }
            }
        }

    }

    /**
     * Возвращает процент обученности сотрудника из кэша, т.е. не совершая трудоемких операций подсчета при этом
     *
     * @param $userid
     * @return int
     */
    public function staffer_progress($userid, $programid=0){
        global $CFG;

        $stageid = 0;
        if($CFG->lm_matrix_enabled) {
            $staffer = lm_staffer::i($this->id, $userid);
            $stageid = $staffer->get_stageid();
        }

        // Загружаем кэш обученности каждого из сотрудников этого партнера
        $this->_get_partner_staff_progress();

        if(isset($this->staffersprogress[$userid][$programid][$stageid])){
            return $this->staffersprogress[$userid][$programid][$stageid];
        }

        return 0;
    }

    /**
     * Возвращает количество ошибок сотрудника из кэша, т.е. не совершая трудоемких операций подсчета при этом
     *
     * @param int $userid
     * @param int $programid (optional)
     * @return int|NULL
     */
    public function staffer_mistakes($userid, $programid=0){
        global $CFG;

        $stageid = 0;
        if($CFG->lm_matrix_enabled) {
            $staffer = lm_staffer::i($this->id, $userid);
            $stageid = $staffer->get_stageid();
        }

        // Загружаем кэш обученности каждого из сотрудников этого партнера
        $this->_get_partner_staff_progress();

        if(isset($this->staffersmistakes[$userid][$programid][$stageid])){
            return $this->staffersmistakes[$userid][$programid][$stageid];
        }

        return FALSE;
    }

    /**
     * Возвращает время пребывания в программе сотрудника из кэша, т.е. не совершая трудоемких операций подсчета при этом
     *
     * @param int $userid
     * @param int $programid (optional)
     * @return int|NULL
     */
    public function staffer_duration($userid, $programid=0){
        global $CFG;

        $stageid = 0;
        if($CFG->lm_matrix_enabled) {
            $staffer = lm_staffer::i($this->id, $userid);
            $stageid = $staffer->get_stageid();
        }

        // Загружаем кэш обученности каждого из сотрудников этого партнера
        $this->_get_partner_staff_progress();

        if(isset($this->staffersduration[$userid][$programid][$stageid])){
            return $this->staffersduration[$userid][$programid][$stageid];
        }

        return FALSE;
    }

    public function tm_list(){
        global $DB;

        $sql = "SELECT u.id, CONCAT(u.lastname, ' ', u.firstname) as fullname
                      FROM {lm_place} lpl
                      JOIN {user} u ON u.id = lpl.tmid
                      WHERE lpl.partnerid={$this->id} AND type='tt'
                      ORDER BY u.lastname ASC";

        return $DB->get_records_sql_menu($sql);
    }

    public function trainer_list(){
        global $DB;

        $sql = "SELECT u.id, CONCAT(u.lastname, ' ', u.firstname) as fullname
                      FROM {lm_place} lpl
                      JOIN {user} u ON u.id = lpl.trainerid
                      WHERE lpl.partnerid={$this->id} AND type='tt'
                      ORDER BY u.lastname ASC";

        return $DB->get_records_sql_menu($sql);
    }

    /**
     * Возвращает список торговых точек партнера
     *
     * @return array
     */
    public function tt_list($mode='full'){
        global $DB;
        $condition = array('partnerid'=>$this->id, 'type'=>'tt');
        if($mode == 'full') {
            return $DB->get_records('lm_place', $condition, 'code ASC');
        }else{
            return $DB->get_records_menu('lm_place', $condition, 'code ASC', "id, CONCAT(code, ' (', name, ')' )");
        }
    }

    /**
     * Назначает программу партнеру
     *
     * @param $programid
     * @return int
     */
    public function appoint_program($programid){
        global $DB, $USER;

        //TODO: Проверить не назначена ли уже такая программа партнеру
        //TODO: Проверка прав доступа

        $dataobj = new StdClass();
        $dataobj->partnerid = $this->id;
        $dataobj->programid = $programid;
        $dataobj->assignedby = $USER->id;

        $dataobj->id = (int) $DB->insert_record('lm_partner_program', $dataobj);

        $this->recalculate_appointed_programs();

        return $dataobj->id;
    }

    /**
     * Возвращает пользователя, который назначил этому партнеру программу $programid
     *
     * @param $programid
     * @return mixed
     * @throws dml_missing_record_exception
     * @throws dml_multiple_records_exception
     */
    public function who_assigned_program($programid){
        global $DB;

        $sql = "SELECT lpp.id as assignid, u.*
                     FROM {lm_partner_program} lpp
                     LEFT JOIN {user} u ON lpp.assignedby=u.id
                     WHERE lpp.programid={$programid} AND lpp.partnerid={$this->id}";

        return $DB->get_record_sql($sql);
    }

    /**
     * Снимаем программу с партнера
     *
     * @param $programid
     * @return bool
     */
    public function disappoint_program($appointedid){
        global $DB;

        //TODO: Проверка прав доступа
        $success = (boolean) $DB->delete_records('lm_partner_program', array('id'=>$appointedid));
        $this->recalculate_appointed_programs();

        return $success;
    }

    /**
     * Возвращает период обучения для программы
     *
     * @param $programid
     * @return int
     */
    public function get_program_period($programid){
        global $DB;

        return (int) $DB->get_field('lm_program', 'period', array('id'=>$programid));
    }

    /**
     * Возвращает SQL для получения списка назначенных программ партнеру
     *
     * @return string
     */
    private function get_appointed_programs_sql(){
        $sql = "SELECT lp.*, lpa.id as aid
              FROM {lm_partner_program} lpa
              JOIN {lm_program} lp ON lpa.programid=lp.id
              WHERE partnerid=:partnerid
              ORDER BY lp.name ASC";

        return $sql;
    }

    /**
     * Возвращает список назначенных программ партнеру, в качестве значений массива StdClass
     *
     * @return array
     */
    public function get_appointed_programs(){
        global $DB;
        return $DB->get_records_sql($this->get_appointed_programs_sql(), array('partnerid'=>$this->id));
    }

    /**
     * Возвращает список назначенных программ партнеру, в качестве ключей массива - id-программы,
     * а в качестве значений массива - название программы.
     *
     * @return array
     */
    public function get_appointed_programs_menu(){
        global $DB;
        return $DB->get_records_sql_menu($this->get_appointed_programs_sql(), array('partnerid'=>$this->id));
    }

    /**
     * Возвращает массив с идентификаторами программ, назначенных партнеру
     * @param $userid
     *
     * @return array
     */
    public function get_appointed_programs_ids($userid=0){
        global $CFG, $DB;

        if($CFG->lm_matrix_enabled) {
            $stageid = 0;
            $postid = 0;
            if($CFG->lm_matrix_enabled && $userid) {
                $staffer = lm_staffer::i($this->id, $userid);
                $postid = $staffer->post()->get_id();
                $stageid = $staffer->get_stageid();
            }

            $programs = lm_matrix::programs_menu($postid, $stageid);
        }else{
            $programs = $DB->get_records_menu('lm_partner_program', array('partnerid' => $this->id), '', 'id, programid');

        }

        return $programs;
    }

    /**
     * Подсчитывает кол-во программ, назначенных партнеру и кэширует это значение в таблице lm_partner
     * для использования в сортировке
     *
     * @return int
     */
    public function recalculate_appointed_programs(){
        global $DB;

        $count = (int) $DB->count_records('lm_partner_program', array('partnerid'=>$this->id));
        if($count != $this->programscount){
            $this->programscount = $count;

            $dataobj = new StdClass();
            $dataobj->id = $this->id;
            $dataobj->programscount = $this->programscount;
            $DB->update_record('lm_partner', $dataobj);
        }
    }

    /**
     * Возвращает количество назначенных программ партнеру
     *
     * @return int
     */
    public function count_appointed_programs(){
        return $this->programscount;
    }

    /**
     * Возвращает количество обученных/не обученных сотрудников по программе у этого партнера
     * Если идентификатор программы не задан, то возвращает количество по всем программам, назначенным
     * партнеру. Возвращаемый результат в виде массива с ключами:
     * -trained  - кол-во обученных
     * -nottrained  - кол-во не обученных
     * -members  - кол-во участнистников, которое должно быть обучено, чтобы достигнуть 100%
     *
     * @param int $programid
     * @return array
     * @throws coding_exception
     */
    public function count_trained($programid=0){
        global $DB;

        $count = array();
        $count['trained'] = 0;
        $count['nottrained'] = 0;
        $count['members'] = 0;

        if(!$programid){
            if($programs = $this->get_appointed_programs()){
                foreach($programs as $program){
                    $counttmp = $this->count_trained($program->id);
                    $count['trained'] += $counttmp['trained'];
                    $count['nottrained'] += $counttmp['nottrained'];
                    $count['members'] += $counttmp['members'];
                }
            }
        }else {

            $sql = "SELECT COUNT(DISTINCT(lar.userid))
                      FROM {lm_activity_request} lar
                      JOIN {lm_activity} la ON la.id=lar.activityid
                      JOIN {lm_partner_program} pa ON pa.partnerid={$this->id} AND pa.programid=la.programid
                      JOIN {lm_partner_staff} lps ON lps.userid=lar.userid AND lps.archive=0
                      WHERE la.programid = {$programid} AND lar.partnerid = {$this->id} AND lar.passed > 0";

            $count['trained'] = $DB->count_records_sql($sql);
            $count['members'] = $this->count_staffers();
            $count['nottrained'] = $count['members'] - $count['trained'];
        }

        return $count;
    }

    /**
     * Пересчитывает процент обученных сотрудников у партнера по программе/в целом
     * и кэширует это значение в таблице lm_partner_program/lm_partner соответственно.
     * Эти данные в дальнейшем используются в сортировках, отчетах и т.п, чтобы увеличить скорость работы.
     *
     * @return boolean
     */
    public function recalculate_trained_percent($programid=0){
        global $DB;

        $percent = $this->calculate_trained_percent($programid);

        // Обновляем % обученности в целом по партнеру, иначе по программе
        if(!$programid && $percent != $this->trainedpercent){
            $this->trainedpercent = $percent;

            $dataobj = new StdClass();
            $dataobj->id = (integer) $this->id;
            $dataobj->trainedpercent = $percent;

            return $DB->update_record('lm_partner', $dataobj);

        }else if($programid){
            $sql = "UPDATE {lm_partner_program} SET trainedpercent={$percent} WHERE programid={$programid} AND partnerid={$this->id}";
            $DB->execute($sql);

            if($this->trainedbyprogram !== NULL) {
                $this->trainedbyprogram[$programid] = $percent;
            }
        }

        return false;
    }

    /**
     * Запускает пересчет процентов обученных сотрудников по всем программам и в целом по партнеру
     */
    public function recalculateall_trained_percent(){
        // Пересчитываем % обученных в целом по партнеру
        $this->recalculate_trained_percent();

        // Пересчитываем % обученных по программам, назначенным партнеру
        if($programs = $this->get_appointed_programs_ids()){
            foreach($programs as $programid){
                $this->recalculate_trained_percent($programid);
            }

            // Обнуляем кэш в рамках этого класса, чтобы избежать использования не актуальных данных
            $this->trainedbyprogram = NULL;
        }
    }

    /**
     * Подсчитывает процент обученных сотрудников по программе
     *
     * @param int $programid
     * @return float
     */
    public function calculate_trained_percent($programid=0){
        $count = $this->count_trained($programid);

        if($count['members'] && $count['trained']){
            return round( ($count['trained']/$count['members'])*100, 2 );
        }

        return 0;
    }

    /**
     * Возвращает долю обученных сотрудников у этого партнера, причем не пересчитывает эти значения, а
     * берет их из кэша (из соотв. таблиц в БД)
     *
     * @param int $programid
     * @return float
     */
    public function trained_percent($programid=0){
        global $DB;

        if(!$programid){
            return $this->trainedpercent;
        }else{
            if($this->trainedbyprogram === NULL){
                $this->trainedbyprogram = $DB->get_records_menu('lm_partner_program', array('partnerid'=>$this->id), '', 'programid, trainedpercent');
            }

            if($this->trainedbyprogram && isset($this->trainedbyprogram[$programid])){
                return $this->trainedbyprogram[$programid];
            }
        }

        return 0;
    }

    /**
     * Выбирает участников, которые успешно прошли тренинг. Если $programid = 0, то
     * возвращает результаты по всем програмам партнера.
     *
     * @param $partnerid
     * @param int $programid
     * @return array
     */
    public function get_passed_members($programid=0){
        global $DB;

        $params = array($this->id);

        $where = "";
        if($programid){
            $where = "AND programid=?";
            $params[] = $programid;
        }



        $sql = "SELECT *
                      FROM (
                            SELECT lar.id as reqid, la.programid, u.id as userid, u.email, u.username, u.picture,
                                   u.imagealt, u.firstname, u.lastname, lar.passed
                                FROM {user} u
                                JOIN {lm_activity_request} lar ON lar.userid=u.id
                                JOIN {lm_activity} la ON la.id=lar.activityid
                                JOIN {lm_partner_staff} lps ON lps.partnerid=lar.partnerid AND lps.userid=lar.userid
                                WHERE lar.partnerid = ? AND lar.passed > 0 AND lps.archive = 0  $where
                                ORDER BY u.lastname ASC, u.firstname ASC
                      ) p
                      GROUP BY p.userid, p.programid";

        return $DB->get_records_sql($sql, $params);


    }

    /**
     * Выбирает участников, которые записались на тренинг (или не явились). Если $programid = 0, то
     * возвращает результаты по всем программам партнера.
     *
     * @param $partnerid
     * @param int $programid
     * @return array
     */
    public function get_requested_members($programid=0){
        global $DB;

        $params = array($this->id);

        $where = "";
        if($programid){
            $where = "AND programid=?";
            $params[] = $programid;
        }

        $sql = "SELECT lar.id as reqid, la.programid, u.id, u.email, u.username, u.picture, u.imagealt, u.firstname, u.lastname, lar.passed
                      FROM {user} u
                      JOIN {lm_activity_request} lar ON lar.userid=u.id
                      JOIN {lm_activity} la ON la.id=lar.activityid
                      WHERE partnerid = ? AND passed <= 0 $where
                      ORDER BY u.lastname ASC, u.firstname ASC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Возвращает название города
     *
     * @return mixed
     */
    public function get_region_name(){
        global $DB;

        return $DB->get_field('lm_region', 'name', array('id'=>$this->regionid));
    }

    /**
     * Возвращает список не используемых регионов для этого партнера. Т.е. отвечает на вопрос
     * в каких регионах нет партнера с компанией такой же как у него.
     *
     * @param $selfregioninclude boolean - Включать ли информацию о регионе этого партнера
     */
    public function get_unused_regions($selfregioninclude=true){
        global $DB;

        if($selfregioninclude && $this->regionid){
            $where = " AND lp.regionid != {$this->regionid}";
        }

        if($this->companyid) {
            $sql = "SELECT lr.id, lr.name
                        FROM(
                            SELECT lp.regionid FROM {lm_partner} lp WHERE lp.companyid={$this->companyid} {$where}
                        ) lp
                        RIGHT JOIN {lm_region} lr ON lp.regionid=lr.id
                        WHERE lp.regionid IS NULL";

            return $DB->get_records_sql_menu($sql);
        }else{
            return get_regions_menu();
        }
    }

    /**
     * Изменяет регион партнера
     *
     * @param $newregionid
     * @return bool
     */
    public function update_region($newregionid){
        global $DB;

        if(!$this->has_capability_edit()){
            return false;
        }

        // В одном городе может быть только один партнер у компании
        if($newregionid) {
            if ($DB->record_exists('lm_partner', array('companyid' => $this->companyid, 'regionid' => $newregionid))) {
                return false;
            }
        }

        $dataobj = new StdClass();
        $dataobj->id = $this->id;
        $dataobj->regionid = $newregionid;

        return $DB->update_record('lm_partner', $dataobj);
    }

    /**
     * Назначает партнеру глобальную группу, в которую будут записываться пользователи при добавлении в сотрудники
     *
     * @param $cohortid
     */
    public function assign_cohort($cohortid){
        global $DB;

        if(!$DB->record_exists('cohort', array('id'=> $cohortid))){
            return false;
        }

        $staffers = $this->get_staffers();

        // Если была назначена группа ранее, то необходимо перед
        // записью в новую группу сначала убрать из нее сотрудников
        if($this->cohortid && $staffers){
            foreach($staffers as $staffer) {
                lm_cohort::remove_member($this->cohortid, $staffer->id);
            }
        }

        if($staffers && $cohortid){
            foreach($staffers as $staffer){
                lm_cohort::add_member($cohortid, $staffer->id);
            }
        }

        $this->cohortid = $cohortid;

        $dataobj = new StdClass();
        $dataobj->id = $this->id;
        $dataobj->cohortid = $this->cohortid;
        $DB->update_record('lm_partner', $dataobj);

        return true;
    }

    /**
     * Имеет ли право текущий пользвователь редактировать партнера
     *
     * @return bool
     */
    public function has_capability_edit(){

        if( has_capability('block/manage:partneredit', context_system::instance()) &&
            is_my_region($this->regionid)
        ){
            return true;
        }

        return false;
    }

    /**
     * Имеет ли право текущий пользователь просматривать партнера
     *
     * @return bool
     */
    public function has_capability_view(){
        global $USER;

        /*if($this->repid == $USER->id || $this->respid == $USER->id){
            return true;
        }

        if(is_my_region($this->regionid)){
            return true;
        }*/

        if(has_capability('block/manage:allregions', context_system::instance())){
            return true;
        }

        return false;
    }

    /**
     * Имеет ли право текущий пользовать управлять программами партнера
     *
     * @return bool
     * @throws Exception
     * @throws coding_exception
     * @throws dml_exception
     */
    public function has_capability_manageprograms(){
        global $USER;

        if(lm_user::is_admin()){
            return true;
        }

        // Если это ответственный за партнера
        /*if($this->respid == $USER->id){
            return true;
        }

        // Если это контактное лицо партнера
        if($this->repid == $USER->id){
            return false;
        }*/

        if( has_capability('block/manage:partneredit', context_system::instance()) &&
            is_my_region($this->regionid)
        ){
            return true;
        }

        return false;
    }
}