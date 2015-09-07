$(document).ready(function(){

    chart = $('.glass').chartBalls({
        data: [],
        xPadding: 30, // горизонтальные отступы от краев
        yPadding: 30, // вертикальные отступы от краев
        cup: {angle: 5, width: 5, color: '#0d316e'}
    });

    $('.info-burn-payment').popover('toggle');
    $('.info-burn-payment').popover('hide');

    $(".info-burn-payment").click(function() {
        $(".popover-content").css("min-height", "40px");

        var imgObj = $("#loadImgSmall"); // заглушка
        $("#loadImgSmall").show();
        // вычислим в какие координаты нужно поместить изображение загрузки, чтобы оно оказалось в серидине страницы:
        position = $(".popover-content").offset();
        // поменяем координаты изображения на нужные:
        var postop = position.top ;
        var posleft = position.left ;
        $(".popover").css("left", posleft + 86);
        $("#loadImgSmall").offset({top: postop + 12, left: posleft + 196});

        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_bank::info_payment_burning',
            data: "",
            success: function (a) {
                a = $.evalJSON(a);
                if (a) {
                    $(".popover-content").html("<div class='burning-info'><ul></ul></div>");
                    $.each(a, function (key, value) {
                        $(".burning-info ul").append("<li><span><b>" + value.balance + "</b> " + value.title + " сгорит 01.02." + value.year + "</span></li>");
                    });
                    $("#loadImgSmall").hide();
                }
            }
        });

        return false;
    });

    $(document).click(function(event) {
        if ( !$(event.target).closest(".popover").length ) {
            $('.info-burn-payment').popover('hide');
            event.stopPropagation();
        }
    });


    $(".filter").on("click", ".period", function(){
        $(".startdate").css({'background':'white'});
        var imgObj = $("#loadImg"); // заглушка
        $("#loadImg").show();
        // вычислим в какие координаты нужно поместить изображение загрузки, чтобы оно оказалось в серидине страницы:
        position = $(".glass").offset();
        // поменяем координаты изображения на нужные:
        var postop = position.top;
        var posleft = position.left;
        var widthSvg = $(".glass svg").width()/2-30;
        var heightSvg = $(".glass svg").height()/2-30;
        $("#loadImg").offset({top: postop + heightSvg, left: posleft + widthSvg});

        var self = $(this);
        var period = self.data("period");
        var date = get_date(period);

        $(".startdate").val(date['start']);
        $(".enddate").val(date['end']);

        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_bank::get_balloons',
            data: "period="+period,
            success: function (a) {
                a = $.evalJSON(a);
                if ( a ) {
                    // перерисовать график при изменении размеров окна
                    $(window).resize(chart.redraw);
                    setTimeout(function() {
                        if ( a.balloons != "NULL" ) {
                            chart.setData(a.balloons);
                        }
                        $("#loadImg").hide();
                    }, 2000);

                    $(".period").removeClass("active");
                    self.addClass("active");
                }
            }
        });
    });

    $.fn.datepicker.defaults.format = "dd.mm.yyyy";
    $.fn.datepicker.defaults.disableTouchKeyboard = true;

    $('.startdate').datepicker().on('show', function(e){
        if ( $(window).height() < 800 ) {
            $(".datepicker").css('top', '171px');
        } else if ( $(window).height() >= 800 ) {
            $(".datepicker").css('top', '192px');
        }
    });
    $(".startdate").on("changeDate", function(event) {
        $('.startdate').datepicker('hide');
        refreshBalls();
    });

    $('.enddate').datepicker().on('show', function(e){
        if ( $(window).height() < 800 ) {
            $(".datepicker").css('top', '171px');
        } else if ( $(window).height() >= 800 ) {
            $(".datepicker").css('top', '192px');
        }
    });
    $(".enddate").on("changeDate", function(event) {
        $('.enddate').datepicker('hide');
        refreshBalls();
    });

   function refreshBalls(){
       var startdate = $(".startdate").val(),
           enddate = $(".enddate").val(),
           datestart_str = startdate.split('.'),
           dateend_str = enddate.split('.');
       var start = new Date(datestart_str[2], parseInt(datestart_str[1], 10) - 1, datestart_str[0]);
       var end = new Date(dateend_str[2], parseInt(dateend_str[1], 10) - 1, dateend_str[0]);

       if ( start.getTime() < end.getTime() ) {
           $(".startdate").css({'background':'white'});
           startdate = datestart_str[2] + "." + datestart_str[1] + "." + datestart_str[0];
           enddate = dateend_str[2] + "." + dateend_str[1] + "." + dateend_str[0];

           var imgObj = $("#loadImg"); // заглушка
           $("#loadImg").show();
           // вычислим в какие координаты нужно поместить изображение загрузки, чтобы оно оказалось в серидине страницы:
           position = $(".glass").offset();
           // поменяем координаты изображения на нужные:
           var postop = position.top;
           var posleft = position.left;
           var widthSvg = $(".glass svg").width()/2-30;
           var heightSvg = $(".glass svg").height()/2-30;
           $("#loadImg").offset({top: postop + widthSvg, left: posleft + widthSvg});

           var self = $(this);
           var period = self.data("period");
           var date = get_date(period);
           $.ajax({
               type: "POST",
               url: '/blocks/manage/?__ajc=lm_bank::get_balloons',
               data: "period=random&startdate="+startdate+"&enddate="+enddate,
               success: function (a) {
                   a = $.evalJSON(a);
                   if (a) {
                       // перерисовать график при изменении размеров окна
                       $(window).resize(chart.redraw);
                       setTimeout(function () {
                           if (a.balloons != "NULL") {
                               chart.setData(a.balloons);
                               $("#loadImg").hide();
                           }
                       }, 500);
                   }
               }
           });
       } else {
           $(".startdate").css({'background':'#FFCACC'});
       }
   }

    $.ajax({
        type: "POST",
        url: '/blocks/manage/?__ajc=lm_bank::get_balloons',
        data: "period=all",
        success: function (a) {
            a = $.evalJSON(a);
            if ( a ) {
                // перерисовать график при изменении размеров окна
                $(window).resize(chart.redraw);
                setTimeout(function(){
                    if ( a.balloons != "NULL" ) {
                        chart.setData(a.balloons);
                    }
                }, 500);

                var date = get_date('all');

                $(".startdate").val(date['start']);
                $(".enddate").val(date['end']);
            }
        }
    });


    $(".glass").on("click", ".ball", function() {

        $(".info .balance").html('');
        $(".info-pay").html('');

        var imgObj = $("#loadImg"); // заглушка

        position = $(".glass").offset();

        var postop = 0;
        var posleft = 0;
        $(".info").offset({top: postop, left: posleft});
        $(".info").hide();
        var position = $(this).offset();
        var payments = $(this).data("id");
        if ( payments ) {
            postop = position.top - 50;
            var positiongoal = $(".glass").offset();
            posleft = positiongoal.left + 450;
            $(".info").offset({top: postop, left: posleft});
            $(".info").show(200);
            $("#loadImg").show();
            $("#loadImg").offset({top: postop + 81, left: posleft + 91});

            $.ajax({
                type: "POST",
                url: '/blocks/manage/?__ajc=lm_bank::info_payment',
                data: "payments="+payments,
                success: function (a) {
                    a = $.evalJSON(a);
                    if ( a ) {
                        var list = "";
                        $.each(a, function (key, value) {
                            var class_amount = 'debit';
                            var amount = '+'+value.amount;
                            if ( value.amount < 0 ) {
                                var class_amount = 'credit';
                                var amount = value.amount;
                            }
                            if (typeof value.comment !== "undefined") {
                                list += "<li><span class='comment'>" + value.comment + "</span><span class='amount " + class_amount + "'>" + amount + "</span></li>";
                            }
                        });
                        $(".info-pay").html(list);

                        $(".info .balance").html(a.balance);
                        $("#loadImg").hide();
                    }
                }
            });
        }
        return false;
    });

    $(".info").on("click", ".close", function(){
        $(".info").offset({top: 0, left: 0});
        $(".info").hide();
    });

    $(document).click(function(event) {
        if ( !$(event.target).closest(".info").length ) {
            $(".info").offset({top: 0, left: 0});
            $(".info").hide();
            event.stopPropagation();
        }
    });


    /*
    * Получить интервал даты в зависимости от периода
    * return startdate & enddate
    **/
    function get_date(period) {
        var d = moment();

        month = d.format('MM');
        year = d.format('YYYY');

        switch ( period ) {
            case 'month':
                startdate = '01.'+month+'.'+year;
                enddate = moment(+year+"-"+month, "YYYY-MM").daysInMonth() +'.'+month+'.'+year;

                break;
            case 'quarter':
                if ( month <=2 ) {
                    startmonth = month - 2 + 12;
                    startyear = year - 1;
                } else {
                    startmonth = month - 2;
                    startyear = year;
                }
                if ( startmonth < 10 ) {
                    startmonth = "0" + startmonth;
                }

                startdate = '01.'+ startmonth + '.' + startyear;
                enddate = moment(+year+"-"+month, "YYYY-MM").daysInMonth() +'.'+  month  +'.'+year;

                break;
            case 'year':
                startyear = year - 1;
                startdate = '01.'+ month + '.' + startyear;
                enddate = moment(+year+"-"+month, "YYYY-MM").daysInMonth() +'.'+  month + '.' + year;

                break;
            case 'all':
                startdate = '01.01.2010';
                enddate = moment(+year+"-"+month, "YYYY-MM").daysInMonth() +'.'+  month + '.' + year;
                break;
        }
        arr = [];
        arr['start'] = startdate;
        arr['end'] = enddate;

        return arr;
    }


    var dateString = '01-01-2010', date,
        dateParts = dateString.split('-');

    date = new Date(dateParts[2], parseInt(dateParts[1], 10) - 1, dateParts[0]);


});


