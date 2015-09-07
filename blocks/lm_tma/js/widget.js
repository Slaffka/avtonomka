$(document).ready(function(){

    $.ajax({
        type: "POST",
        url: '/blocks/manage/?__ajc=lm_tma::all_tmas',
        data: "",
        success: function (a) {
            a = $.evalJSON(a);
            if ( a ) {
                var i = 1;
                $.each(a, function (key, value) {
                    if ( i == 1 ) {
                        var active = 'active';
                        var  data = [
                            {href: '', label: value.time_remaining, value: value.timevalue},
                            {href: '', label: value.progress_tma, value: value.progressvalue}
                        ];
                        var chart = $(".wave").chartWave({
                            data: data,
                            min: 0,
                            max: 100,
                            colors: ['#34a010', '#ffcb05'],
                            caption: value.name,
                            captionColor: '#1C377E',
                            total: value.reward,
                            unit: ' монет',
                            yPadding: 0
                        });

                        refreshStyle();
                        $(window).resize(function(){
                            chart.redraw();
                            refreshStyle();
                        });

                    }
                    $("ul.tma_list").append("<li><a href='' class='slider tma_item"+i+"' data-tmaid='"+value.id+"'><span class='tma_item " + active + "' data-count='"+i+"'></span></a></li>");
                    active = '';
                    i++;
                });
                var countli = $(".tma_list li").size();
                refreshArrow(countli);
            }
        }
    });

    $(".block_lm_tma").on("click", ".slider", function() { // клик по круглишочку
        el = $(this);
        refreshTma(el);

        return false;
    });


    $(".block_lm_tma").on("click", ".next", function(){ // клик по стрелке "след слайдер"
        var number = $(".tma_list li span.active").attr('data-count'); // какая лишка сейчас активна
        var nextnumber = parseInt(number) + 1;
        console.log(nextnumber);
        $(".tma_list li a.tma_item"+nextnumber).click();

        return false;
    });

    $(".block_lm_tma").on("click", ".prev", function(){ // клик по стрелке "след слайдер"
        var number = $(".tma_list li span.active").attr('data-count'); // какая лишка сейчас активна
        var nextnumber = parseInt(number) - 1;
        console.log(nextnumber);
        $(".tma_list li a.tma_item"+nextnumber).click();

        return false;
    });

    function refreshArrow(countli){
        var number = $(".tma_list li span.active").attr('data-count');
        var lastnumber = countli;
        if ( number == 1 ) {
            $(".block_lm_tma .prev").addClass('hide');
        } else {
            $(".block_lm_tma .prev").removeClass('hide');
        }
        if ( number == lastnumber ) {
            $(".block_lm_tma .next").addClass('hide');
        } else {
            $(".block_lm_tma .next").removeClass('hide');
        }
    }

    function refreshTma(el) {
        var imgObj = $("#loadImg"); // заглушка
        $("#loadImg").show();
        // вычислим в какие координаты нужно поместить изображение загрузки, чтобы оно оказалось в серидине страницы:
        position = $(".block_lm_tma").offset();
        // поменяем координаты изображения на нужные:
        var postop = position.top + ($(".block_lm_tma").height()/2) - ($("#loadImg").height()/2);
        var posleft = position.left + ($(".block_lm_tma").width()/2) - ($("#loadImg").width()/2) ;
        // поменяем координаты изображения на нужные:
        $("#loadImg").offset({top:postop, left:posleft});

        tmaid = el.attr('data-tmaid');
        $(".tma_list span").removeClass('active');
        el.find("span").addClass('active');
        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_tma::get_data_widget',
            data: "tmaid="+tmaid,
            success: function (a) {
                a = $.evalJSON(a);
                if ( a ) {
                    $(".wave").removeData('chart');

                    var  data = [
                        {href: '', label: a.time_remaining, value: a.timevalue},
                        {href: '', label: a.progress_tma, value: a.progressvalue}
                    ];

                    var chart = $(".wave").chartWave({
                        data: data,
                        min: 0,
                        max: 100,
                        colors: ['#34a010', '#ffcb05'],
                        caption: a.name,
                        captionColor: '#1C377E',
                        total: a.reward,
                        unit: ' монет'

                    });

                    refreshStyle();
                    $(window).resize(function(){
                        chart.redraw();
                        refreshStyle();
                    });

                    $("#loadImg").hide();
                    var countli = $(".tma_list li").size();
                    refreshArrow(countli);
                }
            }
        });
    }

    function refreshStyle(){
        $(".chart-wave-caption").attr({'x': 0, 'text-anchor': 'start', 'y':20});

        var heightBody = $(".block_lm_tma .body").height() / 2;
        $(".wave .chart-wave-total-value").attr({'y': heightBody});

        var heightPig = $(".block_lm_tma .body").height() / 4;
        $(".block_lm_tma .pig").css({'margin-top': heightPig});

        if ( $(".block_lm_tma .body").height() <= 150 ) {
            $(".block_lm_tma .pig").css({'width': 42, 'min-width': 42});
            $(".block_lm_tma .pig").css({'left': '21%'});
            $(".block_lm_tma .chart-wave-total-value").css({'font-size': 20+'px'});
        } else if ( $(".block_lm_tma .body").height() > 150 && $(".block_lm_tma .body").height() < 200 ) {
            $(".block_lm_tma .pig").css({'width': 55, 'min-width': 55});
            $(".block_lm_tma .pig").css({'left': '21%'});
            $(".block_lm_tma .chart-wave-total-value").css({'font-size': 20+'px'});
        } else if ( $(".block_lm_tma .body").height() >= 200 ) {
            console.log(1);
            $(".block_lm_tma .pig").css({'width':70, 'min-width': 70});
            $(".block_lm_tma .pig").css({'left': '21%'});
            $(".block_lm_tma .chart-wave-total-value").css({'font-size': 24+'px'});
        }
    }

});
