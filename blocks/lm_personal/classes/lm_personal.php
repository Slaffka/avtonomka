<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 6/14/2015
 * Time: 1:21 PM
 */

class lm_personal {

    const URI = '/blocks/manage/?_p=lm_personal';

    private $user = NULL;

    public static $fields = array(
        'picture' => array(
            'field' => 'picture',
            'type'  => 'file',
            'data_type' => 'int',
            'label' => 'Фото'
        ),

        'distrib' => array(
            'field' => 'distribid',
            'type'  => 'select',
            'options' => 'lm_distrib::distrib_menu',
            'data_type' => 'int',
            'label' => 'Канал сбыта'
        ),

        'segment' => array(
            'field' => 'segmentid',
            'type'  => 'select',
            'options' => 'lm_segmfent::segment_menu',
            'data_type' => 'int',
            'label' => 'Сегмент'
        ),

        'since' => array(
            'field' => 'since',
            'type'  => 'text',
            'data_type' => 'string',
            'label' => 'С нами'
        ),

        'email' => array(
            'field' => 'email',
            'type'  => 'text',
            'data_type' => 'string',
            'label' => 'Почта'
        ),

        'phone' => array(
            'field' => 'phone1',
            'type'  => 'text',
            'data_type' => 'string',
            'label' => 'Телефон'
        ),

        'city' => array(
            'field' => 'cityid',
            'type'  => 'modalpicker',
            'data_type' => 'int',
            'label' => 'Город',
            'func'  => 'get_cities'
        ),

        'post' => array(
            'field' => 'postid',
            'type'  => 'select',
            'data_type' => 'int',
            'label' => 'Должность'
        ),

        'parent' => array(
            'field' => 'parentid',
            'type'  => 'modalpicker',
            'options' => 'block_lm_personal_renderer::get_chiefs',
            'data_type' => 'int',
            'label' => 'Руководиель А.'
        ),

        'parentf' => array(
            'field' => 'parentfid',
            'type'  => 'modalpicker',
            'options' => 'block_lm_personal_renderer::get_chiefs',
            'data_type' => 'int',
            'label' => 'Руководиель Ф.'
        ),

        'trainer' => array(
            'field' => 'trainerid',
            'type'  => 'modalpicker',
            'options' => 'block_lm_personal_renderer::get_trainers',
            'data_type' => 'int',
            'label' => 'Тренер'
        )
    );

    public function __construct($userid = NULL)
    {
        global $USER;

        if ( ! $userid) $userid = $USER->id;
        $this->user = lm_user::i($userid);
    }

    public function save_picture($pictureid) {

        $context = context_user::instance($this->user, MUST_EXIST);
        $newpicture = FALSE;

        $fs = get_file_storage();
        // Save newly uploaded file, this will avoid context mismatch for newly created users.
        file_save_draft_area_files($pictureid, $context->id, 'user', 'newicon', 0);
        if (($iconfiles = $fs->get_area_files($context->id, 'user', 'newicon')) && count($iconfiles) == 2) {
            // Get file which was uploaded in draft area.
            foreach ($iconfiles as $file) {
                if (!$file->is_directory()) {
                    break;
                }
            }
            // Copy file to temporary location and the send it for processing icon.
            if ($iconfile = $file->copy_content_to_temp()) {
                // There is a new image that has been uploaded.
                // Process the new image and set the user to make use of it.
                // NOTE: Uploaded images always take over Gravatar.
                $newpicture = (int)process_new_icon($context, 'user', 'icon', 0, $iconfile);
                // Delete temporary file.
                @unlink($iconfile);
                // Remove uploaded file.
                $fs->delete_area_files($context->id, 'user', 'newicon');
            } else {
                // Something went wrong while creating temp file.
                // Remove uploaded file.
                $fs->delete_area_files($context->id, 'user', 'newicon');
                return false;
            }
        }
        return $newpicture;
    }

    public function save_field($field_code, $value) {
        global $CFG, $DB;

        $user = $this->user;

        $field = self::$fields[$field_code]['field'];

        if ($field_code === 'picture') {
            $user->imagefile = $value;

            require_once ($CFG->dirroot.'/user/editlib.php');
            //TODO: remove unused secod param
            useredit_update_picture($user, new lm_personal_edit_form);
            $user->picture = $DB->get_field('user', 'picture', array('id' => $user->id));
        } else if (property_exists($user, $field)) {
            $user->$field = $value;
            user_update_user($user, FALSE);
        } else {
            $position = lm_position::i($user->id);
            if ($position) {
                if ($field_code === 'trainer') {
                    if ($position->areaid) {
                        $place = lm_place::i($position->areaid);
                        if ($place) {
                            $place->set('trainerid', $value);
                            $place->update();
                        }
                    }
                } else if (property_exists($position, $field)) {
                    $position->$field = $value;
                    $position->update();
                } else {
                    return FALSE;
                }
            }
        }
        return $this->get_field_value($field_code);
    }

