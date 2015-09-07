$(document).ready(function(){
    align_grid();

    $(window).resize(function(){
        //if(!print_mode) $("body").css({width:"auto"});
        //print_mode = false;

        align_grid();
    });

    /*$(".btn-print").click(function(){
        $("body").css({width:1100});

        print_mode = true;
        $(window).resize();
        window.print();

        return false;
    });*/

    function align_grid() {
        return false;
        if ($(".block").length < 3) {
            return false;
        }

        //var h = $("#bt-menu").height() + $("header.navbar").height() + $(".lm-profile-nav").height() + 10;
        var h = 100;
        var page_h = $(window).height() - h;
        var blocks_h = 200 + 200 + 120;

        if (blocks_h < page_h ) {
            var block_h = Math.ceil(page_h / 3),
                blocks = $(".pagelayout-grid .gridcol1  > div").css({height: block_h}),
                css = {height: (block_h / 1.3)};

            if( $(".path-manage_profile-index").length ) {
                console.log(blocks.eq(7));
                blocks.eq(7).css(css);
                blocks.eq(8).css(css);
                blocks.eq(9).css(css);
            }

            if( $(".path-manage_profile-evolution").length || $(".path-manage_profile-calendar").length) {
                blocks.eq(6).css(css);
                blocks.eq(7).css(css);
                blocks.eq(8).css(css);
            }
        }



        $.each($(".score"), function () {
            var score = $(this).data("score");
            $(this).css("width", score + "%");
            if (score < 40) {
                $(this).css("color", "#ff4040");
            }
        });

        return true;
    }
});

