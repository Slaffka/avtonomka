<?php

class block_manage_mycourses_renderer extends block_manage_renderer
{
    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=mycourses';
    public $pagename = 'Мои курсы';
    public $type = 'manage_mycourses';
    public $pagelayout = "base";

    public function init_page()
    {
        parent::init_page();
    }

    public function navigation(){
        $subparts = array();

        return $this->subnav($subparts);
    }

    public function require_access(){
        return true; //Имеют доступ все авторизованные пользователи
    }

    public function main_content()
    {
        $is_evalution_stages_enabled = false;
        if( $mypostid = (int) lm_mypost::get_post_id() ) {
            $is_evalution_stages_enabled = lm_post::i($mypostid)->is_evolution_stages_enabled();
        }

        if( $is_evalution_stages_enabled ){
            return $this->evolution_stages_view();
        }else{
            return $this->standard_view();
        }
    }

    public function standard_view()
    {
        global $CFG, $USER;

        if($courses = enrol_get_my_courses()) {
            foreach ($courses as $key => $course) {
                if (!class_exists('course_in_list')) {
                    require_once($CFG->libdir . '/coursecatlib.php');
                }
                $courseinlist = new course_in_list($course);

                $course->image = false;

                /**
                 * @var $file stored_file
                 */
                foreach ($courseinlist->get_course_overviewfiles() as $file) {
                    $isimage = $file->is_valid_image();
                    $url = "$CFG->wwwroot/pluginfile.php" .
                        '/' . $file->get_contextid() . '/' . $file->get_component() . '/' .
                        $file->get_filearea() . $file->get_filepath() . $file->get_filename();
                    if ($isimage) {
                        $course->image = html_writer::empty_tag('img', array('src' => $url));
                        break;
                    }
                }

                $course->progress = (int)lm_course::calculate_course_result($course->id, $USER->id);
            }
        }

        $this->tpl->courses = $courses;

        return $this->fetch('mycourses/index.tpl');
    }

    public function evolution_stages_view()
    {
        $pos = optional_param('pos', 'newcomer', PARAM_TEXT);

        $stageid = lm_matrix::stageid_by_code($pos);
        if(!$stageid){
            return 'Ошибка! Нет такого статуса проф.развития';
        }


        $staffer = lm_staffer::self();
        $this->tpl->programs = $staffer->programs($stageid);
        if($stages = lm_matrix::stages()){
            foreach( $stages as $stage ){
                $stage->progress = $staffer->get_stage_progress($stage->id);
            }
        }

        $this->tpl->stages = $stages;
        $this->tpl->pos = $pos;
        return $this->fetch('mycourses/evolution_stages.tpl');
    }

    /**
     * Возвращает содержимое модального окна "Соответствие фото"
     *
     * @param $p
     * @param $a
     * @return StdClass
     */
    public function ajax_modal_verifyphoto_content($p, $a){
        global $DB, $OUTPUT, $USER;

        $cases = array(
            "Не задан идентификатор курса" => empty($p->courseid),
            "Не задан идентификатор пользователя" => empty($p->userid),
            "Ошибка доступа!" => !lm_position::i($p->userid)->is_my_chief($USER->id)
        );
        if( $a->error = lm_ajaxrouter::has_errors($cases) ) return $a;

        $sql = "SELECT uvp.id, uvp.photo, uvp.userid
                 FROM {userverifier} uv
                 JOIN {userverifier_photo} uvp ON uv.id=uvp.userverifier
                 WHERE uv.course=? AND uvp.userid=? AND uvp.status=0
                 ORDER BY uvp.timecreated";

        $this->tpl->profilephoto = $OUTPUT->user_picture(
            lm_user::i($p->userid),
            array('size' => 213, 'link' => FALSE, 'alttext' => FALSE, 'class' => FALSE)
        );

        $this->tpl->photos = $DB->get_records_sql($sql, array($p->courseid, $p->userid));
        $a->html = $this->fetch('courseplayer/verifyphoto.tpl');

        return $a;
    }

    /**
     * Подтверждает/отклоняет список фотографий (которые сотрудник сделал во время прохождения курса)
     * которые относятся к конкретному курсу и сотруднику
     *
     * @param $p
     * @param $a
     * @return stdClass
     */
    public function ajax_verifyphotos($p, $a){
        global $DB, $USER;

        $notification = lm_notification::i("manage:verifyphoto:".$p->userid, $USER->id);
        $data = $notification->get_data();

        $cases = array(
            "Не задан параметр action" => empty($p->action),
            "Не определен ид курса" => empty($data->courseid),
            "Не определен ид юзера" => empty($data->userid) || empty($p->userid) || $p->userid != $data->userid,
            "Ошибка доступа!" => !lm_position::i($data->userid)->is_my_chief($USER->id)
        );
        if( $a->error = lm_ajaxrouter::has_errors($cases) ) return $a;

        $p->action = $p->action == "apply"? 1: -1;

        // TODO: Ситуация: СВ откроет окошко подтверждения и уйдет пить кофе, а в это время сотрудник сделает еще фото!
        // Нужно предусмотреть это и добавить метки времени

        $sql = "SELECT uvf.id
                      FROM {userverifier} uv
                      JOIN {userverifier_photo} uvf ON uv.id=uvf.userverifier
                      WHERE uv.course=? AND uvf.userid=? AND status=0";

        if( $photos = $DB->get_records_sql($sql, array($data->courseid, $data->userid)) ){
            foreach($photos as $photo){
                $DB->update_record('userverifier_photo', (object)array('id' => $photo->id, 'status' => $p->action));
            }
        }

        // Очищаем прохождение курса, если отклонили фотографии
        if($p->action == -1){
            lm_course::i($data->courseid)->clear_all_attempts($USER->id);
            // TODO: отнять монеты начисленные по этому курсу
        }

        $notification->remove();

        return $a;
    }
}