$(document).ready(function(){
    $('.spoiler_link').click(function(){
        if ($(this).closest("tr").find('.spoiler_body').css("display")=="none") {
            //$('.spoiler_body').hide('normal');
            $(this).closest("tr").find('.spoiler_body_hide').hide();
            $(this).closest("tr").find('.spoiler_body').show();
            $(this).find("i").removeClass("icon-chevron-down").addClass("icon-chevron-up");
            $(this).closest(".no_money_tma").addClass("money_tma").removeClass("no_money_tma");
            if ( $(this).closest("tr").hasClass('even') ) {
                $(this).closest("td").addClass("money_tma_sprites");
            } else if ( $(this).closest("tr").hasClass('odd') ) {
                $(this).closest("td").addClass("money_tma_white");
            }


            // подгружаем прогресс
            var radius = 80, // set the radius of the circle
                limit = 2 * radius * Math.PI-126;

            // строим круг
            $(this).closest("tr").find('.svg circle').attr('stroke-dasharray', limit + 'px').attr('r', radius + 'px');

            $(this).closest("tr").find('.svg .radial-progress-center').attr('r', (radius - 0.01 + 'px'));
            var data = $(this).closest("tr").find(".svg").data();
            var fact = data.fact;
            var plan = data.plan;
            var percent = fact * 100 / plan;
            var factreal = limit * percent /100;

            $(this).closest("tr").find('.svg .radial-progress-cover').attr('stroke-dashoffset', -factreal); // строим прогресс
        } else {
            $(this).closest("tr").find('.spoiler_body').hide();
            $(this).closest("tr").find('.spoiler_body_hide').show();
            $(this).find("i").removeClass("icon-chevron-up").addClass("icon-chevron-down");
            $(this).closest(".money_tma").addClass("no_money_tma").removeClass("money_tma");
            $(this).closest("td").removeClass("money_tma_sprites").removeClass("money_tma_white");
            $(this).closest("tr").find(".editing").removeClass("active");
        }
        return false;
    });

    $(".meter > span").each(function() {
        $(this)
            .data("origWidth", $(this).width())
            .width(0)
            .animate({
                width: $(this).data("origWidth")
            }, 1500);
    });




});