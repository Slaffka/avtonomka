<?php
require_once($CFG->dirroot.'/user/lib.php');

class lm_user extends stdClass
{
    private static $i = NULL;

    public $userid = 0;
    public $hiredate = NULL;

    /**
     * @param $userid
     * @return lm_user
     */
    static public function i($userid){
        $user = 0;
        if($userid && is_object($userid)){
            $user = clone $userid;
            $userid = $user->id;
        }else if($userid) {
            $user = $userid;
        }

        if(!isset(self::$i[$userid]) || !$userid){
            self::$i[$userid] = new lm_user($user);
        }

        return self::$i[$userid];
    }

    private function __construct($userid){
        global $DB;

        $user = null;

        if($userid && is_object($userid)){
            $user = $userid;
        }else if($userid) {
            $user = $DB->get_record('user', array('id' => $userid));
            $lm_user = $DB->get_record('lm_user', array('userid' => $userid));
            foreach($lm_user as $prop => $value) {
                if ( ! isset($user->$prop)) $user->$prop = $value;
            }
        }

        if ($user) {
            foreach ($user as $field => $value) {
                $this->$field = $value;
            }
        }

        return $this;
    }

    /**
     * Возвращает ФИО сотрудника
     *
     * @return string
     */
    public function fullname(){
        return $this->lastname.' '.$this->firstname;
    }

    /**
     * Возвращает email сотрудника
     *
     * @return string
     */
    public function email(){
        return $this->email;
    }

    public static function short_name($user)
    {
        if(!isset($user->firstname) && !isset($user->lastname)){
            return false;
        }

        $firstname = explode(" ", $user->firstname);
        $fio = $user->lastname.' '.mb_strtoupper(mb_substr($firstname[0], 0, 1, 'utf8')).'. ';
        if(isset($firstname[1]) && $firstname[1]){
            $fio .= mb_strtoupper(mb_substr($firstname[1], 0, 1, 'utf8')).'.';
        }
        return $fio;
    }

    /**
     * Возвращает ссылку на профиль сотрудника
     *
     * @return string
     */
    public function link(){
        return '<a href="/user/view.php?id='.$this->id.'" target="_blank">'.$this->fullname().'</a>';
    }

    /**
     * @param $usernew
     * @return stdclass
     * @throws moodle_exception
     */
    public static function create($usernew)
    {
        global $DB, $CFG;

        $usernew->username = $usernew->email = core_text::strtolower($usernew->email);
        $isexists = $DB->record_exists_select('user', 'email LIKE ? OR username LIKE ?', array($usernew->email, $usernew->email));
        if($isexists){
            return NULL;
        }

        $usernew->auth = 'manual';
        $usernew->mnethostid = $CFG->mnet_localhost_id; // always local user
        $usernew->confirmed  = 1;
        $usernew->timecreated = time();
        $usernew->rawpassword = $usernew->password;
        $usernew->password = hash_internal_user_password($usernew->password);
        //$usernew->institution = $usernew->name;
        $usernew->lang        = 'ru';
        $usernew->firstaccess = time();
        $usernew->timecreated = time();
        $usernew->mnethostid  = $CFG->mnet_localhost_id;
        $usernew->secret      = random_string(15);

        $usernew->id = user_create_user($usernew, false);

        return $usernew;
    }


    public function post(){
        $postid = lm_mypost::get_post_id($this->userid);
        return lm_post::i($postid);
    }

    public static function get_partnerid($userid = NULL){
        global $DB, $USER;

        if (empty($userid)) $userid = $USER->id;

        return (int) $DB->get_field('lm_partner_staff', 'partnerid', array('userid' => $userid));
    }


    //////////////////////// ВИРТУАЛЬНЫЕ РОЛИ //////////////////////////////////////////
    /**
     * Является ли текущий пользователем сотрудником какой-нибудь компании?
     *
     * @return bool
     */
    public static function is_staffer(){
        global $DB, $USER;

        return $DB->record_exists('lm_partner_staff', array('userid'=>$USER->id));
    }

    /**
     * Является ли текущий пользователь представителем (контактным лицом) какой-либо компании?
     *
     * @return bool
     */
    public static function is_rep(){
        global $USER;

        if(lm_user::is_admin($USER->id)){
            return false;
        }

        return (boolean) get_my_company_id();
    }

    /**
     * Является ли текущий пользователем ответственным за какого-либо партнера?
     *
     * @return bool
     */
    public static function is_responsible(){
        global $DB, $USER;

        return $DB->record_exists('lm_place', array('respid'=>$USER->id));
    }

    /**
     * Является ли текущий пользователь ТМом?
     *
     * @return bool
     */
    public static function is_tm(){
        global $DB, $USER;

        return $DB->record_exists('lm_place', array('tmid'=>$USER->id));
    }

    /**
     * Является ли текущий пользователем тренером?
     *
     * @return bool
     */
    public static function is_trainer(){
        return (boolean) get_my_regions();
    }

    /**
     *
     * @param $userid
     * @return bool
     */
    public static function is_admin($userid=0){
        global $CFG, $USER;

        if(!$userid){
            $userid = $USER->id;
        }

        if($CFG->siteadmins){
            $admins = explode(',', $CFG->siteadmins);
            if(in_array($userid, $admins)){
                return true;
            }
        }

        return false;
    }
}