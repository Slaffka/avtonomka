<?php
class lm_profile_block extends block_base{
    protected static $user = NULL;
    public $details_btn = true;
    public $blockname = NULL;
    public $details_url = NULL;
    /**
     * @var block_manage_renderer
     */
    public $renderer = NULL;
    public $pname = "";

    function init() {
        global $PAGE;

        $classname = get_class($this);
        $this->blockname = str_replace('block_', '', $classname);

        $userid = optional_param('id', 0, PARAM_INT);
        if(is_null($this->details_url) && $PAGE->url instanceof moodle_url) {
            $this->details_url = $PAGE->url->out_as_local_url() . '&details='
                . $this->blockname . ($userid ? '&id=' . $userid : '');
        }

        $this->title = $this->get_title();
        if ($this->details_btn) $this->title .= $this->get_details_link();

        if ( ! $PAGE->user_is_editing() && $notifications = lm_notification::get_count($this->blockname, TRUE, self::user()->id)) {
            $this->title .='<div class="alert-right">'.$notifications.'</div>';
        }

        if (is_null($this->page)) {
            $this->page = $PAGE;
        }

        if(!$this->renderer) {
            $this->pname = optional_param('_p', '', PARAM_TEXT);
            $this->renderer = lm_renderer::get($this->pname);
        }

        self::user();

    }

    public static function user() {
        global $USER;
        if( ! self::$user) {
            $userid = optional_param('id', 0, PARAM_INT);
            if ( ! $userid) $userid = $USER->id;
            //self::$user = $DB->get_record('user', array('id' => $userid));
            self::$user = lm_user::i($userid);
        }
        return self::$user;
    }

    public function get_title()
    {
        if ($this->details_url) {
            return '<a href="'.$this->details_url.'" class="lm-block-title">'
                        .get_string('pluginname', 'block_'.$this->blockname)
                    .'</a>';
        } else {
            return get_string('pluginname', 'block_'.$this->blockname);
        }
    }

    public function get_details_link()
    {
        global $PAGE;

        if($this->details_url && !$PAGE->user_is_editing()) { /* Предотвращает появление кнопки "подробнее.." в выпадающих списках */
            return '<a href="'.$this->details_url.'" class="lm-block-details">подробнее...</a>';
        }

        return '';
    }

    function applicable_formats() {
        return array('all' => false, 'manage_profile-*'=>true);
    }

    function instance_allow_multiple() {
        return true;
    }

    function has_config() {
        return false;
    }

    function instance_allow_config() {
        return true;
    }


    /**
     * Устанавливает данные в шаблон виджета, метод срабатывает до вывода в буфер, поэтому можно подключать css и js
     *
     * @param block_manage_renderer $tpl
     */
    public function widget_data($tpl){

    }

    /**
     * Определяет содержимое виджета
     *
     * @return stdClass
     * @throws coding_exception
     */
    public function widget_content(){
        global $CFG;

        if ($this->content !== NULL) return $this->content;

        $this->content = new stdClass();
        if ((!isloggedin() or isguestuser())) {
            $this->content->text = '';
            return $this->content;
        }

        if($this->pname != 'profile'){
            $this->content->text = 'Этот блок поддерживается только на странице профиля LMS';
            return $this->content;
        }

        $this->renderer->require_css('style', $this->blockname);

        $this->widget_data($this->renderer);
        $this->content->text = $this->renderer->fetch("/blocks/{$this->blockname}/tpl/index.tpl");

        // Return the content object
        return $this->content;
    }

    /**
     * Здесь можно подключить стили и js для страницы подробнее или выполнить другие действия до вывода в буфер.
     */
    public function details_pre_hook(){

    }

    /**
     * Определяет содержимое страницы подробнее
     *
     * @return string
     */
    public function details_content(){
        return '';
    }

    /**
     * Этот метод вызывается автоматически при инициализации блока. Его не стоит переопределять в классах-потомках,
     * переопределяйте метод widget_content();
     * TODO: сделать метод финальным
     *
     * @return stdClass
     * @throws coding_exception
     */
    public function get_content() {

        $details = optional_param('details', '', PARAM_TEXT);
        // Если пользователь запрашивает страницу подробнее, не нужно грузить содержимое виджета
        if( strpos($details, 'lm_') !== false ){
            if($details == $this->blockname){
                $this->details_pre_hook();
            }

            return $this->content;
        }

        return $this->widget_content();
    }


    /**
     * Returns the role that best describes the blog menu block.
     *
     * @return string
     */
    public function get_aria_role() {
        return 'navigation';
    }


}