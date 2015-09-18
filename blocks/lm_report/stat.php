<?php

require_once('../../config.php');
/*
 * Обработчик запросов с сайта
 */

// !!! проверяем пришел ли номер курса, переводим его в int и проверяем дейстительно ли там число
if (isset($_GET['courseid']) && is_int($courseid = intval($_GET['courseid'])) && isset($_GET['time'])) {
    $user = intval($USER->id);
    $date = date('Y-m-d');
    $time = isset($_GET['time']) ? intval($_GET['time']) : 1;
    //$page = isset($_GET['_p']) ? $_GET['_p'] : 'courseplayer';
    $page = "Прохождение курса";
// ищем есть ли запись по этому курсу за сегодня и если нет - создаем
    $res = $DB->get_record('statistics', array('userid' => $user, 'date' => $date, 'subpage' => $page, 'page' => $courseid));
    if ($res->id !== null) {
        // обновляем значение времени в этой записи на указанную величину
        $obj = $DB->get_record('statistics', array('id' => $res->id));
        $obj->time = $res->time + $time;
        $DB->update_record('statistics', $obj);
        header('HTTP/1.x 200 time updated 1');
    } else {
        // если нет - создаем новую строку
        $new = new stdClass();
        $new->userid = $user;
        $new->date = $date;
        $new->page = $courseid;
        $new->subpage = $page;
        $new->time = $time;
        $DB->insert_record('statistics', $new);
        header('HTTP/1.x 200 time created 1');
    }
// !!! проверяем приходят ли параметры модулей курса
} else if (isset($_GET['_p']) && isset($_GET['subpage']) && isset($_GET['time'])) {
    $page = $_GET['_p'];
    $subpage = $_GET['subpage'];
    $time = $_GET['time'];
    $user = intval($USER->id);
    $date = date('Y-m-d');
    switch ($subpage) {
        case 'userverifier':
            $subpage = "Фотографирование";
            break;
        case 'quiz':
            $subpage = 'Финальное тестирование';
    }
    // если page число - вытягиваем по этом id номер курса с БД
    if (intval($page) != 0) {
        $tmp = $DB->get_record('course_modules', array('id' => $page));
        $page = $DB->get_record('course', array('id' => $tmp->course))->fullname;
    }
    // ищем есть ли запись по этому курсу за сегодня и если нет - создаем
    $res = $DB->get_record('statistics', array('userid' => $user, 'date' => $date, 'subpage' => $subpage, 'page' => $page));
    if ($res->id !== null) {
        // обновляем значение времени в этой записи на указанную величину
        $obj = $DB->get_record('statistics', array('id' => $res->id));
        $obj->time = $res->time + $time;
        $DB->update_record('statistics', $obj);
        header('HTTP/1.x 200 time updated 2');
    } else {
        $new = new stdClass();
        $new->userid = $user;
        $new->date = $date;
        $new->page = $page;
        $new->subpage = $subpage;
        $new->time = $time;
        $DB->insert_record('statistics', $new);
        header('HTTP/1.x 200 time created 2');
    }
}
// если нужные параметры не пришли - выдаем код ошибки
else {
    header('HTTP/1.x 404 bad parametr courseId');
}
