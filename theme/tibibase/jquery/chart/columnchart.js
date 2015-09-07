function SVG(tag) {
    return document.createElementNS('http://www.w3.org/2000/svg', tag);
}

$.fn.columnchart = function (options) {
    options = $.extend({
        scroll_x: true,
        trendline: true,
        maxheight: 250,
        data: {}
    }, options);

    this.each(function () {
        var container = $(this);
        var init = function () {
            var svg = $('#mysvg'),
                gseries = $(SVG("g")).attr({class: 'chart', 'clip-path': 'url(#frame)'}).appendTo(svg),
                clip = $(SVG("clipPath")).attr({id: "frame", x: 0, y: 0}).appendTo(svg),
                frame = $(SVG("rect")).appendTo(clip),
                slider = $("<input>"),
                ox = $(SVG("g")).appendTo(gseries),
                oy = $(SVG("g")).appendTo(svg),
                data = options.data(),
                series = data.series,
                xaxis = data.xaxis,
                hasdata = true;

            // Находим максимальное и минимальное значение,
            // чтобы определить масштаб по вертикали
            var max = 0,
                min = false;

            $.each(series, function(){
                $.each(this.data, function (i, v) {
                    v[0] = parseFloat(v[0]);
                    v[1] = parseFloat(v[1]);

                    if (max < v[0]) max = v[0];
                    if (max < v[1]) max = v[1];
                    if (min > v[0] || min === false) min = v[0];
                    if (min > v[1]) min = v[1];
                });
            });


            // Сокращаем разрядность чисел, используя постфиксы Кило, Мега, Гига
            var digits_max = Math.floor(max).toString().length,
                digits_min = Math.floor(min).toString().length,
                digit_capacity_max = Math.floor(digits_max/3),
                digit_capacity_min = Math.floor(digits_min/3),
                digit_postfix = "",
                postfixes = ["K", "M", "G"],
                divider = 1;

            if( digit_capacity_max == 1 && digits_max % 3 == 0) digit_capacity_max = 0;
            if( digits_max == digits_min && digits_max % 3 == 0) digit_capacity_max = digit_capacity_max -1;

            if( digit_capacity_max && typeof postfixes[digit_capacity_max-1] != "undefined" ){
                digit_postfix = postfixes[digit_capacity_max-1];
            }

            divider = Math.pow(10, digit_capacity_max*3);
            min = Math.floor((min/divider) / 10) * 10;
            max = Math.ceil(max/divider + (max/divider) /10 );

            if(!min && !max){
                min = 0;
                max = 10;
                hasdata = false;
            }



            // Строим граффик
            var chart_width = 300, //Ширина графика
                chart_height = 300, //Высота графика
                chart_shiftx = 35, //Смещение графика по горизонтали
                col1_width = 30, //Ширина столбцов графика
                col2_width = col1_width / 2, //Ширина внутренних столбцов графика
                step_width = 20, //Расстояние между столбцами
                series_shift = 45, //Отступ слева для первого столбца
                itemscount = $.count(series[0].data) ? $.count(series[0].data) : 6, //Кол-во столбцов
                ox_width = itemscount * (col1_width + step_width) + chart_shiftx,
                oy_step = (max - min) / 10,
                frame_width = ox_width - chart_width,
                h = options.maxheight / max;

            if (oy_step < 1) {
                oy_step = Math.ceil(oy_step);
            } else {
                oy_step = Math.round(oy_step);
            }

            $.each(series, function() {

                if(this.type == 'column') {
                    var x1 = 0, x2 = 0, y1 = 0, y2 = 0, h1 = 0, h2 = 0, percent = 0, style = '';
                    if (itemscount && hasdata) {
                        $.each(this.data, function (i, v) {
                            x1 = col1_width * i +  step_width * i + series_shift;
                            x2 = x1 + (col1_width - col2_width) / 2;

                            if (v[0]) {
                                percent = Math.floor(v[1] / v[0] * 100);
                                style = 'font-size:12px;';
                                $(SVG("text")).attr({
                                    x: x1, y: 10, style: style, fill: "#01416a"
                                }).text(percent + '%').appendTo(gseries);
                            }

                            h1 = v[0] / divider * h;
                            h2 = v[1] / divider * h;
                            y1 = options.maxheight - h1;
                            y2 = options.maxheight - h2;

                            $(SVG("rect")).attr({
                                x: x1, y: y1, width: col1_width, height: h1, fill: "rgba(40,96,164, 0.2)", rx: 0, ry: 0
                            }).data(v).appendTo(gseries);

                            $(SVG("rect")).attr({
                                x: x2, y: y2, width: col2_width, height: h2, fill: "rgba(40,96,164, 1)", rx: 0, ry: 0
                            }).data(v).appendTo(gseries);

                        });
                    } else {
                        style = 'font-size:18px; ';
                        $(SVG("text")).attr({
                            x: x1 + chart_width / 2,
                            y: 125,
                            fill: "#01416a",
                            style: style
                        }).text("Нет данных").appendTo(svg);
                    }

                }else if(this.type == 'line') {
                    style = 'stroke:rgb(0,0,0);stroke-width:1px';
                    for(var i=0; i < this.data.length-1; i++) {
                        x1 = col1_width * this.data[i][0] +  step_width * this.data[i][0] + series_shift;
                        x2 = col1_width * (this.data[i+1][0] + 1) +  step_width * this.data[i+1][0] + series_shift;

                        h1 = this.data[i][1] / divider * h;
                        h2 = this.data[i+1][1] / divider * h;
                        y1 = options.maxheight - h1;
                        y2 = options.maxheight - h2;

                        $(SVG("line")).attr({
                            x1:x1, y1:y1, x2:x2, y2:y2, style: style
                        }).appendTo(gseries);
                    }
                }
            });


            // Ось оординат
            d = "M " + chart_shiftx + " 0 L " + chart_shiftx + " " + max * h;
            $(SVG("path")).attr({fill: "none", d: d, stroke: "#01416a", "stroke-width": "1", opacity: "1"}).appendTo(oy);

            // Метки и подписи на оси оординат
            var y_axis_count = (max - min) / oy_step,
                y_step_h = options.maxheight / y_axis_count,
                y_series_offset = 10;

            for (i = 0; i <= y_axis_count; i++) {

                d = "M " + (chart_shiftx - 5) + " " + i * y_step_h + " L " + chart_shiftx + " " + i * y_step_h;
                $(SVG("path")).attr({
                    fill: "none", d: d, stroke: "#01416a", "stroke-width": "1", opacity: "1"
                }).appendTo(oy);

                style = 'font-size:11px;';
                if(i > 0) y_series_offset=5;
                $(SVG("text")).attr({x: 0, y: i * y_step_h + y_series_offset, style: style, fill:"#01416a"})
                    .text( (max - i * oy_step) + digit_postfix).appendTo(oy);
            }


            // Ось абcцисс
            if(itemscount) {
                d = "M " + chart_shiftx + " " + max * h + " L " + ox_width + " " + max * h;
                $(SVG("path")).attr({fill: "none", d: d, stroke: "#01416a", "stroke-width": "1", opacity: "1"}).appendTo(ox);

                // Метки на оси абцисс
                for (var i = 0; i < itemscount + 1; i++) {
                    start = i * (col1_width + step_width) + chart_shiftx;

                    d = "M " + start + " " + max * h + " L " + start + " " + (max * h + 5);
                    $(SVG("path")).attr({
                        fill: "none", d: d, stroke: "#01416a", "stroke-width": "1", opacity: "1"
                    }).appendTo(ox);
                }

                // Подписи для оси абцисс
                for (i=0; i < xaxis.length; i++) {
                    start = i * (col1_width + step_width) + chart_shiftx + 15;
                    style = 'font-size:10px;';
                    $(SVG("text")).attr({x: start, y: max * h + 12, style: style, fill:"#01416a"}).text(xaxis[i]).appendTo(ox);
                }
            }else{
                d = "M " + chart_shiftx + " " + max * h + " L " + 300 + " " + max * h;
                $(SVG("path")).attr({fill: "none", d: d, stroke: "#01416a", "stroke-width": "1", opacity: "1"}).appendTo(svg);
            }


            /*$(".mysvg-left").on("click", function () {
                var x = parseInt($("#frame rect").attr("x"));
                if (x < 250 + 30) {
                    console.log("left " + ":" + x);
                    $("#frame rect").attr("x", x + 10);
                    $(".chart").attr("transform", 'translate(' + (-(x + 10 - 30)) + ')');
                }
            });

            $(".mysvg-right").on("click", function () {
                var x = parseInt($("#frame rect").attr("x"));
                if (x - 30 > 0) {
                    console.log("right " + ":" + x);
                    $("#frame rect").attr("x", x - 10);
                    $(".chart").attr("transform", 'translate(' + (-(x - 10 - 30)) + ')');
                }
            });*/

            frame.attr({x: frame_width, y: 0, width: chart_width, height: chart_height});

            if(ox_width-chart_shiftx > chart_width) {
                gseries.attr('transform', 'translate('+ (-(frame_width - chart_shiftx)) +')');

                slider.attr({
                    type: 'range', max: frame_width, min: chart_shiftx, step: 10, style: 'display: none'
                }).appendTo(container);

                slider.rangeinput({
                    onSlide: function (ev, step) {
                        frame.attr({x: step});
                        gseries.attr("transform", 'translate(' + (-(step - chart_shiftx)) + ')');

                    },
                    // Начальное значение
                    value: frame_width,

                    change: function (e, i) {
                        frame.attr({x: i}, "fast");
                        gseries.attr("transform", 'translate(' + (-(i - chart_shiftx)) + ')');
                    },

                    // Отключить анимацию ползунка
                    speed: 0
                });
            }
        };

        init();
    });
};