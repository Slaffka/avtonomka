<?php

class lm_matrix
{
    public static function stages(){
        return array(
            1 => (object)array('id'=>1, 'code'=>'newcomer', 'name'=>'Новичок'),
            2 => (object)array('id'=>2, 'code'=>'spec', 'name'=>'Специалист'),
            3 => (object)array('id'=>3, 'code'=>'skilled', 'name'=>'Опытный'),
            4 => (object)array('id'=>4, 'code'=>'profi', 'name'=>'Профессионал')
        );
    }

    public static function stageid_by_code($stagecode){
        $stageid = 0;
        foreach(self::stages() as $stg){
            if($stg->code == $stagecode){
                $stageid = $stg->id;
                break;
            }
        }

        return $stageid;
    }

    public static function programs_menu($mypostid=0, $stageid=0){
        global $DB;

        $where = "";
        if($mypostid) $where .= "lpm.postid={$mypostid}";
        if($where && $stageid) $where .=  " AND ";
        if($stageid) $where .= "lpm.stage={$stageid}";
        if(!$where) $where = "1";

        $sql = "SELECT lpm.id, lpm.programid
                      FROM {lm_program_matrix} lpm
                      WHERE {$where}
                      GROUP BY lpm.programid";

        return $DB->get_records_sql_menu($sql);
    }


    public static function programs($mypostid, $stageid){
        global $DB;

        $fields = 'lp.id, c.id as courseid, lp.name, lpm.stage';
        $sql = "SELECT {$fields}
                      FROM {lm_program_matrix} lpm
                      JOIN {lm_program} lp ON lpm.programid=lp.id
                      LEFT JOIN {course} c ON lp.courseid=c.id
                      WHERE lpm.postid={$mypostid} AND lpm.stage={$stageid}
                      ORDER BY lpm.sequence ASC";

        return $DB->get_records_sql($sql);
    }
    public static function inProgram($programid){
        global $DB;

        return $DB->get_records_menu('lm_program_matrix', array("programid"=>$programid), '', "postid, stage");
    }

    public static function update($matrix){
        global $DB;

        $postid = key($matrix);
        $stageid = key($matrix[$postid]);
        if($postid && $stageid){
            $order = array();
            $n = 1;
            foreach($matrix[$postid][$stageid] as $programid){
                if($programid) {
                    $order[$programid] = $n;
                    $n++;
                }
            }

            if($items = $DB->get_records('lm_program_matrix', array('postid'=>$postid, 'stage'=>$stageid))){
                foreach($items as $item){
                    if(!isset($order[$item->programid])){
                        $DB->delete_records('lm_program_matrix', array('id'=>$item->id));
                    }else if($order[$item->programid] != $item->sequence){
                        $item->sequence = $order[$item->programid];
                        $DB->update_record('lm_program_matrix', $item);
                    }

                    unset($order[$item->programid]);
                }
            }

            if(!empty($order)){
                foreach($order as $programid=>$sequence){
                    $dataobj = new StdClass();
                    $dataobj->postid = $postid;
                    $dataobj->stage = $stageid;
                    $dataobj->programid = $programid;
                    $dataobj->sequence = $sequence;

                    $DB->insert_record('lm_program_matrix', $dataobj);
                }
            }
        }
    }
}