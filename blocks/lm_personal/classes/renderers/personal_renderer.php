<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 6/14/2015
 * Time: 12:23 PM
 */
require_once($CFG->dirroot.'/user/lib.php');

class block_lm_personal_renderer extends block_manage_renderer {

    const PATH = '/blocks/lm_personal';
    const URI  = '/blocks/manage/?_p=lm_personal';

    public $pageurl = '/blocks/manage/?_p=lm_personal';
    public $type = 'lm_personal';
    public $pagename = 'Мой профиль';

    /**
     * @var lm_user
     */
    public $user = NULL;

    /**
     * @var lm_personal
     */
    public $personal = NULL;

    public function init_page()
    {
        global $USER;
        
        parent::init_page();

        if( ! $this->user) {
            $userid = optional_param('id', 0, PARAM_INT);
            if ( ! $userid) $userid = $USER->id;
            $this->user = lm_user::i($userid);
        }
        
        $this->personal = new lm_personal($this->user->id);

        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->js('/blocks/manage/yui/camera/getUserMedia.js');
        $this->page->requires->js('/blocks/manage/yui/camera/canvas-to-blob.min.js');
        $this->page->requires->js(self::PATH.'/js/snapshot.js');

        $this->page->requires->js(self::PATH.'/js/personal.js');

        $this->page->requires->js(self::PATH.'/js/file-upload.js');
        $this->page->requires->js(self::PATH.'/js/maskedinput.js');

    }

    public function get_chiefs($q, $limit = 15) {
        global $DB, $OUTPUT;

        $parents_role_id = 14;

        /*$sql = "SELECT lpx.posid as posid, u.*
              FROM {role_assignments} ra
              JOIN {user} u ON u.id = ra.userid
              JOIN {lm_position_xref} lpx ON lpx.userid = u.id AND lpx.archive = 0
             WHERE ra.roleid = {$parents_role_id} AND ra.contextid = 1 AND CONCAT(u.lastname, ' ', u.firstname) LIKE '%{$q}%'
             ORDER BY u.lastname ASC
             LIMIT $limit";*/

        $sql = "SELECT lpx.posid as posid, u.*
              FROM {lm_position_xref} lpx
              JOIN {user} u ON u.id = lpx.userid AND lpx.archive=0
             WHERE CONCAT(u.lastname, ' ', u.firstname) LIKE '%{$q}%'
             ORDER BY u.lastname ASC
             LIMIT $limit";

        $users = $DB->get_records_sql($sql);

        foreach ($users as $key => $user) {
            $users[$key] = $OUTPUT->user_picture($user) . ' ' . fullname($user);
        }

        return $users;
    }

    public function get_trainers($q, $limit = 15) {
        global $DB, $OUTPUT, $CFG;

        $trainers_role_id = (int) $CFG->block_manage_trainerroleid;

        if ($trainers_role_id > 0) {
            $sql = "SELECT /*lpx.posid as posid,*/ u.*
              FROM {role_assignments} ra
              JOIN {user} u ON u.id = ra.userid
              /*JOIN {lm_position_xref} lpx ON lpx.userid=ra.userid AND lpx.archive=0*/
             WHERE ra.roleid = {$trainers_role_id} AND ra.contextid = 1 AND CONCAT(u.lastname, ' ', u.firstname) LIKE '%{$q}%'
             GROUP BY u.id
             ORDER BY u.lastname ASC
             LIMIT $limit";

            $users = $DB->get_records_sql($sql);

            foreach ($users as $key => $user) {
                $users[$key] = $OUTPUT->user_picture($user) . ' ' . fullname($user);
            }

            return $users;
        } else {
            return FALSE;
        }
    }

    public function main_content()
    {
        global $CFG, $USER;

        if( ! $this->user) return 'Профиль не найден';

        $user = $this->user;

        $this->tpl->user_id = $user->id;
        $this->tpl->picture  = $this->personal->get_field_value('picture');
        $this->tpl->fullname = fullname($user);
        if ( lm_user::is_admin() && $USER->id != $user->id ) {
            $this->tpl->loginas = "{$CFG->wwwroot}/course/loginas.php?id=" . SITEID . "&user={$user->id}&sesskey=" . sesskey();
        }

        // доступные поля
        $this->tpl->props = array();
        foreach (lm_personal::$fields as $field_code => &$field){
            if ($field_code === 'picture') {
                $this->tpl->picture['readonly'] = ! $this->personal->has_permission($field_code);
            } else {
                $this->tpl->props[$field_code] = $this->personal->get_field_value($field_code) + $field;
                $this->tpl->props[$field_code]['readonly'] = ! $this->personal->has_permission($field_code);
            }
        }

        return $this->fetch(self::PATH . '/tpl/details.tpl');

    }

    public function ajax_edit_field($params) {

        if (isset(lm_personal::$fields[$params->field])) {
            $field_code = $params->field;
            $field = lm_personal::$fields[$params->field];
        } else {
            return FALSE;
        }

        if ((int) $params->id > 0) $user_id = (int) $params->id;
        else return FALSE;

        if ( ! $this->personal->has_permission($field_code)) return FALSE;

        if ($field['type'] === 'modalpicker') {
            if (isset($params->value)) return $this->personal->save_field($field_code, $params->value);
            else {
                if (isset($field['options']) && is_callable($field['options'])) {
                    $options = call_user_func($field['options'], $params->q);
                    $data = array();
                    foreach ($options as $value => $label) {
                        $data[] = array('id' => $value, 'html' => $label);
                    }
                    return array('data' => $data);
                }
            }
        } else {
            $this->tpl->title = lm_personal_edit_form::$fields[$field['field']]['title'];
            $this->tpl->info  = lm_personal_edit_form::$fields[$field['field']]['info'];

            if (isset(lm_personal_edit_form::$fields[$field['field']])) {
                $form = new lm_personal_edit_form(
                    '/blocks/manage/?__ajc=lm_personal::edit_field&id='.$user_id.'&field='.$field_code,
                    $field,
                    'post'
                );
                $data = $form->get_data();
                if ($data) {
                    if ($field['type'] === 'file') $data->value = $form->upload_picture();

                    if (is_null($data->value)) return FALSE;

                    return $this->personal->save_field($field_code, $data->value);
                } else {
                    $form->set_data(array('value' => $this->user->{$field['field']}));
                    $form->focus();
                    $this->tpl->form = $form->render();
                    $this->tpl->snapshot = $field_code === 'picture';
                    return $this->fetch('/blocks/lm_personal/tpl/edit.tpl');
                }
            }
        }

        return FALSE;
    }

}