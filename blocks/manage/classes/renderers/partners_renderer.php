<?php
class block_manage_partners_renderer extends block_manage_renderer {
    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=partners';
    public $pagename = 'Управление обучением';
    public $type = 'manage_partners';

    public function init_page(){
        parent::init_page();
        $this->page->requires->js('/blocks/manage/yui/bootstrap-editable/bootstrap-editable.min.js');
        $this->page->requires->css('/blocks/manage/yui/bootstrap-editable/bootstrap-editable.css');
        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->js('/blocks/manage/yui/partners.js');
    }

    public function main_content(){

        $partnerid = optional_param('id', 0, PARAM_INT);
        $style = $content = '';
        if($partnerid){
            $content = $this->get_partner_info($partnerid);
            $style = 'display:none';
        }

        $out = '<div class="instancelist-wrapper" style="'.$style.'">';
        $out .= '<div class="input-append partners-search">
              <input class="span9" type="text" placeholder="Введите для поиска" style="width:350px">
              <span class="add-on"><i class="icon icon-search"></i> </span>
            </div>';

        if(has_capability('block/manage:partneradd', context_system::instance())){
            $out .= '<div class="pull-right">';
            $out .= '<a class="btn btn-link btn-partners-xlexport"><i class="icon icon-download-alt"></i> Выгрузить в excel</a>';
            $out .= '<button class="btn btn-addpartner"><i class="icon icon-plus"></i> Добавить партнера</button>';
            $out .= '</div>';
        }
        $out .= $this->partner_table("");
        $out .= '</div>';

        $out .= html_writer::div($content, 'partnerinfo instanceinfo', array('id'=>'partnerinfo'));


        $out .= $this->fetch('modal/addstaff/wrap.tpl');

        $out .= '
                <div id="addtt-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 id="myModalLabel">Добавить торговую точку</h3>
                  </div>
                  <div class="modal-body">
                  <div class="form-horizontal">
                  <div class="control-group">
                        <label class="control-label" for="ttname">Название ТТ</label>
                        <div class="controls">
                            <input id="ttname" type="text" name="ttname" class="ttname" value=""/>
                        </div>
                  </div>
                  <div class="control-group">
                        <label class="control-label" for="ttname">Код ТТ</label>
                        <div class="controls">
                            <input type="text" name="ttcode" class="ttcode" value=""/>
                        </div>
                  </div>
                  </div>

                  </div>
                  <div class="modal-footer">
                    <button class="btn" data-dismiss="modal" aria-hidden="true">Отмена</button>
                    <button class="btn btn-primary">Добавить</button>
                  </div>
                </div>';

        // В этой штуке будем хранить переменные, которые будем использовать в js
        $out .= '<div id="vars" data-partnerid="'.$partnerid.'"></div>';

        return $out;
    }

