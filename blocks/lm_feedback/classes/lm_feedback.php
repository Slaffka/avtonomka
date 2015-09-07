<?php
/**
 * Created by PhpStorm.
 * User: Dominik
 * Date: 07.05.2015
 * Time: 15:58
 */

require_once($CFG->dirroot.'/user/lib.php');

class lm_feedback extends stdClass
{
    const TABLE_NAME = 'lm_feedback';


    private static $i  = NULL;
    public $id   = 0;

    public $component  = 'block_lm_feedback';
    public $area       = 'files';
    public $blockname = "lm_feedback";

    /**
     * @param $id
     * @return lm_feedback
     */
    static public function i($id = 0)
    {
        if(!isset(self::$i[$id]) || !$id){
            self::$i[$id] = new lm_feedback($id);
        }

        return self::$i[$id];
    }

    private function __construct($id = 0)
    {
        global $DB, $CFG;

        if ( $id ) {
            $sql = "SELECT
                f.*, fs.name
                  FROM
                    {$CFG->prefix}lm_feedback f
                  LEFT JOIN {$CFG->prefix}lm_feedback_subjects fs ON fs.id = f.subjectid
                  WHERE
                  f.id = $id
            ";

            if ( $ticket = $DB->get_record_sql($sql) ) {
                foreach ($ticket as $field => $value) {
                    $this->$field = $value;
                }
            }
        }

        return $this;
    }


    public function get_date()
    {
        return date("d/m/Y H:i", $this->time);
    }

    public function get_username_for_ticket()
    {
        $user = lm_user::i($this->userid);
        $username = lm_user::short_name($user);

        return $username;
    }

    /**
     * @param $fields
     * @return StdClass
     */
    public function create($fields)
    {
        global $DB, $USER;

        $time = time();
        $answer = new StdClass();
        $id = 0;
        if ( $USER->id ) {
            $data = new StdClass();
            $data->send    = 1; // помечаем что тикет был полностью заполнен и отправлен

            if ( $ticket = $DB->get_record_select("lm_feedback", "userid = {$USER->id} AND send = 0") ) {
                if ( $ticket->files ) {
                    $data->files = $this->save_files($ticket->files);
                }

                $data->id  = $ticket->id;

                $id = $DB->update_record("lm_feedback", $data);
            } else {
                $id = $DB->insert_record("lm_feedback", $data);
            }
        }

        if ( $id ) {
            $answer->ticketid = $id;
            //$answer->newitemid = file_get_unused_draft_itemid();
            $answer->success  = "Сообщение отправлено. В ближайшее время вы получите ответ.";
        } else {
            $answer->error = "Ошибка во входных параметрах";
        }

        return $answer;
    }

    public function save_files($files)
    {
        global $DB;

        $context = new StdClass();
        $all_files = array();

        if ( $instanceid = $DB->get_field_select("block_instances", "id", "blockname = '{$this->blockname}'") ) {
            $context = context_block::instance($instanceid);

            $files = explode(",", $files);
            if ( !empty($files) ) {
                foreach($files as $draft_itemid) {
                    $fs = get_file_storage();
                    $itemid = rand(1, 999999999);
                    while ($f = $fs->get_area_files($context->id, $this->component, $this->area, $itemid)) {
                        $itemid = rand(1, 999999999);
                    }
                    file_save_draft_area_files($draft_itemid, $context->id, $this->component, $this->area, $itemid, array('subdirs' => false, 'maxfiles' => -1)); // загружаем постоянный файл

                    if ( $fils = $fs->get_area_files($context->id, $this->component, $this->area, $itemid) ) {
                        foreach ( $fils as $file ) {
                            if ( !$file->is_directory() ) {
                                $all_files[] = $file->get_id();
                            }
                        }
                    }
                }
            }
            $all_files = implode(",", $all_files);
        }
        return $all_files;
    }

    /**
     * Обновление данных в тикете, если тикет не создан - создаем его
     * @param $fields
     * @return StdClass
     */
    public function update($fields)
    {
        global $DB, $USER;

        $time = time();
        $data = new StdClass();
        $ticket = $a = false;
        $files = "";
        if ( $USER->id ) {
            $ticket = $DB->get_record_select("lm_feedback", "userid = {$USER->id} AND send = 0");
            $fields = (array)$fields;
            foreach ( $fields as $key => $field ) {
                if ( isset($ticket->files) && $ticket->files ) {
                    $files = $ticket->files;
                }
                if ( $key == 'files' ) {
                    if ( $files ) {
                        if ( !substr_count($files, "$field") ) {
                            $data->files = $files.",".$field;
                        }
                    } else {
                        $data->files = $field;
                    }
                } else {
                    $data->$key = $field;
                }
            }

            $data->time    = $time;
            $data->userid  = $USER->id;

            if ( isset($ticket->id) ) {
                $data->id      = $ticket->id;
                $a = $DB->update_record("lm_feedback", $data);
            } else {
                $a = $DB->insert_record("lm_feedback", $data);
            }
        }

        if ( $a ) {
            return true;
        }

        return "Ошибка во входных параметрах";
    }

