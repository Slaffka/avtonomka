/**
 * Добавление программ, категорий
 */
(function($) {
    var courseid = 0;

    /**
     * Выбор режима, добавить: категорию, программу, программу с привязкой к курсу
     */
    $(".check-mode .radio input").on("click", function () {
        var mode = $(this).val(),
            forms = $(".form"),
            form = $(".form-" + mode);

        courseid = 0;
        forms.addClass('hide');
        form.removeClass('hide');

        if (form.find(".categorylist").length && !form.find(".categorylist").val()) {
            form.find("button").attr("disabled", "disabled");
            form.find(".input-name").attr("disabled", "disabled");
        }
    });

    /**
     * Выбор категории куда добавить программу
     */
    $(".categorylist").on("change", function () {
        var input = $(this).siblings(".input-name");

        if (!$(this).val()) {
            input.siblings("button").attr("disabled", "disabled");
            input.attr("disabled", "disabled");
        } else {
            input.siblings("button").removeAttr("disabled");
            input.removeAttr("disabled");
        }
    });

    /**
     * Нажатие на кнопку "Добавить", запускает процесс добавления категории или программы
     */
    $(".form .btn").on("click", function () {

        var mode = $(".check-mode .radio input:checked").val(),
            category = $(this).siblings(".categorylist").val(),
            name = $(this).siblings(".input-name").val();

        if (mode == 'program' || mode == 'linkedprogram') {
            add_program(mode, category, courseid, name);
        } else if (mode == 'category') {
            add_category(name);
        }
    });

    /**
     * Добавляет категорию и перезагружает страницу
     *
     * @param name
     */
    function add_category(name) {
        var btn = $(".form-category .btn");
        btn.attr("disabled", "disabled");

        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=programs::add_category',
            data: 'name=' + name,
            success: function () {
                btn.removeAttr("disabled");
                window.location = window.location;
            }
        })
    }

    /**
     * Добавляет программу в категорию
     *
     * @param mode - режим: программа или программа с привязкой к курсу
     * @param category - идентификатор категории, в которую добавляем программу
     * @param courseid - идентификатор курса с которым связываем программу (не обязательно), ноль если программа без
     *                   привязки к курсу
     * @param name     - название программы, пусто, если программа с привязкой к курсу (определяется сервером)
     */
    function add_program(mode, category, courseid, name) {
        var table = Y.one(".programgroup-" + category),
            lastrow = addNewRowIn(table),
            btn = $(".form-" + mode + ' .btn');

        btn.attr("disabled", "disabled");

        lastrow.one("td.c0").setHTML(indicator);
        lastrow.one("td.c1").setHTML(name);

        Y.io('/blocks/manage/?__ajc=programs::add_program', {
            method: 'POST',
            data: 'mode=' + mode + '&category=' + category + '&courseid=' + courseid + '&name=' + name,
            on: {
                complete: function (id, response) {
                    data = getJSON(response.responseText);
                    if (data.id) {
                        lastrow.one("td.c0").setHTML(table.all("tbody tr")._nodes.length - 1);
                        lastrow.one("td.c1").setHTML(data.name);
                        lastrow.addClass('courseid-' + courseid + ' programid-' + data.id);
                        $(".input-name").val('');
                    } else {
                        lastrow.one("td.c0").setHTML("-");
                    }
                    btn.removeAttr("disabled");
                }
            }
        });
    }

    /**
     * Выпадающий список для выбора программы с привязкой к курсу
     */
    YUI().use('autocomplete', 'autocomplete-filters', 'autocomplete-highlighters', 'datasource-get', function (Y) {
        Y.one('#input-addprogramm').plug(Y.Plugin.AutoComplete, {
            maxResults: 10,
            resultHighlighter: 'phraseMatch',
            resultTextLocator: function (result) {
                return result.fullname;
            },

            source: '/blocks/manage/ajax.php?ajc=search_courses&q={query}',

            resultListLocator: function (response) {
                return response;
            },
            on: {
                select: function (e) {
                    courseid = e.result.raw.id;
                }
            }
        });
    });

}(jQuery));


/**
 * Удаление программы
 */
Y.one("#region-main").delegate('click', function(e){
    var row = this.ancestor('tr');
    var rownum = row.one("td.c0").getHTML();
    var programid = row.getAttribute("class");
    programid = programid.match(/programid-([0-9]*)/);
    if(programid != null && 1 in programid && typeof programid[1] != 'undefined' && programid != 0){
        row.one("td.c0").setHTML(indicator);
        Y.io('/blocks/manage/ajax.php?ajc=delete_program', {
            method: 'POST',
            data: 'programid='+programid[1],
            on: {
                complete: function (id, response) {
                    data = getJSON(response.responseText);
                    if(data.success){
                        row.remove();
                    }else{
                        row.one("td.c0").setHTML(indicator);
                    }
                }
            }
        });
    }
    event.preventDefault();

}, ".deleteprogram");


/**
 * Редактирование программы
 */
(function($) {
    var programid = 0;

    /**
     * Модальное окно
     */
    $(".editprogram").on("click", function (e) {
        var modal = $("#editprogram-modal"),
            modalbody = modal.find(".modal-body-content");

        programid = getDataFromStr('programid', $(this).parents("tr").attr("class"));
        modalbody.html(indicatorbig);

        $.ajax({
            type: "POST",
            url: "/blocks/manage/?__ajc=programs::load_modal_editprogram",
            data: "programid=" + programid,
            success: function (a) {
                a = getJSON(a);
                modalbody.html(a.html);
            }
        })
    });


    /**
     * Сохранение изменений
     */
    $("#editprogram-modal .btn-primary").on('click', function (e) {
        if (programid) {
            var btn = $(this),
                modal = $("#editprogram-modal"),
                name = modal.find(".programname").val(),
                period = modal.find(".programperiod").val();

            btn.attr("disabled", "disabled");
            modal.find(".alert").addClass("hide");
            $.ajax({
                type: 'POST',
                url: "/blocks/manage/?__ajc=programs::update",
                data: 'programid=' + programid + '&name=' + encodeURIComponent(name) + '&period=' + period,
                success: function (a) {
                    a = getJSON(a);

                    if (!a.error) {
                        $(".programid-" + programid + " .c1").html(a.html);
                        modal.modal("hide");
                    } else {
                        modal.find(".alert .content").html(a.html);
                        modal.find(".alert").removeClass("hide");
                    }
                    btn.removeAttr("disabled");
                }
            });
        }
    });
}(jQuery));
