<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 5/29/2015
 * Time: 8:36 PM
 */

require_once($CFG->dirroot.'/mod/scorm/locallib.php');

class lm_scorm {

    const TABLE = 'scorm_scoes_track';

    /**
     * Преобразует все cmi.interactions.# в массив interactions
     * @param $attempt
     */
    private function _format_interactions(&$attempt) {
        $attempt->interactions = array();
        foreach($attempt as $prop => $value) {
            if (preg_match('#cmi\.interactions\.(\d+)\.(.*)#', $prop, $vars)) {
                $data = json_decode($value);
                $attempt->interactions[(int) $vars[1]][$vars[2]] = is_null($data) ? $value : $data;
                unset($attempt->$prop);
            }
        }
    }

    /**
     * Возвращает форматированный объект попытки прохождения
     * @param $scormid
     * @param $attemptid
     * @param null $userid
     * @return null|object
     */
    public static function get_attempt($scormid, $attemptid, $userid = NULL)
    {
        global $USER, $DB;

        $scormid = (int) $scormid;
        if ($scormid < 1) return NULL;

        $attemptid = (int) $attemptid;
        if ($attemptid < 1) return NULL;

        if (is_null($userid)) $userid = $USER->id;

        $userid = (int) $userid;

        $conditions = array(
            'scormid' => $scormid,
            'attempt' => $attemptid,
            'userid'  => $userid
        );

        $attempt = $DB->get_records(self::TABLE, $conditions, '', 'element, value, timemodified');
        if (!$attempt) return NULL;

        $attempt = scorm_format_interactions($attempt);

        self::_format_interactions($attempt);

        return $attempt;
    }

    /**
     * Возвращает объект последней попытки
     * @param $scormid
     * @param $userid
     * @return null|object
     */
    public static function get_last_attempt($scormid, $userid) {
        $attemptid = scorm_get_last_attempt($scormid, $userid);
        if ($attemptid) return self::get_attempt($scormid, $attemptid, $userid);
        else return NULL;
    }

    /**
     * Возвращает объект последней попытки со статусом completed
     * @param $scormid
     * @param $userid
     * @return null|object
     */
    public static function get_last_completed_attempt($scormid, $userid) {
        $attemptid = scorm_get_last_completed_attempt($scormid, $userid);
        $attempt = self::get_attempt($scormid, $attemptid, $userid);
        if ($attempt && ($attempt->{'cmi.completion_status'} == 'completed'
            || $attempt->{'cmi.completion_status'} == 'passed')
        ){
            return $attempt;
        }
        return NULL;
    }

    /**
     * Переводит продолжительность времени из стандарта скорм в секунды
     * @param $duration
     * @return int
     */
    private static function _format_duration($duration)
    {

        $count = preg_match('/P(([0-9]+)Y)?(([0-9]+)M)?(([0-9]+)D)?T?(([0-9]+)H)?(([0-9]+)M)?(([0-9]+)(\.[0-9]+)?S)?/', $duration, $matches);

        if ($count) {
            $_years = (int)$matches[2];
            $_months = (int)$matches[4];
            $_days = (int)$matches[6];
            $_hours = (int)$matches[8];
            $_minutes = (int)$matches[10];
            $_seconds = (int)$matches[12];
        } else {
            if (strstr($duration, ':')) {
                list($_hours, $_minutes, $_seconds) = explode(':', $duration);
            } else {
                $_hours = 0;
                $_minutes = 0;
                $_seconds = 0;
            }
        }

        // I just ignore years, months and days as it is unlikely that a
        // course would take any longer than 1 hour
        return $_seconds + (($_minutes + ($_hours * 60)) * 60);
    }

    /**
     * Время, проведенное пользователем в скорм (сумма всех попыток)
     * @param $scormid
     * @param $userid
     * @return int|NULL
     */
    public static function get_duration($scormid, $userid)
    {
        global $DB;

        if ((int)$scormid < 1 || (int)$userid < 1) return FALSE;

        $where = array(
            'scormid' => $scormid,
            'userid' => $userid,
            'element' => 'cmi.total_time'
        );

        $duration = NULL;

        //NOTE: summ time of all attempts
        $tracks = $DB->get_records_menu('scorm_scoes_track', $where, '', 'attempt, value');
        foreach ($tracks as $time) {
            $duration += self::_format_duration($time);
        }

        return $duration;
    }

    /**
     * Кол-во ошибок, допущеных пользователем во время прохождения
     * @param $scormid
     * @param $userid
     * @param bool $group
     * @return int|NULL
     */
    public static function get_mistakes($scormid, $userid, $group = FALSE)
    {
        global $DB;

        if ((int)$scormid < 1 || (int)$userid < 1) return FALSE;

        // Возможно стоит переделать на использование self::get_last_completed_attempt,
        // но, по моему, и так нормально, если не лучше
        $sql = "
            SELECT data.value
            FROM
                {scorm_scoes_track} AS status
                LEFT JOIN {scorm_scoes_track} AS data
                    ON data.scormid = status.scormid
                    AND data.userid = status.userid
                    AND data.element LIKE :response

            WHERE
                status.scormid = :scormid
                AND status.userid  = :userid
                AND status.element LIKE :status
                AND status.value = 'completed'

            ORDER BY
                status.attempt DESC,
                data.element DESC

            LIMIT 1
        ";

        $params = array(
            'scormid'  => $scormid,
            'userid'   => $userid,
            'status'   => 'cmi.completion_status',
            'response' => 'cmi.interactions.%.learner_response'
        );

        $mistakes = NULL;

        $data = $DB->get_field_sql($sql, $params);
        if ($data) {
            $data = json_decode($data);
            if ( ! is_null($data)) $mistakes = $group ? $data->mistakes->blocks : $data->mistakes->total;
        }

        return $mistakes;
    }
}
