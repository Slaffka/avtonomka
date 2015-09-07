/*
bank_practices_search_block jQuery модуль для обработки форм
 */
(function( $ ){
    var instances = [];

    function bank_practices_search_block() {
        var _this = {
            // главные настройки
            settings: {
                debug: false,
            },
            // содержит обьекты данного модуля
            obj: {
                $block: {},
            },
            params: {
            },
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
        };
        return _this;
    }

    // добавляем плагин в фунционал jQuery
    $.fn.bank_practices_search_block = function( method ) {
        var id = $(this).attr('id');
        if (!instances[id]) {
            instances[id] = new bank_practices_search_block();
            if ( typeof method === 'object' || ! method ) {
                return instances[id].init( $(this).attr('id'), method );
            }
            return instances[id];
        }
        if (typeof method == 'string') {
            switch (method) {
                case 'get_filter':

        console.log('get_filter called');
        console.log(typeof method);
                    break;
            }
        }
        return instances[id];
    };
})( jQuery );