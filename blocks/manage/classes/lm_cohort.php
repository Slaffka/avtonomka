<?php

require_once($CFG->dirroot.'/cohort/lib.php');

class lm_cohort
{
    public static function get_menu()
    {
        $cohortmenu = array();

        $cohorts = cohort_get_cohorts(1);
        if (isset($cohorts['cohorts']) && is_array($cohorts['cohorts'])) {
            foreach ($cohorts['cohorts'] as $cohort) {
                $cohortmenu[$cohort->id] = $cohort->name;
            }
        }

        return $cohortmenu;
    }

    public static function remove_member($cohortid, $stafferid){
        cohort_remove_member($cohortid, $stafferid);
    }

    public static function add_member($cohortid, $stafferid){
        cohort_add_member($cohortid, $stafferid);
    }
}