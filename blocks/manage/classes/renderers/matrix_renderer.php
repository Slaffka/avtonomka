<?php

class block_manage_matrix_renderer extends block_manage_renderer
{

    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=matrix';
    public $pagename = 'Управление матрицей развития';
    public $type = 'manage_matrix';
    public $pagelayout = "base";

    public function init_page()
    {
        parent::init_page();
        $this->page->requires->js('/blocks/manage/yui/sortable/jquery-sortable.js');
        $this->page->requires->js('/blocks/manage/yui/matrix.js');
    }

    public function require_access(){
        if( !lm_user::is_admin() ) {
            print_error('accessdenied', 'admin');
        }
    }

    public function main_content()
    {
        global $DB;

        $list = array();

        if($categories = $DB->get_records_menu('lm_program', array('parent'=>0), 'name ASC', 'id, name')) {
            if ($postlist = lm_post::posts()) {
                $stages = lm_matrix::stages();
                $matrix = get_records_array('lm_program_matrix', 'id, postid, stage, programid', array(), 'postid ASC, stage ASC, sequence ASC');
                $programlist = get_programs_list();
                $programs = lm_programs::get_menu();

                foreach ($postlist as $post) {
                    $post->stages = array();
                    foreach ($stages as $stage) {

                        $st = new StdClass();
                        $st->name = $stage->name;
                        $st->programs = array();
                        $selectname = 'matrix[' . $post->id . '][' . $stage->id . '][]';
                        if (isset($matrix[$post->id][$stage->id]) && is_array($matrix[$post->id][$stage->id])) {
                            foreach ($matrix[$post->id][$stage->id] as $programid=>$matrixlineid) {
                                $program = new StdClass();
                                $program->id = $programid;
                                $program->name = isset($programs[$programid]) ? $programs[$programid]: '';
                                $program->select = html_writer::select($programlist, $selectname, $programid, array());

                                $st->programs[] = $program;
                            }
                        }

                        $programid = 0;
                        $program = new StdClass();
                        $program->id = $programid;
                        $program->name = isset($programs[$programid]) ? $programs[$programid]: '';
                        $program->select = html_writer::select($programlist, $selectname, $programid, 'Добавьте программу...');

                        $st->programs[] = $program;

                        $post->stages[$stage->id] = $st;
                    }
                    $list[] = $post;
                }
            }
        }
        $this->tpl->list = $list;

        echo $this->fetch('matrix/index.tpl');
    }

    public function ajax_save($p){
        // TODO: проверка прав доступа
        if(!empty($p->matrix)){
            lm_matrix::update($p->matrix);
        }
    }

    public function ajax_switch_stages($p)
    {
        // TODO: проверка прав доступа
        if(!empty($p->postid)){
            lm_post::i($p->postid)->switch_mode();
        }

        header("Location:{$this->pageurl}");
    }

}