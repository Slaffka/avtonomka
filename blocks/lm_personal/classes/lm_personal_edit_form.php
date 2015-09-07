<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 4/14/2015
 * Time: 3:36 PM
 */

require_once $CFG->libdir.'/formslib.php';

class lm_personal_edit_form  extends moodleform {

    public static $fields = array(
        'picture' => array(
            'title'   => 'Изменить фото профиля',
            'info'    => 'Вы можете загрузить изображение в формате JPG, GIF или PNG. Но не более 5 МБ.',
            'accept'  => '.jpg,.fig,.png',
            'mimetype' => array('image/jpeg', 'image/gif', 'image/png'),
            'error'  => 'Изображение должно быть меньше 5мб формата JPG, GIF или PNG',
            'maxfilesize' => 5242880
        ),
        'email' => array(
            'title' => 'Изменить e-mail',
            'regex' => '^(.+?)@(.+?)$',
            'error' => 'Введен некорректный e-mail'
        ),
        'phone1' => array(
            'title' => 'Изменить телефон',
            'mask'  => '+7 (999) 999-99-99',
            'placeholder' => '+7 (___) ___-__-__',
            'regex' => '/^\+?[- )(\d]*$/',
            'error' => 'Введен некорректный номер'
        ),
        'distribid' => array(
            'title' => 'Изменить канал сбыта',
            'regex' => '/^\d+$/',
            'error' => 'Указано неверное значение'
        ),
        'segmentid' => array(
            'title' => 'Изменить сегмент',
            'regex' => '/^\d+$/',
            'error' => 'Указано неверное значение'
        )
    );

    function definition() {
        global $PAGE;

        $form =& $this->_form;
        $field =& $this->_customdata;

        $field = $field + self::$fields[$this->_customdata['field']];

        $attrs = array();
        if ($field['mask']) $attrs['data-mask'] = $field['mask'];
        if ($field['placeholder']) $attrs['placeholder'] = $field['placeholder'];
        if ($field['accept']) $attrs['accept'] = $field['accept'];

        $name = $field['type'] === 'file' ? 'repo_upload_file' : 'value';

        $element = $form->addElement($field['type'], $name, null, $attrs);

        if ($field['type'] === 'select') {
            if (is_callable($field['options'])) {
                $options = call_user_func($field['options']);
            }
            if (is_array($options)) {
                foreach ($options as $value => $label) {
                    $element->addOption($label, $value);
                }
            }
        }

        if ($field['maxfilesize']) {
            $form->addRule($name, $field['error'], 'maxfilesize',  $field['maxfilesize']);
            $form->setMaxFileSize(10*$field['maxfilesize']);
        }
        if ($field['mimetype']) $form->addRule($name, $field['error'], 'mimetype',  $field['mimetype']);

        if ($field['type'] === 'file') {
            $form->addElement('hidden', 'itemid', file_get_unused_draft_itemid());
            $form->addElement('hidden', 'contextid', $PAGE->context->id);
        } else {
            $form->addElement('submit', null, 'Изменить');
        }
    }

    public function upload_picture() {
        global $CFG;

        $field = self::$fields[$this->_customdata['field']];

        //TODO: what is this?!
        $repo_id = 4;

        $item_id    = $this->_form->exportValue('itemid');
        $context_id = $this->_form->exportValue('contextid');

        if ( ! $item_id || ! $repo_id || ! $context_id) return FALSE;

        require_once($CFG->dirroot.'/config.php');
        require_once($CFG->dirroot.'/lib/filelib.php');
        require_once($CFG->dirroot.'/repository/lib.php');
        /**
         * @var $repo repository_upload
         */
        $repo = repository::get_repository_by_id($repo_id, $context_id);
        // Check permissions
        $repo->check_capability();

        $saveas_filename = isset($_FILES['repo_upload_file']) ? $_FILES['repo_upload_file']['name'] : 'avatar.jpg';
        $maxbytes = $field['maxfilesize'];

        $repo->upload($saveas_filename, $maxbytes);

        return $item_id;
    }

    function validation($data, $files) {
        $field = self::$fields[$this->_customdata['field']];
        if ($field['regex'] && ! preg_match($field['regex'], $data['value'])) {
            return array('value' => $field['error']);
        }

        return array();
    }
}