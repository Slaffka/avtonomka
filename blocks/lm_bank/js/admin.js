$(document).ready(function(){
    /*$(document).click(function(event) {
        console.log(event);
        if ($(event.target).closest(".period-month").length) return;
        $(".period-month").addClass("hide");
        event.stopPropagation();
    });*/

    /*$('.lm-subnav-stat-payment .search_user').click(function(){
        var postop = 0;
        var posleft = 0;
        var position = $(this).offset();
        postop = position.top - 50;
        posleft = position.left + 70;
        $("#page-lm-bank-index .picker--opened .picker__holder").offset({top: postop, left: posleft});
        console.log(position);
    });*/

    $('.lm-subnav-stat-payment .search_user').modalpicker({
        pickerlist: '/blocks/manage/?__ajc=base::userpicker_list',
        onpick: function (a, id) {
            location.href = '/blocks/manage/?_p=lm_bank&userid='+id
        }
    });

    if( $("#statistics_diagram").length ) {
        $("#statistics_diagram").columnchart({
            data: function () {
                var stat = $("#statistics_diagram").attr('data-stat');
                if ( stat ) {
                    var data =
                        $.ajax({
                            type: "POST",
                            url: "/blocks/manage/?__ajc=lm_bank::data_diagrams",
                            data: {stat: stat},
                            async: false
                        }).responseText;
                    return getJSON(data);
                }
                return [];
            }
        });
    }

   /* $("#statistics").on("click", "rect", function(){
        console.log(1);
    });*/

    /**
     * Клик по диаграмме - открываем пирог со статой
     */
/*    $(".all-statistics").on("click", ".sum-amount", function(){*/
    $("#statistics_diagram").on("click", "rect", function(){

        var self = this;
        self.$ = self.$ || $(self);
        var data = self.$.data();
        var month = data[2];
        var year  = data[3];
        var whereamount = data[4];

        //self.$.attr("fill", "rgb(19, 118, 3) ");
        $("#mysvg rect").css("fill", "#134c9b");
        self.$.css("fill", "rgb(19, 118, 3) ");

        var imgObj = $("#loadImg"); // заглушка
        $("#loadImg").show();
        var position = $("#channels").offset();
        var postop = position.top + 140;
        var posleft = position.left + 250;
        $("#loadImg").offset({top:postop, left:posleft});

        // Чистим таблицу от старых данных
        $(".lm-bank .name_channel").html('');
        $(".stats").html('');

        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_bank::get_data_for_month',
            data: "month="+month+"&year="+year+"&whereamount="+whereamount,
            success: function (a) {
                a = $.evalJSON(a);
                if ( a ) {
                   /* $.each(a.data, function (key, value) {
                        $(".channels").append("<li class = 'channel' data-channelid='" + value.label + "' data-month='" + month + "' data-year='" + year + "' data-wamount = '" + whereamount + "'>блок - " + value.label + ": " + value.value + "</li>");
                    });
                    console.log(a.data);*/

                    var chart = $('#channels').removeData('chart').chartPie({
                        data: a.data,
                        caption: a.month,
                        captionColor: '#1C377E',
                        colors: ['#3cc011', '#ffcb05', '#ff353d', '#6190D6', '#7cb5ec', '#90ed7d', '#f7a35c', '#8085e9', '#f15c80', '#2b908f', '#f45b5b', '#91e8e1']
                    });
                    $(window).resize(chart.redraw);
                    $("#loadImg").hide();
                }
            }
        });
        return false;
    });

    /**
     * Клик по секции в пироге - открываем таблицу со статой
     */
    $("#channels").on("click", ".sector", function () {
    //$(".all-statistics").on("click", ".channel", function(){
        var data = $(this).data();
        var channel = data.label + ": " + data.value;

        var imgObj = $("#loadImg"); // заглушка
        $("#loadImg").show();
        var position = $(".stats").offset();
        var postop = position.top;
        $("#loadImg").css({top:postop, left:'50%'});

        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_bank::statistics_for_channel',
            data: data,
            success: function (a) {
                if ( a ) {
                    $(".lm-bank .name_channel").html(channel);
                    $(".stats").html(a);
                    $("#loadImg").hide();
                }
            }
        });
    });


    /**
     * Переключалка вкладок
     */
    $('#tabs a[href="'+window.location.hash+'"]').tab('show');
    $('#tabs a').click(function (e) {
        e.preventDefault();
        $(this).tab('show');
        window.location.hash = $(this).attr('href');
    });

    /**
     * Открываем модальное окно для начисления монет
     */
    $(".lm-bank").on("click", ".debit", function(){
        var title = $(this).html();
        var userid = $(this).attr('data-userid');

        $("#operation_coins").modal('show');
        $('#operation_coins').removeClass('hide');
        $("#operation_coins .modal-header h5").html(title);

        $("#operation_coins .modal-body .body").css("min-height", "30px");
        $("#operation_coins .modal-body #loadImg").show();

        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_bank::add_coins',
            data: "userid="+userid,
            success: function (a) {
                if ( a ) {
                    $("#operation_coins .modal-body #loadImg").hide();
                    $("#operation_coins .modal-body .body").html(a);
                }
            },
            fail: function(){
                $("#operation_coins").modal('hide');
                $('#operation_coins').addClass('hide');
            }
        });
        return false;
    });

    /**
     * Открываем модальное окно для списания монет
     */
    $(".lm-bank").on("click", ".credit", function(){
        var title = $(this).html();
        var userid = $(this).attr('data-userid');

        $("#operation_coins").modal('show');
        $('#operation_coins').removeClass('hide');
        $("#operation_coins .modal-header h5").html(title);

        $("#operation_coins .modal-body .body").css("min-height", "30px");
        $("#operation_coins .modal-body #loadImg").show();

        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_bank::take_coins',
            data: "userid="+userid,
            success: function (a) {
                if ( a ) {
                    $("#operation_coins .modal-body #loadImg").hide();
                    $("#operation_coins .modal-body .body").html(a);
                }
            },
            fail: function(){
                $("#operation_coins").modal('hide');
                $('#operation_coins').addClass('hide');
            }
        });
        return false;
    });

    /**
     * Добавить монет юзеру
     */
    $("#operation_coins").on("click", ".add_coins", function(){
        var btn = $(this);
        btn.button('loading');
        var money    = $("#operation_coins .money").val();
        if ( !money ) {
            $("#operation_coins .money").closest("div").addClass('error');
            $("#operation_coins .alert").removeClass('alert-success')
                .removeClass('hide')
                .addClass("alert-error")
                .html('Укажите сколько монет добавить пользователю');
            btn.button('reset');
            return false;
        }

        var channel  = $("#operation_coins .channel").val();
        if ( channel == 'None' ) {
            $("#operation_coins .money").closest("div").removeClass('error').addClass('success');
            $("#operation_coins .channel").closest("div").addClass('error');
            $("#operation_coins .alert").removeClass('alert-success')
                .removeClass('hide')
                .addClass("alert-error")
                .html('Не выбран канал зачисления');
            btn.button('reset');

            return false;
        }

        var instance = $("#operation_coins .search-instance").attr('data-instanceid');
        if ( !$("#operation_coins .search-instance").hasClass('hide') && instance === undefined ) {
            $("#operation_coins .channel").closest("div").removeClass('error').addClass('success');
            $("#operation_coins .alert").removeClass('alert-success')
                .removeClass('hide')
                .addClass("alert-error")
                .html('Вы не выбрали '+$("#operation_coins .search-instance span.text").text());
            btn.button('reset');
            $("#operation_coins .search-instance").addClass('btn-danger');
            return false;
        }

        var comment  = $("#operation_coins .comment").val();
        var userid   = $(this).attr("data-userid");
        $("#operation_coins .search-instance").removeClass('btn-danger');

        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_bank::debit',
            data: "channel="+channel+"&money="+money+"&comment="+comment+"&userid="+userid+"&instance="+instance,
            success: function (a) {
                a = $.evalJSON(a);
                btn.button('reset');
                if ( a.error ) {
                    $("#operation_coins .alert").removeClass('alert-success').removeClass('hide').addClass("alert-error").html(a.text);
                } else {
                    $("#operation_coins .alert").removeClass('alert-error').removeClass('hide').addClass("alert-success").html(a.text);
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                }
            }
        });
        return false;
    });

    /**
     * Отнять монет юзеру
     */
    $("#operation_coins").on("click", ".take_coins", function(){
        var btn = $(this);
        btn.button('loading');

        var money    = $("#operation_coins .money").val();
        if ( !money ) {
            $("#operation_coins .money").closest("div").addClass('error');
            $("#operation_coins .alert").removeClass('alert-success')
                .removeClass('hide')
                .addClass("alert-error")
                .html('Укажите сколько монет снять у пользователя');
            btn.button('reset');
            return false;
        }

        var channel  = $("#operation_coins .channel").val();
        if ( channel == 'None' ) {
            $("#operation_coins .money").closest("div").removeClass('error').addClass('success');
            $("#operation_coins .channel").closest("div").addClass('error');
            $("#operation_coins .alert").removeClass('alert-success')
                .removeClass('hide')
                .addClass("alert-error")
                .html('Не выбран канал списания');
            btn.button('reset');

            return false;
        }

        var instance = $("#operation_coins .search-instance").attr('data-instanceid');
        if ( !$("#operation_coins .search-instance").hasClass('hide') && instance === undefined ) {
            $("#operation_coins .channel").closest("div").removeClass('error').addClass('success');
            $("#operation_coins .alert").removeClass('alert-success')
                .removeClass('hide')
                .addClass("alert-error")
                .html('Вы не выбрали '+$("#operation_coins .search-instance span.text").text());
            btn.button('reset');
            $("#operation_coins .search-instance").addClass('btn-danger');
            return false;
        }

        var comment  = $("#operation_coins .comment").val();
        var userid   = $(this).attr("data-userid");
        $("#operation_coins .search-instance").removeClass('btn-danger');

        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_bank::credit',
            data: "channel="+channel+"&money="+money+"&comment="+comment+"&userid="+userid+"&instance="+instance,
            success: function (a) {
                a = $.evalJSON(a);
                btn.button('reset');
                if ( a.error ) {
                    $("#operation_coins .alert").removeClass('alert-success').removeClass('hide').addClass("alert-error").html(a.text);
                } else {
                    $("#operation_coins .alert").removeClass('alert-error').removeClass('hide').addClass("alert-success").html(a.text);
                    setTimeout(function() {
                        location.reload();
                    }, 500);
                }
            }
        });
        return false;
    });

    /**
     * Выбор канала и инстанса к каналу при операции добавления / списания денег со счета юзера
     */
    $(".lm-bank").on("change", ".channel", function() {
        var imgObj = $("#loadImg"); // заглушка
        $("#loadImg").show();
        var centerY = $(window).scrollTop() + ($(window).height() + $("#loadImg").height())/2;
        var centerX = $(window).scrollLeft() + ($(window).width() + $("#loadImg").width())/2;
        $("#loadImg").offset({top:centerY, left:centerX});

        var btn = $(this);
        var channel = btn.val();
        var userid  = $(".search-instance").attr('data-userid');
        btn.closest(".operation_money").find(".search-instance").addClass("hide");
        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_bank::get_instance',
            data: "channel="+channel+"&userid="+userid,
            success: function (a) {
                a = $.evalJSON(a);
                if ( !a.error ) {
                    $(".picker-instance").modalpicker({
                        onpick: function (a, id) {
                            $(".search-instance").attr('data-instanceid', id);
                            $(".search-instance .instance").html(" - "+id);
                        }
                    });
                    $(".search-instance").removeClass("hide")
                        .attr('href', '/blocks/manage/?__ajc=lm_bank::get_list_instance&code='+channel+"&userid="+userid)
                        .html("Выбрать <span class='text'>"+ a.text+"</span> <span class='instance'></span>");
                    $(".search-instance").click();
                }
                $("#loadImg").hide();
            }
        });
    });

    /**
     * Обработчик события при клике вне модального окна - закрывает модальное окно
     */
    $(document).click(function(event) {
        if ($(event.target).closest("#operation_coins").length || $(event.target).closest(".picker").length) return;
        $("#operation_coins").modal("hide");
        $("#operation_coins .body").html('');
        event.stopPropagation();
    });

    $(".lm-subnav-stat-payment").on("click", ".testdate", function(){
        var imgObj = $("#loadImg"); // заглушка
        $("#loadImg").show();
        var centerY = $(window).scrollTop() + ($(window).height() + $("#loadImg").height())/2;
        var centerX = $(window).scrollLeft() + ($(window).width() + $("#loadImg").width())/2;
        $("#loadImg").offset({top:centerY, left:centerX});
        var type = $("#statistics_diagram").data('stat');
        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_bank::testdate',
            data: "type="+type,
            success: function (a) {
                if ( a ) {
                    alert('Данные успешно сгенерированы!');
                    var url = window.location.href;
                    window.location.href = url;
                }
                $("#loadImg").hide();
            }
        });
        return false;
    });

});


