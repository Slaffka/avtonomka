<?php

defined('MOODLE_INTERNAL') || die;
global $DB;

if ($ADMIN->fulltree) {
    $choises = array(0=>'Не выбрано...');
    if($roles = $DB->get_records_select_menu('role', '', null, 'shortname ASC', 'id, shortname')){
        foreach($roles as $roleid=>$rolename){
            $choises[$roleid] = $rolename;
        }
    }

    // Использовать матрицу развития?
    $settings->add( new admin_setting_configcheckbox('lm_matrix_enabled', get_string('matrix_enabled', 'block_manage'),
        get_string('config_matrix_enabled', 'block_manage'), 0) );

    // Использовать орг структуру?
    $settings->add( new admin_setting_configcheckbox('lm_org_enabled', get_string('org_enabled', 'block_manage'),
        get_string('config_org_enabled', 'block_manage'), 0) );

    $regions = get_mainregions_menu();
    $settings->add(new admin_setting_configselect('lm_defaultregion', get_string('defaultregion', 'block_manage'),
        get_string('configdefaultregion', 'block_manage'), 9, $regions));

    $settings->add(new admin_setting_configselect('block_manage_studentroleid', get_string('studentroleid', 'block_manage'),
        get_string('configstudentroleid', 'block_manage'), 5, $choises));

    $settings->add(new admin_setting_configselect('block_manage_stafferroleid', 'Роль для сотрудника',
        get_string('configstafferroleid', 'block_manage'), 0, $choises));

    $settings->add(new admin_setting_configselect('block_manage_pamroleid', 'Роль для ПАМа',
        'Из списка пользователей, назначенных на эту роль будет формироваться меню для выбора менеджера по работе с партнерами (ПАМ)', 0, $choises));

    $settings->add(new admin_setting_configselect('block_manage_tmroleid', 'Роль для ТМ',
        'Из списка пользователей, назначенных на эту роль будет формироваться меню для выбора ТМ', 0, $choises));

    $settings->add(new admin_setting_configselect('block_manage_trainerroleid', 'Роль для тренера',
        'Из списка пользователей, назначенных на эту роль будет формироваться меню для выбора тренера', 0, $choises));

    $settings->add(new admin_setting_configselect('block_manage_resproleid', 'Роль для ответственного за партера',
        'Из списка пользователей, назначенных на эту роль будет формироваться меню для выбора ответственного за партнера', 0, $choises));

    $settings->add(new admin_setting_configselect('block_manage_reproleid', 'Роль для контактного лица партнера',
        get_string('configreproleid', 'block_manage'), 0, $choises));
}


