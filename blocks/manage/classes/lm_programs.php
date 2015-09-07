<?php
class lm_programs{
    protected static $programs = array();

    public static function get_menu($key='id', $value='name', $search = ""){
        global $DB;

        if ( $search ) {
            $search = " AND name LIKE '%$search%'";
        }
        if( !isset(self::$programs[$key.$value]) ) {
            self::$programs[$key.$value] = $DB->get_records_select_menu('lm_program', "parent!=0 {$search}", null, '', "{$key}, {$value}");
        }

        return self::$programs[$key.$value];
    }

    /**
     * Возвращает список категорий для программ
     *
     * @return array
     */
    public static function get_categories_menu(){
        global $DB;

        return $DB->get_records_menu('lm_program', array('parent'=>0), 'name ASC');
    }

    public static function get_courseid($programid){
        $courseid = 0;
        if( $programs = self::get_menu('id', 'courseid') ){
            if( isset($programs[$programid]) ){
                $courseid = $programs[$programid];
            }
        }
        return $courseid;
    }
}