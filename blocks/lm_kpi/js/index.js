(function ($) {

    if( $(".metric-details").length ) {
        var totalwidth = 0;
        $.each($(".metric-chart"), function () {
            var fact = parseFloat($(this).data("fact")),
                plan = parseFloat($(this).data("plan")),
                predict = parseFloat($(this).data("predict")),
                overflow = predict - plan,
                deficit = 0,
                size = $(this).data("size"),
                title = $(this).data("caption"),
                color = 'critical',
                k = predict / plan;


            if (k >= 1) {
                color = 'success';
            } else if (k >= 0.9 && k < 1) {
                color = 'warning';
            } else {
                color = 'critical';
            }

            size = typeof size == "undefined" ? 140 : 200;

            if (plan < fact) plan = fact;
            if (predict > plan) predict = plan;
            if (overflow < 0) overflow = 0;

            deficit = plan - predict;
            if (!plan && !fact && !predict) deficit = 1;

            var total = fact+(predict - fact)+deficit;
            if(overflow > total) overflow = total;

            if (plan < 0) plan = 0;
            if (fact < 0) fact = 0;
            if (predict < 0) predict = 0;

            donut({
                el: $(this)[0],
                size: size,
                title: title,
                data: [{
                    value: fact,
                    name: 'Факт'
                }, {
                    value: predict - fact,
                    name: 'Прогноз'
                }, {
                    value: deficit,
                    name: 'Нехватка'
                }, {
                    value: overflow,
                    name: 'Превышение'
                }],
                colors: ['rgba(40,96,164, 1)', 'url(#' + color + ')', 'rgba(40,96,164, 0.15)', 'url(#overflow)']
            });

            totalwidth += $(this).parent(".metric-container").outerWidth();
        });
      /*  $(".metrics-list").css({width: totalwidth});*/

        var width = $(".metric-chart").outerWidth() +
            $(".metric-info").outerWidth() +
            $(".metric-history-chart").outerWidth() + 80;

     /*   $(".metric-details").css({width: width});*/
    }

    if( $(".metric-history-chart").length ) {
        $(".metric-history-chart").columnchart({
            data: function () {
                var data = $(".metric-history-chart").data();
                if (data.kpiid) {
                    var data =
                        $.ajax({
                            type: "POST",
                            url: "/blocks/manage/?__ajc=profile::get_kpi_by_month",
                            data: data,
                            async: false
                        }).responseText;

                    return getJSON(data);
                }
                return [];
            }
        });
    }

}(jQuery));