    public function has_permission($field, $readonly=false) {
        global $USER;

        $user = $this->user;

        //TODO: make this in db and in cpanel
        // self, staffer, parent, moderator
        if($readonly){
            $permissions = array(
                'picture' => array(1, 1, 1, 1),
                'distrib' => array(0, 0, 1, 1),
                'segment' => array(0, 0, 1, 1),
                'phone'   => array(1, 1, 1, 1),
                'parentf' => array(0, 0, 1, 1),
                'trainer' => array(1, 0, 1, 1)
            );
        }else {
            $permissions = array(
                'picture' => array(1, 0, 1, 1),
                'distrib' => array(0, 0, 1, 1),
                'segment' => array(0, 0, 1, 1),
                'phone'   => array(1, 0, 1, 1),
                'parentf' => array(0, 0, 1, 1),
                'trainer' => array(1, 0, 1, 1)
            );
        }

        if ( ! isset($permissions[$field])) return FALSE;

        return (bool)
        $permissions[$field][0] && $user->id == $USER->id
        || $permissions[$field][1] && lm_position::i($USER->id)->parentid == lm_position::i($user->id)->id
        || $permissions[$field][2] && lm_position::i($user->id)->parentid == lm_position::i($USER->id)->id
        || $permissions[$field][3] && lm_user::is_admin();
    }

    public function get_field_value($field_code, $short = false) {
        global $OUTPUT;

        $result = array();

        $user = $this->user;
        if ( ! $user->position) $user->position = lm_position::i($user->id);

        switch ($field_code) {
            case 'picture':
                if ($user->picture || !$this->has_permission('picture')) {
                    $result['value'] = $OUTPUT->user_picture(
                        $user,
                        array(
                            'size' => 170,
                            'link' => FALSE,
                            'alttext' => FALSE
                        )
                    );
                } else {
                    $result['value'] = '<div class="upload-userpicture"><i class="icon-photo"></i>Загрузить фото</div>';
                }
                break;
            case 'distrib':
                $result['value'] = lm_distrib::i($user->position->distribid)->get_name();
                break;
            case 'segment':
                $result['value'] = lm_segment::i($user->position->segmentid)->get_name();
                break;
            case 'since':
                if ($user->hiredate) {
                    $interval = (new DateTime())->diff(new DateTime($user->hiredate));

                    $years  = array('год', 'года', 'лет');
                    $months = array('месяц', 'месяца', 'месяцев');
                    $days   = array('день', 'дня', 'дней');

                    if ($interval->y) {
                        $str = get_num_ending(
                            $interval->y == 1 && $interval->m > 5 ? 2 : $interval->y,
                            $years
                        );
                        $result['value'] = $interval->y .
                            ($interval->m > 5 ? '.5' : '') .
                            ' ' . $str;
                    } else if ($interval->m) {
                        $result['value'] = $interval->m . ' ' . get_num_ending($interval->m, $months);
                    } else {
                        $result['value'] = $interval->d . ' ' . get_num_ending($interval->d, $days);
                    }

                    if ( ! $short) $result['value'] .= ' (c ' . date('d.m.Y', strtotime($user->hiredate)). ')';
                }
                break;
            case 'email':
                $result['uri']   = 'mailto:' . $user->email;
                $result['value'] = $user->email;
                break;
            case 'phone':
                if ($this->has_permission('phone', true)) $result['value'] = $user->phone1;
                else $result['value'] = 'Конфиденциальная информация';
                break;
            case 'city':
                $result['value'] = '';
                if ($user->position->cityid) {
                    $user->city = $user->position->get_my_city();
                    $result['value'] = $user->city->name;
                }
                break;
            case 'post':
                $result['value'] = '';
                if ($user->post = lm_post::i($user->position->postid)) {
                    $result['value'] = $user->post->name;
                }
                break;
            case 'parent':
                $result['uri']   = '';
                $result['value'] = '';
                if ($user->parent = $user->position->get_my_chief()) {
                    $user->parent->fullname = lm_user::short_name($user->parent);
                    $user->parent->uri = new moodle_url(self::URI);
                    $user->parent->uri->param('id', $user->parent->id);

                    $result['uri']   = (string) $user->parent->uri;
                    $result['value'] = lm_user::short_name($user->parent);
                }
                break;
            case 'parentf':
                $result['uri']   = '';
                $result['value'] = '';
                if ($user->parentf = $user->position->get_my_fchief()) {
                    $user->parentf->fullname = lm_user::short_name($user->parentf);
                    $user->parentf->uri = new moodle_url(self::URI);
                    $user->parentf->uri->param('id', $user->parentf->id);
                } else {
                    $user->parentf = $user->parent;
                }

                if ($user->parentf) {
                    $result['uri']   = (string) $user->parentf->uri;
                    $result['value'] = lm_user::short_name($user->parentf);
                }
                break;
            case 'trainer':
                $result['uri']   = '';
                $result['value'] = '';
                if ($user->trainer = $user->position->get_my_trainer()) {
                    $user->trainer->fullname = lm_user::short_name($user->trainer);
                    $user->trainer->uri = new moodle_url(self::URI);
                    $user->trainer->uri->param('id', $user->trainer->id);

                    $result['uri']   = (string) $user->trainer->uri;
                    $result['value'] = lm_user::short_name($user->trainer);
                }
                break;
            default:
                break;
        }
        return $result;
    }
}