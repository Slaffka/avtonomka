$(document).ready(function(){

    $('#myModal').modal('hide');

    $(".current-rating").on("click", ".moder", function() {
        var imgObj = $("#loadImg"); // заглушка
        $("#loadImg").show();
        // вычислим в какие координаты нужно поместить изображение загрузки, чтобы оно оказалось в серидине страницы:
        var centerY = $(window).scrollTop() + ($(window).height() + $("#loadImg").height())/2;
        var centerX = $(window).scrollLeft() + ($(window).width() + $("#loadImg").width())/2;
        // поменяем координаты изображения на нужные:
        $("#loadImg").offset({top:centerY, left:centerX});
        metric_id = $(this).data('metricid');
        user_number = $(this).data('usernumber');
        metric_value_id = $(this).data('metric_value_id');
        user_id = $(this).data('userid');

        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=profile::get_params_by_metric',
            data: "metric_id="+metric_id+"&user_number="+user_number+"&metric_value_id="+metric_value_id+"&user_id="+user_id,
            success: function(a) {
                a = $.evalJSON(a);
                $('#myModal').modal('show');
                $("#myModal .modal-body").html(a.text);
                $("#myModal .modal-title").html(a.title);
                $("#loadImg").hide();
            }
        });
        return false;
    });

    $(document).click(function(event) {
        if ($(event.target).closest("#myModal").length) return;
        $("#myModal").modal("hide");
        event.stopPropagation();
    });

    $(".lm-subnav").on("click", ".top", function(){
        $('#myModal').modal('show');
        $("#myModal .modal-body").html('Страница в разработке');
        $("#myModal .modal-title").html('Oops!');
        return false;
    });

    /*$(window).resize(function() {
        height = getPageSize();
        paramheight = getUrlVar('hg');
        if ( typeof paramheight === "undefined") {
            url = location.href+"&hg=" + height;
        } else {
            url = location.href.replace(/(hg=)[^&]+/ig, '$1' + height);
        }
        window.location.href = url;
    });*/



    $('.timepicker').month_picker();



    /*function getPageSize(){
        var xScroll, yScroll;

        if (window.innerHeight && window.scrollMaxY) {
            xScroll = document.body.scrollWidth;
            yScroll = window.innerHeight + window.scrollMaxY;
        } else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
            xScroll = document.body.scrollWidth;
            yScroll = document.body.scrollHeight;
        } else if (document.documentElement && document.documentElement.scrollHeight > document.documentElement.offsetHeight){ // Explorer 6 strict mode
            xScroll = document.documentElement.scrollWidth;
            yScroll = document.documentElement.scrollHeight;
        } else { // Explorer Mac...would also work in Mozilla and Safari
            xScroll = document.body.offsetWidth;
            yScroll = document.body.offsetHeight;
        }

        var windowWidth, windowHeight;
        if (self.innerHeight) { // all except Explorer
            windowWidth = self.innerWidth;
            windowHeight = self.innerHeight;
        } else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
            windowWidth = document.documentElement.clientWidth;
            windowHeight = document.documentElement.clientHeight;
        } else if (document.body) { // other Explorers
            windowWidth = document.body.clientWidth;
            windowHeight = document.body.clientHeight;
        }

        // for small pages with total height less then height of the viewport
        if(yScroll < windowHeight){
            pageHeight = windowHeight;
        } else {
            pageHeight = yScroll;
        }

        // for small pages with total width less then width of the viewport
        if(xScroll < windowWidth){
            pageWidth = windowWidth;
        } else {
            pageWidth = xScroll;
        }
        //return [pageWidth,pageHeight,windowWidth,windowHeight];
        return windowHeight;
    }*/

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

