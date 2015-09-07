<?php
class block_manage_companies_renderer extends block_manage_renderer {
    public $pageurl = '/blocks/manage/?_p=companies';
    public $pagename = 'Компании';
    public $type = 'manage_companies';

    /**
     * @var html_table
     */
    private $table = NULL;

    public function init_page(){
        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->js('/blocks/manage/yui/companies.js');
        parent::init_page();
    }

    public function main_content(){

        if(!has_capability('block/manage:listcompanies', context_system::instance())){
            return 'У вас нет доступа для просмотра этого раздела!';
        }

        $this->table = new html_table();
        $this->table->id = 'companieslist';
        $this->table->head[] = '№';
        $this->table->head[] = 'Компания';
        $this->table->head[] = 'Тип';
        $this->table->head[] = 'Действия';

        //$showactions = '<a href="#" class="action hidecompany"><i class="icon icon-eye-open"></i> </a>';
        $showactions = '<a href="#" class="btn btn-mini btn-flat action editcompany"><i class="icon icon-pencil"></i></a>';

        //$editactions = '<a href="#" class="action hidecompany"><i class="icon icon-eye-open"></i></a>';
        $editactions = '<a href="#" class="btn btn-mini btn-flat action editcompany"><i class="icon icon-checkmark"></i></a>';

        $types = lm_company::get_types();

        $this->add_row(array(0, '', '', $showactions), array('class'=>'showrow clone hide') );
        $this->add_row(array(0, '<input type="text" value="" />', html_writer::select($types, 'field-type', 'partner'), $editactions),
                       array('class'=>'editrow clone hide')
        );

        if($companies = get_companies_list() ){
            $n = 1;
            foreach($companies as $company){

                $attrs = array('class'=>"showrow companyid-{$company->id}");
                $cells = array($n, $company->name, $types[$company->type], $showactions);
                $this->add_row( $cells, $attrs);

                // Добавляем скрытую строку в таблицу, для редактирования на аякс
                $cells = array($n,
                    '<input type="text" value="'.$company->name.'" />',
                    html_writer::select($types, 'field-type', $company->type),
                    $editactions
                );
                $attrs = array('class'=>"hide editrow companyid-{$company->id}");
                $this->add_row( $cells, $attrs );

                $n ++;
            }
        }

        $out = html_writer::table($this->table);

        $out .= '<hr><label>Чтобы добавить компанию</label>';

        $out .= '
              <input id="input-addcompany" class="input-xxlarge" type="text" placeholder="Введите название компании">
              <button id="button-addcompany">Добавить</button>
            ';

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