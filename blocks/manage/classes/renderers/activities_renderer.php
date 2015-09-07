<?php
class block_manage_activities_renderer extends block_manage_renderer {
    public $pageurl = '/blocks/manage/?_p=activities';
    public $pagename = 'Активности';
    public $type = 'manage_activities';

    public function init_page(){
        parent::init_page();
        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->js('/blocks/manage/yui/activities.js');
    }

    public function main_content(){

        $type = optional_param('type', '', PARAM_TEXT);
        $state = optional_param('state', '', PARAM_TEXT);
        $activityid = optional_param('id', 0, PARAM_INT);
        $style = $content = '';
        if($activityid){
            $content = $this->get_activity_info($activityid);
            $style = 'display:none';
        }

        $tabplanned = '<div class="instancelist-wrapper" style="'.$style.'">';
        if(!lm_user::is_rep()) { //Если не является представителем

            if(!$type)
                $type = 'auditory';

            $tabplanned .= '<ul class="nav nav-pills activitytype pull-left">
                  <li class="' . ($type == 'auditory' ? 'active' : '') . '" data-type="auditory"><a href="/blocks/manage?_p=activities">Аудиторные</a></li>
                  <li class="' . ($type == 'field' ? 'active' : '') . '" data-type="field"><a href="/blocks/manage?_p=activities&type=field">Полевые</a></li>
                  <li class="' . ($type == 'online' ? 'active' : '') . '" data-type="online"><a href="/blocks/manage?p=activities&type=online">Дистанционные</a></li>
                  <li class="' . ($type == 'method' ? 'active' : '') . '" data-type="method"><a href="/blocks/manage?p=activities&type=method">Методические</a></li>
                </ul>';
        }else{
            if(!$state)
                $state = 'planned';

            $tabplanned .= '<ul class="nav nav-pills activitystate pull-left">
                  <li class="' . ($state == 'planned' ? 'active' : '') . '" data-state="planned"><a href="/blocks/manage?_p=activities">Предстоящие</a></li>
                  <li class="' . ($state == 'finished' ? 'active' : '') . '" data-state="finished"><a href="/blocks/manage?_p=activities&state=finished">Завершенные</a></li>
                </ul>';
        }

        $this->pageurl .= '&type='.$type;

        if(has_capability('block/manage:activityadd', context_system::instance())) {
            $tabplanned .= '<div class="pull-right">';
            $tabplanned .= '<a class="btn btn-link btn-activities-xlexport"><i class="icon icon-download-alt"></i> Выгрузить в excel</a>';
            $tabplanned .= '<button class="btn btn-addactivity"><i class="icon icon-plus"></i> Добавить активность</button>';
            $tabplanned .= '</div>';
        }

        $tabplanned .= '<div style="clear:both">
                          <div class="controls controls-row">
                            <div class="input-append activity-search">
                              <input id="search-activity" class="span6" style="width:350px" type="text" placeholder="Введите ФИО тренера или название программы" >
                              <span class="add-on"><i class="icon icon-search"></i> </span>
                            </div>
                            <div class="form-inline pull-right">
                            <label for="">Начиная с</label>
                            <input id="filter-startdate" class="calendar-trigger input-mini" type="text">
                            <label for="">до</label>
                            <input id="filter-enddate" class="calendar-trigger input-mini" type="text">
                            </div>
                          </div>
                        </div><div id="calendar" class="hide"></div>';

        $tabplanned .= $this->activity_table('', $type, $state);
        $tabplanned .= '</div>';

        $tabplanned .= html_writer::div($content, 'activityinfo instanceinfo', array('id'=>'activityinfo'));


        $out = $tabplanned;

        return $out;
    }

