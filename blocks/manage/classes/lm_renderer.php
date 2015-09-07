<?php

class lm_renderer{
    private static $renderer = NULL;

    /**
     * @param $pagename
     * @return null|block_manage_renderer
     */
    public static function get($pagename=NULL){
        global $PAGE;

        if(self::$renderer === NULL && $pagename){
            $parts = explode('_', $pagename);
            $blockname = 'manage';
            if( isset($parts[0]) && $parts[0] == 'lm' ){
                $blockname = implode('_', $parts);
                $pagename = NULL;
            }

            self::$renderer = $PAGE->get_renderer('block_'.$blockname, $pagename);
        }
        return self::$renderer;
    }

}