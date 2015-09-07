<?php
class block_manage_base_renderer extends block_manage_renderer{
    public function main_content(){
        return '';
    }

    /**
     * Список для окна выбора пользователей
     *
     * @throws coding_exception
     */
    public function ajax_userpicker_list($p){
        global $OUTPUT;


        $data = array();
        if(!isset($p->q)){
            $p->q = "";
        }

        if ($users = get_userlist($p->q)) {
            foreach ($users as $user) {
                $data[] = (object)array('id' => $user->id, 'html' => $OUTPUT->user_picture($user) . ' ' . fullname($user));
            }
        }

        return (object) array('data'=>$data);
    }

    public function ajax_turn_edition($p)
    {
        global $PAGE, $USER;

        if(isset($p->edit) && !empty($p->sesskey)) {
            if ($PAGE->user_allowed_editing() && $p->edit != -1 && confirm_sesskey($p->sesskey)) {
                $USER->editing = $p->edit;
            }
        }

        if(isset($p->redirect)){
            redirect($p->redirect);
        }
    }
}