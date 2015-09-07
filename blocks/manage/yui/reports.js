var filter = null,
    type = null,
    datefrom = null,
    dateto = null,
    regions = null;


$(document).ready(function(){
    //Инициализируем мультиселек с выбором регионов
    $("#menuregions").multiselect({
        header: false,
        height:'300',
        minWidth:'345',
        noneSelectedText: "В регионах ...",
        selectedText: "# выбрано",
        beforeclose: function(event, ui){
            reload_report();
        }
    });

    /**
     * Выгрузка отчета в Excel
     */
    $(".btn-report-xlexport").on("click", function(){
        var partnerid = $("#menupartner option:selected").val(),
            trainerid = $("#menutrainer option:selected").val(),
            userid = $("#menuuser option:selected").val();

        if(type == 'partner' && !partnerid) {
            alert('Не выбран партнер!');
            return false;
        }else if(type == 'trainer' && !trainerid){
            alert('Не выбран тренер!');
            return false;
        }else if(type == 'staff'){

            if(!partnerid){
                alert('Не выбран партнер!');
                return false;
            }

            if(!userid){
                alert('Не выбран сотрудник!');
                return false;
            }
        }

        window.location = "/blocks/manage/?_do=exel_export_report&"+get_query();
    });

    // Обновляем переменные filter, type, datefrom
    get_query();

    //Инициализируем графики
    reload_charts();
});


/**
 * Формирует строку из идентификаторов выбранных регионов.
 * Например: 1,7,10,30
 * @returns {string}
 */
function get_checked_regions_query(){
    var query = '';
    $.each($("#menuregions").multiselect("getChecked"), function(){
        if(query){
            query += ',';
        }

        query = query + $(this).val();
    });

    return query;
}

/**
 * Формирует get-строку для индивидуальных фильтров
 * @returns {*}
 */
function get_filter_query(){
    var filters = $(".filtermenu");

    filter = '';
    $.each(filters, function(){
        if(filter){
            filter += '&';
        }
        filter += 'filter['+$(this).attr("name")+']='+$(this).val();
    });

    return filter;
}

/**
 * Формирует get-строку для запроса обновления отчета
 * @returns {string}
 */
function get_query(){

    var query = '';

    type = $(".reporttype .active").data("type");
    datefrom = $("#filter-startdate").val();
    dateto = $("#filter-enddate").val();
    filter = get_filter_query();
    regions = get_checked_regions_query();



    query = filter;
    if(type)
        query += '&type='+type;

    if(datefrom)
        query += '&datefrom='+datefrom;

    if(dateto)
        query += '&dateto='+dateto;

    if(regions)
        query += '&regions='+regions;

    return query;
}

/**
 * Проверяет изменились ли данные фильтра. Необходимо для того, чтобы не делать лишние запросы к серверу.
 * @returns {boolean}
 */
function is_changes(){
    if(datefrom != $("#filter-startdate").val())
        return true;

    if(dateto != $("#filter-enddate").val())
        return true;

    if(regions != get_checked_regions_query())
        return true;

    return filter != get_filter_query();
}

/**
 * Запускает перезагрузку страницы при выборе фильтра
 */
if(Y.all(".filtermenu")){
    Y.delegate("change", function(node){
        var href = window.location.toString();

        href = '/blocks/manage/?_p=report';
        query = get_query();
        if(query){
            href += '&'+query;
        }

        window.location = href;
    }, "#region-main", ".filtermenu");
}

/**
 * Обновляет отчет без перезагрузки страницы. Метод сам определяет данные, которые необходимо передать серверу.
 */
function reload_report(){
    if(is_changes()) {
        $.ajax({
            type: 'POST',
            url: '/blocks/manage/ajax.php?ajc=reload_report',
            data: get_query(),
            success: function (a) {
                a = getJSON(a);
                $(".report-wrapper").html(a.html);
                reload_charts();
            }
        });
    }
}

/**
 * Инициализация графиков
 */
function reload_charts(){
    if($('#report-program-results').length)
        charts_program_results('report-program-results');

    if($('#chart-partner-results').length)
        charts_partner_results('chart-partner-results');

    if($('#chart-tm-results').length)
        charts_tm_results('chart-tm-results');

    if($('#chart-trainer-results').length)
        charts_trainer_results('chart-trainer-results');

    if($("#chart-staffer-results").length)
        charts_staffer_results("chart-staffer-results");

    if($("#chart-indexstudy-results").length)
        charts_indexstudy_results("chart-indexstudy-results");


    if($("#chart-indexquality-results").length)
        charts_indexquality_results("chart-indexquality-results");

    if($("#chart-indexsale-results").length)
        charts_indexsale_results("chart-indexsale-results");

    if($("#chart-staffrotation-results").length)
        charts_indexsale_results("chart-staffrotation-results");
}


