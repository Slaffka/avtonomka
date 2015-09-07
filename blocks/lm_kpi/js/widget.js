$(document).ready(function(){
    lm_kpi_align();

    $(window).on("lm.print", function(){
        lm_kpi_align();
    });

    $(window).resize(function(){
        lm_kpi_align();
    });


    // TODO: Допилить, нужны не только размеры полушайб, но и их контейнеров и отступов (чтобы все было по центру)
    function calculate_sizes(count, w, h, sizes){
        var size = 0;

        if( count <= 1 ) {
            size = w > h ? w : h;
            if (w > 2 * h) size = h * 2;
            if (h > 2 * w) size = w * 2;

            // Уменьшаем размер на величину margin
            size = size - 24;
        }else if( count <= 2 ){
            if( w > h ){
                w = w/2;
            }else if( h > w ){
                h = h/2;
            }
            size = calculate_sizes(1, w, h);
        }else if( count % 2 ){
            size = [calculate_sizes(1, w, h*0.45, sizes),
                    calculate_sizes(count-1, w, h*0.55, sizes)];
        }else{
            // Количество кратно двум, поэтому можем расположить элементы равномерно,
            var countpersection = 2;
            var sectionscount = count/countpersection;

            if( w > h ){
                w = w/sectionscount;
            }else if(h > w ){
                h = h/sectionscount;
            }

            size = calculate_sizes(sectionscount, w, h, sizes);
        }

        return size;
    }


    function lm_kpi_align(){

        var charts = $(".block .metric-chart"),
            chartcount = charts.length,
            block = $(".block_lm_kpi"),
            blockwidth = block.outerWidth()-20,
            blockheight = block.outerHeight()-46;
        var size = calculate_sizes(chartcount, blockwidth, blockheight);
        var sizes = [];

        if(size instanceof Array){
            sizes.push( size.shift() );
            size = size.shift();
            for(var i=1; i <= chartcount-1; i++) sizes.push( size );
        }else{
            for(var i=1; i <= chartcount; i++) sizes.push( size );
        }

        charts.html('').css({padding:0});

        var n = 1,
            items = [];
        $.each( charts, function () {
            var fact = parseFloat($(this).data("fact")),
                plan = parseFloat($(this).data("plan")),
                predict = parseFloat($(this).data("predict")),
                overflow = predict - plan,
                deficit = 0,
                size = sizes[n-1],
                title = $(this).data("caption"),
                datalbl = plan && predict ? Math.ceil(fact*100/plan) + " из " + Math.ceil(predict*100/plan) + "%": "0 из 0%";


            var color = 'critical',
                k = predict / plan;
            if (k >= 1) {
                color = 'success';
            } else if (k >= 0.9 && k < 1) {
                color = 'warning';
            }

            if (plan < fact) plan = fact;
            if (predict > plan) predict = plan;
            if (overflow < 0) overflow = 0;
            deficit = plan - predict;
            if (!plan && !fact && !predict) deficit = 1;
            var total = fact+(predict - fact)+deficit;
            if(overflow > total) overflow = total;

            items.push({
                el:$(this)[0], fact:fact, predict:predict-fact, deficit:deficit,
                overflow:overflow, predictcolor:color, title:title, datalbl:datalbl, size:size
            });
            n++;
        });

        $.each( items, function() {
            donut({
                el: this.el,
                size: this.size,
                title: this.title,
                type: "semi-circle",
                datalbl: this.datalbl,
                data: [{
                    value: this.fact,
                    name: 'Факт'
                }, {
                    value: this.predict,
                    name: 'Прогноз'
                }, {
                    value: this.deficit,
                    name: 'Всего'
                }, {
                    value: this.overflow,
                    name: 'Превышение'
                }],
                colors: ['rgba(40,96,164, 1)', 'url(#' + this.predictcolor + ')', 'rgba(40,96,164, 0.15)', 'url(#overflow)']
            });
        });
    }
});