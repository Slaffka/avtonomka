<?php

class block_manage_webservicetest_renderer extends block_manage_renderer
{

    public $cache = array();

    public function main_content()
    {
        global $DB;
        $this->tpl->errors = $this->find_duplicates();
        //$this->merge_partners();
        //$this->change_company(1, 74);
        return $this->fetch('webservicetest/index.tpl');
    }


    protected function get_records_array($table, $fields, $conditions=array(), $sort='')
    {
        global $DB;
        $result = array();

        $field = (explode(',', str_replace(' ', '', $fields)));
        $fieldscount = count($field);
        if($fieldscount > 3){
            return false;
        }

        if($records = $DB->get_records($table, $conditions, $sort, $fields)){
            foreach($records as $record){
                switch($fieldscount){
                    case 1:
                        $result[] = $record->$field[0];
                        break;
                    case 2:
                        $result[$record->{$field[1]}] = $record->{$field[0]};
                        break;
                    case 3:
                        $result[$record->{$field[1]}][$record->{$field[2]}] = $record->{$field[0]};
                        break;
                }

            }
        }


        return $result;
    }


    public function find_duplicates(){
        global $DB;

        $sql = "SELECT lp.id, lp.name, lp.companyid, lp.regionid, lc.name as companyname
                      FROM {lm_partner} lp
                      JOIN {lm_company} lc ON lp.companyid=lc.id
                      /*WHERE lp.companyid=1 AND lp.regionid=9*/";

        $exists = array();
        $errors = array();
        if($partners = $DB->get_records_sql($sql)){
            foreach($partners as $partner){
                $partnername = $partner->companyname.'('.$partner->name.')';
                /*if(lm_partner::i($partner->id)->has_dependences()){
                    $partnername .= " - Зависим";
                }*/
                if(isset($exists[$partner->companyid][$partner->regionid])){

                    if(!isset($errors[$partner->companyid][$partner->regionid])){
                        $partnerid = key($exists[$partner->companyid][$partner->regionid]);
                        $errors[$partner->companyid][$partner->regionid][$partnerid] = $exists[$partner->companyid][$partner->regionid][$partnerid];
                    }

                    $errors[$partner->companyid][$partner->regionid][$partner->id] = $partnername;
                }else{
                    $exists[$partner->companyid][$partner->regionid][$partner->id] = $partnername;
                }
            }


        }

        return $errors;
    }

