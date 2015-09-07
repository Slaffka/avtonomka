/**
 * Created by FullZero on 5/11/2015.
 */

$(function () {

    /* Графический вид */
    var $users = $('#myteam-user-list');
    var $userList = $users.children('.myteam-user');
    var $mainDiagram = $('#myteam-rating-diagram');
    var $diagrams = $('#myteam-rating-diagrams');
    var $diagramList = $diagrams.children('.myteam-user-diagram');

    if ($diagramList.length) {

        $diagramList.each(function (index) {
            var self = this;
            self.$ = self.$ || $(self);
            self.index = index;

            self.$chart = self.$.children('.chart');

            var data = self.$chart.data();

            data = $.map(data.values, function(value, index) {
                return {
                    caption: data.dates[index],
                    value: value ? value : 0
                };
            });

            var chart = self.$chart.chartLine({
                data: data,
                //max: 4,
                min: 0,
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

            $(window).resize(chart.redraw);
        });

        var userId = (
            location.hash.indexOf('#user-') === 0
                ? location.hash
                : $diagramList.prop('id'))
            .split('-').pop();

        var $shownDiagram = null;
        function showDiagram(userId) {
            var $diagram = $('#user-diagram-' + userId);

            if ($shownDiagram && $diagram.attr('id') === $shownDiagram.attr('id')) return;

            $userList.removeClass('selected');
            $userList.filter('[data-user-id="' + userId + '"]').addClass('selected');

            if ($shownDiagram) {
                var elm = $diagramList[$shownDiagram[0].index + 1];
                if (elm) $shownDiagram.insertBefore(elm);
                else $shownDiagram.appendTo($diagrams);
            }
            $diagram.appendTo($mainDiagram);
         /*   $diagramList.width((100/($diagramList.length - 2 + (userId === 'total'))) + '%');*/
            $diagramList.width(300);
            $diagram.width('auto');
            for (var i = 0, l = $diagramList.length; i < l; i++) {
                $diagramList[i].$chart.data('chart').redraw();
            }
            $shownDiagram = $diagram;
        }

        showDiagram(userId);

        $userList.click(function () {
            var self = this;
            self.$ = self.$ || $(self);
            var userId = self.$.data('user-id');
            showDiagram(userId);
        });
    }
});
