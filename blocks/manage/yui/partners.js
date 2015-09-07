var partnerid = Y.one("#vars").getAttribute('data-partnerid');
var searchaddon = Y.one(".partners-search .add-on").getHTML();
var searchingnow = false;

/**
 * Поиск по партнерам
 */
Y.one(".partners-search").on('keyup', function(){
    goSearch();
});

$(".btn-partners-xlexport").on("click", function(){
    window.location = "/blocks/manage/?_do=exel_export_partners&search="+$(".partners-search input").val();
});

function goSearch(){
    if(!searchingnow){
        searchingnow = true;
        var input = Y.one(".partners-search input");
        var value = input.get("value");
        var addon = Y.one(".partners-search .add-on");
        addon.setHTML(indicator);

        data = '';
        if(resetpage){
            data = '&resetpage=true';
        }

        Y.io('/blocks/manage/ajax.php?ajc=search_partner', {
            method: 'POST',
            data: 'q='+value+'&page='+page+data,
            on: {
                complete: function (id, response) {
                    searchingnow = false;
                    resetpage = true;
                    addon.setHTML(searchaddon);

                    if(value != input.get("value")){
                        goSearch();
                    }else{
                        var data = response.responseText, // полученные данные
                            rows = '',
                            paging = '';

                        if (Y.one("div.no-overflow table tbody")) {
                            if(data) {
                                data = Y.Node.create(data);
                                rows = data.one("table.flexible tbody");
                                paging = data.one(".paging");

                                rows = rows.getHTML();
                                Y.one("div.no-overflow table tbody").setHTML(rows);
                                Y.one(".no-overflow ~ .paging").setHTML(paging);
                            }else{
                                Y.one("div.no-overflow table tbody").setHTML(data);
                                Y.one(".no-overflow ~ .paging").setHTML('');
                            }
                        } else {
                            window.location = window.location;
                        }
                    }
                }
            }
        });
    }
}


/**
 * Добавление нового партнера
 */
if(Y.one(".btn-addpartner")) {
    resetpage = false;
    Y.one(".btn-addpartner").on("click", function () {
        var btn = this;
        btn.setAttribute("disabled", "disabled");
        openInstance();
        Y.io('/blocks/manage/?__ajc=partners::get_partner_info', {
            method: 'POST',
            data: 'partnerid=0',
            on: {
                complete: function (id, response) {
                    data = getJSON(response.responseText);
                    if (data) {
                        partnerid = data.partnerid;
                        Y.one('.partnerinfo').setHTML(data.html);
                    }
                    btn.removeAttribute("disabled");
                    open_instance_trigger();
                }
            }
        });
    });
}

/**
 * Удаление партнера
 */
$("#region-main").on("click", ".delete-instance", function(){
    if(confirm("Вы действительно хотите удалить этого партнера?")){
        $.ajax({
            type: "POST",
            url: "/blocks/manage/ajax.php?ajc=remove_partner",
            data: "partnerid="+partnerid
        });

        closeInstance();
    }
});


/**
 * Клик по таблице с партнерами - открывает детальную информацию
 */
Y.delegate("click", function(e){
    partnerid = this.getAttribute('class');
    partnerid = partnerid.split('partner-')[1];

    openInstance();
    Y.io('/blocks/manage/?__ajc=partners::get_partner_info', {
        method: 'POST',
        data: 'partnerid='+partnerid,
        on: {
            complete: function (id, response) {
                data = getJSON(response.responseText);
                if(data){
                    partnerid = data.partnerid;
                    Y.one('.partnerinfo').setHTML(data.html);
                    open_instance_trigger();
                }
            }
        }
    });
}, 'table.flexible', '.row-partner');


/**
 * Назначение/переназначение менеджеров
 */
$(".manager .btn-appoint").modalpicker({
    pickerlist: "/blocks/manage/?__ajc=base::userpicker_list",
    customdata: function(trigger){
        var point = trigger.parents(".manager").data("point");
        return {point: point, partnerid:partnerid};
    },
    onpick: function(target, id){
        target = target.parents(".manager");
        var point = target.data("point");

        target.addClass("processing");
        $.ajax({
            type: "POST",
            url: "/blocks/manage/?__ajc=partners::appoint_manager",
            data: "partnerid=" + partnerid + "&userid=" + id + "&point=" + point,
            success: function(a){
                a = getJSON(a);
                if(a.html)
                    target.replaceWith(a.html);
            }
        });
    }
});






/**
 * Открывает select-поля при изменении значений
 */

