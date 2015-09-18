// файл подключен в:
// blocks/manage/classes/renderers/courseplayer_renderer.php строка 264
// blocks/manage/index.php
// mod/userverifier/view.php
// mod/quiz/view.php

var time_sec = 5; // периодичность отправки запросов (мин)
var link = '../../blocks/lm_report/stat.php'            // относительный адрес скрипта обработчика (относительно файла вызова)
var prefix_page = 'lm'; // префикс отслеживаемых страниц
var pattern_param = '_p'; // ключ ссылки отслеживаемых страниц
var subpage = 'subpage'; // имя параметра подраздела

// вытягиваем параметры с url
var search = window.location.search.substr(1);
keys = {};
search.split('&').forEach(function (item) {
    item = item.split('=');
    keys[item[0]] = item[1];
});
// проверяем пришли ли поля с url
if (typeof (keys) !== undefined) {
    // если страница курса и id курса определен - отправляем данные на сервер
    if (keys[pattern_param] == 'courseplayer' && keys['courseid']) { // ищем страницу курсов и id
        var xml = new XMLHttpRequest();
        var time_int = parseInt(time_sec) * 1000;
        var interval = setInterval("send('?_p=courseplayer&courseid=' + keys['courseid'])", time_int);
    } else { // ищем в ссылке id или _p
        if (typeof (keys['id']) !== typeof (undefined) || typeof (keys['_p'] !== typeof (undefined))) {
            // определяем имя файла и его директории           
            var href = location.pathname;
            href = href.split('/');
            var end = href.pop();
            var eend = href.pop();
            if (typeof (keys['id']) !== typeof (undefined) && end == 'view.php' && (eend == 'userverifier' || eend == 'quiz')) {
                // отправляем
                var xml = new XMLHttpRequest();
                var time_int = parseInt(time_sec) * 1000;
                var interval = setInterval("send('?_p=' + keys['id'] + '&subpage=' + eend)", time_int);
            } else {
                // если не курсы - парсим другой параметр урла
                var param = keys[pattern_param].split('_');
                // если префикс параметра соответствует паттерну - парсим дальше
                if (param[0] == prefix_page) {
                    if (typeof (keys[subpage]) !== typeof (undefined)) {
                        var category = keys[subpage];
                    } else {
                        var category = 'index';
                    }
                    // отправляем
                    var xml = new XMLHttpRequest();
                    var time_int = parseInt(time_sec) * 1000;
                    var interval = setInterval("send('?_p=' + document.title + '&subpage=' + category)", time_int);;
                }
            }
        }
    }
}

function send(url) {
    xml.open('GET', link + url + '&time=' + time_sec, true);
    xml.send();
}