    public function activity_table($q="", $type='', $state='', $startdate=0, $enddate=0){
        ob_start();
        $table = new flexible_table('activities');

        $table->define_columns(
            array('la__id', 'u__lastname', 'la__startdate', 'c__fullname', 'la__enddate', 'la__hourscount', 'la__requestcount', 'la__trainedcount')
        );
        $table->define_headers(array('№', 'Тренер', 'Даты проведения', 'Программа', 'Статус тренинга', 'Часов', 'Заявок', 'Обучено'));
        $table->is_sortable = true;
        $table->define_baseurl($this->pageurl);

        $perpage = 30;


        $total = get_activities_count($type, $state, $q, $startdate, $enddate);

        $table->pagesize($perpage, $total);
        $table->setup();

        $sortby = $table->get_sql_sort();
        if($sortby){
            $sortby = str_replace('__', '.', $sortby);
            $sortby = "ORDER BY {$sortby}";
        }else{
            $sortby = "ORDER BY la.startdate DESC";
        }

        $activities = get_activities($type, $state, $q, $startdate, $enddate, $sortby, $this->pagenum, $perpage);
        if($activities){
            $n = $this->pagenum*$perpage+1;
            foreach($activities as $activity){
                $activityobj = lm_activity::i($activity);

                $cells = array($n,
                               $activity->trainerfio,
                               $activityobj->date_range(),
                               $activity->name,
                               $activityobj->get_status_name(),
                               $activityobj->count_hours(),
                               $activityobj->count_members(),
                               $activityobj->count_trained_members()
                );
                $table->add_data($cells, 'row-activity activity-'.$activity->id);
                $n ++;
            }
        }

        $table->finish_output();
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }

    public function get_activity_info($activityid){

        $activity = lm_activity::i($activityid);
        $canedit = $activity->has_capability_edit();
        $isrep = lm_user::is_rep();

        $btndelete = '';
        if($canedit){
            $btndelete = '<div class="pull-right delete-instance">
                                <a class="btn btn-link">Удалить</a>
                            </div>';
        }

        $out = '<h2>Информация об активности
                   <div class="pull-right close-instance">
                            <button class="btn"><i class="icon icon-checkmark"></i> OK</button>
                   </div>
                   '.$btndelete.'
               </h2>';

        if(!$activity->id){
            $out .= 'Такой активности нет в базе данных!';

            return $out;
        }


        if(!$activity->has_capability_view()){
            $out .= 'Вы не имеете прав доступа для просмотра информации об этой активности!';

            return $out;
        }

        $out .= '<div class="span10">'.$this->select_programs($activity->programid, $canedit).'</div>';
        $out .= '<div class="span5">';
        $out .= $this->select_activitytype($activity->type, $canedit);


        $out .= $this->select_type($activity->auditory, $canedit);

        $placename = "Не выбрано";
        if($activity->placeid) {
            $placename = lm_place::i($activity->placeid)->fullname();
        }

        $class = $canedit ? "contenteditable field-place": "nonecontenteditable";
        $out .= '<div>
                  <b>Место проведения</b>
                  <div class="'.$class.'" contenteditable="false">'.$placename.'</div>
                </div>';



        $out .= '</div>';

        $out .= '<div class="span5 pull-right">';
        $out .= $this->select_memberscount($activity->maxmembers, $canedit);
        $out .= $this->htmlline('Тренер', array('userid'=>$activity->trainer_fullname()), $canedit,
            '<input id="trainerid" />'
        );
        $out .= '</div><div class="clearer"></div> ';


        $out .= '<div class="span12">';
        $out .= html_writer::label('<b>Даты и время:</b>', '');

        if($canedit)
            $out .= $this->timeline(0, 0, 0, 'hide');

        $out .= '<div id="datesbox"><span class="datecontrols-area">';
        if(!$canedit)
            $out .= '<ul>';

        if(isset($activity->properties['dates']) && is_array($activity->properties['dates'])){
            foreach($activity->properties['dates'] as $dateid=>$date){
                $dateid++;
                if($canedit){
                    $out .= $this->timeline($dateid, $date->start, $date->end, '');
                }else{
                    $out .= '<li>';
                    $out .= userdate($date->start, '%d.%m.%Y'). '  c  '
                            .userdate($date->start, '%H:%M'). '  до  '
                            .userdate($date->end, '%H:%M');
                    $out .= '</li>';
                }
            }
        }

        if(!$canedit)
            $out .= '</ul>';

        $out .= '</span>';

        if($canedit)
            $out .= '<a class="btn btn-link btn-adddate pull-left"><i class="icon icon-plus"></i> Добавить дату</a><div class="clearer"></div><br> ';

        $out .= '</div>';


        if($canedit) {
            $out .= $this->textarea('comment', nl2br($activity->comment), 'Комментарии для тренеров', $canedit);
        }

        if($isrep || $canedit) {
            $out .= $this->textarea('comment2', nl2br($activity->comment2), 'Комментарии для партнеров', $canedit);
        }

        $out .= '</div><div class="clearer"></div>';


        $out .= $this->get_members_panel($activityid);

        return $out;
    }


