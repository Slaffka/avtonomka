$(document).ready(function(){

    /* Если нажали на иконку поиск */
    $(".path-lm_settinggoals-today_plan").on("click", ".search-btn", function(){
        var search_str = $("#search_outlet").val();
        var search_param = getUrlVar('search');
        console.log(search_param);
        if ( typeof search_param === "undefined") {
            url = location.href+"&search=" + search_param;
        } else {
            url = location.href.replace(/(search=)[^&]+/ig, '$1' + search_str);
        }
        window.location.href = url;
    });

    /* Задержка при вводе в поле ПОИСК ТТ */
    var delay = (function(){
        var timer = 0;
        return function(callback, ms){
            clearTimeout (timer);
            timer = setTimeout(callback, ms);
        };
    })();

    /* Водим в ПОИСК ТТ чтото */
    $('#search_outlet').keyup(function() {
        var imgObj = $("#loadImg"); // заглушка
        $("#loadImg").show();
        var position = $(".path-lm_settinggoals-today_plan #region-main div table").offset();
        var postop = $(window).height() / 2;
        var posleft = $(window).width()/ 2;
        $("#loadImg").offset({top:postop, left:posleft});
        delay(function(){
            var data = $("#search_outlet").data();
            var str_data = "";
            $.each(data, function(i,elem) {
                str_data += i+"="+elem+"&";
            });
            search_atr = $("#search_outlet").val();

            $.ajax({
                type: "POST",
                url: '/blocks/manage/?__ajc=lm_settinggoals::outlets_list',
                data: str_data+"search="+search_atr,
                success: function(a) {
                    a = $.evalJSON(a);
                    if ( a ) {
                        $(".content-tt-list").html(a);
                    }
                    $("#loadImg").hide();
                }
            });
            return false;
        }, 1200 );
    });

    /* Клик по ТТ - помечаем это */
    $(".path-lm_settinggoals-today_plan").on("click", "td.tt", function(){
        if ( $(this).hasClass('active') ) {
            $(this).removeClass('active');
            $(this).find(".cb_outlet").prop('checked', false).trigger('change');
        } else {
            $(this).addClass('active');
            $(this).find(".cb_outlet").prop('checked', true).trigger('change');
        }
    });

    function getUrlVars(){
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for(var i = 0; i < hashes.length; i++){
            hash = hashes[i].split('=');
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }
        return vars;
    }

    function getUrlVar(name){
        return getUrlVars()[name];
    }

});