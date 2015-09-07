<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 4/4/2015
 * Time: 12:00 PM
 */

defined('MOODLE_INTERNAL') || die();
require_once $CFG->libdir.'/formslib.php';

class lm_rating_import_form extends moodleform
{
    function definition()
    {
        $mform =& $this->_form;

        // File upload.
        $acceptedtypes = array('xml');
        $mform->addElement('filepicker', 'import_file', 'Загрузите файл в формате xml', null, array('accepted_types' => $acceptedtypes));
        $mform->addRule('import_file', null, 'required');

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

class block_manage_importrating_renderer extends lm_base_import
{
    /**
     * @var string
     */
    public $pageurl = '/blocks/manage/?_p=importrating';
    public $pagename = 'Импорт рейтингов';
    public $type = 'manage_importrating';

    /**
     * @var stored_file
     */
    private $file = NULL;

    /**
     * @var lm_unireader
     */
    public $reader = NULL;

    public function init_page() {
        if( ! lm_user::is_admin()) die();

        parent::init_page();
    }

    public function main_content() {
        global $CFG, $DB;

        if( ! lm_user::is_admin()) return 'Ошибка доступа!';

        @set_time_limit(0);
        raise_memory_limit(MEMORY_EXTRA);

        $form = new lm_rating_import_form($this->pageurl);
        if ($form->is_submitted() && $form->is_validated()) {

            $this->file = $form->get_file('import_file');

            if ($this->file instanceof stored_file) {

                $importer = new lm_rating_import($this->file);

                $time = mktime(true);
                $count = $importer->import();
                $this->tpl->time = number_format(mktime(true) - $time, 4);

                if ($count !== FALSE) {
                    $this->tpl->metric_count = $count['metric'];
                    $this->tpl->param_count  = $count['param'];
                    $this->tpl->page = 'complete';
                } else {
                    $this->tpl->errors[] = 'Не удается прочитать файл импорта';
                    $this->tpl->form = $form->render();
                }
                $this->file->delete();
            } else {
                $this->tpl->errors[] = 'Ошибка при определении типа файла';
            }
        } else {
            $this->tpl->form = $form->render();
        }
        return $this->fetch('importrating/index.tpl');
    }

}