/*
ajax_form jQuery модуль для обработки форм
 */
(function( $ ){
    var instances = [];

    function ajax_form() {
        var _this = {
            // ид данной формы
            form_id: '',
            // главные настройки формы, переписываются при инициализации
            settings: {
                debug: false,
                submit_path: '#',
                ajax: {
                    async: true,
                    cache: true,
                    contentType: 'application/x-www-form-urlencoded; charset=UTF-8',
                    processData: true,
                }
            },
            // содержит обьекты данного модуля
            obj: {
                $form: {},
            },
            post_params: {
            },
            // статус актуального процесса формы
            process_pending: false,
            // инициализация модуля
            init: function( form, options ) {
                _this.obj.$form = $('#' + form);
                _this.form_id = _this.obj.$form.attr('id');
                _this.settings.submit_path = _this.obj.$form.attr("action");
                _this.settings = $.extend(
                   _this.settings,
                    options
                );
                _this.debug('инициализация формы с ид:' + _this.form_id);
                _this.debug('актуальные настройки модуля:', _this.settings);

                // отключаем отправление формы и запускаем функцию для обработки отправки формы
                _this.obj.$form.on('submit', function(e) {
                    e.preventDefault();
                    _this.submit_form();
                });
                _this.obj.$form.on("remove", _this.unload);
            },
            // посылаем запрос на сервер
            submit_form: function () {
                _this.debug('посылаем запрос на сервер');
                return _this.process_post(
                    this.settings.submit_path,
                    function(data) {
                        _this.debug('пришел результат от сервера');
                        data = _this.parse_result_from_server(data);
                        _this.obj.$form.trigger('ajax-form.response-parsed', data);
                    }
                );
            },
            // обрабатываем реквест
            process_post: function(path, callback) {
                if (_this.process_pending) {
                    _this.debug('запрос уже находится в пути...');
                    return false;
                }
                _this.debug('актуальные параметры для запроса:', _this.post_params);
                _this.process_pending = true;
                _this.obj.$form.trigger('ajax-form.start-submit', data);

                _this.debug('актуальные настройки:', _this.settings);
                $.ajax({
                    type: 'POST',
                    url: path,
                    async: _this.settings.ajax.async,
                    cache: _this.settings.ajax.cache,
                    contentType: _this.settings.ajax.contentType,
                    processData: _this.settings.ajax.processData,
                    data: _this.post_params,
                    beforeSend: function (xhr) {
                        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    },
                    success: function (data) {
                        _this.process_pending = false;
                        callback(data);
                    },
                }).always(function() {
                    _this.process_pending = false;
                    _this.obj.$form.trigger('ajax-form.finish-submit', data);
                });
            },
            // разбираем ответ от сервера
            parse_result_from_server: function(data) {
                _this.debug('разбираем ответ от сервера');
                try {
                    result = {
                        type: 'json',
                        result: jQuery.parseJSON(data)
                    };
                    if (typeof result.result !== 'object') {
                        throw "ответ не json обьект";
                    }
                } catch (e) {
                    result = {
                        type: 'raw',
                        result: data
                    };
                }
                _this.debug('ответ от сервера является типа: ' + result.type);
                return result;
            },
            debug: function(msg, data) {
                if (!_this.settings.debug) {
                    return false;
                }
                console.log(msg);
                if (typeof data !== 'undefined') {
                    console.log(data);
                }
            },
            unload: function () {
                instances[_this.form_id] = null;
            }
        };
        return _this;
    }

    // добавляем плагин в фунционал jQuery
    $.fn.ajax_form = function( method, params ) {
        var id = $(this).attr('id');
        if (!instances[id]) {
            instances[id] = new ajax_form();
            if ( typeof method === 'object' || ! method ) {
                return instances[id].init( $(this).attr('id'), method );
            }
            return instances[id];
        }
        if (typeof method == 'string') {
            switch (method) {
                case 'set_post_params':
                    instances[id].post_params = $.extend(
                        instances[id].post_params,
                        params
                    );
                    instances[id].debug('set_post_params new params:', instances[id].post_params);
                    break;
                case 'set_post_form':
                    instances[id].post_params = params;
                    instances[id].settings.ajax.async = false;
                    instances[id].settings.ajax.cache = false;
                    instances[id].settings.ajax.contentType = false;
                    instances[id].settings.ajax.processData = false;
                    break;
            }
        }
        return instances[id];
    };
})( jQuery );