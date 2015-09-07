<?php
class block_manage_places_renderer extends block_manage_renderer {
    public $pageurl = '/blocks/manage/?_p=places';
    public $pagename = 'Места проведения';
    public $placeid = 0;
    public $subtype = 'class';
    public $type = 'manage_places';
    public $place = NULL;

    public function init_page(){


        $this->placeid = optional_param('id', 0, PARAM_INT);
        if($this->placeid){
            $this->place = lm_place::i($this->placeid);
        }
        $this->subtype = $this->place ? $this->place->type: optional_param('type', 'class', PARAM_TEXT);
        $this->pageurl = $this->pageurl.'&type='.$this->subtype;

        if($this->subtype == 'class'){
            $this->pagename = 'Классы';
        }else{
            $this->pagename = 'Торговые точки';
        }

        parent::init_page();
        $this->page->requires->js('/blocks/manage/yui/bootstrap-editable/bootstrap-editable.min.js');
        $this->page->requires->css('/blocks/manage/yui/bootstrap-editable/bootstrap-editable.css');
        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->js('/blocks/manage/yui/places.js');

    }

    public function main_content(){
        if(!has_capability('block/manage:editplaces', context_system::instance())) {
            return 'Вы не имеете прав доступа для просмотра этой страницы!';
        }

        $this->tpl->placeid = $this->placeid;
        $this->tpl->type = $this->subtype;
        $this->tpl->place_details = $this->tpl->placeid ? $this->get_place_info($this->tpl->placeid): '';
        $this->tpl->places_list = $this->places_table();
        return $this->fetch('place/index.tpl');
    }

    public function places_table($search=''){
        global $DB;
        $perpage = 50;
        $params = array();

        ob_start();
        $table = new flexible_table('activities');

        if($this->subtype == 'class') {
            $table->define_columns(
                array('lp__id', 'lr__name', 'lp__address', 'lp__name', 'lp__partner', 'la__equipment')
            );
            $table->define_headers(array('№', 'Город', 'Адрес', 'Название', 'Партнер', 'Оборудование'));
        }else{
            $table->define_columns(
                array('lp__id', 'lpl__code', 'lr__name', 'lp__address', 'lp__name', 'lp__partner')
            );
            $table->define_headers(array('№', 'Код ТТ', 'Город', 'Адрес', 'Название', 'Партнер'));
        }

        $table->is_sortable = true;
        $table->define_baseurl($this->pageurl);

        $select = "lpl.type='{$this->subtype}'";
        $join = '';
        if($search) {
            $select .= " AND (lpl.code LIKE '%{$search}%' OR lpl.name LIKE '%{$search}%'
            OR lc.name LIKE '{$search}%' OR lp.name LIKE '{$search}%')";
            $join = "LEFT JOIN {lm_partner} lp ON lp.id=lpl.partnerid
                     LEFT JOIN {lm_company} lc ON lc.id=lp.companyid";
        }


        $sql = "SELECT COUNT(lpl.id)
                      FROM {lm_place} lpl
                      LEFT JOIN {lm_region} lr ON lr.id=lpl.cityid
                      {$join}
                      WHERE {$select}";

        $total = $DB->count_records_sql($sql);
        $table->pagesize($perpage, $total);
        $table->setup();

        $sql = "SELECT lpl.*, lr.name as cityname
                      FROM {lm_place} lpl
                      LEFT JOIN {lm_region} lr ON lr.id=lpl.cityid
                      {$join}
                      WHERE {$select}
                      LIMIT ".$this->pagenum*$perpage.", $perpage";
        $places = $DB->get_records_sql($sql, $params);


        if($places){
            $n = $this->pagenum*$perpage + 1;
            foreach($places as $place){
                $oplace = lm_place::i($place);
                $partner = lm_partner::i($place->partnerid);

                if($this->subtype == 'class') {
                    $cells = array($n,
                        $place->cityname,
                        $oplace->street,
                        $oplace->name,
                        $partner->fullname(),
                        $oplace->get_equipment_info()
                    );
                }else{
                    $cells = array($n,
                        $place->code,
                        $place->cityname,
                        $oplace->street,
                        $oplace->name,
                        $partner->fullname()
                    );
                }
                $table->add_data($cells, 'row-place place-'.$place->id);
                $n ++;
            }
        }

