var indicator = '<img src="http://'+window.location.host+'/pix/i/loading_small.gif" />';
var indicatorbig = '<img src="http://'+window.location.host+'/pix/i/loading.gif" />';

$(document).ready(function() {
    $(".menurole").change(function () {
        url = $(this).data("redirecturl");
        url = url.replace("role=__", "role="+$(this).val());
        window.location = url;
    });

    $('.icon-bell').notification({
        url: '/blocks/manage/?__ajc=lm_notifications::get_list',
        placement: 'bottom'
    });

    $("header").on("click", ".notification_verifyphoto", function(e){
        e.preventDefault();

        var id = $(this).data("id"),
            courseid = $(this).data("courseid"),
            userid = $(this).data("userid"),
            modal = $("<div>").addClass("modal modal-verifyphoto hide fade").data("userid", userid),
            modalbody = $("<div>").addClass("modal-body"),
            closebtn = $('<button type="button" class="close" data-dismiss="modal" aria-hidden="true">закрыть</button>'),
            header = $('<div>').addClass("modal-header").html(closebtn);

        modal.append(header).append( modalbody.html(indicatorbig)).modal();

        $('.modal-backdrop').on("click", function() {
            modal.modal('hide');
        });

        $.ajax({
            url: '/blocks/manage/?__ajc=mycourses::modal_verifyphoto_content',
            data: {courseid:courseid, userid:userid},
            success: function(a) {
                a = getJSON(a);
                if(a.error) a.html = a.error;
                modalbody.html(a.html);
            }
        });
    });

    $("body").on("click", ".btn-verifyphoto", function(e){
        e.preventDefault();

        var btn = $(this),
            action = btn.data("action"),
            userid = $(this).parents(".modal").data("userid");

        btn.button("loading");
        if( action ) {
            $.ajax({
                type: "POST",
                url: "/blocks/manage/?__ajc=mycourses::verifyphotos",
                data: {action: action, userid:userid},
                success: function (a) {
                    btn.button("reset");
                    $(".modal").modal("hide");
                }
            });
        }else{
            $(".modal").modal("hide");
        }
    });

});


(function( $ ) {
    $("body").on("click", ".lm-section-actions button", function(){
        var btn = $(this),
            header = btn.parents(".lm-section-header"),
            panel = header.find("#"+btn.data("panel"));

        if(panel.length){
            var act = "hide";
            if(panel.hasClass("hide")) act = "show";
            header.find(".lm-section-actions button").removeClass("active");
            header.find(".lm-section-panel").addClass("hide");
            if(act == "show") {
                panel.removeClass("hide");
                btn.addClass("active");
            }
        }
    });
}) (jQuery);


