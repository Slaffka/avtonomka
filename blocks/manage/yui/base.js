var navlist = Y.one('.instancelist-wrapper');
var details = Y.one('.instanceinfo'),
    page = getDataFromStr('page', window.location.toString(), '='),
    resetpage = true;

if(!page){
    page = 0;
}


if($("#region-main").length) {
    // Скрываем первую пагинацию
    if (Y.one('.paging')) {
        Y.one('.paging').setAttribute('style', 'display:none');
    }

    /**
     * Открывает select/input-поле для редактирования
     */
    Y.one("#region-main").delegate('click', function () {
        var field = this,
            addon = field.next(".ce-addon");
        if (addon) {
            field.setAttribute('style', 'display:none');
            addon.setAttribute('style', 'display:inline-block');
            addon.one("select, input").focus();
        }

    }, ".contenteditable");

    Y.one('#region-main').delegate('click', function () {
        closeInstance();
    }, '.close-instance');

    /**
     * Скрывает редактируемое поле
     */
    Y.use('event-focus', function () {
        Y.one("#region-main").delegate('blur', function () {
            var addon = this,
                field = addon.previous('.contenteditable'),
                list = Y.one(".yui3-widget.yui3-aclist.yui3-widget-positioned");

            if (list == null || list != null && list.hasClass("yui3-aclist-hidden")) {
                addon.setAttribute('style', 'display:none');
                field.setAttribute('style', 'display:inline-block');
            }
        }, ".ce-addon");
    });
}

function addNewRowIn(tablenode){
    var table = Y.one(tablenode),
        tplrows = table.all("tr.clone.hide"),
        lastrow = table.one(".lastrow"),
        rnum = 0;

    if(lastrow) {
        rnum = lastrow.getAttribute("class");
        rnum = rnum.match(/r([0-9]*)\s/)[1];
        rnum = rnum ? 0: 1;
    }

    tplrows.each(function(node){
        lastrow.removeClass("lastrow");
        table.one("tbody").append(node.cloneNode(true).addClass("lastrow"));
        lastrow = table.one(".lastrow").removeClass("clone hide");
    });

    if(tplrows._nodes.length > 1){
        lastrow.addClass('hide');
    }

    return lastrow;
}




function closeInstance(){
    var ac_fio = document.getElementById('activityinfo');
    if(!ac_fio) {
        ac_fio = document.getElementById('partnerinfo');
    }
    if(ac_fio){
        ac_fio = ac_fio.getElementsByClassName('userfioinput');

        if(ac_fio[0] != null){
            document.getElementById('userfio-tmp').appendChild(ac_fio[0]);
        }
    }

    navlist.setAttribute('style', 'display:block');
    details.setHTML('');
    goSearch();
}

function openInstance(){
    navlist.setAttribute('style', 'display:none');
    details.setHTML(indicatorbig);
}


/**
 * Возвращает численные данные по шаблону из строки
 * Например, если у нас есть строка "somestring-999 anything", и мы хотим извлечь идентификатор 999, то
 * эта функция то что нужно! Вызываем так: getDataFromStr('999', 'somestring-999 anything');
 *
 * @param search
 * @param classname
 * @param separator
 * @returns {*}
 */
function getDataFromStr(search, classname, separator){
    if(typeof separator == 'undefined'){
        separator = '-';
    }

    re = new RegExp(search+separator+'([0-9]*)', "i");
    data = classname.match(re);
    if(data != null && 1 in data && typeof data[1] != 'undefined' && data != 0){
        return data[1];
    }

    return null;
}



/**
 * Разбирает Json, в случае ошибки переадресовывает на reproductionlink
 * Например, если закончилась сессия у пользователя и он пытается сделать аякс-запрос, он
 * будет переадресован на страницу входа
 *
 * @param data
 * @returns {*}
 */
function getJSON(data){
    if(!data || typeof data === 'object'){
        return data;
    }

    data = eval('('+data+')');
    if(data.reproductionlink){
        window.location = data.reproductionlink;
    }

    return data;
}


