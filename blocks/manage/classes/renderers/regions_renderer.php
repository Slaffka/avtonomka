<?php
class block_manage_regions_renderer extends block_manage_renderer {
    public $pageurl = '/blocks/manage/?_p=regions';
    public $pagename = 'Регионы';
    public $type = 'manage_regions';

    /**
     * @var html_table
     */
    private $table = NULL;

    public function init_page(){
        parent::init_page();
        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->js('/blocks/manage/yui/regions.js');
    }

    public function main_content(){
        global $DB;

        if(!has_capability('block/manage:listregions', context_system::instance())){
            return 'У вас нет доступа для просмотра этого раздела!';
        }

        $out = '<div id="assignedtrainers-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
                  <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3>Назначенные тренеры</h3>
                  </div>
                  <div class="modal-body">
                      <div class="alert alert-error hide">
                        <button type="button" class="close" data-dismiss="alert">×</button>
                        <div class="content"></div>
                      </div>
                      <div class="modal-body-content"></div>
                  </div>
                  <div class="modal-footer">
                    <button class="btn btn-close" data-dismiss="modal" aria-hidden="true">OK</button>
                  </div>
                </div>';

        $out .= '<label>Чтобы добавить город:</label>';

        $out .= html_writer::select(get_mainregions_menu(), 'region', 0, 'Выберите регион...', array('class'=>'regionlist'));
        $out .= '
              <input id="input-addcity" class="input-xlarge" type="text" placeholder="Введите название региона" disabled>
              <button id="button-addcity" disabled>Добавить</button><hr>
            ';

        $showactions = '<a href="#" class="btn btn-mini action editregion hide"><i class="icon icon-pencil"></i></a>';
        $showactions .= '<a href="#assignedtrainers-modal" class="btn btn-mini action trainerassignment hide" data-toggle="modal">
                             <i class="icon icon-plus"></i><i class="icon icon-user"></i> </a>';

        $editactions = '<a href="#" class="btn btn-mini action editregion"><i class="icon icon-checkmark"></i></a>';

        $types = lm_company::get_types();

        if($regions = get_regions()) {
            foreach($regions as $regionid=>$region) {
                $out .= '<h3>'.$region->name.'</h3>';
                $this->table = new html_table();
                $this->table->id = 'regionlist-'.$regionid;
                $this->table->attributes['class'] = 'generaltable regionlist';
                $this->table->head[] = '';
                $this->table->head[] = '';


                $this->add_row(array('', '', '', $showactions), array('class'=>'showrow clone hide') );
                $this->add_row(array('<input type="text" value="" />', '', $editactions), array('class'=>'editrow clone hide'));
                if($region->cities) {
                    $n = 1;
                    foreach ($region->cities as $cityid => $city) {

                        $count = $DB->count_records('lm_region_trainer', array('regionid' => $cityid));
                        if (!$count) {
                            $count = 'Нет тренеров';
                        } else {
                            $count = $count . ' тренеров';
                        }

                        $cells = array($n, $city, $count, $showactions);
                        $attrs = array('class' => "showrow regionid-{$cityid}");
                        $this->add_row($cells, $attrs);

                        // Добавляем скрытую строку в таблицу, для редактирования на аякс
                        $cells = array($n, '<input type="text" value="' . $city . '" />', $count, $editactions);
                        $attrs = array('class' => "hide editrow regionid-{$cityid}");
                        $this->add_row($cells, $attrs);

                        $n ++;
                    }
                }
                $out .= html_writer::table($this->table);
            }
        }


        return $out;
    }

    protected function add_row($cellslist=array(), $attributes=array()){
        if(!$cellslist){
            $cellslist = array('', '', '', 0);
        }

        $cells = array();
        foreach($cellslist as $key=>$cell){

            $cells[] = new html_table_cell($cell);
        }

        $row = new html_table_row($cells);
        $row->attributes = $attributes;
        $this->table->data[] = $row;
    }
}