        $table->finish_output();
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public function get_place_info($placeid){
        $place = lm_place::i($placeid);
        $this->tpl->canedit = $place->has_capability_edit();

        $this->tpl->fields = array();

        // ОБЩАЯ ИНФОРМАЦИЯ
        $this->tpl->fields[] = (object) array('type'=>'headline', 'name'=>'Общая информация');
        $this->tpl->fields[] = (object) array('type'=>'select', 'name'=>'Тип', 'code'=>'type', 'val'=>$place->type,
                'title'=>'Тип места', 'emptytext'=>'Не указано', 'source'=>'/blocks/manage/?__ajc=places::types'
        );

        if($this->subtype == 'tt') {
            $this->tpl->fields[] = (object)array('type' => 'text', 'name' => 'Код ТТ', 'code' => 'code', 'val' => $place->code,
                'title' => 'Введите код торговой точки', 'emptytext' => 'Не указано', 'source' => false
            );
        }

        $this->tpl->fields[] = (object) array('type'=>'text', 'name'=>'Название', 'code'=>'name', 'val'=>$place->name,
            'title'=>'Введите название аудитории', 'emptytext'=>'Не указано', 'source'=>false
        );

        $this->tpl->fields[] = (object) array('type'=>'select', 'name'=>'Партнер', 'code'=>'partnerid', 'val'=>$place->partnerid,
            'title'=>'Какому партнеру принадлежит аудитория?', 'emptytext'=>'Не указано', 'source'=>'/blocks/manage/ajax.php?ajc=get_partners_list'
        );


        // ХАРАКТЕРИСТИКИ АУДИТОРИИ
        if($this->subtype == 'class') {
            $this->tpl->fields[] = (object)array('type' => 'headline', 'name' => 'Характеристики аудитории');
            $this->tpl->fields[] = (object)array('type' => 'text', 'name' => 'Вместимость', 'code' => 'capacity', 'val' => $place->capacity,
                'title' => 'Введите вместимость аудитории', 'emptytext' => 'Не указано', 'source' => false
            );

            $this->tpl->fields[] = (object)array('type' => 'checklist', 'name' => 'Партнер', 'code' => 'equipment', 'val' => $place->equip_info_json(),
                'title' => 'Выберите оборудование', 'emptytext' => 'Не указано', 'source' => '/blocks/manage/ajax.php?ajc=get_equip_options'
            );
        }

        // КОНТАКТНОЕ ЛИЦО
        $this->tpl->fields[] = (object) array('type'=>'headline', 'name'=>'Контактное лицо');
        $this->tpl->fields[] = (object) array('type'=>'text', 'name'=>'ФИО', 'code'=>'contactname', 'val'=>$place->contactname,
            'title'=>'Введите ФИО контактного лица', 'emptytext'=>'Не указано', 'source'=>false
        );

        $this->tpl->fields[] = (object) array('type'=>'text', 'name'=>'Телефон', 'code'=>'contactphone', 'val'=>$place->contactphone,
            'title'=>'Введите номер телефона контактного лица', 'emptytext'=>'Не указано', 'source'=>false
        );

        $this->tpl->fields[] = (object) array('type'=>'text', 'name'=>'Email', 'code'=>'contactemail', 'val'=>$place->contactemail,
            'title'=>'Введите email контактного лица', 'emptytext'=>'Не указано', 'source'=>false
        );

        // ОТВЕТСТВЕННЫЙ ЗА ТОРГОВУЮ ТОЧКУ
        if($this->subtype == 'tt') {

            $val = $this->manager_view($place->tmid, 'tmid');
            $this->tpl->fields[] = (object)array('type' => 'headline', 'name' => 'ТМ');
            $this->tpl->fields[] = (object)array('type' => 'html', 'name' => 'Пользователь', 'html'=>$val);

            $val = $this->manager_view($place->trainerid, 'trainerid');
            $this->tpl->fields[] = (object)array('type' => 'headline', 'name' => 'Тренер');
            $this->tpl->fields[] = (object)array('type' => 'html', 'name' => 'Пользователь', 'html'=>$val);

            $val = $this->manager_view($place->respid, 'respid');
            $this->tpl->fields[] = (object)array('type' => 'headline', 'name' => 'Ответственный за торговую точку');
            $this->tpl->fields[] = (object)array('type' => 'html', 'name' => 'Пользователь', 'html'=>$val);

            $val = $this->manager_view($place->repid, 'repid');
            $this->tpl->fields[] = (object)array('type' => 'headline', 'name' => 'Контактное лицо');
            $this->tpl->fields[] = (object)array('type' => 'html', 'name' => 'Пользователь', 'html'=>$val);
        }

        // КАК ДОБРАТЬСЯ
        $this->tpl->fields[] = (object) array('type'=>'headline', 'name'=>'Как добраться?');
        $this->tpl->fields[] = (object) array('type'=>'address', 'name'=>'Адрес', 'code'=>'address', 'val'=>false,
            'title'=>'Введите адрес', 'emptytext'=>'Не указано', 'source'=>false
        );

        if($this->subtype == 'class') {
            $this->tpl->fields[] = (object)array('type' => 'text', 'name' => 'Как нас найти', 'code' => 'comment', 'val' => $place->comment,
                'title' => 'Как нас найти?', 'emptytext' => 'Не указано', 'source' => false
            );

            $this->tpl->fields[] = (object)array('type' => 'text', 'name' => 'Что необходимо иметь при себе для прохода на территорию?', 'code' => 'comment2', 'val' => $place->comment2,
                'title' => 'Что необходимо иметь при себе для прохода на территорию?', 'emptytext' => 'Не указано', 'source' => false
            );
        }

        return $this->fetch('place/details.tpl');
    }