    public function partner_table($q=""){
        ob_start();
        $table = new flexible_table('partners');

        // Не получается использовать lp.id (не изменяется направление сортировки)
        // Поэтому используем двойное подчеркивание и после get_sql_sort() заменим их на точки
        $table->define_columns(array('lp__id', 'lc__name', 'lp__programscount', 'lp__trainedpercent'));
        $table->define_headers(array('№', 'Компания', 'Кол-во программ', '% обученных'));
        $table->is_sortable = true;
        $table->define_baseurl($this->pageurl);

        $perpage = 150;


        $table->pagesize($perpage, count_partners($q));
        $table->setup();

        if($sortby = $table->get_sql_sort()){
            $sortby = str_replace('__', '.', $sortby);
            $sortby = "ORDER BY {$sortby}";
        }

        $partners = get_partners($q, $sortby, $this->pagenum, $perpage);

        if($partners){
            $n = $this->pagenum*$perpage+1;
            foreach($partners as $partner){
                $partnerobj = lm_partner::i($partner);
                $cells = array($n,
                    $partnerobj->fullname(),
                    $partnerobj->count_appointed_programs(),
                    $partnerobj->trained_percent()
                );
                $table->add_data($cells, 'row-partner partner-'.$partner->id);
                $n++;
            }
        }


        $table->finish_output();
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public function get_partner_info($partnerid){

        $partner = lm_partner::i($partnerid);
        if( !$partner->id ){
            return 'Такого партнера нет в базе данных!';
        }

        if(!$partner->has_capability_view()){
            return 'Вы не имеете прав доступа для просмотра информации об этом партнере!';
        }

        $iscapedit = $this->tpl->iscapedit = $partner->has_capability_edit();
        $this->tpl->partnerid = $partnerid;
        $this->tpl->iscapartnersview = has_capability('block/manage:partnersview', context_system::instance());
        $this->tpl->select_companies = $this->select_company($partner->companyid, $iscapedit);
        $this->tpl->line = $this->htmlline('Филиал', array('name'=>$partner->name), $iscapedit);
        //$this->tpl->select_region = $this->select_region($partner->regionid, $iscapedit);
        $this->tpl->field_region = new StdClass();
        $this->tpl->field_region->lbl = "Город";
        $this->tpl->field_region->val = $partner->regionid;

        $this->tpl->programs_panel = $this->appointed_programs_panel($partnerid);
        $this->tpl->stafferlist = $this->get_staff_panel($partnerid);
        if($iscapedit) {
            $this->tpl->cohorts_list = $this->select_cohorts($partner->cohortid);
            $this->tpl->tm_list = html_writer::select(
                $partner->tm_list(), 'field-tmid', 0, 'По всем ТМ',
                array("class"=>"filter-element", "data-type"=>"tmid")
            );
            $this->tpl->trainer_list = html_writer::select(
                $partner->trainer_list(), 'field-trainerid', 0, 'По всем тренерам',
                array("class"=>"filter-element", "data-type"=>"trainerid")
            );

            $partner = lm_partner::i($partnerid);
            $ttlist = $partner->tt_list("short");
            $ttlist = array_merge_by_keys(array(-1 => "Не привязаны к ТТ"), $ttlist);
            $this->tpl->tt_list = html_writer::select(
                $ttlist, 'field-ttid', 0, 'По всем ТТ',
                array("class"=>"filter-element", "data-type"=>"ttid")
            );

            $this->tpl->stafferlist_archive = $this->get_staff_panel($partnerid, true);
        }

        $this->tpl->managers = array();
        $managerlist = array('pam' => $partner->get_pam());
        foreach($managerlist as $point=>$user){
            $this->tpl->managers[]  = $this->manager_view($user, $point);
        }

        // Контактное лицо компании не должен видеть комментарий о себе
        $this->tpl->comment = '';
        if($iscapedit){
            $this->tpl->comment = $this->textarea('comment', nl2br($partner->comment), 'Комментарий', $iscapedit);
        }

        $this->tpl->ttlist = array();
        if($tts = $partner->tt_list()){
            foreach($tts as $tt){
                $this->tpl->ttlist[] = $this->tt_view($tt);
            }
        }

        $menu = lm_partner::i($partnerid)->get_appointed_programs_menu();
        $this->tpl->programs_list = html_writer::select($menu, 'menuselectprogram', '', 'Выберите программу...', array('class'=>'pull-right'));
        $this->tpl->resultpanel = $this->result_panel($partnerid);

        return $this->fetch('partner/partner.tpl');
    }


    public function get_manager_points(){
        return array(
            'pam'     => 'Менеджер по работе с партнером (ПАМ)'
        );
    }

    public function manager_view($user, $point){
        global $CFG, $DB, $OUTPUT;

        if(!is_object($user)){
            if(!$user = $DB->get_record('user', array('id'=>$user)) ){
                $user = guest_user();
            }
        }

        $points = $this->get_manager_points();
        $pointname = $points[$point];

        $manager = (object) array('point'=>$point, 'pointname'=>$pointname, 'appointed'=>false, 'fullname'=>null, 'pic'=>null);

        if($user){
            $manager->pic = $OUTPUT->user_picture($user, array('size' => 50));
        }
        if($user && $user->id != $CFG->siteguest){
            $manager->appointed = true;
            $manager->fullname = fullname($user);
        }

        $this->tpl->manager = $manager;
        return $this->fetch('partner/manager.tpl');
    }

    public function tt_view($ttid){
        $tt = lm_place::i($ttid);
        if($tt->id){
            if(!$tt->code) $tt->code = ' - ';
            $this->tpl->tt = (object) array('link'=>$tt->link(), 'name'=>$tt->name(), 'code'=>$tt->code);
            return $this->fetch('partner/tt.tpl');
        }

        return false;
    }

    public function appointed_programs_panel($partnerid){
        global $CFG;

        $partner = lm_partner::i($partnerid);
        $canmanageprograms = $partner->has_capability_manageprograms();

        $table = new html_table();
        $table->id = 'appointedactivities';
        $table->head[] = '№';
        $table->head[] = 'Программа';
        $table->head[] = 'Период обучения';
        if($canmanageprograms)
            $table->head[] = '';

        $cells = array(
            new html_table_cell(''),
            new html_table_cell(''),
            new html_table_cell(''),
            new html_table_cell('<a href="#" class="cancelactivity">отменить</a>')
        );
        $row = new html_table_row($cells);
        $row->attributes['class'] = "clone hide";
        $table->data[] = $row;


        if($appointedprograms = $partner->get_appointed_programs()){
            $n = 1;
            foreach($appointedprograms as $program){
                $name = $program->name;
                if($program->courseid) {
                    $name = html_writer::link($CFG->wwwroot . '/course/view.php?id=' . $program->courseid, $program->name, array("target" => "_blank"));
                }

                $cells = array();
                $cells[] = new html_table_cell($n);
                $cells[] = new html_table_cell($name);
                $cells[] = new html_table_cell($program->period);
                if($canmanageprograms)
                    $cells[] = new html_table_cell('<a href="#" class="cancelactivity">отменить</a>');

                $row = new html_table_row($cells);
                $row->attributes['class'] = "papointedid-".$program->aid." programid-".$program->id;
                $table->data[] = $row;

                $n ++;
            }
        }

        $out = html_writer::table($table);

        if( $canmanageprograms ){
            $out .= html_writer::select(get_programs_list(), 'menuaddprogram', '', 'Добавить программу...');
        }

        return $out;
    }

    public function result_panel($partnerid, $programid=0){
        global $CFG;

        $table = new html_table();
        $passed = lm_partner::i($partnerid)->get_requested_members($programid);
        $n = 1;
        foreach($passed as $member){
            $cells = array();
            $cells[] = $n;
            $link = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$member->id.'" target="_blank">'.$member->lastname.' '.$member->firstname.'</a>';
            $cells[] = new html_table_cell($link);
            if($member->passed < 0){
                $cells[] = new html_table_cell('Пропустил тренинг');
            }else{
                $cells[] = new html_table_cell('-');
            }
            $cells[] = new html_table_cell('');

            $table->data[] = new html_table_row($cells);

            $n++;
        }

        $requeststab = html_writer::table($table);


        $table = new html_table();
        $passed = lm_partner::i($partnerid)->get_passed_members($programid);
        $n = 1;
        foreach($passed as $member){
            $cells = array();
            $cells[] = $n;
            $link = '<a href="'.$CFG->wwwroot.'/user/view.php?id='.$member->userid.'" target="_blank">'.$member->lastname.' '.$member->firstname.'</a>';
            $cells[] = new html_table_cell($link);
            $cells[] = new html_table_cell(date('d.m.Y', $member->passed));
            $cells[] = new html_table_cell('');

            $table->data[] = new html_table_row($cells);

            $n++;
        }

        $passedtab = html_writer::table($table);


        $out =
            '<ul class="nav nav-tabs" id="myTab">
              <li class="active"><a href="#activity-requests" data-toggle="tab">Регистрация на активности</a></li>
              <li><a href="#activity-finished" data-toggle="tab">Обученные</a></li>
            </ul>

            <div class="tab-content">
              <div class="tab-pane active" id="activity-requests">'.$requeststab.'</div>
              <div class="tab-pane" id="activity-finished">'.$passedtab.'</div>
            </div>';

        return $out;
    }

    public function get_staff_panel($partnerid, $archive=false, $tmid=0, $trainerid=0, $ttid=0){
        $out = "";

        $partner = lm_partner::i($partnerid);
        $staffers = $partner->get_staffers($archive, "", $tmid, $trainerid, $ttid);

        foreach($staffers as $staffer){
            $out .= $this->get_staffer_view($staffer, $archive, $partner->has_capability_edit());
        }

        return $out;
    }

    public function get_staffer_view($user, $archive, $actionslist=false){
        global $DB, $OUTPUT;

        if(is_object($user)){
            $staffer = $user;
        }else{
            $staffer = $DB->get_record('user', array('id'=>$user), 'id, picture, imagealt, firstname, lastname');
        }
        $pic = $OUTPUT->user_picture($staffer);

        $table = new html_table();
        $table->attributes['class'] = 'staff-table';
        $table->data[] = new html_table_row( array(new html_table_cell($pic)) );
        $table->data[] = new html_table_row( array(new html_table_cell($staffer->lastname.' '.$staffer->firstname)) );

        if($actionslist) {
            $row = '<div class="dropdown" data-userid="' . $user->id . '" data-ttid="'. $user->ttid .'">
                  <a class="dropdown-toggle btn-mini" role="button" data-toggle="dropdown" data-target="#" href="#">
                    Действия
                    <b class="caret"></b>
                  </a>
                  <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                    <li class="' . ($archive ? ' hide' : '') . '"><a class="staff-action staff-action-changett" data-type="changett" role="menuitem" tabindex="-1" href="#">Переместить в другую ТТ</a></li>
                    <li class="' . ($archive ? ' hide' : '') . '"><a class="staff-action staff-action-archive" data-type="archive" role="menuitem" tabindex="-1" href="#">Поместить в архив</a></li>
                    <li><a class="staff-action staff-action-remove" data-type="remove" role="menuitem" tabindex="-1" href="#">Удалить из lms</a></li>
                    <li><a class="staff-action staff-action-finalyremove" data-type="finaly-remove" role="menuitem" tabindex="-1" href="#">Удалить полностью с портала</a></li>
                  </ul>
                </div>';
            $table->data[] = new html_table_row(array(new html_table_cell($row)));
        }
        return html_writer::table($table);
    }


    /**
     * Возвращает информацию о партнере с идентификатором $p->partnerid или создает нового партнера, если
     * переден $p->partnerid равен нулю
     *
     * @param $p
     */
    public function ajax_get_partner_info($p){
        $partner = lm_partner::i(0);
        if(!$p->partnerid){
            if(!empty($p->companyid)) $partner->companyid = $p->companyid;
            if(!empty($p->cityid)) $partner->regionid = $p->cityid;

            $p->partnerid = $partner->create();
        }

        $a = new StdClass();
        $a->html = $this->get_partner_info($p->partnerid);
        $a->partnerid = $p->partnerid;
        echo json_encode($a);
    }

    /**
     * Добавляет точку продаж у партнера
     *
     * @throws coding_exception
     */
    public function ajax_create_tt(){
        $name = optional_param('ttname', '', PARAM_TEXT);
        $code = optional_param('ttcode', '', PARAM_TEXT);
        $partnerid = optional_param('partnerid', 0, PARAM_INT);

        $a = (object) array('id'=>false, 'html'=>'');
        $place = lm_place::i(0);
        $place->set('name', $name)->set('partnerid', $partnerid)->set('code', $code)->set('type', 'tt');
        $place = $place->create();
        if($a->id = $place->id){
            $a->html = $this->tt_view($a->id);
        }

        return $a;
    }

    /**
     * Список для окна выбора менеджеров
     *
     * @throws coding_exception
     */
    public function ajax_managerpicker_list($p){
        global $OUTPUT;


        $data = array();
        if(!empty($p->point) && !empty($p->partnerid)) {
            $function = 'get_'.$p->point.'_list';
            if (function_exists($function) && $users = $function($p->q, $p->partnerid)) {
                foreach ($users as $user) {
                    $data[] = (object)array('id' => $user->id, 'html' => $OUTPUT->user_picture($user) . ' ' . fullname($user));
                }
            }
        }

        return (object) array('data'=>$data);
    }

    /**
     * Назначает менеджера на должность $point
     *
     * @return object
     * @throws coding_exception
     */
    public function ajax_appoint_manager($p){

        $a = (object) array('html'=>'', 'success'=>false);
        if(!empty($p->partnerid) && !empty($p->userid) && !empty($p->point)) {
            $partner = lm_partner::i($p->partnerid);
            $method = 'appoint_'.$p->point;
            if(method_exists($partner, $method)){
                $partner->$method($p->userid);
                $a->html = $this->manager_view($p->userid, $p->point);
                $a->success = true;
            }
        }

        return $a;
    }

    /**
     * Список для выбора торговой точки по партнеру
     *
     * @param $p
     */
    public function ajax_ttpicker_list($p){
        $data = array();
        if($places = get_places('tt', $p->q, $p->partnerid)) {
            foreach ($places as $place) {
                $place = lm_place::i($place);
                if($place->code){
                    $data[] = (object) array('id'=> $place->id, 'html'=>$place->fullname());
                }
            }
        }

        $a = (object) array('data'=>$data);
        echo json_encode($a);
    }

    /**
     * Переносит сотрудника на другую торговую точку
     *
     * @param $p
     */
    public function ajax_relocate_staffer($p){
        if(empty($p->fromttid)) $p->fromttid = 0;

        $a = new StdClass();
        $a->success = false;

        if(!empty($p->partnerid) && !empty($p->userid) && !empty($p->tottid)){
            $a->success = lm_partner::i($p->partnerid)->relocate_staffer($p->userid, $p->fromttid, $p->tottid);
        }

        echo json_encode($a);
    }

    /**
     * Фильтрует сотрудников партнера по параметрам
     *
     * @param $p
     */
    public function ajax_refresh_staffer_list($p){
        $a = new StdClass();
        $a->success = false;
        $a->html = "";

        if(!empty($p->partnerid)){
            if(empty($p->tmid) || !is_numeric($p->tmid)) $p->tmid = 0;
            if(empty($p->trainerid) || !is_numeric($p->trainerid)) $p->trainerid = 0;
            if(empty($p->ttid) || !is_numeric($p->ttid)) $p->ttid = 0;
            if(empty($p->archive)) $p->archive = false;
            $p->archive = (boolean) $p->archive;

            $a->html = $this->get_staff_panel($p->partnerid, $p->archive, $p->tmid, $p->trainerid, $p->ttid);
            $a->success = true;
        }

        echo json_encode($a);
    }

    public function ajax_modal_addstaff($p){
        $a = new StdClass();
        $a->html = "";
        if(!empty($p->partnerid)){
            $partner = lm_partner::i($p->partnerid);
            $ttlist = $partner->tt_list("short");
            $this->tpl->staffmodalexists_ttlist = html_writer::select(
                $ttlist, 'ttid', 0, "Укажите ТТ",
                array("class"=>"ttlist")
            );

            $this->tpl->staffmodalnew_ttlist = html_writer::select(
                $ttlist, 'ttid', 0, "Укажите ТТ",
                array("class"=>"ttlist", "disabled"=>"disabled")
            );

            $a->html = $this->fetch("modal/addstaff/content.tpl");
        }

        echo json_encode($a);
    }


    public function ajax_avail_regions($p){

        if(!empty($p->partnerid)){
            $regions = lm_partner::i($p->partnerid)->get_unused_regions();
        }else{
            $regions = get_regions_menu();
        }

        $a = array();
        $a[] = (object) array('value'=>0, 'text'=>'Не выбрано');
        if($regions){
            foreach($regions as $id=>$name){
                $a[] = (object) array('value'=>$id, 'text'=>$name);
            }
        }

        return $a;
    }

    public function ajax_save_region($p){
        if(empty($p->value)) $p->value = 0;

        if(!empty($p->pk)){
            lm_partner::i($p->pk)->update_region($p->value);
        }
    }
}