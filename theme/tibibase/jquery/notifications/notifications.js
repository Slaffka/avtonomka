(function($) {
    $.fn.notification = function(options) {

        options = $.extend({
            'url': "",
            'placement': 'auto', //top | bottom | left | right | auto
            'maxHeight': "auto",
            'title': ""
        }, options);



        function stopEvent(e) {
            if(e.stopPropagation) e.stopPropagation();
            if(e.preventDefault) e.preventDefault();
            e.cancelBubble=true;
            e.returnValue=false;
            return false;
        }

        function startDrag(e) {
            var offset       = $scroll.offset().top + e.pageY - $bar.offset().top,
                scrollHeight = $scroll.height() - $bar.height();

            $bar.addClass('active');
            var dragHandler = function drag(e) {
                var pos = e.pageY - offset;
                if (pos < 0) pos = 0;
                else if (pos > scrollHeight) pos = scrollHeight;

                $list.scrollTop(Math.round($list[0].scrollHeight * pos/$scroll.height()));
            };

            window.addEventListener('mousemove', dragHandler, false);
            window.addEventListener('mouseup', function () {
                window.removeEventListener('mousemove', dragHandler, false);
                window.removeEventListener('mouseup', arguments.callee, false);
                $bar.removeClass('active');
            }, false);
            return stopEvent(e || window.event);
        };
        function HideAllPopovers(){
            $('.popover').each(function(){
                $list.empty();
                $(this.previousSibling).popover('hide');
                $list.append('<img class="wait_loader" src="/pix/i/loading.gif">');
            });
        }
        this.css("cursor", "pointer");
        var $x=this;

        $('html').on('mouseup', function(e) {
            if(!$(e.target).closest('.popover').length && e.target!=$x[0]) {
                HideAllPopovers();
            }
        });

        var $block = $('<div class="block_messages_plugin"></div>'),
            $content = $('<div class="content_messages_plugin"></div>').appendTo($block),
            $list = $('<div class = "list_messages_plugin"><img class="wait_loader" src="/pix/i/loading.gif"></div>').appendTo($content),
            $scroll = $('<div class="scroll_plugin"></div>').appendTo($content),
            $bar = $('<div class="bar_plugin"></div>').appendTo($scroll);
        $list.css('max-height', options.maxHeight);
        this.popover({
            html: true,
            content: $block,
            placement: options.placement,
            title: options.title
        });

        this.click(function(){
            $('.popover').css('left',  '-285px');
            $.getJSON(options.url)
                .done(function(data) {
                    $list.empty();
                    if(data.length!=0) {

                        $.each(data, function (key, val) {
                            var $elem = $('<a class="message_plugin"></a>').addClass("notification_"+val.event);

                            if( !$.isEmptyObject(val.data) ){
                                $.each(val.data, function(i, v){
                                    $elem.data(i, v);
                                });
                            }
                            $elem.text(val.message);
                            $elem.addClass(val.type+"_notification");
                            $elem.attr("href", val.url).attr("id", "notification_"+val.id).data("id", val.id);
                            $elem.appendTo($list);

                        });
                        if (data.length*$('.message_plugin').outerHeight(true)>$('.list_messages_plugin').outerHeight(true)) {
                            $list.scroll( function(){
                                $bar.css({
                                    'top': Math.floor(10000*$list.scrollTop()/$list[0].scrollHeight)/100 + '%'
                                });
                            });
                            $scroll.height('100%');
                            $bar.height(Math.floor(10000*$list.outerHeight()/($list[0].scrollHeight-$("img.wait_loader").height()))/100%100 + '%');
                            $bar.mousedown(startDrag);
                        }else{
                            $scroll.height(0);
                            $bar.height(0);
                        }
                    }else{
                        $('<p class="empty">Новых уведомлений нет</p>').appendTo($list);
                        $scroll.height(0);
                    }
                    $("img.wait_loader").remove();

                })
                .fail(function() {
                    HideAllPopovers();
                    alert('Возникла ошибка при подключении к серверу');
                });
        });

    };
})(jQuery);