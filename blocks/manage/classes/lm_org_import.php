<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 6/29/2015
 * Time: 3:57 PM
 */

class lm_org_import extends lm_base_import {

    public $iid = 0;

    /**
     * @var lm_unireader
     */
    public $reader = NULL;

    /**
     * Партнер, к которому будут привязываться территории (торговые точки)
     * @var null
     */
    public $addtoparterid = 1;


    protected static $start_from = 4;

    /**
     * Внешний идентификатор позиции в орг структуре
     *
     * @var null
     */
    protected static $k_code = NULL;

    /**
     * Внешний идентификатор, указывающий на родительскую позицию для позиции в орг структуре
     *
     * @var null
     */
    protected static $k_parentcode = NULL;

    /**
     * Внешний идентификатор сотрудника
     *
     * @var null
     */
    protected static $k_staffercode = NULL;

    /**
     * Название города, к которому привязана позиция
     *
     * @var null
     */
    protected static $k_citycode = NULL;

    /**
     * Внешний идентификатор должности
     *
     * @var null
     */
    protected static $k_postcode = NULL;

    /**
     * Название должности
     *
     * @var int
     */
    protected static $k_postname = NULL;

    /**
     * Внешний идентификатор канала сбыта
     *
     * @var null
     */
    protected static $k_distribcode = NULL;

    /**
     * Название канала сбыта
     *
     * @var int
     */
    protected static $k_distribname = NULL;

    /**
     * Внешний идентификатор, определяющий код территории
     *
     * @var null
     */
    protected static $k_areacode = NULL;

    /**
     * Дата назначения на должность
     *
     * @var null
     */
    protected static $k_dateassignment = NULL;


    protected static $required = array();

    public function __construct($file) {
        // Large files are likely to take their time and memory. Let PHP know
        // that we'll take longer, and that the process should be recycled soon
        // to free up memory.
        @set_time_limit(0);
        raise_memory_limit(MEMORY_EXTRA);

        $this->reader = new lm_unireader($file);
        $this->iid = $this->reader->iid;
    }

    public function k_settings()
    {
        self::$required = array('k_idnum', 'k_email',
            'k_fullname'=> array('k_lastname', 'k_firstname') // значит k_fullname OR k_lastname AND k_firstname
        );

        if($this->reader && $this->reader->filetype == 'xml'){
            self::$start_from = 0;
            self::$k_code = "id";
            self::$k_parentcode = "parentcode";
            self::$k_staffercode = "staffercode";
            self::$k_citycode = "cityid";
            self::$k_postcode = "id";
            self::$k_postname = "name";
            self::$k_distribcode = "id";
            self::$k_distribname = "name";
            self::$k_areacode = "areaid";
            self::$k_dateassignment = 'dateassignment';
        }
    }

    public function import($step = NULL) {
        $errors = array();
        $out   = '';

        if ($step === 1 || is_null($step)) {
            $this->reader->start();
            $this->k_settings();
            list($errors, $out) = $this->step1();
        }

        return array($errors, $out);
    }

    private function step1() {
        global $DB;
        
        $errors = array();

        $n = 0;
        while ($item = $this->reader->next()) {
            $n ++;

            if($n >= self::$start_from ){
                $code           = $item['_attrs'][self::$k_code];
                $parentcode     = $item[self::$k_parentcode];
                $staffercode    = $item[self::$k_staffercode];
                $citycode       = $item[self::$k_citycode];
                $postcode       = $item['post']['_attrs'][self::$k_postcode];
                $postname       = trim($item['post'][self::$k_postname]);
                $distribcode    = $item['distribchannel']['_attrs'][self::$k_distribcode];
                $distribname    = trim($item['distribchannel'][self::$k_distribname]);
                $areacode       = $item[self::$k_areacode];
                $dateassignment = $item[self::$k_dateassignment];

                // Поиск города
                if( ! $citycode || !$cityid = lm_city::get_id_by_code($citycode)) $cityid = 0;

                // Поиск территории
                //TODO: need to create class lm_area
                //if( ! $areacode || !$areaid = lm_area::get_id_by_code($areacode)) $areaid = 0;
                if( ! $areacode || !$areaid = $DB->get_field('lm_area', 'id', array('code' => $areacode))) $areaid = 0;

                // Поиск и создание должности
                $post = lm_post::get_by_code($postcode);
                if ( $postcode && !$post->get_id() ) {
                    $post = lm_post::i(0)->set_code($postcode)->set_name($postname)->create();
                }

                // Поиск и создание канала сбыта
                $distrib = lm_distrib::get_by_code($distribcode);
                if ( $distribcode && !$distrib->get_id() ) {
                    $distrib = lm_distrib::i(0)->set_code($distribcode)->set_name($distribname)->create();
                }

                // Проверяем существование позиции в оргструктуре
                $posid = lm_position::posid_by_code($code);

                $position = new lm_position(null);
                $position->code = $code;
                $position->parentcode = $parentcode;
                $position->postcode = $postcode;
                $position->cityid = $cityid;
                $position->postid = $post->get_id();
                $position->areaid = $areaid;
                $position->distribid = $distrib->get_id();

                if ($posid) {
                    $position->id = $posid;
                    $position->update();
                } else {
                    $position->create();
                    $posid = $position->get_id();
                }

                // Проверяем назначение сотрудника на позицию
                if( $pos_xrefs = lm_position::get_staffer_xrefs($staffercode, $posid) ) {
                    $pos_xref = array_shift($pos_xrefs);
                    if( count($pos_xrefs) ){
                        // TODO: предупреждение в лог о том, что существует несколько записей с одним и тем же сотрудником на позицию
                    }else{
                        if( $pos_xref->staffercode != $staffercode ){
                            // Помещаем предыдущего сотрудника в архив по этой позиции
                            lm_position::update_staffer_xref_archive($pos_xref->id, 1);
                            // Назначаем нового сотрудника на позицию
                            lm_position::insert_staffer_xref($staffercode, $posid, $dateassignment);
                        }
                    }
                }else{
                    // Назначаем нового сотрудника на позицию
                    lm_position::insert_staffer_xref($staffercode, $posid, $dateassignment);
                }
            }
        }

        lm_position::make_correct();

        $out = "";

        return array($errors, $out);
    }
}