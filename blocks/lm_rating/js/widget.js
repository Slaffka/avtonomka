$(document).ready(function(){

    setTimeout(lm_rating_align);

    $(window).resize(lm_rating_align).on("lm.print", lm_rating_align);

    var data = getJSON(
        $.ajax({
            type: "POST",
            url: "/blocks/manage/?__ajc=lm_rating::get_rating_by_month",
            async: false
        }).responseText
    );

    if(data) {
        var chart = $('#my-rating-graff').chartLine({
            data: data,
            min: 0,
            max: 4,
            width: 3,
            opacity: 0.25,
            xAxis: {labels: true},
            colors: [
                {threshold: 0,   color: '#29527A'},
                {threshold: 0.1, color: '#FD0801'},
                {threshold: 1,   color: '#E5690A'},
                {threshold: 2,   color: '#dddd00'},
                {threshold: 3,   color: '#99B957'}
            ]
        });
    }
    function lm_rating_align() {
        var block = $(".block_lm_rating"),
            blockwidth = block.outerWidth()-20,
            blockheight = block.outerHeight()-80;

        $("#my-rating-graff").css({width:blockwidth, height:blockheight});

        if (chart) chart.redraw();
    }

});