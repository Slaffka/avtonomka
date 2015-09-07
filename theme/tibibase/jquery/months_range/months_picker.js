(function( $ ) {
    // значение по умолчанию
    var defaults = {
        range:
            '<div class = "period-month text-center " style="display: none;">'+
                '<div class="input-append">'+
                    '<select style="" name="select_startweek" class="startmonth">'+
                        '<option value="1">Январь</option>'+
                        '<option value="2">Февраль</option>'+
                        '<option value="3">Март</option>'+
                        '<option value="4">Апрель</option>'+
                        '<option value="5">Май</option>'+
                        '<option value="6">Июнь</option>'+
                        '<option value="7">Июль</option>'+
                        '<option value="8">Август</option>'+
                        '<option value="9">Сентябрь</option>'+
                        '<option value="10">Октябрь</option>'+
                        '<option value="11">Ноябрь</option>'+
                        '<option value="12">Декабрь</option>'+
                    '</select>'+
                '</div>'+
                '<div class="input-prepend" style="">'+
                    '<div class="btn-group">'+
                        '<select style="" name="start_year" class="startyear">'+
                            '<option>2012</option>'+
                            '<option>2013</option>'+
                            '<option>2014</option>'+
                            '<option selected>2015</option>'+
                            '<option>2016</option>'+
                            '<option>2017</option>'+
                        '</select>'+
                    '</div>'+
                '</div>'+
                '<span class="mdash">&mdash;</span>'+
                '<div class="input-append">'+
                    '<select style="" name="select_startweek" class="endmonth">'+
                        '<option value="1">Январь</option>'+
                        '<option value="2">Февраль</option>'+
                        '<option value="3">Март</option>'+
                        '<option value="4">Апрель</option>'+
                        '<option value="5">Май</option>'+
                        '<option value="6">Июнь</option>'+
                        '<option value="7">Июль</option>'+
                        '<option value="8">Август</option>'+
                        '<option value="9">Сентябрь</option>'+
                        '<option value="10">Октябрь</option>'+
                        '<option value="11">Ноябрь</option>'+
                        '<option value="12">Декабрь</option>'+
                    '</select>'+
                '</div>'+
                '<div class="input-prepend" style="">'+
                    '<div class="btn-group">'+
                        '<select style="" name="end_year" class="endyear">'+
                            '<option>2012</option>'+
                            '<option>2013</option>'+
                            '<option>2014</option>'+
                            '<option selected>2015</option>'+
                            '<option>2016</option>'+
                            '<option>2017</option>'+
                        '</select>'+
                    '</div>'+
                '</div>'+
            '</div>',
        form:
            '<div class="period">'+
                '<input id="range_month" class="range_month" type="text" placeholder="С месяца - По месяц">'+
            '</div>'+
            '<div style="float: left" class="month_picker">'+
                '<div class="btn btn-period">Показать</div>'+
            '</div>'
    };

    // наши публичные методы
    var methods = {
        // инициализация плагина
        init: function(params) {
            var options = $.extend({
                getUrlVars: function(){
                    var vars = [], hash;
                    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
                    for(var i = 0; i < hashes.length; i++){
                        hash = hashes[i].split('=');
                        vars.push(hash[0]);
                        vars[hash[0]] = hash[1];
                    }
                    return vars;
                },
                getUrlVar: function(name){
                        return $.getUrlVars()[name];
                }},
            defaults, params);

            var cur_date = $.getUrlVar('date');
            // надо заменить сущности на html код
            $(this).on("click", ".range_month", function(){
                if ( $(".period-month").css('display') == 'block' ) {
                    $(".period-month").hide(200);
                } else {
                    $(".period-month").remove();
                    $(".period").append(options.range);
                    $(".period-month").show(200);
                }

            });

            $(this).on("click", ".btn-period", function(){
                range_month = $(".range_month").val();
                if ( range_month ) {
                    $(".period-month").hide("slow");
                    href = location.href;
                    if (typeof cur_date === "undefined") {
                        value = href + "&date=" + range_month;
                    } else {
                        value = href.replace(/(date=)[^&]+/ig, '$1' + range_month);
                    }
                    window.location.replace(value);
                } else {
                    alert('Не выбран период');
                }
            });

            $(this).html(options.form);

            $(".timepicker").on("change", ".startmonth", function() {
                startmonth = $(".startmonth").val();
                if ( startmonth < 10 ) {
                    startmonth = '0' + $(".startmonth").val();
                }
                date = startmonth + '/' + $(".startyear").val();
                $(".range_month").val(date);
            });

            $(".timepicker").on("change", ".startyear", function() {
                startmonth = $(".startmonth").val();
                if ( startmonth < 10 ) {
                    startmonth = '0' + $(".startmonth").val();
                }
                date = startmonth + '/' + $(".startyear").val();
                $(".range_month").val(date);
            });

            $(".timepicker").on("change", ".endmonth", function() {
                startmonth = $(".startmonth").val();
                if ( startmonth < 10 ) {
                    startmonth = '0' + $(".startmonth").val();
                }
                endmonth = $(".endmonth").val();
                if ( endmonth < 10 ) {
                    endmonth = '0' + $(".endmonth").val();
                }

                date = startmonth + '/' + $(".startyear").val() + ' - ' + endmonth + '/' + $(".endyear").val();
                $(".range_month").val(date);
            });

            $(".timepicker").on("change", ".endyear", function() {
                startmonth = $(".startmonth").val();
                if ( startmonth < 10 ) {
                    startmonth = '0' + $(".startmonth").val();
                }
                endmonth = $(".endmonth").val();
                if ( endmonth < 10 ) {
                    endmonth = '0' + $(".endmonth").val();
                }

                date = startmonth + '/' + $(".startyear").val() + ' - ' + endmonth + '/' + $(".endyear").val();
                $(".range_month").val(date);
            });

            if ( typeof cur_date !== "undefined" ) {
                cur_date = cur_date.replace(/%20/g, " ");
                return $(".range_month").val(cur_date);
            }

        },
        // показать выбор месяца
        show:function(range) {

        },
        // скрыть выбор месяца
        hide:function() {
            $(this).html(form);
        }
    };

    $.fn.month_picker = function(method){
        if ( methods[method] ) {
            return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Метод "' +  method + '" не найден в плагине month_picker' );
        }
    };

    $.extend({
        getUrlVars: function(){
            var vars = [], hash;
            var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
            for(var i = 0; i < hashes.length; i++)
            {
                hash = hashes[i].split('=');
                vars.push(hash[0]);
                vars[hash[0]] = hash[1];
            }
            return vars;
        },
        getUrlVar: function(name){
            return $.getUrlVars()[name];
        }
    });

    $(document).click(function(event) {
        if ( $(".period-month").css('display') == 'block' && !$(event.target).hasClass('range_month') && !$(event.target).closest(".period-month").length ) {
            $(".period-month").hide(200);
            event.stopPropagation();
        }
    });

})(jQuery);
