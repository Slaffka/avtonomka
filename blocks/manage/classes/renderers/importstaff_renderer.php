<?php

require_once $CFG->libdir.'/formslib.php';
require_once($CFG->libdir.'/gradelib.php');

class staff_import_form extends moodleform
{
    function definition()
    {
        $mform =& $this->_form;

        // course id needs to be passed for auth purposes
        $mform->addElement('hidden', 'id', optional_param('id', 0, PARAM_INT));
        $mform->setType('id', PARAM_INT);

        // File upload.
        $acceptedtypes = array('xml', 'csv', 'txt');
        $mform->addElement('filepicker', 'userfile', 'Загрузите файл в формате csv или xml', null, array('accepted_types' => $acceptedtypes));
        $mform->addRule('userfile', null, 'required');

        /*$encodings = core_text::get_encodings();
        $mform->addElement('select', 'encoding', get_string('encoding', 'grades'), $encodings);*/

        $this->add_action_buttons(false, 'Начать импорт');
    }

    function get_file($elname) {
        global $USER;

        if (!$this->is_submitted() or !$this->is_validated()) {
            return false;
        }

        $element = $this->_form->getElement($elname);

        if ($element instanceof MoodleQuickForm_filepicker || $element instanceof MoodleQuickForm_filemanager) {
            $values = $this->_form->exportValues($elname);
            if (empty($values[$elname])) {
                return false;
            }
            $draftid = $values[$elname];
            $fs = get_file_storage();
            $context = context_user::instance($USER->id);
            if (!$files = $fs->get_area_files($context->id, 'user', 'draft', $draftid, 'id DESC', false)) {
                return false;
            }
            return reset($files);
        }

        return false;
    }
}



class block_manage_importstaff_renderer extends block_manage_renderer
{
    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=importstaff';
    public $pagename = 'Импорт сотрудников';
    public $type = 'manage_importstaff';
    public $iid = 0;
    public $step = 1;
    /**
     * @var lm_staff_import
     */
    public $importer = NULL;

    public function init_page(){
        if(!lm_user::is_admin()){
            die();
        }

        $this->iid = optional_param('iid', null, PARAM_TEXT);
        $this->step = optional_param('step', 1, PARAM_INT);

        if($this->iid && $this->step){
            $this->pagename .= " - Шаг ".$this->step;
        }

        parent::init_page();
        $this->page->requires->js('/blocks/manage/yui/base.js');
        $this->page->requires->js('/blocks/manage/yui/importsales.js');
    }

    public function main_content()
    {
        if(!lm_user::is_admin()) {
            return 'Ошибка доступа!';
        }
        $out = '';

        // Если файл еще не был отправлен, проверяем наличие загрузки, либо отображаем начальную форму загрузки файлов
        $mform = NULL;
        if (!$this->iid) {
            $mform = new staff_import_form($this->pageurl, array('acceptedtypes' => lm_unireader::$acceptedtypes));
            if ($formdata = $mform->get_data()) {
                $file = $mform->get_file('userfile');

                if( !($file instanceof stored_file) ){
                    return 'Ошибка при определении типа файла';
                }

                $this->importer = new lm_staff_import($file);
            } else {
                // Показываем стандартную форму загрузки файла
                return $mform->render();
            }
        } else {
            $this->importer = new lm_staff_import($this->iid);
        }
        return $this->procced();
    }

    public function procced()
    {
        global $CFG;

        $out = "";
        $error = false;
        if($this->step && $result = $this->importer->import($this->step)){
            list($error, $out) = $result;
        }

        $this->tpl->info = $out;

        $prevstep = $this->step - 1;
        if($prevstep) {
            $this->tpl->prevstephref = $this->pageurl . '&iid=' . $this->iid . '&step=' . $prevstep;
        }

        $nextstep = $this->step + 1;

        if($this->step < 2) {
            $this->tpl->nextstephref = $this->pageurl . '&iid=' . $this->iid . '&step=' . $nextstep;
        }else{
            $this->tpl->nextstephref = $CFG->wwwroot.'/blocks/manage/?_p=places';
        }

        $this->tpl->nextstepdisabled = $error;
        $this->tpl->nextstepname = $this->step == 2 ? "Завершить": "Далее";

        return $this->fetch('importsales/index.tpl');
    }


}