Y.one(".partnerinfo").delegate('change', function(){
    var select = this,
        value = select.get("value"),
        text = select.one("option:checked").get("text"),
        field = select.getAttribute("data-field");


    select.ancestor(".ce-addon").setAttribute('style', 'display:none')
          .previous(".contenteditable").setHTML(text).setAttribute('style', 'display:inline-block');

    Y.io('/blocks/manage/ajax.php?ajc=update_field', {
        method: 'POST',
        data: 'partnerid='+partnerid+'&field='+field+'&value='+value
    });
}, ".ce-select");



/**
 * Изменяет input поля при вводе текста
 */
Y.one(".partnerinfo").delegate('keyup', function(node){
    var field = this.getAttribute("data-field");
    var value = this.get('innerHTML');

    Y.io('/blocks/manage/ajax.php?ajc=update_field', {
        method: 'POST',
        data: 'partnerid='+partnerid+'&field='+field+'&value='+encodeURIComponent(value)
    });
}, ".contenteditable");


/**
 * Назначение программы партнеру
 */
Y.one(".partnerinfo").delegate('change', function(e){
    var programid = this.get('value');
    var table = Y.one("table#appointedactivities");

    if(programid && table.one('.programid-'+programid) == null){
        var value = Y.one("#menumenuaddprogram option:checked").get("text");

        var lastrow = addNewRowIn("table#appointedactivities");

        lastrow.one("td.c1").setHTML(value);
        lastrow.one("td.c0").setHTML(indicator);
        lastrow.one("td.c2").setHTML('');

        Y.io('/blocks/manage/ajax.php?ajc=appoint_program', {
            method: 'POST',
            data: 'partnerid='+partnerid+'&programid='+programid,
            on: {
                complete: function (id, response) {
                    data = getJSON(response.responseText);
                    if(data.id){
                        lastrow.one("td.c0").setHTML(table.all("tbody tr")._nodes.length-1);
                        lastrow.one("td.c2").setHTML(data.period);
                        lastrow.addClass('papointedid-'+data.id);
                        lastrow.addClass('programid-'+programid);
                    }else{
                        lastrow.one("td.c0, td.c1").setHTML("-");
                    }
                }
            }
        });
    }

}, "#menumenuaddprogram");


/**
 * Отмена программы у партнера
 */
Y.one(".partnerinfo").delegate("click", function(e){
    var row = this.ancestor('tr');
    var rownum = row.one("td.c0").getHTML();
    var appointedid = row.getAttribute("class");
    appointedid = appointedid.match(/papointedid-([0-9]*)/);

    if(appointedid != null && 1 in appointedid && typeof appointedid[1] != 'undefined' && appointedid != 0){
        row.one("td.c0").setHTML(indicator);
        Y.io('/blocks/manage/ajax.php?ajc=disappoint_program', {
            method: 'POST',
            data: 'partnerid='+partnerid+'&appointedid='+appointedid[1],
            on: {
                complete: function (id, response) {
                    data = getJSON(response.responseText);
                    if(data.success){
                        row.remove();
                    }else{
                        row.one("td.c0").setHTML(rownum);
                    }
                }
            }
        });
    }

    event.preventDefault();
}, ".cancelactivity");


/**
 * Фильтрация сотрудников по параметрам: ТМ, Тренер, ТТ, архив
 */
$("body").on("change", ".filter-element", function(){
    var select = $(this),
        data = {};
    select.attr("disabled", "disabled");

    $.each($(".filter-element"), function(i, elem){
        if($(elem).attr("type") == "checkbox"){
            val = $(elem).prop("checked");
        }else{
            val = $(elem).val();
        }
        type = $(elem).data("type");
        if(val) data[type] = val;
    });
    data["partnerid"] = partnerid;

    $.ajax({
        type: "POST",
        url: "/blocks/manage/?__ajc=partners::refresh_staffer_list",
        data: data,
        success: function(a){
            a = getJSON(a);
            if(a.success){
                $(".staff-list").html(a.html);
                if(a.html){
                    $(".staff-wrapper .muted-placeholder").addClass("hide");
                }else{
                    $(".staff-wrapper .muted-placeholder").removeClass("hide");
                }
            }
            select.removeAttr("disabled");
        }
    });
});

/**
 * Добавление сотрудника партнеру
 */
$("#addstaff-modal").modalAddStaff({
    feedbacktype: 'full',
    getpartnerid: function(){
        return partnerid;
    },
    oncreate: function(data){
        var placeholder = $(".staff-wrapper .muted-placeholder");

        if(data.success){
            $(".staff-list").append(data.html);
            if(!placeholder.hasClass("hide")){
                placeholder.addClass("hide");
            }
            $("#addstaff-modal").modal('hide');
            return true;
        }else{
            $(".alert").removeClass('hide').find(".content").html(data.html);
            return false;
        }
    }
});


/**
 * Перенос сотрудника на другую ТТ
 */