if(typeof $.fn.editableutils != "undefined") {
    (function ($) {
        "use strict";

        var Address = function (options) {
            this.init('address', options, Address.defaults);
        };

        //inherit from Abstract input
        $.fn.editableutils.inherit(Address, $.fn.editabletypes.abstractinput);

        $.extend(Address.prototype, {
            init: function (type, options, defaults) {
                this.type = type;
                this.options = $.extend({}, defaults, options);
                return false;
            },


            /**
             Renders input from tpl

             @method render()
             **/
            render: function () {
                this.$input = this.$tpl.find('input, select');
            },

            /**
             Default method to show value in element. Can be overwritten by display option.

             @method value2html(value, element)
             **/
            value2html: function (value, element) {
                if (!value) {
                    $(element).empty();
                    return;
                }
                if (typeof value == 'string') {
                    $.ajax({
                        type: "POST",
                        url: value,
                        success: $.proxy(function (v) {
                            v = getJSON(v);
                            this.value = v;
                            this.updpreview(v, element);
                        }, this)
                    });

                    $(element).html('Не указано'); //Хак, чтобы не считалось пустым
                } else {
                    this.value = value;
                    this.updpreview(value, element);
                }
            },


            updpreview: function (value, element) {
                if (!value.cityid.text) {
                    return false;
                }

                var html = $('<div>').text(value.cityid.text).html();

                if (value.street)
                    html += ', ' + $('<div>').text(value.street).html();

                if (value.num)
                    html += ', ' + $('<div>').text(value.num).html();

                if (value.bld)
                    html = html + ', стр.' + $('<div>').text(value.bld).html();

                if (value.corp)
                    html = html + ', корп.' + $('<div>').text(value.corp).html();

                if (value.floor)
                    html = html + ', эт.' + $('<div>').text(value.floor).html();

                if (value.metro)
                    html = html + ' (м.' + $('<div>').text(value.metro).html() + ')';

                $(element).html(html);
            },


            /**
             Converts value to string.
             It is used in internal comparing (not for sending to server).

             @method value2str(value)
             **/
            value2str: function (value) {
                var str = '';
                if (value) {
                    for (var k in value) {
                        str = str + k + ':' + value[k] + ';';
                    }
                }
                return str;
            },

            /*
             Converts string to value. Used for reading value from 'data-value' attribute.

             @method str2value(str)
             */
            str2value: function (str) {
                /*
                 this is mainly for parsing value defined in data-value attribute.
                 If you will always set value by javascript, no need to overwrite it
                 */
                return str;
            },

            /**
             Sets value of input.

             @method value2input(value)
             @param {mixed} value
             **/
            value2input: function (value) {
                if (!this.value) {
                    return;
                }

                var v = this.value;
                this.$input.filter('[name="cityid"]').val(v.cityid.value);
                this.$input.filter('[name="street"]').val(v.street);
                this.$input.filter('[name="metro"]').val(v.metro);
                this.$input.filter('[name="num"]').val(v.num);
                this.$input.filter('[name="bld"]').val(v.bld);
                this.$input.filter('[name="corp"]').val(v.corp);
                this.$input.filter('[name="floor"]').val(v.floor);
            },

            /**
             Returns value of input.

             @method input2value()
             **/
            input2value: function () {
                this.value = {
                    cityid: {
                        value: this.$input.find('option:selected').val(),
                        text: this.$input.find('option:selected').text()
                    },
                    street: this.$input.filter('[name="street"]').val(),
                    metro: this.$input.filter('[name="metro"]').val(),
                    num: this.$input.filter('[name="num"]').val(),
                    bld: this.$input.filter('[name="bld"]').val(),
                    corp: this.$input.filter('[name="corp"]').val(),
                    floor: this.$input.filter('[name="floor"]').val()
                };

                return this.value;
            },

            /**
             Activates input: sets focus on the first field.

             @method activate()
             **/
            activate: function () {
                this.$input.filter('[name="cityid"]').focus();
            },

            validate: function () {

            },

            /**
             Attaches handler to submit form in case of 'showbuttons=false' mode

             @method autosubmit()
             **/
            autosubmit: function () {
                this.$input.keydown(function (e) {
                    if (e.which === 13) {
                        $(this).closest('form').submit();
                    }
                });
            }
        });

        var tpl = $.ajax({
            type: "POST",
            url: "/blocks/manage/ajax.php?ajc=get_address_tpl",
            async: false
        }).responseText;

        tpl = getJSON(tpl).html;
        Address.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
            tpl: tpl,
            inputclass: ''
        });

        $.fn.editabletypes.address = Address;


    }(window.jQuery));
}