    public function merge_partner_with($primarypartnerid, $secondarypartnerid){
        global $DB;

        $partnerid = $secondarypartnerid;

        if(!isset($this->cache['primaryprograms'])){
            $this->cache['primaryprograms'] = $this->get_records_array('lm_partner_program', 'id, programid, partnerid');
        }
        $primaryprograms = $this->cache['primaryprograms'];

        if(!isset($this->cache['primarystaff'][$partnerid])) {
            $this->cache['primarystaff'] = array();
            $this->cache['primarystaff'][$partnerid] = $this->get_records_array('lm_partner_staff', 'userid', array('partnerid' => $partnerid));
        }
        $primarystaff = $this->cache['primarystaff'];

        if(!isset($this->cache['primarystaffprogress'][$partnerid])) {
            $this->cache['primarystaffprogress'] = array();
            $this->cache['primarystaffprogress'][$partnerid] = $this->get_records_array('lm_partner_staff_progress', 'id, userid, programid', array('partnerid' => $partnerid));
        }
        $primarystaffprogress = $this->cache['primarystaffprogress'];

        if(!isset($this->cache['primarystat'][$partnerid])) {
            $this->cache['primarystat'] = array();
            $this->cache['primarystat'][$partnerid] = $this->get_records_array('lm_stat', 'id, ttid, period', array('partnerid' => $partnerid));
        }
        $primarystat = $this->cache['primarystat'];



        $sql = "UPDATE {lm_activity_request} SET partnerid={$primarypartnerid} WHERE partnerid={$partnerid}";
        if(!$DB->execute($sql)){
            die("Ошибка при обновлении lm_activity_request #{$primarypartnerid} -> #{$partnerid}");
        }

        if($programs = $DB->get_records('lm_partner_program', array('partnerid'=>$partnerid))){
            foreach($programs as $program){
                if(isset($primaryprograms[$program->programid][$program->partnerid])){
                    $conditions = array('partnerid'=>$program->partnerid, 'programid'=>$program->programid);
                    $DB->delete_records('lm_partner_program', $conditions);
                }else{
                    $program->partnerid = $primarypartnerid;
                    $DB->update_record('lm_partner_program', $program);
                }
            }
        }


        if($staffers = $DB->get_records('lm_partner_staff', array('partnerid'=>$partnerid))){
            foreach($staffers as $staffer){
                if(isset($primarystaff[$staffer->userid])){
                    $conditions = array('partnerid'=>$staffer->partnerid, 'userid'=>$staffer->userid);
                    $DB->delete_records('lm_partner_staff', $conditions);
                }else{
                    $staffer->partnerid = $primarypartnerid;
                    $DB->update_record('lm_partner_staff', $staffer);
                }
            }
        }


        if($progresses = $DB->get_records('lm_partner_staff_progress', array('partnerid'=>$partnerid))){
            foreach($progresses as $progress){
                if(isset($primarystaffprogress[$progress->userid][$progress->programid])){
                    $conditions = array('partnerid'=>$progress->partnerid, 'userid'=>$progress->userid,
                        'programid'=>$progress->programid);
                    $DB->delete_records('lm_partner_staff_progress', $conditions);
                }else{
                    $progress->partnerid = $primarypartnerid;
                    $DB->update_record('lm_partner_staff_progress', $progress);
                }
            }
        }

        $sql = "UPDATE {lm_place} SET partnerid={$primarypartnerid} WHERE partnerid={$partnerid}";
        if(!$DB->execute($sql)){
            die("Ошибка при обновлении lm_place #{$primarypartnerid} -> #{$partnerid}");
        }


        if($stats = $DB->get_records('lm_stat', array('partnerid'=>$partnerid))){
            foreach($stats as $stat){
                if(isset($primarystat[$stat->ttid][$stat->period])){
                    $conditions = array('partnerid'=>$stat->partnerid, 'ttid'=>$stat->ttid,
                        'period'=>$stat->period);
                    $DB->delete_records('lm_stat', $conditions);
                }else{
                    $stat->partnerid = $primarypartnerid;
                    $DB->update_record('lm_stat', $stat);
                }
            }
        }

        $DB->delete_records('lm_partner', array('id'=>$secondarypartnerid));
    }


    public function merge_partners(){
        global $DB;

        if($duplicates = $this->find_duplicates()){
            foreach($duplicates as $companyid=>$regions){
                foreach($regions as $regionid=>$partners){
                    $first = true;
                    $primarypartnerid = 0;
                    foreach($partners as $partnerid=>$partnername){
                        if($first){ // С первым партнером объединяем остальных
                            $primarypartnerid = $partnerid;
                            $first = false;
                        }else{
                            $this->merge_partner_with($primarypartnerid, $partnerid);
                        }
                    }
                }
            }
        }
    }

    public function change_company($fromcompanyid, $tocompanyid){
        global $DB;
        if($partners = $DB->get_records('lm_partner', array('companyid'=>$fromcompanyid), '', 'id, regionid')){
            foreach($partners as $partner) {
                if($primarypartnerid = $DB->get_field('lm_partner', 'id', array('companyid'=>$tocompanyid, 'regionid'=>$partner->regionid))){
                    $this->merge_partner_with($primarypartnerid, $partner->id);
                }else{
                    $partner->companyid = $tocompanyid;
                    $DB->update_record('lm_partner', $partner);
                }
            }
        }
    }
}