$(".staff-action-changett").modalpicker({
    pickerlist: "/blocks/manage/?__ajc=partners::ttpicker_list",
    customdata: function(trigger){
        return {partnerid:partnerid};
    },
    onshow: function(target){
        target.parents(".dropdown").removeClass("open");
    },
    onpick: function(target, tottid){
        var data = target.parents(".dropdown"),
            userid = data.data("userid"),
            usernode = target.parents(".staff-table"),
            fromttid = data.data("ttid");

        usernode.addClass("processing");

         $.ajax({
             type: "POST",
             url: "/blocks/manage/?__ajc=partners::relocate_staffer",
             data: {partnerid:partnerid, userid:userid, fromttid: fromttid, tottid:tottid},
             success: function(a){
                 a = getJSON(a);
                 if(a.success)usernode.remove();
             }
         });
    }
});


/**
 * Действия над пользователем - удаление/перенос в архив
 */
$("#region-main").on("click", ".staff-action", function(){
    var userid = $(this).parents(".dropdown").data("userid"),
        action = $(this).data("type"),
        usernode = $(this).parents(".staff-table"),
        wrapper = $(this).parents(".staff-wrapper"),
        btnmode = $(".btn-gotoarchive"),
        mode = "active",
        confirmed = false;

    if(wrapper.hasClass("archive-staff")){
        mode = "archive";
    }

    switch(action){
        case 'archive':
            if(confirm("Перенести сотрудника в архив?")){
                usernode.effect( "transfer", { to: btnmode }, 600 ).detach().appendTo(".archive-staff .staff-list");
                usernode.find(".staff-action[data-type='archive']").hide();
                confirmed = true;
            }
            break;

        case 'remove':
            if(confirm("Вы действительно хотите удалить сотрудника? Все данные в lms об этом сотруднике будут утеряны.")){
                usernode.toggle( "explode", {pieces: 25}, 500, function(){usernode.remove()});
                confirmed = true;
            }
            break;

        case 'finaly-remove':
            if(confirm("Вы уверены, что хотите удалить пользователя полностью с портала?")){
                usernode.toggle( "explode", {pieces: 25}, 500, function(){usernode.remove()});
                confirmed = true;
            }
            break;
    }

    if(confirmed){
        $.ajax({
            type: "POST",
            url: "/blocks/manage/ajax.php?ajc=action_user",
            data: 'partnerid=' + partnerid + "&userid=" + userid + "&action=" + action
        });
    }


    if(!$(".archive-staff .staff-table").length){
        $(".archive-staff .muted-placeholder").removeClass("hide");
    }else{
        $(".archive-staff .muted-placeholder").addClass("hide");
    }

    if(!$(".active-staff .staff-table").length){
        $(".active-staff .muted-placeholder").removeClass("hide");
    }else{
        $(".active-staff .muted-placeholder").addClass("hide");
    }

    event.preventDefault();
});





/**
 * Кнопка переключения Сотрудники - Архив, анимация
 */
$("#region-main").on("click", ".btn-gotoarchive", function(){
    $( ".archive-staff" ).toggle("Easings", "linear");
    $( ".active-staff" ).toggle("Easings", "linear" );
});



/**
 * Добавление торговых точек партнеру
 */
$("#region-main").on("click", "#addtt-modal .btn-primary", function(){
    var ttname = $("#addtt-modal .ttname").val(),
        ttcode = $("#addtt-modal .ttcode").val(),
        btn = $(this);

    btn.attr("disabled", "disabled");
    $.ajax({
        type: 'POST',
        url: '/blocks/manage/?__ajc=partners::create_tt',
        data: "partnerid="+partnerid+'&ttname='+ttname+'&ttcode='+ttcode,
        success: function(a){
            a = getJSON(a);
            if (a && a.html) {
                $("#addtt-modal").modal("hide");
                $('.ttlist').append(a.html);
            }
            btn.removeAttr("disabled");
        }
    });
});

/**
 * Выбор программы для просмотр результатов тренинга.
 */
Y.one(".partnerinfo").delegate('change', function(e){
    var select = this,
        programid = select.get("value");

    Y.io('/blocks/manage/ajax.php?ajc=get_training_results', {
        method: 'POST',
        data: 'partnerid=' + partnerid + '&programid=' + programid,
        on: {
            complete: function (id, response) {
                data = getJSON(response.responseText);
                if(data.success){
                    Y.one(".result-panel").setHTML(data.html);
                }
            }
        }
    });

}, "#menumenuselectprogram");

$(document).ready(function(){
    open_instance_trigger();
});

function open_instance_trigger(){
    $('.editable-text').editable({
        mode: 'inline',
        pk: partnerid,
        sourceCache: false,
        sourceOptions: {data:{partnerid:partnerid}},
        url: '/blocks/manage/?__ajc=partners::save_region'
    });
}