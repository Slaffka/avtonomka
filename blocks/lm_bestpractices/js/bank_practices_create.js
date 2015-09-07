/*
bank_practices_create jQuery модуль для обработки форм
 */
(function( $ ){
    var instances = [];

    function bank_practices_create() {
        var _this = {
            // главные настройки
            settings: {
                debug: true,
            },
            element_id: null,
            // содержит обьекты данного модуля
            obj: {
                $block: {}
            },
            params: {
            },
            // инициализация модуля
            init: function( block, options ) {
                _this.element_id = block;
                _this.obj.$block = $('#' + block);

                _this.settings = $.extend(
                   _this.settings,
                    options
                );

                _this.debug('инициализация блока с ид:' + block);
                _this.debug('актуальные настройки модуля:', _this.settings);
                _this.update_params();

                _this.obj.$block.on(
                    'change keyup',
                    '#practice_name, #practice_goal, #practice_description, #practice_resourcesfinance, ' +
                    '#practice_resourcesother, #practice_from, #practice_to',
                    _this.update_params
                );

                _this.obj.$block.on('click', '.file-uploader.add-file', _this.add_file);
                _this.obj.$block.on('click', '.file-list i.remove', _this.remove_file);
                _this.obj.$block.on('change', '.file-to-upload', _this.file_selected);
                _this.obj.$block.on('click','.lm_bestpractices-block-value', _this.toggle_filter);
                _this.obj.$block.on("remove", _this.unload);
                _this.obj.$block.on('change', '#agree', function() {
                    if ($(this).is(':checked')) {
                        _this.obj.$block.find('.submit').removeClass('disabled');
                    } else {
                        _this.obj.$block.find('.submit').addClass('disabled');
                    }
                });
                _this.obj.$block.on('click', '.submit', function(e) {
                    e.preventDefault();
                    if (!$(this).hasClass('disabled')) {
                        _this.obj.$block.trigger(
                            'bank_practices_create.form-submitted'
                        );
                    }
                });
            },
            toggle_filter: function () {
                $elem = $(this);
                if ($elem.hasClass("selected")) {
                    $elem.removeClass("selected");
                } else {
                    $elem.addClass("selected");
                }
                _this.update_params();
            },
            add_file: function(e) {
                e.preventDefault();
                _this.debug('add file to the list', $(this));
                var target_name = $(this).attr('data-name');
                if (typeof target_name == 'undefined') {
                    return true;
                }
                var target_list = $(this).parent().find('ul.file-list[data-name="' + target_name + '"]');
                if (target_list.length == 0) {
                    target_list = $('<ul class="file-list" data-name="' + target_name + '"></ul>');
                    $(this).before(target_list);
                }
                var max_count = $(this).attr('data-max-count');
                target_list.find("li input[type='file']").each(function() {
                    if (!$(this).hasClass('selected')) {
                        $(this).remove();
                    }
                });
                if (typeof max_count != 'undefined' && target_list.find('li').length >= max_count) {
                    return true;
                }
                var accept = $(this).attr('data-accept');
                if (typeof accept == 'undefined') {
                    accept = '*';
                }
                var new_li = $('<li><input name="' + target_name + '" type="file" class="file-to-upload" accept="' +
                             accept + '"> <span class="filename"></span><i class="icon-cross remove"></i></li>');
                target_list.append(new_li);
                new_li.find("input[type='file']").click();

            },
            remove_file: function(e) {
                e.preventDefault();

                var a = $('a[data-name="' + $(this).closest('li').find("input[type='file']").attr('name') + '"]');
                var ul_id = $(this).closest('ul').attr('id');
                var max_count = a.attr('data-max-count');
                if ($(this).closest('li').remove()) {
                    if (typeof max_count != 'undefined' && $('#' + ul_id).find('li').length <= max_count) {
                        a.removeClass('disabled');
                    }
                }
            },
            file_selected: function () {
                _this.debug('file selected', $(this));
                $(this).addClass('selected');
                var ul = $(this).closest('ul');
                if (ul.is(':hidden')) {
                    ul.show();
                }
                $(this).closest('li').find('.filename').html($(this).val().split('\\').pop());
                var max_count = $('a[data-name="' + $(this).attr('name') + '"]').attr('data-max-count');
                if (typeof max_count != 'undefined' && ul.find('li').length >= max_count) {
                    $('a[data-name="' + $(this).attr('name') + '"]').addClass('disabled');
                }
                _this.update_params();
            },
            update_params: function () {
                var formData = new FormData();
                formData.append('practice_name', $('#practice_name').val());
                formData.append('practice_goal', $('#practice_goal').val());
                formData.append('practice_description', $('#practice_description').val());
                formData.append('practice_resourcesfinance', $('#practice_resourcesfinance').val());
                formData.append('practice_resourcesother', $('#practice_resourcesother').val());
                if ($('#practice_parentid').length > 0) {
                    formData.append('practice_parentid', $('#practice_parentid').val());
                }
                if ($('#practice_comments').length > 0) {
                    formData.append('practice_comments', $('#practice_comments').val());
                }

                formData.append('practice_from', $('#practice_from').val());
                formData.append('practice_to', $('#practice_to').val());
                formData.append('subpage', 'create');

                _this.obj.$block.find('#practice_tt').find('.lm_bestpractices-block-value.selected').each(function() {
                    formData.append('practice_tt[]', $(this).attr('data-value'));
                });

                _this.obj.$block.find('#practice_type').find('.lm_bestpractices-block-value.selected').each(function() {
                    formData.append('practice_type[]', $(this).attr('data-value'));
                });

                _this.obj.$block.find('.file-to-upload.selected').each(function() {
                    formData.append($(this).attr('name') + '[]', $(this)[0].files[0]);
                    console.log($(this)[0].files[0]);
                });

                _this.params = formData;

                _this.params_changed();
            },
            params_changed: function () {
                _this.debug('trigger: bank_practices_create.params-changed');
                _this.obj.$block.trigger(
                    'bank_practices_create.params-changed',
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
            unload: function () {
                instances[_this.element_id] = null;
            }
        };
        return _this;
    }
    // добавляем плагин в фунционал jQuery
    $.fn.bank_practices_create = function( method ) {
        var id = $(this).attr('id');
        if (!instances[id]) {
            instances[id] = new bank_practices_create();
            if ( typeof method === 'object' || ! method ) {
                return instances[id].init( $(this).attr('id'), method );
            }
            return instances[id];
        }
        return instances[id];
    };
})( jQuery );