// Плагин расширяющий bootstrap modal для использования вместо селектбоксов
(function( $ ) {
    var Pickerlist = function (element, options) {
        this.$element    = $(element);
        this.options     = options,
            this.content = $('<div class="mp-content"></div>'),
            this.header = $('<div class="mp-header"></div>'),
            this.search = $('<input class="search-input" type="text" name="q" value="" placeholder="Введите для поиска" />'),
            this.sectionsbox = $('<ul class="nav nav-pills mp-sections"></ul>');
    };

    Pickerlist.DEFAULTS = {
        modal: "",
        sections: {},
        pickerlist: "",
        activesection: "",
        customdata: {}
    };

    Pickerlist.prototype.init = function(){

        var options = this.options;
        if (!this.options.modal) {
            n = 0;
            this.options.modal = "picker-" + n;
            var modaltmp = $("#" + this.options.modal);
            while (modaltmp.length) {
                n++;
                this.options.modal = "picker-" + n;
                modaltmp = $("#" + this.options.modal);
            }

            $('<div class="picker hide" aria-hidden="false"><div class="picker__holder">' +
                '<div id="' + this.options.modal + '" class="modal" tabindex="-1" role="dialog" aria-labelledby="" aria-hidden="true">' +
                '</div></div></div>').appendTo("body");
        }

        // WTF? target = $trigger = this.$element = element
        // почему не использовать просто this.$element ведь все это одно и то же?
        var trigger = this.$element,
            target = $(trigger.selector),
            modal = $("#" + this.options.modal),
            picker = modal.parents(".picker"),
            currentsection = this.options.activesection,
            mp = this;

        this.content.appendTo(modal);
        this.header.prependTo(modal);
        this.search.prependTo(this.header);



        if (!$.isEmptyObject(this.options.sections)) {
            n = 1;
            $.each( this.options.sections, $.proxy(function (code, name) {
                var item = $('<li><a href="#">' + name + '</a></li>').attr("data-section", code);
                if (!this.options.activesection && n == 1 || this.options.activesection == code) {
                    item.addClass("active");
                }

                item.appendTo(this.sectionsbox);
                n++;
            }, this) );
            this.sectionsbox.prependTo(this.header);
        }

        modal.on("show.bs.modal", function () {
            if (typeof options.onshow == "function") options.onshow.call(modal, target);

            picker.removeClass("hide").addClass("picker--opened");

            if (typeof options.pickerlist != "undefined") {
                mp.rebuild_list();
            }
        }).on("shown.bs.modal", function () {
            $("html").css("overflow", "hidden");
            $(".modal-backdrop").css("background-color", "transparent");

            $("body").on("click.pickermodal", ".picker__list-item", function () {
                var id = $(this).data("pick");
                if (typeof options.onpick == "function") options.onpick.call(modal, target, id);
                modal.modal('hide');
            }).on('click.pickermodal', function (event) {
                if ($(event.target).closest(modal).length) return;
                modal.modal('hide');
                event.stopPropagation();
            });
        }).on("hidden.bs.modal", function () {
            picker.addClass("hide").removeClass("picker--opened");
            $("html").css("overflow", "visible");
            $("body").off("click.pickermodal");
        }).on("keypress", "."+this.search.attr("class"), function (e) {
            var q = mp.search.val() + String.fromCharCode(e.which);
            mp.rebuild_list( q );
        }).on("keyup", "."+this.search.attr("class"), function (e) {
            // если нажали backspace или enter, перестраиваем список
            if (e.which == 8 || e.which == 13) {
                mp.rebuild_list( mp.search.val() );
            }
        }).on("click", ".mp-sections li", function () {
            currentsection = $(this).data("section");
            $(".mp-sections li").removeClass("active");
            $(this).addClass("active");
            mp.rebuild_list();

            return false;
        });

        trigger.on("click", function () {
            target = $(this);
            modal.modal("show");
            return false;
        });

        return modal;
    };

    Pickerlist.prototype.rebuild_list = function (q) {
        this.content.html(indicatorbig);
        if (typeof q == "undefined") q = "";

        var data = {};
        data.q = q;
        data.section = this.sectionsbox.find("li.active").data("section");
        var customdata = {},
            target = $(this.$element.selector),
            content = this.content;


        if (typeof this.options.customdata == "function") {
            customdata = this.options.customdata(target);
        }

        $.each(customdata, function (k, v) {
            data[k] = v;
        });

        if ( this.$element.attr('href') ) {
            url = this.$element.attr('href');
            paramdata = this.$element.data();
            $.each(paramdata, function (k, v) {
                data[k] = v;
            });
        } else {
            url = this.options.pickerlist;
        }

        $.ajax({
            type: "POST",
            url: url,
            data: data,
            success: function (a) {
                a = getJSON(a);
                if (typeof a.data == "object") {
                    html = '<ul class="picker__list" role="listbox">';
                    $.each(a.data, function () {
                        html += '<li class="picker__list-item" data-pick="' + this.id + '" role="option">'
                            + this.html + '</li>';
                    });
                    html += '</ul>';

                    content.html(html);
                }
            }
        });
    };

    Pickerlist.prototype.update_customdata = function(options){
        this.options = options;
    };

    $.fn.modalpicker = function (option) {

        var options = $.extend({}, Pickerlist.DEFAULTS, typeof option == 'object' && option),
            action  = typeof option == 'string' ? option : null;

        data = new Pickerlist(this, options);

        if (!action) action = 'init';

        data[action]();
    };

    $.fn.modalpicker.Constructor = Pickerlist;

})( jQuery );

$("[title]").tooltip();


/**
 * Подсчитывает кол-во свойств в объекте
 *
 * @param object
 * @returns {number}
 */
$.count = function(object){
    var n = 0;
    for (var key in object) n++;
    return n;
};


/**
 *
 * МОДАЛЬНЫЕ ОКНА
 *
 * **/


