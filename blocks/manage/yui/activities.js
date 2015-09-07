var activityid = 0;
var searchaddon = Y.one(".activity-search .add-on").getHTML();
var searchingnow = false;


tmp = window.location.toString().match(/&id=([0-9]*)/);
if(tmp != null && 1 in tmp && tmp[1] != 'undefined'){
    activityid = tmp[1];
}

/**
 * Поиск по активностям
 */
Y.one(".activity-search").on('keyup', function(){
    goSearch();
});


/**
 * Выгрузка отчета в Excel
 */
$(".btn-activities-xlexport").on("click", function(){
    var startdate = $("#filter-startdate").val(),
        enddate = $("#filter-enddate").val(),
        type = $("ul.activitytype li.active").data('type'),
        q = $(".activity-search input").val();

    window.location = "/blocks/manage/?_do=exel_export_activities&search="+ q +"&startdate="+startdate+
                      "&enddate="+enddate+"&type="+type;
});

function goSearch(){
    if(!searchingnow){
        searchingnow = true;
        var input = Y.one(".activity-search input"),
            value = input.get("value"),
            addon = Y.one(".activity-search .add-on"),
            type = '',
            state = '',
            startdate = Y.one("#filter-startdate").get("value"),
            enddate = Y.one("#filter-enddate").get("value");

        if(Y.one("ul.activitytype li.active")){
            type = Y.one("ul.activitytype li.active").getAttribute('data-type');
        }

        if(Y.one("ul.activitystate li.active")){
            state = Y.one("ul.activitystate li.active").getAttribute('data-state');
        }

        addon.setHTML(indicator);
        data = '';
        if(resetpage){
            data = '&resetpage=true';
        }

        Y.io('/blocks/manage/ajax.php?ajc=search_activity', {
            method: 'POST',
            data: 'q='+value+'&type='+type+'&state='+state+'&startdate='+startdate+'&enddate='+enddate+'&page='+page+data,
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
 * Добавление новой активности
 */
if(Y.one(".btn-addactivity")) {
    Y.one(".btn-addactivity").on("click", function () {
        var btn = this,
            type = '';

        btn.setAttribute("disabled", "disabled");
        if(Y.one("ul.activitytype li.active")){
            type = Y.one("ul.activitytype li.active").getAttribute('data-type');
        }

        openInstance();
        Y.io('/blocks/manage/ajax.php?ajc=get_activity_info', {
            method: 'POST',
            data: 'activityid=0&type='+type,
            on: {
                complete: function (id, response) {
                    data = getJSON(response.responseText);
                    if (data) {
                        activityid = data.activityid;
                        Y.one('.activityinfo').setHTML(data.html);
                    }
                    btn.removeAttribute("disabled");
                }
            }
        });
    });
}

/**
 * Удаление активности
 */
$("#region-main").on("click", ".delete-instance", function(){
    if(confirm("Вы действительно хотите удалить активность?")){
        $.ajax({
            type: "POST",
            url: "/blocks/manage/ajax.php?ajc=remove_activity",
            data: "activityid="+activityid
        });

        closeInstance();
    }
});



/**
 * Клик по таблице с активностям - открывает детальную информацию
 */
Y.delegate("click", function(e){
    activityid = this.getAttribute('class');
    activityid = activityid.split('activity-')[1];

    openInstance();
    Y.io('/blocks/manage/ajax.php?ajc=get_activity_info', {
        method: 'POST',
        data: 'activityid='+activityid,
        on: {
            complete: function (id, response) {
                data = getJSON(response.responseText);
                if(data){
                    Y.one('.activityinfo').setHTML(data.html);
                }
            }
        }
    });
}, 'table.flexible', '.row-activity');


/**
 * Поиск по тренерам
 */
$("body").on('focusin', '#trainerid', function() {
    var select = $(this),
        addon = select.parents('.ce-addon'),
        ce = addon.siblings(".contenteditable");

    select.val('');

    select.autocomplete({
        source: "/blocks/manage/ajax.php?ajc=search_users",
        minLength: 2,
        select: function (event, ui) {
            addon.attr("style", "display:none");
            ce.html(ui.item.label).attr("style", "display:inline-block");
            $.ajax({
                type: "POST",
                url: "/blocks/manage/ajax.php?ajc=appoint_trainer",
                data: 'activityid='+activityid+'&userid='+ui.item.id,
                success:function(a){
                    a=getJSON(a);
                }
            });
        }
    });
});


/**
 * Обновляем значение при вводе текста в contenteditable-textarea
 */
Y.one("#region-main").delegate('keyup', function(node){
    var field = this.getAttribute("data-field");
    var value = this.get('innerHTML');

    Y.io('/blocks/manage/ajax.php?ajc=update_activity_field', {
        method: 'POST',
        data: 'activityid='+activityid+'&field='+field+'&value='+value
    });
}, ".ce-textarea");


/**
 * При изменении значения в select-поле, обновляем соотв. данные
 */
Y.one("#region-main").delegate('change', function(){
    var select = this;
    var value = select.get("value");
    var name = select.one("option:checked").get("text");
    var field = select.getAttribute('data-field');

    select.ancestor(".ce-addon").setAttribute('style', 'display:none');
    select.ancestor(".ce-addon").previous(".contenteditable").setHTML(name).setAttribute('style', 'display:inline-block');

    Y.io('/blocks/manage/ajax.php?ajc=update_activity_field', {
        method: 'POST',
        data: 'activityid='+activityid+'&field='+field+'&value='+value
    });
}, ".ce-addon select");


/**
 * Выбор мест проведения
 */
$(".field-place").modalpicker({
    sections: {class:"Классы", tt:"Торговые точки"},
    activesection: "class",
    pickerlist: "/blocks/manage/?__ajc=activities::placepicker_list",
    onpick: function(trigger, id){
        trigger.html(indicator);
        $.ajax({
            type: "POST",
            url: "/blocks/manage/?__ajc=activities::set_place",
            data: "activityid=" + activityid + "&placeid=" + id,
            success: function(a){
                a = getJSON(a);
                if(a.html)
                    trigger.html(a.html);
            }
        })
    }
});



/**
 * Добавление даты тренинга
 */
Y.one("#region-main").delegate('click', function(){
    var btn = this;
    var controls = Y.one(".datecontrols.hide").cloneNode(true).appendTo("#datesbox .datecontrols-area").removeClass("hide");

    updateDate(controls);

}, ".btn-adddate");

/**
 * Обновление дат тренинга
 */
Y.one("#region-main").delegate('change', function(){
    var controls = this.ancestor(".datecontrols");
    updateDate(controls);

}, ".datecontrols-area select");

/**
 * Удаление даты тренинга
 */
Y.one("#region-main").delegate('click', function(){
    var controls = this.ancestor(".datecontrols");
    var dateid = controls.getAttribute('data-dateid');
    controls.remove();
    Y.io('/blocks/manage/ajax.php?ajc=remove_activity_date', {
        method: 'POST',
        data: 'activityid='+activityid+'&dateid='+dateid
    });

}, ".btn-remove");


function updateDate(controls){
    var time = [];

    controls.all('select').each(function (node) {
        time[node.get('name')] = node.get("value");
    });

    var dateid = controls.getAttribute('data-dateid');
    var datefrom = Date.UTC(time['years'], time['months']-1, time['days'], time['hoursfrom'], time['minutesfrom']) / 1000;
    var dateto = Date.UTC(time['years'], time['months']-1, time['days'], time['hoursto'], time['minutesto']) / 1000;

    Y.io('/blocks/manage/ajax.php?ajc=set_activity_date', {
        method: 'POST',
        data: 'activityid='+activityid+'&datefrom='+datefrom+'&dateto='+dateto+'&dateid='+dateid,
        on: {
            complete: function (id, response) {
                var data = eval('('+response.responseText+')');
                if(data.success){
                    controls.setAttribute('data-dateid', data.dateid);
                }
            }
        }
    });
}

/**
 * При вывобре партнера в окошке добавления участника - меняем список сотрудников
 */
Y.one("#region-main").delegate('change', function(){
    var select = this,
        value = select.get("value");

    Y.io('/blocks/manage/ajax.php?ajc=get_members_list', {
        method: 'POST',
        data: 'activityid='+activityid+'&partnerid='+value,
        on: {
            complete: function (id, response) {
                data = getJSON(response.responseText);
                if(data.success){
                    Y.one('.members-list').setHTML(data.html);
                }
            }
        }
    });
}, "#menufield-partner");

/**
 * Добавление участника в активность
 */
Y.one('#region-main').delegate('click', function(){
    var btn = this,
        partnerid = Y.one('#menufield-partner').get("value"),
        users = [];

    Y.all("#menufield-staffer option:checked").each( function() {
        users.push(this.get('value'));
    });

    users = users.toString();

    if(partnerid && users){
        btn.setAttribute('disabled', 'disabled');
        Y.io('/blocks/manage/ajax.php?ajc=add_member', {
            method: 'POST',
            data: 'activityid='+activityid+'&users='+users+'&partnerid='+partnerid,
            on: {
                complete: function (id, response) {
                    data = getJSON(response.responseText);
                    btn.removeAttribute("disabled");
                    if(data.success){
                        Y.Array.each(data.html, function(val, i){
                            Y.one('.members-panel').append(val);
                        });
                        $("#addmember-modal").modal('hide');
                    }else{
                        Y.one("#addmember-modal .alert").removeClass('hide').one(".content").setHTML(data.error);
                    }
                }
            }
        });
    }
}, '#addmember-modal .btn-primary');

/**
 * Добавление сотрудника партнеру
 */
$("#addstaff-modal").modalAddStaff({
    feedbacktype: 'optionitem',
    getpartnerid: function(){
        return $("#menufield-partner").val();
    },
    oncreate: function(data){
        var list = $("#menufield-staffer");

        if(data.success){
            list.append(data.html);
            $("#addstaff-modal").modal('hide');
            return true;
        }else{
            $(".alert").removeClass('hide').find(".content").html(data.html);
            return false;
        }
    }
});


/**
 * Выполнение действий над участником: прошел/не прошел
 */
Y.one('#region-main').delegate('click', function(){
    var btn = this,
        btns = btn.ancestor('.btn-group').all('button'),
        action = btn.getAttribute('data-action'),
        memberid = btn.ancestor('table').getAttribute('data-memberid');


    btn.setAttribute('style', 'background-color:#ddd');
    btn.siblings('button').removeClass('active').setAttribute('style', 'background-color:transparent');
    btns.setAttribute('disabled', 'disabled');

    Y.io('/blocks/manage/ajax.php?ajc=process_member', {
        method: 'POST',
        data: 'activityid='+activityid+'&memberid='+memberid+'&action='+action,
        on: {
            complete: function (id, response) {
                data = getJSON(response.responseText); // Нужно для редиректа, если пользователь не авторизован
                btns.removeAttribute("disabled");
            }
        }
    });
}, '.btn-useraction');




YUI().use('calendar', function (Y) {

    var calendar = new Y.Calendar({
                        contentBox: "#calendar",
                        showPrevMonth: true,
                        showNextMonth: true,
                        date: new Date()
                    }).render(),
        dtdate = Y.DataType.Date,
        mouseincalendar = false,
        activeinput = false;

    Y.all('.calendar-trigger').on('focus', function(e){
        activeinput = e.currentTarget;
        var date = activeinput.get('value');
        Y.all('.yui3-calendar-day').removeClass('yui3-calendar-day-selected');

        if(date){
            calendar.set('date', new Date(date));
            day = parseInt(date.match(/-(\d+)$/)[1]);
            Y.all('.yui3-calendar-day').each(function(node){
                if(node.getHTML() == day){
                    node.addClass('yui3-calendar-day-selected');
                }else{
                    node.removeClass('yui3-calendar-day-selected');
                }
            });
        }
        Y.one('#calendar').removeClass('hide');
    });
    Y.all('.calendar-trigger').on('blur', function(){
        if(!mouseincalendar){
            activeinput = false;
            Y.one('#calendar').addClass('hide');
            goSearch();
        }
    });
    Y.one("#calendar").on('mouseenter', function(){
        mouseincalendar = true;
    });
    Y.one("#calendar").on('mouseleave', function(){
        mouseincalendar = false;
    });

    calendar.on("selectionChange", function (ev) {
        var newDate = ev.newSelection[0];
        activeinput.set('value', dtdate.format(newDate));
        activeinput = false;
        Y.one('#calendar').addClass('hide');
        goSearch();
    });
});
