<?php

class block_manage_orgstructure_renderer extends block_manage_renderer
{
    const PERSONAL_URL="?_p=lm_personal&id=";
    const COMPANY_URL="?_p=partners&id=";

    public $pageurl = '/blocks/manage/?_p=orgstructure';
    public $pagename = 'Управление оргструктурой';
    public $type = 'manage_orgstructure';
    public $pagelayout = "base";

    public function init_page()
    {
        parent::init_page();
        // $this->page->requires->js('/blocks/manage/yui/sortable/jquery-sortable.js');
        $this->page->requires->js('/blocks/manage/yui/org.js');
        $this->page->requires->js('/blocks/manage/yui/gtreetable.js');
        $this->page->requires->css('/blocks/manage/yui/chosen/style.css');

        $this->page->requires->js('/blocks/manage/yui/chosen/chosen.js');
        $this->page->requires->css('/blocks/manage/yui/chosen/chosen.css');


    }

    public function main_content(){
        global $DB;

        echo $this->fetch('orgstructure/index.tpl');

    }

    public function ajax_get_list(){//для заполнения select'ов
        global $DB;

        $type = optional_param('type', '', PARAM_TEXT);

        if (in_array($type, ['region', 'partner', 'segment', 'post', 'distrib', 'place'] , true)){
            return array_values($DB->get_records("lm_".$type, null, 'name', 'id, name'));
        }else{
            if ($type == 'user'){
                return array_values($DB->get_records("user", null, 'lastname', 'id, firstname, lastname'));
            }
        }
    }

    public function ajax_get_children(){
        $parent = optional_param('parent', null, PARAM_INT);
        $partner = optional_param('partner', null, PARAM_INT);
        return self::check_children(self::get_positions($parent, null, $partner));
    }

    public function ajax_change_positions(){
        //исправить !!!!
        //userid на posid
        //В селекте выбирают пользователя, но необходимо указывать id позиции
        $company = optional_param("company", null, PARAM_INT);

        $id = optional_param("id", null, PARAM_INT);
        $parent = optional_param("parent", null, PARAM_INT);
        $func_dir = optional_param("func_dir", null, PARAM_INT);
        $post = optional_param("post", null, PARAM_INT);
        $place = optional_param("areaid", null, PARAM_INT);
        $distrib = optional_param("distrib", null, PARAM_INT);
        $region = optional_param("cityid", null, PARAM_INT);
        $segment = optional_param("segment", null, PARAM_INT);
        $division = optional_param("division", "", PARAM_TEXT);

        $export = optional_param("pos_export", false, PARAM_BOOL);

        $type = optional_param("type", "", PARAM_TEXT);

        global $DB;
        switch ($type){
            case "update":
                if($export){
                    $update_data=(object) array(
                        'id'=>$id,
                        'parentfid'=>$func_dir,
                        'divisionname'=>$division
                    );
                    //$DB->execute("UPDATE 'mdl_lm_position'  SET `parentfid`=$p->func_dir, `divisionname`=$p->division WHERE `id` = $p->id");

                }else{
                    $update_data=(object) array(
                        'id'=>$id,
                        'parentid'=>$parent,
                        'parentfid'=>$func_dir,
                        'postid'=>$post,
                        'areaid'=>$place,
                        'distribid'=>$distrib,
                        'segmentid'=>$segment,
                        'divisionname'=>$division
                    );
                }
                $DB->update_record('lm_position',$update_data);
                break;
            case "add":
                $insert_data = (object) array('code'=>0,
                    'parentcode'=>0,
                    'partnerid'=>$company,
                    'postcode'=>0,
                    'parentid'=>$parent,
                    'parentfid'=>$func_dir,
                    'cityid'=>$region,
                    'postid'=>$post,
                    'areaid'=>$place,
                    'distribid'=>$distrib,
                    'segmentid'=>$segment,
                    'divisionname'=>$division
                );
                $DB->insert_record('lm_position', $insert_data);
                //$DB->execute("INSERT INTO `mdl_lm_position`(`code`, `parentcode`, `partnerid`, `postcode`, `parentid`, `parentfid`, `cityid`, `postid`, `areaid`, `distribid`, `segmentid`, `divisionname`) VALUES (, 0, 0, $p->company, 0, $p->parent, $p->func_dir, $p->region, $p->post, $p->place, $p->distrib, $p->segment, $p->division)");
                break;
        }

    }

    public function ajax_delete_position(){
        global $DB;

        $id = optional_param("id", null, PARAM_INT);

        return $DB->delete_records('lm_position', array('id'=>$id));

    }

