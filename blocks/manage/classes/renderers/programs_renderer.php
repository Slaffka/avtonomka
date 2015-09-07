<?php
class block_manage_programs_renderer extends block_manage_renderer {
    public $pageurl = '/blocks/manage/?_p=programs';
    public $pagename = 'Программы';
    public $type = 'manage_programs';

    public function init_page(){
        parent::init_page();
        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->js('/blocks/manage/yui/programs.js');
    }

    public function main_content(){
        global $CFG;

        if(!has_capability('block/manage:listprograms', context_system::instance())){
            return 'У вас нет доступа для просмотра этого раздела!';
        }

        $this->tpl->tables = array();
        if($categories = get_programs_tree() ){
            foreach($categories as $category){
                $this->tpl->tables[] = '<h3>' . $category->name . '</h3>';

                $table = new html_table();
                $table->head = array('', '', '', '');

                $actionedit = html_writer::link("#editprogram-modal", '<i class="icon icon-pencil"></i>',
                    array("class"=>"btn btn-mini btn-flat editprogram", "data-toggle"=>"modal", "title"=>"Редактировать программу")
                );
                $actiondelete = html_writer::link("#", '<i class="icon icon-remove"></i>',
                    array("class"=>"btn btn-mini btn-flat deleteprogram",  "title"=>"Удалить программу")
                );

                $cells = array(
                    new html_table_cell(''),
                    new html_table_cell(''),
                    new html_table_cell($actionedit.$actiondelete)
                );
                $clonerow = new html_table_row($cells);
                $clonerow->attributes['class'] = "clone hide";
                $table->data[] = $clonerow;

                if($category->programs) {
                    $n = 1;
                    foreach($category->programs as $program) {

                        if ($program->courseid) {
                            $name = html_writer::link($CFG->wwwroot . '/course/view.php?id=' . $program->courseid, $program->name,
                                array("target" => "_blank"));
                        } else {
                            $name = $program->name;
                        }

                        $actions = "";
                        if(!$program->courseid){
                            $actions .= $actionedit;
                        }
                        $actions .= $actiondelete;

                        $cells = array(
                            new html_table_cell($n),
                            new html_table_cell($name),
                            new html_table_cell($actions)
                        );

                        $row = new html_table_row($cells);
                        $row->attributes['class'] = "courseid-{$program->courseid} programid-" . $program->id;
                        $table->data[] = $row;
                        $n ++;
                    }
                }

                $table->attributes['class'] = "generaltable programgroup programgroup-{$category->id}";
                $this->tpl->tables[] = html_writer::table($table);
            }
        }

        $this->tpl->selectcategory = html_writer::select(
            lm_programs::get_categories_menu(), 'category', 0, 'Выберите категорию...',
            array('class'=>'categorylist')
        );

        return $this->fetch('program/index.tpl');
    }

    /**
     * Добавление категории
     *
     * @param $p
     * @return bool|object
     */
    public function ajax_add_category($p){
        global $DB;

        //TODO: Проверка прав доступа
        if(empty($p->name)) return false;

        $dataobj = (object)array('name'=>$p->name);
        $dataobj->id = $DB->insert_record('lm_program', $dataobj);

        return $dataobj;
    }

    /**
     * Добавляет программу в список
     *
     * @param $p
     * @return object
     */
    public function ajax_add_program($p){

        if(empty($p->name)) $p->name = '';
        if(empty($p->mode)) $p->mode = '';
        if(empty($p->courseid)) $p->courseid = 0;
        if(empty($p->category)) $p->category = 0;

        $dataobj = (object)array('id'=>0, 'parent'=>$p->category, 'name'=>$p->name, 'courseid'=>$p->courseid);

        if($p->category && ($p->mode == 'program' && $p->name || $p->mode == 'linkedprogram' && $p->courseid)){
            // Добавляем программу и генерируем ссылку
            $program = lm_program::create($dataobj);
            $dataobj->id = $program->id;
            $dataobj->name = $program->link();
        }

        return $dataobj;
    }

    /**
     * Генерирует содержимое модального окна добавления программы
     *
     * @param $p
     * @return object
     */
    public function ajax_load_modal_editprogram($p){

        $a = (object)array('html'=>'', 'error'=>false);
        if(empty($p->programid)) $a->error = true;

        if(!$a->error){
            $program = lm_program::i($p->programid);
            $this->tpl->programid = $program->id;
            $this->tpl->name = $program->name;
            $this->tpl->courseid = $program->courseid;
            $this->tpl->period = $program->period;
            $a->html = $this->fetch('program/modal_editprogram.tpl');
        }

        return $a;
    }

    /**
     * Обновляет данные о программе
     *
     * @param $p
     * @return object
     */
    public function ajax_update($p){
        $a = (object)array('html'=>'', 'error'=>false);
        if(!isset($p->programid) || !$p->programid){
            $a->error = true;
            $a->html = "Ошибка! Не задан id програмы, обратитесь в службу поддержки!";
            return $a;
        }

        if(!isset($p->name) || !$p->name){
            $a->error = true;
            $a->html = "Укажите название программы";
            return $a;
        }

        if($p->programid && $p->name){
            $program = lm_program::i($p->programid);
            if($program->id) {
                $program->set('name', $p->name)->set('period', $p->period)->update();
                $a->html = $p->name;
            }else{
                $a->error = true;
                $a->html = "Ошибка! Программа не найдена, обратитесь в службу поддержки!";
            }
        }

        return $a;
    }
}