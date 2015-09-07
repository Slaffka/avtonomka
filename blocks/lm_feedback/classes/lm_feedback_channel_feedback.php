<?php


class lm_feedback_channel_feedback
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
            if ( $tickets = lm_feedback::get_tickets_user($userid, $search_string) ) {
                foreach ($tickets as $ticket) {
                    $data[] = (object)array('id' => $ticket->id, 'html' => mb_substr($ticket->message, 0, 50));
                }
            }
        }

        $result = array();
        $result['data'] = $data;
        $result['text'] = "обращение";

        return (object) $result;
    }
}