/**
 * Created by FullZero on 5/11/2015.
 */
$(function () {
    var $table = $('#lm_myteam_table-tutoring');

    $table.find('tr td.course a').click(function () {
        loadModal(this.href);
        return false;
    });


    /**
     * Загружает url в модальном окне бустрапа
     */
    function loadModal(uri) {
        var $modal = $('<div class="table-modal modal fade lm_myteam_course_statistic">'),
            $dialog = $('<div class="modal-dialog">').appendTo($modal),
            $content = $('<div class="modal-content">').appendTo($dialog);

        $modal.on('hide.bs.modal', function () {
            $modal.remove();
        });
        $content.html('<div class="modal-body" style="text-align: center"><img src="/pix/i/loading.gif" /></div>');

        function storeData(data) {
            if ( ! data.length) destroy();

            $content.html(data).find('form.mform');
        }

        $.ajax(uri)
            .done(storeData)
            .fail(function () {
                $modal.modal('hide');
            })
        ;

        $modal.modal({keyboard: true});
        $('.modal-backdrop').click(function () {
            $modal.modal('hide');
        });

        function destroy() {
            $modal.modal('hide');
            window.setTimeout(function () {
                $modal.remove();
            }, 1000);
        }
    }

    /* Графический вид */
    var $users = $('#myteam-user-list');
    var $userList = $users.children('.myteam-user');
    var $mainDiagram = $('#myteam-tutoring-diagram');
    var $diagrams = $('#myteam-tutoring-diagrams');
    var $diagramList = $diagrams.children('.myteam-user-diagram');

    if ($diagramList.length) {

        $diagramList.each(function (index) {
            var self = this;
            self.$ = self.$ || $(self);
            self.index = index;

            self.$chart = self.$.children('.chart');

            if (self.$chart.length) {
                var data = self.$chart.data();

                data.programs = $.map(data.programs, function (row) {
                    return {
                        label: row.name,
                        value: row.progress,
                        href: '/blocks/manage/?__ajc=lm_myteam::course_statistic&program=' + row.id
                    };
                });

                var chart = self.$chart.chartWave({
                    data: data.programs,
                    min: 0,
                    max: 100,
                    total: {label: 'Обученность', value: data.total},
                    unit: '%'
                });

                $('a', chart.getNode()).click(function () {
                    var href = this.getAttribute('xlink:href');
                    if (href) loadModal(href);
                    return false;
                });

                $(window).resize(chart.redraw);
            }
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
            $diagramList.width(100/($diagramList.length - 2 + (userId === 'total')) + '%');
            $diagram.width('auto');

            for (var i = 0, l = $diagramList.length; i < l; i++) {
                if ($diagramList[i].$chart.length) $diagramList[i].$chart.data('chart').redraw();
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
