/*
best_practices_form jQuery модуль для обработки форм
 */
(function( $ ){
    var instances = [];

    function best_practices_form() {
        var _this = {
            // главные настройки
            settings: {
                debug: false,
                default_init: true,
                custom_init: function () {},
            },
            // содержит обьекты данного модуля
            obj: {
                $form: {},
            },
            params: {
            },
            // инициализация модуля
            init: function( form, options ) {
                _this.obj.$form = $('#' + form);
                _this.form_id = _this.obj.$form.attr('id');
                _this.settings = $.extend(
                   _this.settings,
                    options
                );
                _this.debug('инициализация формы с ид:' + _this.form_id);
                _this.debug('актуальные настройки модуля:', _this.settings);

                if (_this.settings.default_init == true) {
                    _this.init_form();
                    _this.init_table_order();
                    _this.init_favorites();
                    _this.init_pagination();
                    _this.init_show_detailpage();
                    _this.init_foreign_create_practice();
                    _this.init_subpmenu();
                }
                _this.settings.custom_init(_this);
            },
            init_form: function () {
                _this.debug('инициализация: init_form');
                _this.obj.$form.on('ajax-form.response-parsed',function(e, data) {
                    if (typeof data.result.view != 'undefined') {
                        _this.obj.$form.find('.form-content').html(data.result.view);
                        if (_this.obj.$form.find('.form-content').find('#practice_detail_page').length > 0) {
                            $('#lm_bestpractices-search').hide();
                        } else {
                            $('#lm_bestpractices-search').show();
                        }
                    }
                });
                _this.obj.$form.ajax_form({
                    debug: _this.settings.debug
                });
            },
            init_table_order: function () {
                _this.debug('инициализация: init_table_order');
                // передаём сортировку в форму и обновляем страничку
                $('body').on("click", ".table_order", function() {
                    _this.obj.$form.ajax_form(
                        'set_post_params',
                        {
                            order_field: [$(this).attr('data-field')],
                            order_direction: [$(this).attr('data-order')],
                            page: 1
                        }
                    );
                    _this.obj.$form.submit();
                });

            },
            init_favorites: function () {
                _this.debug('инициализация: init_favorites');
                // добовляем практику в избранные
                $('body').on("click", ".add_favorites", function() {
                    $(this).addClass('disabled');
                    $.ajax({
                        type: "POST",
                        url: '/blocks/manage/?__ajc=lm_bestpractices::favorite_add',
                        data: 'id=' + $(this).data('id'),
                        success: function(data) {
                            _this.obj.$form.submit();
                        }
                    });
                });
                $(".remove_favorite").popover({
                    html: true
                });
                $("body").on('click', '.remove_favorite_y', function() {
                    $.ajax({
                        type: "POST",
                        url: '/blocks/manage/?__ajc=lm_bestpractices::favorite_remove',
                        data: 'id=' + $(this).data('id'),
                        success: function(data) {
                            $(".remove_favorite").popover('hide');
                            form.submit();
                        }
                    });
                });

                $("body").on('click', '.remove_favorite_n', function() {
                    $(".remove_favorite").popover('hide');
                });

            },
            init_pagination: function () {
                _this.debug('инициализация: init_pagination');
                // передаём клик в пагинаторе и обновляем страноичку
                $('body').on("click", ".pagination a", function(e) {
                    e.preventDefault();
                    _this.obj.$form.ajax_form(
                        'set_post_params',
                        {
                            page: $(this).attr('data-page')
                        }
                    );
                    _this.obj.$form.submit();
                });
            },
            init_show_detailpage: function () {
                _this.debug('инициализация: init_show_detailpage');
                $('body').on("click", ".show_detailpage", function(e) {
                    e.preventDefault();
                    _this.obj.$form.ajax_form(
                        'set_post_params',
                        {
                            detailpage: $(this).data('detailpage'),
                            practiceid: $(this).data('id'),
                            historyid: $(this).data('history-id'),
                            back_detailpage: $(this).data('back-detailpage'),
                        }
                    );
                    _this.obj.$form.submit();
                });

                // идём обратно из страници деталей
                $('body').on("click", ".go_back_from_detailpage", function(e) {
                    e.preventDefault();
                    _this.obj.$form.ajax_form(
                        'set_post_params',
                        {
                            detailpage: $(this).data('detailpage'),
                            practiceid: $(this).data('id'),
                        }
                    );
                    _this.obj.$form.submit();
                });
            },
            init_foreign_create_practice: function () {
                _this.debug('инициализация: init_foreign_create_practice');
                // запускаем показ карточки практики
                 $("body").on('click', '.foreign_create_practice', function() {
                    $(this).addClass('selected');
                    _this.obj.$form.ajax_form(
                        'set_post_params',
                        {
                            subpage: 'foreign_create_practice',
                            practiceid: $(this).data('id'),
                        }
                    );
                    _this.obj.$form.submit();
                });
            },
            init_subpmenu: function () {
                _this.debug('инициализация: init_subpmenu');
                  // передаём сортировку в форму и обновляем страничку
                $('body').on("click", ".lm_bestpractices_submenu .btn", function(e) {
                    e.preventDefault();
                    $(".lm_bestpractices_submenu .btn").removeClass('selected');
                    $(this).addClass('selected');
                    _this.obj.$form.ajax_form(
                        'set_post_params',
                        {
                            detailpage: null,
                            page:1,
                            subpage: $(this).attr('href').substr(1)
                        }
                    );
                    _this.obj.$form.submit();
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
    $.fn.best_practices_form = function( method ) {
        var id = $(this).attr('id');
        if (!instances[id]) {
            instances[id] = new best_practices_form();
            if ( typeof method === 'object' || ! method ) {
                return instances[id].init( $(this).attr('id'), method );
            }
            return instances[id];
        }
        return instances[id];
    };
})( jQuery );