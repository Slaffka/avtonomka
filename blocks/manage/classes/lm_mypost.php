<?php

class lm_mypost extends lm_post
{
    private static $i = NULL;

    /**
     * @param $postid
     * @return lm_mypost
     */
    static public function i($userid=0){
        global $USER;

        if(!$userid) $userid = $USER->id;

        if( !isset(self::$i[$userid]) ){
            self::$i[$userid] = new lm_mypost($userid);
        }

        return self::$i[$userid];
    }

    public function __construct($userid){
        global $USER;

        if($userid === 0) $userid = $USER->id;
        if( $postid = self::get_post_id($userid) ) {
            parent::__construct($postid);
        }
    }

    public static function get_post_id($userid=0)
    {
        global $DB, $USER;

        if(!$userid) $userid = $USER->id;

        $sql = "SELECT pst.id
                    FROM {lm_position_xref} lpx
                    JOIN {lm_position} lp ON lpx.posid=lp.id
                    JOIN {lm_post} pst ON lp.postid=pst.id
                    WHERE lpx.userid={$userid}
                    LIMIT 0, 1";

        return $DB->get_field_sql($sql);
    }
}