    public function get_members_panel($activityid){

        $this->tpl->memberspanel = '';

        $activity = lm_activity::i($activityid);
        $canedit = $activity->has_capability_edit();
        $members = $activity->get_members();

        if($members){
            foreach($members as $member){
                $this->tpl->memberspanel .= $this->get_member_view($member, $member->partnerid, $canedit);
            }
        }

        $partnerid = get_my_company_id();
        $this->tpl->select_partners = $this->select_partners($partnerid);
        $this->tpl->select_members = $this->select_members($partnerid);

        return $this->fetch('activity/members_panel.tpl');
    }

    public function get_member_view($user, $partnerid, $edit=false){
        global $DB, $OUTPUT;

        if(is_object($user)){
            $member = $user;
        }else{
            $member = $DB->get_record('user', array('id'=>$user), 'id, picture, imagealt, firstname, lastname');
        }

        $pic = $OUTPUT->user_picture($member);

        $table = new html_table();
        $table->attributes['class'] = 'pull-left staff-table';
        $table->attributes['data-memberid'] = $member->id;
        $table->data[] = new html_table_row( array(new html_table_cell($pic)) );

        $partnerlink = '<div class="partnerlink">'.lm_partner::i($partnerid)->link().'</div>';

        $table->data[] = new html_table_row( array(new html_table_cell($member->lastname.' '.$member->firstname.$partnerlink)) );

        if($edit) {
            $passedclass = $notpassedclass = '';
            if ($member->passed > 0) {
                $passedclass = 'active';
            } else if ($member->passed < 0) {
                $notpassedclass = 'active';
            }
            $dropdown = '<div class="btn-group" data-toggle="buttons-radio">
                      <button type="button" class="btn btn-mini btn-link btn-useraction ' . $passedclass . '" data-action="passed">Прошел</button>
                      <button type="button" class="btn btn-mini btn-link btn-useraction ' . $notpassedclass . '" data-action="notpassed">Не прошел</button>
                    </div>';
            $table->data[] = new html_table_row(array(new html_table_cell($dropdown)));
        }

        return html_writer::table($table);
    }

    function timeline($dateid=0, $timestampfrom=0, $timestampto=0, $class=''){
        $datecontrols = '<div class="form-inline datecontrols pull-left '.$class.'" data-dateid="'.$dateid.'" style="margin-bottom:10px;clear:both">';


        $datecontrols .= html_writer::select_time('days', 'days', $timestampfrom);
        $datecontrols .= html_writer::select_time('months', 'months', $timestampfrom);
        $datecontrols .= html_writer::select_time('years', 'years', $timestampfrom);

        $datecontrols .= html_writer::label(' c', '', true, array('style'=>'margin:0 10px 0 30px'));
        $datecontrols .= html_writer::select_time('hours', 'hoursfrom', $timestampfrom);
        $datecontrols .= html_writer::select_time('minutes', 'minutesfrom', $timestampfrom);
        $datecontrols .= html_writer::label('до', '', true, array('style'=>'margin:0 10px'));
        $datecontrols .= html_writer::select_time('hours', 'hoursto', $timestampto);
        $datecontrols .= html_writer::select_time('minutes', 'minutesto', $timestampto);

        $datecontrols .= '<button class="btn btn-link btn-remove"><i class="icon icon-remove"></i> удалить</button> ';



        $datecontrols .= '</div>';

        return $datecontrols;
    }

