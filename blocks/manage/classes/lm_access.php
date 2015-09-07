<?php
class lm_access{
    public static function definitions(){
        return array('tp' => array('manage_mycourses'=>1, 'lm_settinggoals'=>1, 'lm_bestpractices'=>1,
            'lm_rating'=>1, 'lm_tma'=>1, 'lm_bank'=>1, 'lm_feedback'=>1, 'lm_myteam'=>1),
            'merch' => array('manage_mycourses'=>1, 'lm_rating'=>1, 'lm_bank'=>1, 'lm_feedback'=>1, 'lm_bestpractices'=>1, 'lm_myteam'=>1),
            'svsales' => array('manage_mycourses'=>1, 'lm_settinggoals'=>1, 'lm_bestpractices'=>1,
                'lm_rating'=>1, 'lm_tma'=>1, 'lm_bank'=>1, 'lm_feedback'=>1, 'lm_myteam'=>1),
            'trainer' => array('manage_mycourses'=>1, 'lm_rating'=>1, 'lm_myteam'=>1,
                'manage_activities'=>1),
            'hr' => array(),
            'all' => array('lm_personal'=>1, 'manage_profile'=>1, 'mycourses'=>1, 'lm_feedback'=>1,
                'manage_courseplayer'=>1, 'lm_notifications'=>1),
        );
    }

    public static function get_myroles(){
        global $USER;

        $myroles = array();
        if($roles = get_user_roles(context_system::instance(), $USER->id)){
            foreach($roles as $role){
                $myroles[$role->shortname] = $role->shortname;
            }
        }

        return $myroles;
    }

    public static function has($type){
        global $CFG, $USER;

        if($CFG->siteadmins){
            $admins = explode(',', $CFG->siteadmins);
            if(in_array($USER->id, $admins)){
                return true;
            }
        }

        if( lm_user::is_admin()){
            return true;
        }

        $access = self::definitions();
        $roles = self::get_myroles();

        foreach($roles as $rolename){
            if(isset($access[$rolename][$type]) && $access[$rolename][$type]){
                return true;
            }
        }

        if(isset($access['all'][$type]) && $access['all'][$type]){
            return true;
        }

        return false;
    }
}