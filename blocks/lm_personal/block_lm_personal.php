<?php

defined('MOODLE_INTERNAL') || die();

class block_lm_personal extends lm_profile_block {

    public $details_btn = false;
    public $details_url = '/blocks/manage/?_p=lm_personal';

    /**
     * @param block_lm_personal_renderer $renderer
     * @return bool
     */
    public function widget_data($renderer) {
        global $OUTPUT;

        $user = self::user();
        if( ! $user) return FALSE;

        $personal = new lm_personal($user->id);

        $tpl = $renderer->tpl;

        $tpl->upic      = $OUTPUT->user_picture($user, array('size' => 60, 'link'=>FALSE, 'alttext'=>FALSE));
        $tpl->ufullname = fullname($user);
        $tpl->since     = $personal->get_field_value('since', true);
        $tpl->email     = $personal->get_field_value('email');
        $tpl->phone     = $personal->get_field_value('phone');
        $tpl->post      = $personal->get_field_value('post');
        $tpl->chief     = $personal->get_field_value('parent');
        $tpl->fchief    = $personal->get_field_value('parentf');

        if ($tpl->fchief) $tpl->fchief = $tpl->chief;

        $tpl->upiclink = $this->details_url;
        /*if( $USER->id == $user->id  ){
            $tpl->upiclink = "{$CFG->wwwroot}/user/edit.php?id={$user->id}";
        }else if ( is_admin() && $USER->id != $user->id ) {
            $tpl->upiclink = "{$CFG->wwwroot}/user/editadvanced.php?id={$user->id}";
            $tpl->loginas = "{$CFG->wwwroot}/course/loginas.php?user={$user->id}&sesskey=" . sesskey();
        }*/
        return TRUE;
    }

}