    public function check($userid)
    {
        if ( $this->userid == $userid ) {
            return true;
        }
        return false;
    }

    public function send_message($message)
    {
        $admin_email = lm_user::i(2)->email(); // TODO: почта админа!
        $email_to = lm_user::i($this->userid)->email();
        $subject = "RE: ".$this->subject;

        $headers  = "Content-type: text/html; charset = utf-8 \r\n";
        $headers .= "From: Группа Черкизово <{$admin_email}>\r\n";

        mail($email_to, $subject, $message, $headers);

        return true;
    }

    public function upload_file_to_draft($param)
    {
        global $CFG, $DB, $USER;

        $repo_id = 4;                // репозиторий upload
        $item_id = $param->itemid;
        $context = new StdClass();

        $result = new StdClass();
        $result->error = true;
        $result->text = 'Ошибка! Повторите попытку.';

        if ( $instanceid = $DB->get_field_select("block_instances", "id", "blockname = '{$this->blockname}'") ) {
            $context = context_block::instance($instanceid);

            if (!$item_id || !$repo_id || !$context->id) return FALSE;

            require_once($CFG->dirroot . '/config.php');
            require_once($CFG->dirroot . '/lib/filelib.php');
            require_once($CFG->dirroot . '/repository/lib.php');
            /**
             * @var $repo repository_upload
             */
            $repo = repository::get_repository_by_id($repo_id, $context->id);
            $repo->check_capability();
            $saveas_filename = md5(microtime());
            $maxbytes = 5242880;

            $f = array();
            try {
                $f = $repo->upload($saveas_filename, $maxbytes); // загружаем в черновик
            } catch (moodle_exception $e) {
                $result->text = $e->getMessage();
            }

            if (isset($f['id'])) {
                $field = new StdClass();
                $field->files = $f['id'];
                $this->update($field);

                $result->error = false;
                $result->count = self::get_count_files();

                return $result;
            }
        }
        return $result;
    }

    public static function get_count_files()
    {
        global $DB, $USER;
        $count_files = 0;
        if ( $files = $DB->get_field_select("lm_feedback", "files", "userid = {$USER->id} AND send = 0") ) {

            $sql = "SELECT count(id) as count FROM {files} WHERE itemid IN({$files}) AND filename != '.' AND filesize != 0 AND filearea = 'draft' AND userid = {$USER->id}";
            if ( $files = $DB->get_record_sql($sql) ) {
                $count_files = $files->count;
            }
        }

        return $count_files;
    }


    public function get_files()
    {
        global $CFG, $DB;
        $text = "";
        $files = explode(",", $this->files);
        if ( !empty($files) ) {
            foreach ( $files as $fileid ) {
                if ( !is_null($fileid) ) {
                    $result = $DB->get_record("files", array('id' => $fileid));

                    if ( $result ) {
                        $baseurl = $CFG->wwwroot . "/pluginfile.php/$result->contextid/$result->component/$result->filearea/$result->itemid/$result->filename";
                        $ext = array_pop(explode(".", $result->filename));

                        $urlimage = "";
                        switch ($ext) {
                            case 'jpg':
                            case 'jpeg':
                            case 'png':
                            case 'gif':
                                $urlimage = $baseurl;
                                break;

                            case 'pdf':
                                $urlimage = 'pdf';
                                break;

                            case 'doc':
                            case 'docx':
                                $urlimage = 'doc';
                                break;

                            case 'xls':
                            case 'xlx':
                                $urlimage = 'xls';
                                break;

                            case 'ppt':
                            case 'pptx':
                                $urlimage = 'ppt';
                                break;
                        }

                        $text .= "<li><a href='{$baseurl}' target='_blank'><img src='{$urlimage}' width = '100px'></a></li>\r\n";
                    }
                }
            }
        }

        return $text;
    }

    public static function get_tickets_user($userid, $search)
    {
        global $DB;

        $where = "";
        if ( $search ) {
            $where = "AND message LIKE '%{$search}%'";
        }
        $tickets = array();
        $tickets = $DB->get_records_select("lm_feedback", "userid = {$userid} {$where}");

        return $tickets;
    }

    public function get_messages()
    {
        global $DB;

        $messages = array();
        if ( $this->id ) {
            $messages = $DB->get_records_select("lm_feedback_messages", "feedbackid = {$this->id}");
        }
        return $messages;
    }

}