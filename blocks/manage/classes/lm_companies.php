<?php
class lm_companies{
    public static $companylist = NULL;

    /**
     * Возвращает список компаний с очищенными наименованиями (см. clean_company_name), которые существуют в системе
     *
     * @return null|array
     */
    public static function _list(){
        global $DB;

        if(self::$companylist !== NULL){
            return self::$companylist;
        }

        if($companies = $DB->get_records_menu('lm_company', array(), '', 'id, name')){
            foreach($companies as $id=>$name){
                self::$companylist[$id] = self::clean_name($name);
            }
        }

        return self::$companylist;
    }

    public static function name_exists($name){
        $cleanedname = self::clean_name($name);
        $companies = lm_companies::_list();

        if($companyid = array_search ($cleanedname, $companies)){
            return $companyid;
        }

        return false;
    }



    public static function get_companies_by_synonym($synonym){
        global $DB;

        $synonym = self::clean_name($synonym);

        $sql = "SELECT id, name
                      FROM {lm_company}
                      WHERE (synonyms LIKE ? OR synonyms LIKE ? OR synonyms LIKE ? OR synonyms LIKE ?)
                      GROUP BY id";

        return $DB->get_records_sql($sql, array("{$synonym}", "{$synonym},%", "%,{$synonym},%", "%,{$synonym}"));
    }

    public static function check_conflict_by_synonym($synonym){
        $companies = self::get_companies_by_synonym($synonym);
        if(count($companies) > 1){
            return $companies;
        }else{
            false;
        }
    }

    public static function synonyms_exists($synonym){

        $company = self::get_companies_by_synonym($synonym);

        if(!$company){
            return NULL;
        }else if(count($company) > 1){
            return false;
        }else{
            $company = array_pop($company);
            return $company->id;
        }
    }

    /**
     * Очищает название компании от организационно-правовых форм, а также символов: точка, запятая, скобки, пробелы
     *
     * @param $name
     * @return string
     */
    public static function clean_name($name)
    {
        $cleanedname = preg_replace('/ООО | ООО|ИП | ИП|[-"\'., ()]/', '', $name);
        $cleanedname = str_replace(array('ё', 'Ё', 'й', 'Й'), array('е', 'е', 'и', 'и'), $cleanedname);
        return mb_strtolower($cleanedname, 'utf-8');
    }
}