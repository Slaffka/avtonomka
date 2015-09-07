<?php

class block_manage_renderer extends plugin_renderer_base {
    /**
     * Базовая ссылка страницы
     * @var string
     */
    public $pageurl = NULL;

    /**
     * Название страницы, может использоваться в навигации
     * @var string
     */
    public $pagename = NULL;

    /**
     * Показывать ли кнопку возврата в навигационном меню
     * См. метод lm_subnav(), который формирует навигацию
     * @var bool
     */
    public $subnavback = true;

    public $subpage = NULL;

    /**
     * Номер страницы (если используется пагинация)
     * @var int
     */
    public $pagenum = 0;

    /**
     * Тип слоя. Определяет расположение header, footer, контейнеры для контента и т.п.
     * Смотри в теме папку layout и файл с соотв. названием.
     * @var string
     */
    public $pagelayout = "base";

    /**
     * Тип страницы, от этого параметра зависит расположение блоков
     * @var null
     */
    public $type = NULL;

    /**
     * Включает настройку блоков страницы для каждой роли индивидуально
     * @var bool
     */
    public $rolecustomblocks = false;

    /**
     * Редактируем страницу для должности с id равным $editinpost
     * @var int
     */
    public $editinpost = 0;

    /**
     * Переменная, которая будет доступна в шаблонах smarty
     * @var null|StdClass
     */
    public $tpl = NULL;

    /**
     * @var Smarty
     */
    protected $smarty = NULL;

    public function __construct(moodle_page $page, $target){
        require_login();

        parent::__construct($page, $target);
        if($this->pageurl) {
            $this->editinpost = optional_param('role', 0, PARAM_INT);

            $url = new moodle_url($this->pageurl);

            if($this->editinpost){
                $url->param('role', $this->editinpost);
            }
            if ($this->subpage = optional_param('subpage', 'index', PARAM_TEXT)) {
                $url->param('subpage', $this->subpage);
                $this->pageurl = $url->out_as_local_url();
            }
        }

        $this->init_page();
        $this->tpl = new StdClass();
    }

    public function init_page(){
        global $CFG, $PAGE;

        if( lm_user::is_admin() ){
            // Этот класс используется для стилевого оформления
            $this->page->add_body_class('user-admin');
        }

        $this->require_css('style');

        $this->page->requires->css('/blocks/manage/font-awesome/css/font-awesome.min.css');
        $this->page->requires->js('/blocks/manage/yui/json/jquery.json.min.js');

        /* Это необходимо, чтобы иметь возможность кастомной настройки блоков на подстраницах */
        $pagetype = $this->type.'-'.$this->subpage;

        $postid = $this->page->user_is_editing() ? $this->editinpost: lm_mypost::i()->get_id();

        if($postid) {
            // $postid является числом и возникают проблемы с перетаскиванием блоков (не сохраняются),
            // если $pagetype содержит число. /lib/ajax/blocks.php фильтрует входящий параметр как PARAM_ALPHAEXT
            // Поэтому прийдется преобразовывать число в строковый код, например 123 в abc
            $postcode = "";
            $postid = (string)$postid;

            $alphabet = range('a', 'j');
            $alphabet = array_combine(range(1, count($alphabet)), $alphabet);
            for ($n = 0; $n <= strlen($postid); $n++) {
                $postcode .= $alphabet[$postid{$n}];
            }

            $pagetype .= '-r'.$postcode;
        }

        $this->page->set_url($this->pageurl);
        $PAGE->set_pagelayout($this->pagelayout);
        $PAGE->set_pagetype($pagetype);


        $this->page->set_heading($this->pagename);
        $this->page->set_title($this->pagename);
        $this->page->navbar->ignore_active(false);
        //$this->page->navbar->add(get_string("pluginname", 'block_manage'), new moodle_url($this->pageurl));
        $this->pagenum = optional_param('page', 0, PARAM_INT);

        $this->smarty = new Smarty();

        $this->smarty->template_dir = $CFG->dirroot .'/blocks/manage/tpl/';
        $this->smarty->compile_dir = $CFG->dataroot.'/cache/smarty/templates_c';
        $this->smarty->cache_dir = $CFG->dataroot .'/cache/smarty/cache';

        $this->require_access();
    }

    public function navigation(){
        return '';
    }

    /**
     * Возвращает название блока к которому относится рендерер
     *
     * @return string|null
     */
    public function blockname(){
        $classname = get_class($this);
        $blockname = preg_replace('/(block_)|(_renderer)/', '', $classname);
        if( $classname != $blockname) return $blockname;

        return NULL;
    }

    /**
     * Подключает js/css файл к странице
     *
     * @param $filename
     * @param $type
     * @param null $blockname
     * @throws coding_exception
     */
    protected function require_file($filename, $type, $blockname=NULL){
        global $CFG;

        if( !$blockname ) $blockname = $this->blockname();

        $path = "{$blockname}/$type/$filename.$type";
        $defaultpath = "theme/tibibase/blocks";
        $custompath = "theme/{$CFG->theme}/blocks";
        if( $type == 'js' ){
            $defaultpath = "blocks";
            $custompath = "theme/{$CFG->theme}/blocks";
        }

        if( file_exists($CFG->dirroot."/{$custompath}/{$path}") ) {
            $this->page->requires->css("/{$custompath}/$path");
        }else if( file_exists($CFG->dirroot."/{$defaultpath}/$path") ){
            $this->page->requires->css("/{$defaultpath}/$path");
        }
    }

