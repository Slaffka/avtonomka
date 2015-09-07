$(document).ready(function(){
    align_grid();
    lmsubnav_responsive();

    $(window).resize(function(){
        //if(!print_mode) $("body").css({width:"auto"});
        //print_mode = false;

        align_grid();
        lmsubnav_responsive();
    });

    /*$(".btn-print").click(function(){
        $("body").css({width:1100});

        print_mode = true;
        $(window).resize();
        window.print();

        return false;
    });*/

    /**
     * Адаптирует главное меню (lm_subnav) под текущие размеры окна браузера.
     * Скрывает непомещающиеся пункты главного меню  в кнопку "еще" или наоборот вынемает их, если есть место
     */
    function lmsubnav_responsive(){
        var container = $("header .lm-subnav"),  // Контейнер в котором находится меню и кнопка назад
            menu = $("header .lm-subnav-items"), // Контейнер в котором находится меню
            back = $("header .lm-subnav-back"),  // Контейнер кнопки "назад"
            items = $("header .lm-subnav-items > .item"), // Пункты меню (кроме кнопки "еще")
            itemmore = $("header .lm-subnav .item-more"), // Кнопка "еще"
            dropdown = menu.find(".item-more .dropdown-menu"),// Меню, которое открывается при нажатии на "еще"
            dropdownitems = dropdown.find(".item"); // Пункты всплывающего меню

        if( container.width() ) {
            var n = 1;
            // Достаем спрятанные пункты меню до тех пор, пока есть место для них
            while ( dropdown.find(".item").length && container.width() > menu.width() + back.width() ) {
                if ( !dropdownitems.length ) break;
                dropdownitems.filter(":first").remove().insertBefore(itemmore);
                dropdownitems = dropdown.find(".item");
                if( !dropdownitems.length ) itemmore.css({display:"none"});
            }

            n = 1;
            // Прячем пункты меню до тех пор, пока меню не станет помещаться в контейнер
            while (container.width() < menu.width() + back.width()) {
                items = $("header .lm-subnav-items > .item");
                if ( !items.length ) break; // Подстраховка, если дела плохи :)
                items.filter(":last").remove().prependTo(dropdown);
                itemmore.css({display:"inline-block"});
                n++;
            }

           console.log(container.width(), menu.width() + back.width());
        }
    }

    function align_grid() {

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