/**
 * Граффик "Соотношение обученных/не прошедших/в процессе по программе"
 */
function charts_program_results(id) {
    var options = {
        chart: {
            renderTo: id,
            type: 'spline'
        },
        title: {
            text: 'Результаты'
        },
        series: [
            {
                type: 'pie'
            }
        ],
        tooltip: {
            formatter: function() {
                return this.key + ' - <b>' + this.y + '</b> чел. ('+Number(this.percentage).toFixed(1)+'%)';
            }
        }
    };

    $.getJSON(
        '/blocks/manage/ajax.php?ajc=charts_program_get_results',
        get_query(),
        function (a) {
            if(a && !a.emptytext) {
                options.series[0].data = a;
                var chart = new Highcharts.Chart(options);
            }else{
                $("#"+id).html(a.emptytext);
            }
        }
    );
}

/**
 * Граффик "Соотношение обученных/не обученных"
 */
function charts_partner_results(id) {
    var options = {
        chart: {
            renderTo: id,
            type: 'spline'
        },
        title: {
            text: 'Результаты'
        },
        series: [
            {
                type: 'pie'
            }
        ],
        tooltip: {
            formatter: function() {
                return this.key + ' - <b>' + this.y + '</b> чел. ('+Number(this.percentage).toFixed(1)+'%)';
            }
        }
    };

    $.getJSON(
        '/blocks/manage/ajax.php?ajc=charts_partner_results',
        get_query(),
        function (a) {
            if(a && !a.emptytext) {
                options.series[0].data = a;
                var chart = new Highcharts.Chart(options);
            }else{
                $("#"+id).html(a.emptytext);
            }
        }
    );
}

function charts_tm_results(id){
    $("#chart-tm-results").height("700px");
    var options = {
        chart: {
            renderTo: id,
            type: 'areaspline'
        },
        title: {
            text: 'Обученность персоонала по программам в разрезе ТМ'
        },
        xAxis: {
            categories: [],
            title: {
                text: null
            },
            labels: {
                rotation: 90,
                useHTML: true
            },
            style:{
                lineHeight: '9px',
                width: '150px',
                overflow: 'hidden',
                whiteSpace: 'nowrap'
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: '% обученных',
                align: 'high'
            }
        },
        tooltip: {
            valueSuffix: ' '
        },
        plotOptions: {
            bar: {
                dataLabels: {
                    enabled: true
                }
            },
            column: {
                pointPadding: 0,
                borderWidth: 0,
                groupPadding: 0,
                grouping: false
            }
        },
        series: []
    };

    $.getJSON(
        '/blocks/manage/ajax.php?ajc=charts_tm_results',
        get_query(),
        function (a) {
            if(a && !a.emptytext) {
                options.series = a.data;
                options.xAxis.categories = a.programs;
                var chart = new Highcharts.Chart(options);
            }else{
                $("#"+id).html(a.emptytext);
            }
        }
    );
}


/**
 * Граффик "Соотношение тренингов по количеству обученных"
 */
function charts_trainer_results(id) {
    var options = {
        chart: {
            renderTo: id
        },
        title: {
            text: 'Тренинги по количеству обученных'
        },
        series: [
        {
            type: 'pie'
        }
        ],
        tooltip: {
            formatter: function() {
                return 'Обучено по программе "' + this.key + '" - <b>' + this.y + '</b> чел. ('+Number(this.percentage).toFixed(1)+'%)';
            }
        }
    };

    $.getJSON(
        '/blocks/manage/ajax.php?ajc=charts_trainer_results',
        get_query(),
        function (a) {
            if(a && !a.emptytext) {
                options.series[0].data = a;
                var chart = new Highcharts.Chart(options);
            }else{
                $("#"+id).html(a.emptytext);
            }
        }
    );
}


$(".btn-result-details").on('click', function(){
    var modalbody = $("#stafferinfo-modal .modal-body"),
        activityid = $(this).data("activityid");

    modalbody.html(indicatorbig);

    $.ajax({
        type: "POST",
        url: "/blocks/manage/ajax.php?ajc=get_staffers_results",
        data: get_query() + '&activityid=' + activityid,
        success: function(a){
            a = getJSON(a);
            modalbody.html(a.html);
        }
    });
});


/**
 * Граффик "Соотношение тренингов по количеству обученных"
 */
