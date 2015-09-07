/**
 * Custom scroll bar
 * Created by FullZero on 7/22/2015.
 */
(function ($) {
    function ScrollBar(node) {
        var self    = this,
            $list   = $(node),
            $scroll = $('<div class="jquery_scroll_plugin_scroll"></div>').appendTo($list.parent()),
            $bar    = $('<div class="jquery_scroll_plugin_bar"></div>').appendTo($scroll);

        $list.addClass('jquery_scroll_plugin_list');
        $list.scroll(moveScroller);

        $bar.mousedown(startDrag);

        self.resize = function resize() {
            $bar.height(Math.floor(10000*$list.outerHeight()/node.scrollHeight)/100%100 + '%');
        };
        self.resize();

        self.scrollToTop = function scrollToTop() {
            $list.scrollTop(0);
        };

        self.scrollToBottom = function scrollToBottom() {
            console.log('scroll to bottom');
            $list.scrollTop(node.scrollHeight);
        };

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

                $list.scrollTop(Math.round(node.scrollHeight * pos/$scroll.height()));
            };

            window.addEventListener('mousemove', dragHandler, false);
            window.addEventListener('mouseup', function () {
                window.removeEventListener('mousemove', dragHandler, false);
                window.removeEventListener('mouseup', arguments.callee, false);
                $bar.removeClass('active');
                stopDrag();
            }, false);
            return stopEvent(e || window.event);
        }

        function stopDrag() {
            /* Bring scroll bar to position
             var options = {
             duration: 300,
             };
             var w = $list.children('li').outerWidth(true),
             s = $list.scrollLeft();
             $list.animate({'scrollLeft': Math.round(s - (s+w/2)%w + w/2)}, options);
             */
        }


        function moveScroller (e) {
            $bar.css('top', Math.floor(10000*$list.scrollTop()/node.scrollHeight)/100 + '%');
        }
    }

    $.fn.scrollBar = function scroll(method, options) {
        this.each(function () {
            var scrollBar = $.data(this, 'scroll-bar');
            if (scrollBar) {
                if (scrollBar[method] instanceof Function) scrollBar[method](options);
            } else {
                scrollBar = new ScrollBar(this);
                $.data(this, 'scroll-bar', scrollBar);
            }
        });
        return this;
    };
})(jQuery);
