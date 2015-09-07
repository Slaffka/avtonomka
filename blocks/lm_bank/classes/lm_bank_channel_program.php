<?php


class lm_bank_channel_program
{
    /**
     * @param $userid
     * @return object
     * @throws coding_exception
     */
    public static function get_instances($userid)
    {
        $search_string = optional_param("q", "", PARAM_TEXT);

        $data = array();
        if ( $programs = lm_programs::get_menu('id', 'name', $search_string) ) {
            foreach ($programs as $id => $program) {
                $data[] = (object)array('id' => $id, 'html' => mb_substr($program, 0, 50));
            }
        }
        $result = array();
        $result['data'] = $data;
        $result['text'] = " программу";

        return (object) $result;
    }
}