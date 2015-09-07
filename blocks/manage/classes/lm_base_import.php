<?php

class lm_base_import
{
    public static $companylist = NULL;

    protected $guessedcompanies = array();
    protected $guessedcities = array();
    protected $guessedpartners = array();
    public $type = 'importbase';


    /**
     * Возвращает идентификатор торговой точки по ее коду, либо ноль если такой ТТ не найдено!
     *
     * @param $code
     * @return int
     */
    public function guess_tt($code)
    {
        global $DB;
        return (int)$DB->get_field('lm_place', 'id', array('code' => $code));
    }

    /**
     * Возвращает идентификатор компании по ее названию.
     *
     * @param $name
     * @return bool
     */
    public function guess_company($name)
    {
        if (isset($this->guessedcompanies[$name])) {
            return $this->guessedcompanies[$name];
        }

        $this->guessedcompanies[$name] = lm_companies::synonyms_exists($name);
        return $this->guessedcompanies[$name];
    }


    /**
     * Возвращает список компаний с очищенными наименованиями (см. clean_company_name), которые существуют в системе
     *
     * @return null|array
     */
    public function get_exists_companies()
    {
        global $DB;

        if (self::$companylist !== NULL) {
            return self::$companylist;
        }

        if ($companies = $DB->get_records_menu('lm_company', array(), '', 'id, name')) {
            foreach ($companies as $id => $name) {
                self::$companylist[$id] = lm_companies::clean_name($name);
            }
        }

        return self::$companylist;
    }

    /**
     * Возвращает идентификатор партнера по id-компании и id-города
     *
     * @param $companyid
     * @param $cityid
     * @return mixed
     */
    public function guess_partner($companyid, $cityid)
    {
        global $DB;

        if (isset($this->guessedpartners[$cityid][$companyid])) {
            return $this->guessedpartners[$cityid][$companyid];
        }

        $this->guessedpartners[$cityid][$companyid] = $DB->get_field('lm_partner', 'id', array('companyid' => $companyid, 'regionid' => $cityid));

        return $this->guessedpartners[$cityid][$companyid];
    }


    /**
     * Возвращает идентификатор пользователя по его ФИО
     *
     * @param $fullname
     * @return bool
     */
    public function guess_user_by_fullname($fullname)
    {
        global $DB;

        $parts = explode(" ", $fullname);
        $cleanedname = $this->cleanname($fullname);

        if (!empty($parts) && count($parts) > 1) {
            $firstname = $parts[1];
            $lastname = $parts[0];
            $latfirstname = ru2lat($firstname);
            $latlastname = ru2lat($lastname);
        } else {
            return false;
        }

        $sql = "SELECT id FROM {lm_user}
                  WHERE synonyms LIKE '{$cleanedname}' OR synonyms LIKE '{$cleanedname},%'
                        OR synonyms LIKE '%,{$cleanedname},%' OR synonyms LIKE '%,{$cleanedname}'";
        if ($users = $DB->get_records_sql($sql)) {
            if (count($users) > 1) {
                //TODO: Сгенерировать ошибку, т.к. здесь не должно быть найдено более одного совпадения!
                //throw new Exeption('По полному фио найдено более одного пользователя');
            }

            $users = array_pop($users);
            return $users->id;
        }


        $sql = "SELECT u.id, u.firstname, u.lastname, u.city, u.email, lu.synonyms
                 FROM {user} u
                 LEFT JOIN {lm_user} lu ON lu.userid=u.id
                 WHERE (
                        u.firstname LIKE '{$firstname}' OR u.firstname LIKE '{$firstname} %' OR u.firstname LIKE ' {$firstname}'
                        OR u.firstname LIKE '{$latfirstname}' OR u.firstname LIKE '{$latfirstname} %' OR u.firstname LIKE ' {$latfirstname}'
                        )
                        AND
                        (
                        u.lastname LIKE '{$lastname}' OR u.lastname LIKE '{$lastname} ' OR u.lastname LIKE ' {$lastname}'
                        OR u.lastname LIKE '{$latlastname}' OR u.lastname LIKE '{$latlastname} ' OR u.lastname LIKE ' {$latlastname}'
                        ) AND confirmed=1

                 GROUP BY. u.id";

        if (!$users = $DB->get_records_sql($sql)) {
            return false;
        }

        if (count($users) > 1) {
            foreach ($users as $user) {

                if (!strpos($user->email, '@')) {
                    unset($users[$user->id]);
                }
            }
        }

        if (count($users) > 1) {
            return $users;
        } else {
            $users = array_pop($users);
            return $users->id;
        }
    }

    /**
     * TODO: Сделать функцию, которая будет убирать лишние пробелы в firstname и lastname у пользователей в таблице user
     * Она нужна для лучшего распознавания пользователя по ФИО (метод guess_user_by_fullname). Функция будет запускаться через крон!
     */
    public function fix_spaces_at_fullnames()
    {

    }

    protected function cleanname($name){
        $name = str_replace(array(' ', 'ё', 'Ё', 'й', 'Й'), array('', 'е', 'е', 'и', 'и'), $name);
        return mb_strtolower($name, 'utf-8');
    }
}