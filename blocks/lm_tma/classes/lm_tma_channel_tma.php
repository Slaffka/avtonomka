<?php


class lm_tma_channel_tma
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
        if ( $userid ) {
            if ( $tmas = lm_tma::tma_for_user($userid, $search_string) ) {
                foreach ($tmas as $tma) {
                    $data[] = (object)array('id' => $tma->id, 'html' => mb_substr($tma->title, 0, 50));
                }
            }
        }

        $result = array();
        $result['data'] = $data;
        $result['text'] = "обращение";

        return (object) $result;
    }
}