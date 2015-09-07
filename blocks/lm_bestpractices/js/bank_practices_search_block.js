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
                $selected_filter: {},
            },
            params: {
            },
            // инициализация модуля
            init: function( block, options ) {
                _this.obj.$block = $('#' + block);
                _this.obj.$selected_filter = _this.obj.$block.find('.lm_bestpractices-selected-filters');
                _this.obj.$filters = _this.obj.$block.find(".lm_bestpractices-filters");

                _this.obj.$search_term = _this.obj.$block.find('#search_term');
                _this.obj.$search_profit_from = _this.obj.$block.find('#search_profit_from');
                _this.obj.$search_profit_to = _this.obj.$block.find('#search_profit_to');
                _this.obj.$search_data_from = _this.obj.$block.find('#search_data_from');
                _this.obj.$search_data_do = _this.obj.$block.find('#search_data_do');

                _this.obj.$block.on(
                    'change keyup',
                    '#search_term, #search_profit_from, #search_profit_to, #search_data_from, #search_data_do',
                    _this.update_params
                );


                _this.settings = $.extend(
                   _this.settings,
                    options
                );
                _this.debug('инициализация блока с ид:' + block);
                _this.debug('актуальные настройки модуля:', _this.settings);
                _this.update_params();
                _this.obj.$block.find(".lm_bestpractices-search-detail-btn").on("click", function() {
                    if (_this.obj.$filters.is(":hidden")) {
                        _this.obj.$filters.show();
                    } else {
                        _this.obj.$filters.hide();
                    }
                });
                _this.obj.$block.on('click','.lm_bestpractices-filter-value', function() {
                    _this.toggle_filter($(this));
                })
                _this.obj.$selected_filter.on('click', 'span.btn i', function () {
                    var $elem = $(this).parent()
                    var filter = _this.obj.$block.find('.lm_bestpractices-filter[data-filter="' + $elem.attr('data-filter') + '"]');
                    _this.toggle_filter(
                        filter.find('[data-value="' + $elem.attr('data-value') + '"]')
                    );
                });
            },
            toggle_filter: function ($elem) {
                //достаём параметры
                var filter = $elem.closest('.lm_bestpractices-filter').attr('data-filter');
                var value = $elem.attr('data-value');
                var name = $elem.html();

                if ($elem.hasClass("selected")) {
                    $elem.removeClass("selected");
                    _this.obj.$selected_filter.find("[data-value='" + value + "'][data-filter='" + filter + "']").remove();
                } else {
                    $elem.addClass("selected");
                    _this.obj.$selected_filter.append(
                        '<span class="btn" data-value="' + value +
                        '" data-filter="' + filter + '">' + name +
                        '<i class="icon-cross"></i>'
                    );
                }
                _this.update_params();
            },
            update_params: function () {
                params = {
                    search_term: _this.obj.$search_term.val(),
                    search_profit_from: _this.obj.$search_profit_from.val(),
                    search_profit_to: _this.obj.$search_profit_to.val(),
                    search_data_from: _this.obj.$search_data_from.val(),
                    search_data_do: _this.obj.$search_data_do.val(),
                    type: [],
                    position: [],
                    area: [],
                }
                _this.obj.$selected_filter.find('span.btn').each(function(key, value) {
                    $elem = $(value);
                    params[$elem.attr('data-filter')].push($elem.attr('data-value'));
                });
                _this.params = $.extend(
                   _this.params,
                    { filter: params }
                );
                _this.params_changed();
            },
            params_changed: function () {
                _this.obj.$block.trigger(
                    'bank_practices_search_block.params-changed',
                    _this.params
                );
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