/**
 * Created by FullZero on 5/15/2015.
 */
$(function () {
    var $users = $('#myteam-user-list');

    var $userList = $users.children('.myteam-user');

    var $diagrams = $('#myteam-mistakes-diagrams');

    var $diagramList = $diagrams.children('.myteam-user-diagram');

    if ($diagramList.length) {
        $diagramList.each(function () {
            var self = this;
            self.$ = self.$ || $(self);

            var data = self.$.children('.data').data();

            if (data) {
                var progress = [];
                for (var key in data.progress) {
                    progress.push({label: key, value: data.progress[key]});
                }

                var chart = self.$.chartPie({
                    data: progress,
                    total: {caption: 'Всего', value: data.total}
                });

                $(window).resize(chart.redraw);
            }
        });

        var userId = (
            location.hash.indexOf('#user-') === 0
                ? location.hash
                : $diagramList.prop('id'))
            .split('-').pop();

        function showDiagram(userId) {
            $userList.removeClass('selected');
            $userList.filter('[data-user-id="' + userId + '"]').addClass('selected');

            $diagramList.hide();
            $('#user-diagram-' + userId).show().chartPie().redraw();
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