<?php

defined('MOODLE_INTERNAL') || die;
global $DB;

if ($ADMIN->fulltree) {

    // подготовка значений для длительности
    $options_mins = ['-' => 'Не выбрано...'];
    for ($i=0; $i <= 60; $i+=5) {
        $time = ($i < 10 ? '0' : '') . $i;
        $options_mins[$time] = $time;
    }

    // настройка длительности первого этапа
    $settings->add(
        new admin_setting_configselect(
            'block_lm_goalsetting_deadline_1',
            'Первый этап (статус на сегодня)',
            '',
            '15',
            $options_mins
        )
    );

    // настройка длительности второго этапа
    $settings->add(
        new admin_setting_configselect(
            'block_lm_goalsetting_deadline_2',
            'Второй этап (согласование сегодняшних целей)',
            '',
            '30',
            $options_mins
        )
    );
}
