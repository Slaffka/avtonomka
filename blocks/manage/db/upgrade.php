<?php
/**
 * Handles upgrading instances of this block.
 *
 * @param int $oldversion
 * @param object $block
 */
function xmldb_block_manage_upgrade($oldversion, $block) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();


    if($oldversion < 2014082001){
        $sql = "CREATE TABLE {$CFG->prefix}lm_sales(
                    id int(10) NOT NULL auto_increment,
                    trainerid int(10) NOT NULL DEFAULT 0,
                    valsr int(10) NOT NULL DEFAULT 0,
                    valpr int(10) NOT NULL DEFAULT 0,
                    periodstart int(10) NOT NULL DEFAULT 0,
                    periodend int(10) NOT NULL DEFAULT 0,
                    PRIMARY KEY  (id)
               )";

        $DB->execute($sql);
    }

    if($oldversion < 2014090100){
        $table = new xmldb_table('lm_partner');
        $field = new xmldb_field('pamid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'regionid');
        if(!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);

        $field = new xmldb_field('tmid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'pamid');
        if(!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);

        $field = new xmldb_field('trainerid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'tmid');
        if(!$dbman->field_exists($table, $field)) $dbman->add_field($table, $field);
    }

    if($oldversion < 2014090101){
        $sql = "ALTER TABLE {lm_partner} CHANGE userid repid INT( 10 ) NOT NULL DEFAULT  '0'";
        $DB->execute($sql);
    }

    if($oldversion < 2014090300){
        $table = new xmldb_table('lm_place');
        $field = new xmldb_field('code', XMLDB_TYPE_CHAR, '25', null, true, null, '', 'id');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('type', XMLDB_TYPE_CHAR, '10', null, true, null, '', 'code');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('respid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'comment2');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if($oldversion < 2014090302){
        $DB->execute("UPDATE {lm_place} SET type='class'");
    }

    if($oldversion < 2014091300){
        $table = new xmldb_table('lm_program');
        $field = new xmldb_field('name', XMLDB_TYPE_CHAR, '200', null, true, null, '', 'id');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('parent', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'period');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        if(!$groupid = $DB->insert_record('lm_program', array('name'=>'Обучение'))){
            return false;
        }

        $DB->execute("UPDATE {lm_program} SET parent={$groupid}");

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
    }

    if($oldversion < 2014091401){
        if($programs = $DB->get_records_select('lm_program', "courseid != 0", null, '', 'id, courseid, name')){
            foreach($programs as $program) {
                $program->name = $DB->get_field_select('course', 'fullname', "id={$program->courseid}");
                $DB->update_record('lm_program', $program);
            }
        }
    }

    if($oldversion < 2014092400) {
        $sql = "UPDATE {lm_region} SET  name =  'Южный федеральный округ' WHERE  id=3";
        $DB->execute($sql);
    }


    if($oldversion < 2014092401){
        $table = new xmldb_table('lm_region');
        $field = new xmldb_field('translitname', XMLDB_TYPE_CHAR, '255', null, true, null, "", 'name');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
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
    }

    if($oldversion < 2014100200){
        $table = new xmldb_table('lm_region');
        $field = new xmldb_field('synonyms', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('lm_company');
        $field = new xmldb_field('synonyms', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $table = new xmldb_table('lm_partner');
        $field = new xmldb_field('synonyms', XMLDB_TYPE_TEXT, null, null, null, null, null, 'name');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if($oldversion < 2014100900){
        $table = new xmldb_table('lm_place');
        $field = new xmldb_field('postcode', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'cityid');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('rawaddress', XMLDB_TYPE_TEXT, null, null, null, null, null, 'partnername');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if($oldversion < 2014101001){
        $DB->execute("DROP TABLE {lm_sales}");

        $sql = "CREATE TABLE {lm_stat}(
                    id int(10) NOT NULL auto_increment,
                    partnerid int(10) NOT NULL DEFAULT 0,
                    ttid int(10) NOT NULL DEFAULT 0,
                    decade int(1) NOT NULL DEFAULT 0,
                    month int(2) NOT NULL DEFAULT 0,
                    year int(4) NOT NULL DEFAULT 0,
                    plansales int(10) NOT NULL DEFAULT 0,
                    factsales int(10) NOT NULL DEFAULT 0,
                    returns int(10) NOT NULL DEFAULT 0,
                    stock int(10) NOT NULL DEFAULT 0,
                    PRIMARY KEY  (id)
               )";

        $DB->execute($sql);
    }

    if($oldversion < 2014110602){
        if($companies = $DB->get_records('lm_company')){
            foreach($companies as $company){
                if(!$company->name){
                    return false;
                }

                $company->synonyms = preg_replace('/ООО | ООО|ИП | ИП|[-"\'., ()]/', '', $company->name);
                $company->synonyms = str_replace(array('ё', 'Ё', 'й', 'Й'), array('е', 'е', 'и', 'и'), $company->synonyms);
                $company->synonyms = mb_strtolower($company->synonyms, 'utf-8');

                $DB->update_record('lm_company', $company);
            }
        }
    }

    if($oldversion < 2014111000){
        $sql = "CREATE TABLE {lm_user}(
                    id int(10) NOT NULL auto_increment,
                    userid int(10) NOT NULL DEFAULT 0,
                    synonyms text NOT NULL,
                    PRIMARY KEY  (id)
               )";

        $DB->execute($sql);
    }

    if($oldversion < 2014111100){
        $table = new xmldb_table('lm_place');
        $field = new xmldb_field('tmid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'respid');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if($oldversion < 2014111900){
        $table = new xmldb_table('lm_place');
        $field = new xmldb_field('trainerid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'tmid');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('repid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'trainerid');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }


        if($partners = $DB->get_records('lm_partner')){
            foreach($partners as $partner){
                if($tts = $DB->get_records('lm_place', array('type'=>'tt', 'partnerid'=>$partner->id))){
                    foreach($tts as $tt){
                        $dataobj = new StdClass();
                        $dataobj->id = $tt->id;
                        $dataobj->tmid = $tt->tmid ? $tt->tmid : $partner->tmid;
                        $dataobj->trainerid = $tt->trainerid ? $tt->trainerid : $partner->trainerid;
                        $dataobj->respid = $tt->respid ? $tt->respid : $partner->respid;
                        $dataobj->repid = $tt->repid ? $tt->repid : $partner->repid;

                        $DB->update_record('lm_place', $dataobj);
                    }
                }
            }
        }

    }

    if($oldversion < 2014111901){
        $table = new xmldb_table('lm_partner');
        $field = new xmldb_field('tmid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'pamid');
        $dbman->rename_field($table, $field, 'tmid_deprecated');

        $field = new xmldb_field('trainerid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'tmid_deprecated');
        $dbman->rename_field($table, $field, 'trainerid_deprecated');

        $field = new xmldb_field('respid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'trainerid_deprecated');
        $dbman->rename_field($table, $field, 'respid_deprecated');

        $field = new xmldb_field('repid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'respid_deprecated');
        $dbman->rename_field($table, $field, 'repid_deprecated');
    }

    if($oldversion < 2014112400){
        $table = new xmldb_table('lm_partner_staff');
        $field = new xmldb_field('ttid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'id');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if($oldversion < 2014112900){
        $table = new xmldb_table('lm_stat');
        $field = new xmldb_field('period', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'ttid');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        if($items = $DB->get_records("lm_stat")){
            foreach($items as $item){
                $month = strlen($item->month) < 2 ? "0".$item->month: $item->month;
                $item->period = $item->year.$month.$item->decade;
                $DB->update_record('lm_stat', $item);
            }
        }

    }

    if($oldversion < 2014112901){
        $DB->execute("ALTER TABLE {lm_stat} ADD INDEX (  partnerid )");
        $DB->execute("ALTER TABLE {lm_stat} ADD INDEX (  ttid )");

        $DB->execute("ALTER TABLE {lm_place} ADD INDEX (  partnerid )");
        $DB->execute("ALTER TABLE {lm_place} ADD INDEX (  cityid )");
        $DB->execute("ALTER TABLE {lm_place} ADD INDEX (  respid )");
        $DB->execute("ALTER TABLE {lm_place} ADD INDEX (  tmid )");
        $DB->execute("ALTER TABLE {lm_place} ADD INDEX (  trainerid )");
        $DB->execute("ALTER TABLE {lm_place} ADD INDEX (  repid )");
    }

    if($oldversion < 2015010200){
        $sql = "CREATE TABLE {$CFG->prefix}lm_org(
                    id int(10) NOT NULL auto_increment,
                    name varchar(255) DEFAULT NULL,
                    roleid int(10) NOT NULL DEFAULT 0,
                    parent int(10) NOT NULL DEFAULT 0,
                    PRIMARY KEY  (id)
               )";

        $DB->execute($sql);
    }

    if($oldversion < 2015012802){
        $sql = "CREATE TABLE {lm_program_matrix}(
                    id int(10) NOT NULL auto_increment,
                    roleid int(10) NOT NULL DEFAULT 0,
                    courseid int(10) NOT NULL DEFAULT 0,
                    status int(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY  (id)
               )";

        $DB->execute($sql);
    }

    if($oldversion < 2015012900){
        $table = new xmldb_table('lm_program_matrix');
        $field = new xmldb_field('roleid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'id');
        $dbman->rename_field($table, $field, 'posid');
    }

    if($oldversion < 2015020300){
        $table = new xmldb_table('lm_program_matrix');
        $field = new xmldb_field('courseid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'posid');
        $dbman->rename_field($table, $field, 'programid');
    }


    if($oldversion < 2015020600){
        $DB->execute("ALTER TABLE {lm_activity} ADD INDEX (  trainerid )");
        $DB->execute("ALTER TABLE {lm_activity} ADD INDEX (  programid )");
        $DB->execute("ALTER TABLE {lm_activity} ADD INDEX (  placeid )");

        $DB->execute("ALTER TABLE {lm_activity_request} ADD INDEX (  activityid )");
        $DB->execute("ALTER TABLE {lm_activity_request} ADD INDEX (  partnerid )");
        $DB->execute("ALTER TABLE {lm_activity_request} ADD INDEX (  userid )");

        $DB->execute("ALTER TABLE {lm_partner} ADD INDEX (  companyid )");
        $DB->execute("ALTER TABLE {lm_partner} ADD INDEX (  regionid )");
        $DB->execute("ALTER TABLE {lm_partner} ADD INDEX (  pamid )");

        $DB->execute("ALTER TABLE {lm_partner_program} ADD INDEX (  programid )");
        $DB->execute("ALTER TABLE {lm_partner_program} ADD INDEX (  partnerid )");

        $DB->execute("ALTER TABLE {lm_partner_staff} ADD INDEX (  ttid )");
        $DB->execute("ALTER TABLE {lm_partner_staff} ADD INDEX (  partnerid )");
        $DB->execute("ALTER TABLE {lm_partner_staff} ADD INDEX (  userid )");

        $DB->execute("ALTER TABLE {lm_partner_staff_progress} ADD INDEX (  partnerid )");
        $DB->execute("ALTER TABLE {lm_partner_staff_progress} ADD INDEX (  userid )");
        $DB->execute("ALTER TABLE {lm_partner_staff_progress} ADD INDEX (  programid )");

        $DB->execute("ALTER TABLE {lm_program} ADD INDEX (  courseid )");

        $DB->execute("ALTER TABLE {lm_program_matrix} ADD INDEX (  posid )");
        $DB->execute("ALTER TABLE {lm_program_matrix} ADD INDEX (  programid )");

    }

    if($oldversion < 2015021100){
        $table = new xmldb_table('lm_program_matrix');
        $field = new xmldb_field('sequence', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'status');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $sql = "SELECT lpm.*, lp.name
                    FROM mdl_lm_program_matrix lpm
                    JOIN mdl_lm_program lp ON lp.id=lpm.programid
                    ORDER BY posid ASC, status ASC, lp.name ASC";

        if($programs = $DB->get_records_sql($sql)){
            $sequence = 1;
            $prevposid = false;
            $prevstatus = false;
            foreach($programs as $program){
                if($prevstatus !== false && $prevposid !== false){
                    if($prevposid != $program->posid || $prevstatus != $program->status){
                        $sequence = 1;
                    }
                }

                $program->sequence = $sequence;
                $DB->update_record('lm_program_matrix', $program);

                $sequence ++;
                $prevposid = $program->posid;
                $prevstatus = $program->status;
            }
        }
    }

    if($oldversion < 2015021101){
        $sql = "DELETE FROM {lm_program_matrix} WHERE status=0";
        $DB->execute($sql);
    }

    if($oldversion < 2015021300){
        $table = new xmldb_table('lm_org');

        $field = new xmldb_field('use_evolution_stages', XMLDB_TYPE_INTEGER, '1', null, true, null, 0, 'parent');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if($oldversion < 2015021301){
        $table = new xmldb_table('lm_org');
        $field = new xmldb_field('use_evolution_stages', XMLDB_TYPE_INTEGER, '1', null, true, null, 0, 'parent');
        $dbman->rename_field($table, $field, 'evolution_stages_enabled');
    }

    if($oldversion < 2015030500) {
        $DB->execute("RENAME TABLE {lm_org} TO {lm_post}");
    }

    if($oldversion < 2015030501){
        $sql = "CREATE TABLE {lm_org}(
                    id int(10) NOT NULL auto_increment,
                    code int(10) NOT NULL DEFAULT 0,
                    parentcode int(10) NOT NULL DEFAULT 0,
                    staffercode int(10) NOT NULL DEFAULT 0,
                    postcode int(10) NOT NULL DEFAULT 0,
                    userid int(10) NOT NULL DEFAULT 0,
                    cityid int(10) NOT NULL DEFAULT 0,
                    postid int(10) NOT NULL DEFAULT 0,

                    PRIMARY KEY  (id)
               )";

        $DB->execute($sql);
    }

    if($oldversion < 2015030502) {
        $dataobj = new StdClass();
        $dataobj->name = "Созданы автоматически";
        $dataobj->parentid = 0;

        $DB->insert_record('lm_region', $dataobj);
    }

    if( $oldversion < 2015030503 ){
        $DB->execute("ALTER TABLE {lm_org} CHANGE `code` `code` VARCHAR(32) NOT NULL DEFAULT '0'");
        $DB->execute("ALTER TABLE {lm_org} CHANGE `parentcode` `parentcode` VARCHAR(32) NOT NULL DEFAULT '0'");
        $DB->execute("ALTER TABLE {lm_org} CHANGE `staffercode` `staffercode` VARCHAR(32) NOT NULL DEFAULT '0'");
        $DB->execute("ALTER TABLE {lm_org} CHANGE `postcode` `postcode` VARCHAR(32) NOT NULL DEFAULT '0'");
    }

    if($oldversion < 2015030504){
        $table = new xmldb_table('lm_post');

        $field = new xmldb_field('code', XMLDB_TYPE_CHAR, '32', null, true, null, 0, 'id');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if($oldversion < 2015030505){
        $table = new xmldb_table('lm_org');

        $field = new xmldb_field('areaid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'postid');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('parentid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'postcode');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if($oldversion < 2015030800){
        $table = new xmldb_table('lm_partner_staff');
        $field = new xmldb_field('stageid', XMLDB_TYPE_INTEGER, '2', null, true, null, 0, 'type');
        $field->setComment('id этапа развития сотрудника (см.матрицу развития)');

        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if($oldversion < 2015030900){
        $table = new xmldb_table('lm_partner_staff_progress');
        $field = new xmldb_field('stageid', XMLDB_TYPE_INTEGER, '2', null, true, null, 0, 'programid');

        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if($oldversion < 2015030901) {
        $DB->execute("UPDATE {lm_partner_staff_progress} SET stageid=1");
    }

    if ($oldversion < 2015040800) {
        $table = new xmldb_table('lm_org');

        $field = new xmldb_field('trainerid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'userid');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if($oldversion < 2015041000){
        $DB->execute("ALTER TABLE {lm_org} DROP trainerid");
        $DB->execute("ALTER TABLE {lm_org} DROP userid");
        $DB->execute("ALTER TABLE {lm_org} DROP staffercode");
        $DB->execute("RENAME TABLE {lm_org} TO {lm_position}");

        $sql = "CREATE TABLE {lm_position_xref}(
                    id int(10) NOT NULL auto_increment,
                    posid int(10) NOT NULL DEFAULT 0,
                    userid int(10) NOT NULL DEFAULT 0,
                    dateassignment date NOT NULL DEFAULT 0,
                    active int(1) NOT NULL DEFAULT 0,
                    PRIMARY KEY  (id),
                    UNIQUE KEY (posid, userid, dateassignment)
               )";

        $DB->execute($sql);
    }

    if( $oldversion < 2015041300 ){
        $table = new xmldb_table('lm_position_xref');
        $field = new xmldb_field('active', XMLDB_TYPE_INTEGER, '1', null, true, null, 0, 'dateassignment');
        $dbman->rename_field($table, $field, 'archive');
    }

    if( $oldversion < 2015041301 ){
        $table = new xmldb_table('lm_position_xref');
        $field = new xmldb_field('staffercode', XMLDB_TYPE_CHAR, '32', null, true, null, 0, 'id');
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if( $oldversion < 2015041302 ) {
        $DB->execute("ALTER TABLE {lm_user} ADD hiredate DATE NOT NULL AFTER userid");
    }




    if( $oldversion < 2015041400 && $CFG->dbname == 'cherkizovo' ){
        // old => new
        $postids = array("11" => "1", "12" => "2", "8 "=> "3", "7" => "4");

        $alphabet = range('a', 'j');
        $alphabet = array_combine(range(1, count($alphabet)), $alphabet);

        $codes = array();
        foreach($postids as $oldid=>$newid) {
            $oldid = (string)$oldid;
            $newid = (string)$newid;

            $oldpostcode = "r";
            for ($n = 0; $n <= strlen($oldid); $n++) {
                $oldpostcode .= $alphabet[$oldid{$n}];
            }

            $newpostcode = "r";
            for ($n = 0; $n <= strlen($newid); $n++) {
                $newpostcode .= $alphabet[$newid{$n}];
            }

            $codes[$oldpostcode] = $newpostcode;

        }

        $select = array();
        foreach($codes as $oldcode=>$newcode) {
            $select[] = "pagetypepattern LIKE '%-{$oldcode}'";
        }
        $select = implode(" OR ", $select);

        if( $instances = $DB->get_records_select('block_instances', $select) ){
            foreach($instances as $instance){
                preg_match('|-(r[a-j]*)$|', $instance->pagetypepattern, $matches);
                if($matches){
                    $code = array_pop($matches);
                    if(isset($codes[$code])){
                        $instance->pagetypepattern = str_replace("-{$code}", "-{$codes[$code]}", $instance->pagetypepattern);
                    }
                }
            }

            foreach($instances as $instance){
                $DB->update_record('block_instances', $instance);
            }
        }
    }


    if($oldversion < 2015041600){

        $sql = "CREATE TABLE {lm_distrib} (
                    id int(10) NOT NULL auto_increment,
                    code varchar(32) NOT NULL,
                    name varchar(255) NOT NULL,
                    PRIMARY KEY  (id),
                    UNIQUE KEY `code` (`code`)
               )";
        $DB->execute($sql);

        $sql = "CREATE TABLE {lm_segment} (
                    id int(10) NOT NULL auto_increment,
                    code varchar(32) NOT NULL,
                    name varchar(255) NOT NULL,
                    PRIMARY KEY  (id),
                    UNIQUE KEY `code` (`code`)
               )";
        $DB->execute($sql);

        $table = new xmldb_table('lm_position');

        $field = new xmldb_field('distribid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0);
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }

        $field = new xmldb_field('segmentid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0);
        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }


    }

     if($oldversion < 2015041601){
         $table = new xmldb_table('lm_position');

         $field = new xmldb_field('parentfid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'parentid');
         if(!$dbman->field_exists($table, $field)){
             $dbman->add_field($table, $field);
         }
     }


    if($oldversion < 2015050800){
        $table = new xmldb_table('lm_program_matrix');

        $field = new xmldb_field('posid', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'id');
        $dbman->rename_field($table, $field, 'postid');

        $field = new xmldb_field('status', XMLDB_TYPE_INTEGER, '1', null, true, null, 0, 'programid');
        $dbman->rename_field($table, $field, 'stage');
    }

    if($oldversion < 2015051501){
        $table = new xmldb_table('lm_partner_staff_progress');
        $field = new xmldb_field('mistakes', XMLDB_TYPE_INTEGER, '4', null, true, null, 0, 'progress');

        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if($oldversion < 2015052500){
        $table = new xmldb_table('lm_partner_staff_progress');
        $field = new xmldb_field('duration', XMLDB_TYPE_INTEGER, '10', null, true, null, 0, 'mistakes');

        if(!$dbman->field_exists($table, $field)){
            $dbman->add_field($table, $field);
        }
    }

    if ( $oldversion < 2015052501 )
    {

        $sql = "
            UPDATE
              `mdl_lm_position_xref` AS `t1`
            LEFT JOIN
              (SELECT `t2`.`posid`, max(`t2`.`dateassignment`) AS `max` FROM  `mdl_lm_position_xref` AS `t2` GROUP BY `t2`.`posid`) as `t3` ON `t3`.`posid` = `t1`.`posid`
            SET
              `t1`.`archive` = `t1`.`dateassignment` < `t3`.`max`";
        $DB->execute($sql);

       /* $sql = "SELECT id, posid FROM {lm_position_xref} ORDER BY posid ASC, dateassignment ASC";
        $positions = $DB->get_records_sql($sql);
        if ( !empty($positions ) ) {
            $oldposition = 0;
            foreach ( $positions as $position ) {
                if ( $oldposition == $position->posid ) {
                    $dbdata = new StdClass();
                    $dbdata->id = $oldposition;
                    $dbdata->archive = 1;
                    $DB->update_record("lm_position_xref", $dbdata);
                }
                $oldposition = $position->posid;
            }
        }*/


    }

    if($oldversion < 2015060200){
        $sql = "ALTER TABLE {lm_partner_staff_progress}
                CHANGE `progress` `progress` FLOAT(5,2) NULL DEFAULT NULL,
                CHANGE `mistakes` `mistakes` SMALLINT(4) NULL DEFAULT NULL,
                CHANGE `duration` `duration` INT(11) NULL DEFAULT NULL";

        $DB->execute($sql);
    }

    if($oldversion < 2015061200){
        $DB->execute("UPDATE {config} SET `value` = 'tibibase' WHERE name LIKE 'theme'");
        $DB->delete_records('config_plugins', array('plugin'=>'block_lm_coins'));
        $DB->delete_records('config_plugins', array('plugin'=>'theme_chmpz'));
    }

    if($oldversion < 2015061401){
        $DB->execute("UPDATE {block_instances} SET `blockname` = 'lm_personal' WHERE `blockname` LIKE 'lm_profile_mini'");
    }

    if( $oldversion < 2015061701 ){
        $DB->execute("UPDATE {scorm_scoes} SET launch='index_lms.html' WHERE launch LIKE 'launcher.html'");
    }

    if( $oldversion < 2015070700 ){
        $sql = "CREATE TABLE {lm_outlet} (
                  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NOT NULL DEFAULT '',
                  PRIMARY KEY (`id`))
                ENGINE = InnoDB";
        $DB->execute($sql);
        $sql = "CREATE TABLE {lm_nomenclature} (
                  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
                  `name` VARCHAR(255) NOT NULL,
                  PRIMARY KEY (`id`))
                ENGINE = InnoDB";
        $DB->execute($sql);
    }

    if( $oldversion < 2015070900 ){
        $DB->execute("DROP TABLE {lm_outlet}");
    }

    if( $oldversion < 2015071000 ) {
        $sql = 'ALTER TABLE {lm_nomenclature} ADD `code` VARCHAR(32) NOT NULL AFTER `id`, ADD UNIQUE (`code`)';
        $DB->execute($sql);
    }


    if( $oldversion < 2015071300 ){
        $sql = "CREATE TABLE {lm_area} (
                  `id` int(10) NOT NULL auto_increment,
                  `name` VARCHAR(255) NOT NULL,
                  PRIMARY KEY (`id`))
                ENGINE = InnoDB";
        $DB->execute($sql);

        $sql = "CREATE TABLE {lm_trade_outlets} (
                  `id` int(10) NOT NULL auto_increment,
                  `areaid` int(10) NOT NULL DEFAULT '0',
                  `name` VARCHAR(255) NOT NULL,
                   PRIMARY KEY (`id`))
                ENGINE = InnoDB";
        $DB->execute($sql);
    }

    if ($oldversion < 2015071302) {
        $sql = 'ALTER TABLE {lm_region} ADD code VARCHAR(32) NOT NULL AFTER id, ADD INDEX (code)';
        $DB->execute($sql);
    }

    if ($oldversion < 2015071305) {
        $sql = 'ALTER TABLE {lm_area} ADD code VARCHAR(32) NOT NULL AFTER id, ADD INDEX (code)';
        $DB->execute($sql);

        $sql = 'ALTER TABLE {lm_trade_outlets} ADD code VARCHAR(32) NOT NULL AFTER id, ADD INDEX (code)';
        $DB->execute($sql);
    }

    if ($oldversion < 2015071306) {
        $sql = 'ALTER TABLE {lm_area} DROP INDEX code, ADD INDEX code (code)';
        $DB->execute($sql);

        $sql = 'ALTER TABLE {lm_trade_outlets} DROP INDEX code, ADD INDEX code (code)';
        $DB->execute($sql);
    }

    if( $oldversion < 2015071500 ){
        $sql = 'ALTER TABLE {lm_position} ADD `divisionname` VARCHAR(255) AFTER `segmentid`, ADD `partnerid` bigint(10) AFTER `parentcode`';
        $DB->execute($sql);

        $DB->execute("UPDATE {lm_position} SET partnerid = 1");
    }

    if( $oldversion < 2015072800 ){
        $sql = 'ALTER TABLE {lm_trade_outlets} ADD address VARCHAR(255) NOT NULL DEFAULT "" AFTER name';
        $DB->execute($sql);
    }

    if( $oldversion < 2015072801 ){
        if($instances = $DB->get_records_select('block_instances', "pagetypepattern LIKE 'lm-%'", array(), '', 'id, pagetypepattern')){
            foreach($instances as $instance){
                $instance->pagetypepattern = str_replace('lm-', 'lm_', $instance->pagetypepattern);
                $DB->update_record('block_instances', $instance);
            }
        }
    }

    if( $oldversion < 2015072802 ){
        if($instances = $DB->get_records_select('block_instances', "pagetypepattern LIKE 'lm_profile%'", array(), '', 'id, pagetypepattern')){
            foreach($instances as $instance){
                $instance->pagetypepattern = str_replace('lm_profile', 'manage_profile', $instance->pagetypepattern);
                $DB->update_record('block_instances', $instance);
            }
        }
    }

    if( $oldversion < 2015072803 ){
        if($instances = $DB->get_records_select('block_instances', "pagetypepattern LIKE 'lm_manage_profile%'", array(), '', 'id, pagetypepattern')){
            foreach($instances as $instance){
                $instance->pagetypepattern = str_replace('lm_manage_profile', 'manage_profile', $instance->pagetypepattern);
                $DB->update_record('block_instances', $instance);
            }
        }
    }

    if( $oldversion < 2015073100 ){
        $sql = 'ALTER TABLE {lm_user} CHANGE hiredate hiredate DATE NULL DEFAULT NULL';
        $DB->execute($sql);

        $sql = 'UPDATE {lm_user} SET hiredate = NULL WHERE hiredate = "1970-01-01"';
        $DB->execute($sql);
    }

    if( $oldversion < 2015080600){
        $sql = "SELECT uvf.id, uv.course, uvf.userid, lp.parentid as chiefposid
                      FROM {userverifier} uv
                      JOIN {userverifier_photo} uvf ON uv.id=uvf.userverifier
                      JOIN {lm_position_xref} lpx ON lpx.userid=uvf.userid AND lpx.archive=0
                      JOIN {lm_position} lp ON lp.id=lpx.posid
                      GROUP BY userid ASC, course ASC";

        if( $items = $DB->get_records_sql($sql) ){
            foreach($items as $item) {
                $item->chiefid = $DB->get_field('lm_position_xref', 'userid', array('posid'=>$item->chiefposid, 'archive'=>0));
                if( $item->chiefid ) {
                    $data = (object)array('userid' => $item->userid, 'courseid' => $item->course);
                    lm_notification::add('manage:verifyphoto', false, $item->chiefid, $data);
                }
            }
        }
    }

    return true;
}