    public function require_js($filename, $blockname=NULL){
        $this->require_file($filename, 'js', $blockname);
    }

    public function require_css($filename, $blockname=NULL){
        $this->require_file($filename, 'css', $blockname);
    }



    public function require_access(){
        if(!lm_access::has($this->type)) {
            print_error('permissiondenied', 'block_manage');
        }
    }


    public function main_content(){
        echo 'Содержимое не определено!';
    }

    public function fetch($tplpath){
        global $CFG;

        $path = '/blocks/manage/tpl/';
        // Если путь начинается со слеша считаем, что указан относительный путь от корня папки с moodle
        // Это необходимо, чтобы была возможность использовать Smarty в других блоках, зависящих от block_manage
        if($tplpath{0} == '/'){
            $path = '';
        }

        $tplpath = $CFG->dirroot.$path.$tplpath;
        if($this->tpl){
            foreach($this->tpl as $name=>$value) {
                $this->smarty->assign($name, $value);
            }
        }
        return $this->smarty->fetch($tplpath);
    }

    public function display(){
        echo $this->header();
        echo $this->main_content();
        echo $this->footer();
    }

    public function add_pretend_manage_block(block_contents $bc, $pos=BLOCK_POS_LEFT) {
        $this->page->blocks->add_fake_block($bc, $pos);
    }

    public function js_module($module){
        return $this->page->requires->js_module($module);
    }


    public function subnav($subpages, $current=null, $paramname='subpage'){
        $this->tpl->pagetype = $this->type;
        $this->tpl->subpagemenu = array();
        if(!$current) $current = $this->subpage;

        foreach($subpages as $key=>$item){

            if (is_array($item)){
                $pname  = $item['name'];
                $pcode  = !empty($item['code']) ? $item['code'] : "";
                $purl   = $item['url'] ? new moodle_url($item['url']) : new moodle_url($this->pageurl);
                $alerts = (int) $item['alerts'];
            }else{
                $pname  = $item;
                $pcode  = $key;
                $purl   = new moodle_url($this->pageurl);
                $alerts = 0;
            }

            if ($pcode) $purl->param($paramname, $pcode);

            $item = new StdClass();
            $item->name    = $pname;
            $item->url     = $purl;
            $item->class   = $pcode;
            $item->current = $current == $pcode;
            $item->alerts  = $alerts;

            $this->tpl->subpagemenu[] = $item;
        }

        if( $this->subnavback ) {
            $this->tpl->headname = $this->pagename;
            $this->tpl->headurl = $this->pageurl;
        }


        return $this->fetch('general/subnav.tpl');
    }

    public function htmlline($name, array $values, $iseditable=false, $add=''){
        $class = 'nonecontenteditable';
        if($iseditable){
            $class = 'contenteditable';
        }

        if($iseditable && !$add){
            $iseditable = 'true';
        }else{
            $iseditable = 'false';
        }

        $out = '<div><b>'.$name.': </b>';

        foreach($values as $shortname=>$val){
            $out .= '<div class="'.$class.' field-'.$shortname.'" contenteditable="'.$iseditable.'" data-field="'.$shortname.'">'.$val.'</div>';
        }
        if($add){
            $out .= '<div class="ce-addon" style="display:none">'.$add.'</div>';
        }
        $out .= '</div>';

        return $out;
    }

    public function textarea($name, $text, $label='', $editable=false){
        $class = 'nonece-textarea';
        if(!$editable){
            $editable = 'false';
        }else{
            $class = 'contenteditable ce-textarea';
            $editable = 'true';
        }

        $out = '<div><label><b>'.$label.': </b></label>';
        $out .= '<div class="'.$class.' field-'.$name.'" contenteditable="'.$editable.'" data-field="'.$name.'">'.$text.'</div>';
        $out .= '</div>';

        return $out;
    }

    public function select_type($default='partner', $editable=false){
        if(!$editable){
            return $this->htmlline('Аудитория', array('type'=>lm_company::get_type_name($default)), false);
        }

        $select = html_writer::select(lm_company::get_types(), 'field-type', $default, '', array('data-field'=>'partnertype', 'class'=>'ce-select'));
        return $this->htmlline('Аудитория', array('type'=>lm_company::get_type_name($default)), $editable, $select);
    }

    public function select_company($default=0, $editable=false){
        $companies = get_companies_menu();
        if(!isset($companies[$default])){
            $default = 'Не выбрано';
        }else{
            $default = $companies[$default];
        }

        if(!$editable){
            return $this->htmlline('Компания', array('companyid'=>$default), false);
        }

        $select = html_writer::select($companies, 'field-companyid', $default, 'Выберите компанию...', array('data-field'=>'companyid', 'class'=>'ce-select'));
        return $this->htmlline('Компания', array('companyid'=>$default), $editable, $select);
    }

    public function select_region($default=0, $editable=false){
        $regions = get_regions_menu();

        if(!isset($regions[$default])){
            $default = 'Не выбрано';
        }else {
            $default = $regions[$default];
        }

        if(!$editable){
            return $this->htmlline('Город', array('regionid'=>$default), false);
        }

        $select = html_writer::select(get_regions_list(), 'field-regionid', $default, 'Выберите город...', array('data-field'=>'regionid', 'class'=>'ce-select'));
        return $this->htmlline('Город', array('regionid'=>$default), $editable, $select);
    }

    public function select_cohorts($default=0){
        return html_writer::select(lm_cohort::get_menu(), 'field-cohortid', $default, 'Нет', array('data-field'=>'cohortid', 'class'=>'ce-select'));
    }
}