$.fn.modalAddStaff = function(options){

    options = $.extend({
        /**
         * Вид возвращаемого ответа от сервера. Возможные варианты:
         *  - full - html-разметка содержащая фото/фио/кнопки управления пользователем
         *  - optionitem - html для вставки в select
         */
        feedbacktype: 'full',

        /**
         * Возвращает идентификатор партнера, к которому добавится сотрудник
         *
         * @returns {number}
         */
        getpartnerid: function(){
            return 0;
        },

        /**
         * Выполняется после того, как выполнится ajax-запрос создающий пользователя.
         *
         * @param data - ответ от сервера
         */
        oncreate: function(data){

        }
    }, options);

    var make = function(){
        $("body").on("show.bs.modal", "#addstaff-modal", function(){
            var mbody = $( "#addstaff-modal .modal-body");
            mbody.html(indicatorbig);

            $.ajax({
                dataType: "json",
                url: "/blocks/manage/?__ajc=partners::modal_addstaff&partnerid="+options.getpartnerid(),
                success: function(a){
                    mbody.html(a.html);
                }
            });
        });

        /**
         * Добавление сотрудника партнеру
         */
        $("body").on("click", "#addstaff-modal .btn-primary", function(e){

            var btn = $(this),
                query = value = "",
                value = "",
                errors = false,
                modal = $("#addstaff-modal"),
                querytype = $("input[name='addstafftype']:checked").val(),
                issendemail = !$("input[name='issendemail']").prop("checked");

            $(".alert").addClass('hide');

            $.each($('.'+querytype+'-input-block input, '+'.'+querytype+'-input-block select'), function () {
                value = $(this).val();
                relative = $(this).attr('data-relative');
                if(relative){
                    value = $("#"+relative).val();
                }

                if(!value){
                    errors = true;
                    $(this).attr("style", "border:1px solid red");
                }else{
                    $(this).attr("style", "");
                }
                query = query + $(this).attr("name") + "=" + value + "&";
            });

            if(!errors){
                btn.attr("disabled", "disabled");

                $.ajax({
                    type: 'POST',
                    url: '/blocks/manage/ajax.php?ajc=create_user',
                    data: 'partnerid=' + options.getpartnerid() + '&' + query + 'type='+querytype+'&issendemail='+issendemail
                    +'&feedbacktype='+options.feedbacktype,
                    success: function(a){
                        a = getJSON(a);
                        btn.removeAttr("disabled");
                        if(options.oncreate(a)){
                            setCorrectState();
                        }
                    }
                });
            }

        });


        /**
         * Поиск сотрудников в окне "Добавить сотрудника"
         */
        $("body").on('focusin', '#search-staff', function() {
            $(this).autocomplete({
                source: "/blocks/manage/ajax.php?ajc=search_users",
                minLength: 2,
                open:function(event, ui){
                    // Фиксим проблему слоев, чтобы всплывающее меню не оказалось под модальным окном
                    $(".ui-autocomplete.ui-menu").css('z-index', '1100');
                },
                select: function (event, ui) {
                    $("#selecteduserid").val(ui.item.id);
                }
            });
        });

        $("body").on("click", "input[name='addstafftype']", function(e){
            setCorrectState();
        });

        function setCorrectState(){
            var modal = $("#addstaff-modal"),
                type = modal.find("input[name='addstafftype']:checked").val(),
                othertype = type == 'newuser'? 'existsuser':'newuser';

            $.each(modal.find('.'+othertype+'-input-block input, ' + '.'+othertype+'-input-block select'), function () {
                $(this).attr("style", "").attr("disabled", "disabled").val("");
            });

            $.each($('.'+type+'-input-block input, ' + '.'+type+'-input-block select'), function () {
                $(this).attr("style", "").removeAttr("disabled").val("");
            });
        }
    };

    make();
};


/**
 * По-умолчанию в бутстрапе модальные окна накладываются друг на друга, если вызывать их поочередно, при этом
 * возникает неприятное ощущение от затемнения фона и часто мешает предыдущее окно.
 * Этот плагин позволяет изменить поведение модальных бутстрап окон таким образом, что они показываются
 * поочередно.
 *
 */
(function ($) {
    var hiddenmodals = [];
    // Событие show.bs.modal сработает до того, как отрисутеся окно
    $("body").on("show.bs.modal", ".modal", function () {
        var modal = $(this);
        // Скрываем все открытые окна, добавляея их к списку скрытых
        // Эта информация понадобится для восстановления предыдущего открытого окна
        $.each($(".modal.fade.in"), function () {
            // В этот список не добавляем окно, которое было только что закрыто
            // для возвращения к предыдущему (прилетело из hide.bs.modal)
            if (!$(this).hasClass("modal-prev")) {
                hiddenmodals.push($(this).attr("id"));
            } else {
                $(this).removeClass("modal-prev");
            }
            $(this).modal("hide");
        });

        // Событие hide.bs.modal сработает до того, как закрываемое окно будет скрыто
    }).on("hide.bs.modal", ".modal", function () {
        if (hiddenmodals.length > 0) {
            var modalid = hiddenmodals.pop(),
                modal = $("#" + modalid);

            // Это событие прилетело к нам из show.bs.modal, срабатывает во время открытия второго
            // окна, при этом первое скрывается и выполняется этот обработчик, поэтому в этом случае
            // нам нужно оставить все как есть - заталкиваем обратно извлеченный элемент
            if (modal.attr("id") == $(this).attr("id")) {
                hiddenmodals.push(modalid);
            } else {
                // А здесь было закрыто окно пользователем, начинаем суету:
                // Ставим метку, что это окно было предыдущим закрытым
                $(this).addClass("modal-prev");
                // И показываем предыдущее открытое, пойдет работать обработчик show.bs.modal ...
                $("#" + modalid).modal("show");
            }
        }
    });
}(jQuery));

