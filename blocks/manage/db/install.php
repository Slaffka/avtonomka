<?php

function xmldb_block_manage_install() {
    global $DB;

    // В XMLDB нет типа date, поэтому в схеме БД установлено datetime. Изменим тип данных на нужный
    $DB->execute("ALTER TABLE {lm_user} CHANGE hiredate hiredate DATE NOT NULL");
    $DB->execute("ALTER TABLE {lm_position_xref} CHANGE dateassignment dateassignment DATE NOT NULL");


    $regions = array();

    $regions[1] = (object) array("name" => "Центральный федеральный округ", "parentid" => 0);
    $regions[2] = (object) array("name" => "Северо-западный федеральный округ", "parentid" => 0);
    $regions[3] = (object) array("name" => "Южный федеральный округ", "parentid" => 0);
    $regions[4] = (object) array("name" => "Северо-кавказский федеральный округ", "parentid" => 0);
    $regions[5] = (object) array("name" => "Приволжский федеральный округ", "parentid" => 0);
    $regions[6] = (object) array("name" => "Уральский федеральный округ", "parentid" => 0);
    $regions[7] = (object) array("name" => "Сибирский федеральный округ", "parentid" => 0);
    $regions[8] = (object) array("name" => "Дальневосточный федеральный округ", "parentid" => 0);
    $regions[9] = (object) array("name" => "Созданы автоматически", "parentid" => 0);


    $regions[] = (object) array("name" => "Москва", "parentid" => 1);
    $regions[] = (object) array("name" => "Брянск", "parentid" => 1);
    $regions[] = (object) array("name" => "Владимир", "parentid" => 1);
    $regions[] = (object) array("name" => "Кострома", "parentid" => 1);
    $regions[] = (object) array("name" => "Набережные Челны", "parentid" => 1);
    $regions[] = (object) array("name" => "Обнинск", "parentid" => 1);
    $regions[] = (object) array("name" => "Тула", "parentid" => 1);

    $regions[] = (object) array("name" => "Санкт-Петербург", "parentid" => 2);

    $regions[] = (object) array("name" => "Астрахань", "parentid" => 3);
    $regions[] = (object) array("name" => "Краснодар", "parentid" => 3);
    $regions[] = (object) array("name" => "Сочи", "parentid" => 3);

    $regions[] = (object) array("name" => "Йошкар-Ола", "parentid" => 5);
    $regions[] = (object) array("name" => "Казань", "parentid" => 5);
    $regions[] = (object) array("name" => "Киров", "parentid" => 5);
    $regions[] = (object) array("name" => "Оренбург", "parentid" => 5);
    $regions[] = (object) array("name" => "Пенза", "parentid" => 5);
    $regions[] = (object) array("name" => "Салават", "parentid" => 5);
    $regions[] = (object) array("name" => "Самара", "parentid" => 5);
    $regions[] = (object) array("name" => "Стерлитамак", "parentid" => 5);
    $regions[] = (object) array("name" => "Ульяновск", "parentid" => 5);
    $regions[] = (object) array("name" => "Уфа", "parentid" => 5);
    $regions[] = (object) array("name" => "Чебоксары", "parentid" => 5);

    $regions[] = (object) array("name" => "Новосибирск", "parentid" => 7);
    $regions[] = (object) array("name" => "Томск", "parentid" => 7);

    $regions[] = (object) array("name" => "Владивосток", "parentid" => 8);
    $regions[] = (object) array("name" => "Хабаровск", "parentid" => 8);


    if (is_array($regions) AND count($regions) > 0) {
        foreach ($regions as $entry) {
            try {
                $DB->insert_record("lm_region", $entry);
            } catch (Exception $e) {
                echo 'Error: ' .  $e->getMessage() . "\n";
            }
        }
    }


    function ru2lat($string){
        $tr = array(
            "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D","Е"=>"E", "Ё"=> "YO", "Ж"=>"ZH","З"=>"Z","И"=>"I","Й"=>"Y","К"=>"K","Л"=>"L","М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R","С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"Kh","Ц"=>"Ts","Ч"=>"Ch","Ш"=>"Sh","Щ"=>"Sch","Ъ"=>"","Ы"=>"Y","Ь"=>"", "Э"=>"E","Ю"=>"Yu","Я"=>"Ya",
            "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d","е"=>"e","ё"=> "yo","ж"=>"zh","з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l","м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r","с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"kh","ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"", "ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
        );

        return strtr($string, $tr);
    }

    if($cities = $DB->get_records_select('lm_region', 'parentid != 0')){
        foreach($cities as $city){
            $city->translitname = ru2lat($city->name);
            $DB->update_record('lm_region', $city);
        }
    }




    if(!$groupid = $DB->insert_record('lm_program', array('name'=>'Методическая работа'))){
        return false;
    }
    $DB->insert_record('lm_program', array('name'=>'Создание презентации', 'parent'=>$groupid));
    $DB->insert_record('lm_program', array('name'=>'Создание раздаточного материала', 'parent'=>$groupid));
    $DB->insert_record('lm_program', array('name'=>'Создание раздаточного материала для корпоративных партнеров', 'parent'=>$groupid));
    $DB->insert_record('lm_program', array('name'=>'Адаптация презентации', 'parent'=>$groupid));
    $DB->insert_record('lm_program', array('name'=>'Адаптация презентации для корпоративных партнеров', 'parent'=>$groupid));
    $DB->insert_record('lm_program', array('name'=>'Аадаптация раздаточного материала под конкретного партнера', 'parent'=>$groupid));
    $DB->insert_record('lm_program', array('name'=>'Адаптация презентации для партнера под конкретного партнера', 'parent'=>$groupid));


    if(!$groupid = $DB->insert_record('lm_program', array('name'=>'Встречи по развитию'))){
        return false;
    }
    $DB->insert_record('lm_program', array('name'=>'Встречи на ротацию на позицию', 'parent'=>$groupid));
    $DB->insert_record('lm_program', array('name'=>'Встречи на ротацию в тренерский корпус', 'parent'=>$groupid));


    if(!$groupid = $DB->insert_record('lm_program', array('name'=>'Развитие на раб. месте'))){
        return false;
    }
    $DB->insert_record('lm_program', array('name'=>'Коуч-сессии', 'parent'=>$groupid));
    $DB->insert_record('lm_program', array('name'=>'Фасилитационные сессии', 'parent'=>$groupid));


    if(!$groupid = $DB->insert_record('lm_program', array('name'=>'Отчетность'))){
        return false;
    }
    $DB->insert_record('lm_program', array('name'=>'Отчетность по сотрудникам собственной розницы', 'parent'=>$groupid));
    $DB->insert_record('lm_program', array('name'=>'Аналитика результатов по собственной розницы', 'parent'=>$groupid));
    $DB->insert_record('lm_program', array('name'=>'Отчетность по партнерской рознице', 'parent'=>$groupid));
    $DB->insert_record('lm_program', array('name'=>'Аналитика результатов по партнерской рознице', 'parent'=>$groupid));


    if(!$groupid = $DB->insert_record('lm_program', array('name'=>'Планирование'))){
        return false;
    }
    $DB->insert_record('lm_program', array('name'=>'Планрование обучения по собственной розницы', 'parent'=>$groupid));
    $DB->insert_record('lm_program', array('name'=>'Планрование обучения по партнерской рознице', 'parent'=>$groupid));

    $DB->execute('ALTER TABLE {lm_area} ADD INDEX (code)');
    $DB->execute('ALTER TABLE {lm_trade_outlets} ADD INDEX (code)');

}