    public function manager_view($user, $type){
        global $CFG, $DB, $OUTPUT;

        if(!is_object($user)){
            if(!$user = $DB->get_record('user', array('id'=>$user)) ){
                $user = guest_user();
            }
        }

        $rp = (object) array('appointed'=>false, 'fullname'=>null, 'pic'=>null, 'type'=>$type);

        if($user){
            $rp->pic = $OUTPUT->user_picture($user, array('size' => 50));
        }
        if($user && $user->id != $CFG->siteguest){
            $rp->appointed = true;
            $rp->fullname = fullname($user);
        }

        $this->tpl->u = $rp;
        return $this->fetch('place/manager.tpl');
    }






    public function ajax_appoint_manager($p){
        $a = (object) array('html'=>'', 'success'=>false);
        if(!empty($p->placeid) && !empty($p->userid) && !empty($p->type)) {
            $place = lm_place::i($p->placeid);

            if($place->appoint_manager($p->userid, $p->type)){
                $a->html = $this->manager_view($p->userid, $p->type);
                $a->success = true;
            }
        }

        return $a;
    }

    public function ajax_types(){
        $a = array();
        $a[] = (object) array('value'=>0, 'text'=>'Не выбрано');
        if($types = lm_place::get_types()){
            foreach($types as $code=>$name){
                $a[] = (object) array('value'=>$code, 'text'=>$name);
            }
        }

        return $a;
    }

    public function ajax_place_get_address(){
        $placeid = optional_param('pk', 0, PARAM_INT);
        $a = new StdClass();

        $place = lm_place::i($placeid);
        if($place->id){
            $a->cityid = (object) array('value'=>$place->cityid, 'text'=>$place->city_name());
            $a->street = $place->street;
            $a->metro = $place->metro;
            $a->num = $place->num;
            $a->bld = $place->bld;
            $a->corp = $place->corp;
            $a->floor = $place->floor;
        }

        return $a;
    }

    public function ajax_search(){
        $resetpage = (boolean) optional_param('resetpage', true, PARAM_BOOL);
        if(!$resetpage){
            $this->pagenum = optional_param('page', 0, PARAM_INT);
        }

        $search = optional_param('value', '', PARAM_TEXT);

        $a = new StdClass();
        $a->html = $this->places_table($search);

        return $a;
    }
}