    function select_partners($default=0, $hide=false){
        $partners = get_partners_menu();
        $style = '';
        if($hide){
            $style = 'display:none';
        }

        $out = html_writer::label('Партнер', '', true, array('style'=>$style));
        $out .= html_writer::select($partners, 'field-partner', $default, 'Не выбрано', array('data-field'=>'programid', 'style'=>$style));
        return $out;
    }

    function select_members($partnerid=0){
        $members = lm_partner::i($partnerid)->get_staffers_menu();

        $out = '<div class="members-list">';
        $out .= html_writer::label('Сотрудник (используйте ctrl+щелчок левой клавишей мыши для выбора)', '');

        $properties = array();
        $properties['style'] = 'min-width:250px;min-height:200px';
        $properties['multiple'] = '';
        if(!$partnerid){
            $properties['disabled'] = 'disabled';
        }
        $out .= html_writer::select($members, 'field-staffer', 0, '', $properties);
        $out .= '</div>';

        return $out;
    }

    function select_memberscount($default=0, $editable=false){
        $count = range(0, 100);
        $count[200] = 200;
        $count[300] = 300;
        $count[400] = 400;
        $count[500] = 500;
        $count[1000] = 1000;

        if(!$editable){
            return $this->htmlline('Участников (max)', array('maxmembers'=>$count[$default]), $editable);
        }

        $select = html_writer::select($count, 'field-maxmembers', $default, '', array('data-field'=>'maxmembers'));
        return $this->htmlline('Участников (max)', array('maxmembers'=>$count[$default]), $editable, $select);
    }

    function select_activitytype($default='auditory', $editable=false){
        $types = lm_activity::types();
        if(!$editable){
            return $this->htmlline('Тип активности', array('activitytype'=>$types[$default]), $editable);
        }
        $select = html_writer::select($types, 'field-activitytype', $default, '', array('data-field'=>'activitytype'));
        return $this->htmlline('Тип активности', array('activitytype'=>$types[$default]), $editable, $select);
    }

    function select_programs($default=0, $editable=false){
        $programs = get_programs_list();
        array_unshift($programs, 'Не выбрано');

        if($default && $menu = lm_programs::get_menu() ){
            $default = $menu[$default];
        }else{
            $default = 'Не выбрано';
        }

        if(!$editable){
            return $this->htmlline('Программа', array('programs'=>$default), $editable);
        }

        $select = html_writer::select($programs, 'field-programs', $default, '', array('data-field'=>'programid'));
        return $this->htmlline('Программа', array('programs'=>$default), $editable, $select);
    }


    public function ajax_placepicker_list($p){
        $data = array();
        if($places = get_places($p->section, $p->q)) {
            foreach ($places as $rawplace) {
                $place = lm_place::i($rawplace);
                if($p->section == "class" && $place->name || $p->section == "tt" && $place->code){
                    $data[] = (object) array('id'=> $place->id, 'html'=>$place->fullname().' '.$rawplace->companyname);
                }
            }
        }

        $a = (object) array('data'=>$data);
        echo json_encode($a);
    }

    public function ajax_set_place($p){
        global $DB;

        $dataobj = new StdClass();
        $dataobj->id = $p->activityid;
        $dataobj->placeid = $p->placeid;
        $DB->update_record('lm_activity', $dataobj);

        $a = new StdClass();
        $a->success = true;
        $a->html = lm_place::i($dataobj->placeid)->fullname();
        echo json_encode($a);
    }
}