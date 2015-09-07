/**
 * Created by FullZero on 5/5/2015.
 */

$(function(undefined) {
    var $block   = $('.block_lm_notifications'),
        $content = $block.find(".content"),
        $list    = $content.find(".lm_notifications_notification_list");

    lm_notification_align();
    $(window).resize(lm_notification_align);

    $(window).on("lm.print", lm_notification_align);

    // list resize
    function lm_notification_align() {
        var blockwidth = $block.outerWidth() - 35,
            blockheight = $block.outerHeight() - 45;

        $list.css({width: blockwidth, height: blockheight});
    }

    // custom scroll bar
    var $scroll = $('<div class="scroll"></div>').appendTo($content),
        $bar    = $('<div class="bar"></div>').appendTo($scroll);

    $scroll.css({
        position: 'absolute',
        right: '5px',
        top: '0',
        bottom: '0',
        width: '2px',
        backgroundColor: '#B9CDE5'
    });
    $list.scroll(moveScroller);

    $bar.css({
        position: 'absolute',
        left: '-2px',
        top: '0',
        width: '6px',
        backgroundColor: '#4F81BD',
        borderRadius: '2px'
    });
    $bar.mousedown(startDrag);

    resize();


    function resize() {
        $bar.height(Math.floor(10000*$list.outerHeight()/$list[0].scrollHeight)/100%100 + '%');
    }

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
            stopDrag();
        }, false);
        return stopEvent(e || window.event);
    };

    function stopDrag() {
        /* Bring scroll bar to position
         var options = {
         duration: 300,
         };
         var w = $list.children('li').outerWidth(true),
         s = $list.scrollLeft();
         $list.animate({'scrollLeft': Math.round(s - (s+w/2)%w + w/2)}, options);
         */
    };


    function moveScroller (e) {
        $bar.css('top', Math.floor(10000*$list.scrollTop()/$list[0].scrollHeight)/100 + '%');
    }

});
