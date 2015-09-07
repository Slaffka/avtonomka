<?php

defined('MOODLE_INTERNAL') || die;
global $DB;

if ($ADMIN->fulltree) {
    $settings->add(
        new admin_setting_configcheckbox(
            'lm_bestpractices_new_practice_notification_active',
            'Включить оповещения о новых практиках?',
            '',
            0
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'lm_bestpractices_new_practice_notification_email',
            'eMail для оповещения',
            '',
            'bestpractice@cherkizovo.com',
            PARAM_NOTAGS
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'lm_bestpractices_accept_practice_money',
            'Начисление монет при согласование модератором',
            '',
            '100',
            PARAM_NOTAGS
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'lm_bestpractices_top_best_practice_place_1',
            'Первое место "Лучшая практика"',
            '',
            '5000',
            PARAM_NOTAGS
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'lm_bestpractices_top_best_practice_place_2',
            'Второе место "Лучшая практика"',
            '',
            '3000',
            PARAM_NOTAGS
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'lm_bestpractices_top_best_practice_place_3',
            'Третие место "Лучшая практика"',
            '',
            '1000',
            PARAM_NOTAGS
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'lm_bestpractices_top_best_embedded_practice_place_1',
            'Первое место "Лучшая реализация Практик"',
            '',
            '5000',
            PARAM_NOTAGS
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'lm_bestpractices_top_best_embedded_practice_place_2',
            'Второе место "Лучшая реализация Практик"',
            '',
            '3000',
            PARAM_NOTAGS
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'lm_bestpractices_top_best_embedded_practice_place_3',
            'Третие место "Лучшая реализация Практик"',
            '',
            '1000',
            PARAM_NOTAGS
        )
    );

    $settings->add(
        new admin_setting_configtext(
            'lm_bestpractices_top_biggest_respect_place_1',
            'Первое место "Наибольший Respect"',
            '',
            '5000',
            PARAM_NOTAGS
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'lm_bestpractices_top_biggest_respect_place_2',
            'Второе место "Наибольший Respect"',
            '',
            '3000',
            PARAM_NOTAGS
        )
    );
    $settings->add(
        new admin_setting_configtext(
            'lm_bestpractices_top_biggest_respect_place_3',
            'Третие место "Наибольший Respect"',
            '',
            '1000',
            PARAM_NOTAGS
        )
    );

    global $DB;

    $sql = "SELECT id, name FROM {role} WHERE name != ''";
    $res = $DB->get_records_sql($sql);

    foreach ($res as $role) {
        $settings->add(
            new admin_setting_configselect(
                'lm_bestpractices_role_' . $role->id,
                'Роль в модуле для: '. $role->name,
                '',
                0,
                lm_bestpractices_practice_roles::get_role_list()
            )
        );
    }
}