    public function ajax_add_new_user(){
        $partnerid = optional_param('partnerid', 0, PARAM_INT);
        $issendemail = optional_param('issendemail', true, PARAM_BOOL);
        $staffer = new StdClass();
        $partner = lm_partner::i($partnerid);
        $result = false;

        $staffer->firstname = optional_param('firstname', '', PARAM_TEXT);
        $staffer->lastname = optional_param('lastname', '', PARAM_TEXT);
        $staffer->password = optional_param('password', '', PARAM_TEXT);
        $staffer->email = $staffer->username = optional_param('email', '', PARAM_TEXT);
        $result = $staffer->userid = $partner->create_staffer($staffer, $issendemail);

        $a = new StdClass();
        $a->success = false;
        $a->html = "";

        if($result == 'already_exists') {
            $a->html = "Такой пользователь уже существует!";
        }else if($result == 'already_partners_staffer'){
            $a->html = "Этот пользователь уже был добавлен партнеру ранее!";
        }elseif($staffer->userid){
            $fullname = lm_staffer::i($partnerid, $staffer->userid)->fullname();
            $a->html = '<option id="'.$staffer->userid.'">'.$fullname.'</option>';
            $a->id = $staffer->userid;
            $a->success = true;
        }

        return json_encode($a);
    }

    public function ajax_get_nodes(){
        if(strlen(optional_param('term', NULL, PARAM_TEXT))){
            return self::search_positions();
            exit();
        }
        $id_edit=optional_param('id_edit', null, PARAM_INT);
        if($id_edit){
            return self::get_positions(null, $id_edit, null, true);
            exit();
        }

        global $DB;
        $level = optional_param('level', null, PARAM_INT);
        $id=optional_param('id', -1, PARAM_TEXT);
        $nodes = new stdClass();

        if($id == '0'){
            $nodes->nodes = array_values($DB->get_records("lm_partner", null, 'name', 'id, name'));
            foreach($nodes->nodes as $node){
                $node->level = 0;
                $node->search_type = false;
                $node->ref = self::COMPANY_URL.$node->id;
                $node->id = "company_".$node->id;
            }
        }else{
            $nodes->nodes = self::ajax_get_children();
            foreach($nodes->nodes as $node){
                $node->search_type=false;
                $node->level = $level+1;
                $node->ref = self::PERSONAL_URL.$node->ref;
            }
        }
        return $nodes;
    }

    //исправно работает при поиске по одному слову
    private function search_positions(){
        global $DB;
        $parents=[];
        $q = optional_param('term', '', PARAM_TEXT);
        $partner = optional_param('partner', null, PARAM_INT);
        $cities = optional_param_array('cities', null, PARAM_INT);
        $posts = optional_param_array('posts', null, PARAM_INT);
        $segments = optional_param_array('segments', null, PARAM_INT);
        $distributions = optional_param_array('distributions', null, PARAM_INT);
        $experiences = optional_param_array('experiences', null, PARAM_INT);


        $users = $DB->get_records_select(
            'user', "(firstname LIKE ? OR lastname LIKE ?) AND deleted <> 1",
            array("$q%", "$q%"), '', 'id'
        );

        $results = [];

        foreach($users as $user){
            $res=[];

            $sql="
                    SELECT
                      pos.id
                    FROM
                      `mdl_lm_position` as pos
                    INNER JOIN `mdl_lm_position_xref` ON mdl_lm_position_xref.posid=pos.id

                    WHERE
                      mdl_lm_position_xref.archive = 0
                    AND
                      mdl_lm_position_xref.userid = $user->id
                    AND
                      pos.partnerid=$partner";


            if (count($cities)){
                $sql .= ' AND pos.cityid IN (' . implode(",", $cities) . ')';
            }

            if (count($posts)){
                $sql .= ' AND pos.postid IN (' . implode(",", $posts) . ')';
            }

            if (count($segments)){
                $sql .= ' AND pos.segmentid IN (' . implode(",", $segments) . ')';
            }


            if (count($distributions)){
                $sql .= ' AND pos.distribid IN (' . implode(",", $distributions) . ')';
            }

            if (count($experiences)){
                $sql .= ' AND DATEDIFF(CURDATE(), mdl_lm_user.hiredate)/30 IN (' . implode(",", $experiences) . ')';
            }

            $posids = $DB->get_records_sql($sql);


            foreach($posids as $posid){
                $id = $posid->id;
                do{
                    $pos = new stdClass();
                    $pos->parent = $DB->get_field('lm_position', 'parentid', array('id' => $id));

                    if(!in_array($id, $parents)){
                        $parents[]=$id;
                        $pos->child = self::get_positions($pos->parent, $id)[0];
                    }else{
                        break;
                    }


                    if(is_null($pos->child)){
                        $res=[];
                        break;//нарушение в дереве - откидываем всю ветку, все равно корректный путь не построится
                    }else{
                        $res[] = $pos->child;
                        $id = $pos->parent;
                    }
                }while($id);//как только $id = 0 - корневой элемент
            }
            $results = array_merge($res, $results);
        }
        $results = self::turn($results);//свертка
        $r = self::expand($results);//развертка - обход получившегося дерева в глубину слева направо
        $nodes = new stdClass();
        $nodes->nodes = $r;

        return $nodes;

    }