function charts_staffer_results(id) {
    var options = {
        chart: {
            renderTo: id,
            type: 'pie'
        },
        title: {
            text: 'Соотношение времени по видам обучения'
        },
        series: [
            {
                type: 'pie'
            }
        ],
        tooltip: {
            formatter: function(i) {
                return 'Затраченное время по виду обучения <br> "' + this.key + '" - <b>' + this.point.formatted + '</b> ('+Number(this.percentage).toFixed(1)+'%)';
            }
        }
    };

    $.getJSON(
        '/blocks/manage/ajax.php?ajc=charts_staffer_results',
        get_query(),
        function (a) {
            if(a && !a.emptytext) {
                options.series[0].data = a;
                var chart = new Highcharts.Chart(options);
            }else{
                $("#"+id).html(a.emptytext);
            }
        }
    );
}

function charts_indexstudy_results(id){

    var options = {
        chart: {
            renderTo: id,
            type: 'areaspline'
        },
        title: {
            text: 'Index Study'
        },
        xAxis: {
            categories: [],
            title: {
                text: null
            },
            labels: {
                rotation: 90,
                useHTML: true
            },
            style:{
                lineHeight: '9px',
                width: '150px',
                overflow: 'hidden',
                whiteSpace: 'nowrap'
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: '',
                align: 'high'
            }
        },
        tooltip: {
            valueSuffix: ' '
        },
        plotOptions: {
            bar: {
                dataLabels: {
                    enabled: true
                }
            },
            column: {
                pointPadding: 0,
                borderWidth: 0,
                groupPadding: 0,
                grouping: false
            }
        },
        series: []
    };

    $.getJSON(
        '/blocks/manage/ajax.php?ajc=charts_indexstudy_results',
        get_query(),
        function (a) {
            if(a && !a.emptytext) {
                options.series = a.data;
                options.xAxis.categories = a.periods;
                var chart = new Highcharts.Chart(options);
            }else{
                $("#"+id).html(a.emptytext);
            }
        }
    );
}

function charts_indexquality_results(id){
    var options = {
        chart: {
            renderTo: id,
            type: 'areaspline'
        },
        title: {
            text: 'Index Study'
        },
        xAxis: {
            categories: [],
            title: {
                text: null
            },
            labels: {
                rotation: 90,
                useHTML: true
            },
            style:{
                lineHeight: '9px',
                width: '150px',
                overflow: 'hidden',
                whiteSpace: 'nowrap'
            }
        },
        yAxis: {
            min: 0,
            title: {
                text: '',
                align: 'high'
            }
        },
        tooltip: {
            valueSuffix: ' '
        },
        plotOptions: {
            bar: {
                dataLabels: {
                    enabled: true
                }
            },
            column: {
                pointPadding: 0,
                borderWidth: 0,
                groupPadding: 0,
                grouping: false
            }
        },
        series: []
    };

    $.getJSON(
        '/blocks/manage/ajax.php?ajc=charts_indexquality_results',
        get_query(),
        function (a) {
            if(a && !a.emptytext) {
                options.series = a.data;
                options.xAxis.categories = a.periods;
                var chart = new Highcharts.Chart(options);
            }else{
                $("#"+id).html(a.emptytext);
            }
        }
    );
}

function charts_indexsale_results(id){
    $("#chart-indexsale-results").height("500px");
    var options = {
        chart: {
            renderTo: id,
            type: 'areaspline'
        },
        title: {
            text: 'Index Study'
        },
        xAxis: {
            categories: [],
            title: {
                text: null
            },
            labels: {
                rotation: 90,
                useHTML: true
            },
            style:{
                lineHeight: '9px',
                width: '150px',
                overflow: 'hidden',
                whiteSpace: 'nowrap'
            }
        },
        yAxis: {
            title: {
                text: '',
                align: 'high'
            }
        },
        tooltip: {
            valueSuffix: ' '
        },
        plotOptions: {
            bar: {
                dataLabels: {
                    enabled: true
                }
            },
            column: {
                pointPadding: 0,
                borderWidth: 0,
                groupPadding: 0,
                grouping: false
            }
        },
        series: []
    };

    $.getJSON(
        '/blocks/manage/ajax.php?ajc=charts_indexsale_results',
        get_query(),
        function (a) {
            if(a && !a.emptytext) {
                options.series = a.data;
                options.xAxis.categories = a.periods;
                var chart = new Highcharts.Chart(options);
            }else{
                $("#"+id).html(a.emptytext);
            }
        }
    );
}

/**
 * Календарь для указания периода фильтра
 */
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
            reload_report();
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
        reload_report();
    });
});