    private function get_positions($parentid=null, $id=null, $partnerid=null, $for_compare = false){//возвращает список позиций по условиям отбора

        global $DB;

        $sql = 'SELECT ';
        if ($for_compare){
            $sql .= 'pos.id,
                    pos.partnerid,
                    pos.cityid,
                    pos.segmentid,
                    pos.distribid,
                    pos.postid,
                    pos.areaid,
                    pos.code,
                    pos.parentfid,
                    worker.id as `ref`,
                    DATEDIFF(CURDATE(), mdl_lm_user.hiredate)/30 as `diff`';
        }else{
            $sql .= 'pos.id,
                    pos.parentid,
                    pos.code,
                    pos.parentfid,
                    pos.areaid,
                    CONCAT(worker.firstname, " ", worker.lastname) as `name`,
                    worker.id as `ref`,
                    mdl_lm_post.name AS `post`,
                    mdl_lm_region.name AS `city`,
                    distribution.name as `distrib`,
                    place.trainerid,
                    CONCAT(trainer.firstname, " ",trainer.lastname) as `trname`,
                    CONCAT(funcdir.firstname, " ", funcdir.lastname) as `funcdirname`';
        }

        $sql .='
                    FROM mdl_lm_position as pos
                    LEFT OUTER JOIN mdl_lm_position_xref ON mdl_lm_position_xref.posid = pos.id
                    INNER JOIN mdl_user as worker ON mdl_lm_position_xref.userid = worker.id
                    LEFT OUTER JOIN mdl_lm_post ON pos.postid=mdl_lm_post.id
                    LEFT OUTER JOIN mdl_lm_region ON mdl_lm_region.id=pos.cityid
                    LEFT OUTER JOIN mdl_lm_place as place ON place.id=pos.areaid
                    LEFT OUTER JOIN mdl_user as trainer ON place.trainerid=trainer.id
                    LEFT JOIN mdl_lm_distrib as distribution ON distribution.id=pos.distribid
                    LEFT JOIN mdl_lm_user on mdl_lm_user.id = worker.id
                    LEFT OUTER JOIN mdl_lm_position_xref as xref2 ON xref2.posid=pos.parentfid
                    LEFT OUTER JOIN mdl_user AS funcdir ON funcdir.id = xref2.userid
                    WHERE
                      mdl_lm_position_xref.archive = 0 AND mdl_lm_position_xref.userid <> 0';

        if(!is_null($parentid)){
            $sql .=  ' AND pos.parentid=' . $parentid;
        }

        if ($id){
            $sql .= ' AND pos.id = '. $id;
        }

        if ($partnerid){
            $sql .= ' AND pos.partnerid ='. $partnerid;
        }

        return array_values($DB->get_records_sql($sql));

    }

    private function check_children(array $positions){//возвращает список позиций, прошедших отбор

        $partners = optional_param_array('partners', null, PARAM_INT);
        $cities = optional_param_array('cities', null, PARAM_INT);
        $posts = optional_param_array('posts', null, PARAM_INT);
        $segments = optional_param_array('segments', null, PARAM_INT);
        $distributions = optional_param_array('distributions', null, PARAM_INT);
        $experiences = optional_param_array('experiences', null, PARAM_INT);
        $res=[];
        foreach($positions as $position){
            $check = true;
            if (count($partners)) {
                $check &= in_array($position->partnerid, $partners);
            }
            if (count($cities)) {
                $check &= in_array($position->cityid, $cities);
            }
            if (count($posts)) {
                $check &= in_array($position->postid, $posts);
            }
            if (count($distributions)){
                $check &= in_array($position->distribid, $distributions);
            }
            if (count($segments)){
                $check &= in_array($position->segmentid, $segments);
            }
            if (count($experiences)){
                $check &= in_array($position->diff, $experiences);
            }
            if($check){
                $res[]=$position;
            }else{//если child не прошел - проверяем его детей
                if(count(self::check_children(self::get_positions($position->id, null, null, true)))){
                    $res[]=$position;
                }
            }
        }
        return $res;
    }

    private function turn($results, $parentid=0,  $level=1){//сворачивает массив данных в дерево, попутно расставляя level (необходимо для плагина)
          //найти родительский элемент

            $res=[];
            foreach($results as $r){
                $r->search_type=true;
                if ($r->parentid==$parentid){
                    $res[]=$r;
                }
            }

            foreach($res as $child){
                //после него вставить потомков
                $child->level=$level;
                $child->child = self::turn($results, $child->id, $level+1);
            }
        return $res;

    }

    private function expand(array $arr, &$new=[]){//т.к. плагину необходимо возвращать упорядоченный список
        foreach($arr as $a) {
            if (count($a->child)!=0){
                $new[]=$a;//НИЧЕГО НЕ МЕНЯТЬ, ОПТИМИЗАЦИЮ УСЛОВИЯ НЕ ПРОИЗВОДИТЬ, ПОРЯДОК ДЕЙСТВИЙ ОЧЕНЬ ВАЖЕН
                $r=self::expand($a->child, $new);
                unset($a->child);
            }else{
                $new[]=$a;
            }

        }
        return $new;
    }
}