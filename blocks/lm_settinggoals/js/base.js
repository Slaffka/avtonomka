/**
 * Created by Andrej Schartner on 08/07/2015.
 */
(function ( $ ) {
    function countdownTimer( baseElement, options ) {
        var self = {
            settings: {
                debug: false,
            },
            elem: {
                $base: null,
                $hours: null,
                $minutes: null,
                $delimiter: null,
                $label: null,
            },
            data: {
                textOver: "Просрочено времени:",
                textLeft: "Осталось времени:",
                timeLeft: 0
            },
            init: function( ) {
                self.debug('set up timer for: ', self.elem.$base);
                self.elem.$hours = $(".settinggoals-timer-hours" ,self.elem.$base);
                self.elem.$minutes = $(".settinggoals-timer-minutes" ,self.elem.$base);
                self.elem.$delimiter = $(".settinggoals-timer-delimiter" ,self.elem.$base);
                self.elem.$label = $(".settinggoals-timer-label" ,self.elem.$base);

                self.data = $.extend(
                    self.data,
                    self.elem.$base.data()
                );
                self.debug('get data for the current obj:', self.data);
                self.timerInterval = setInterval(self.updateTimer, 1000);
            },
            timerInterval: false,
            updateTimer: function() {
                self.data.timeLeft--;
                console.log(self.data.timeLeft);
                if (self.data.timeLeft < (-3600)) {
                    $('.settinggoals-timer').hide();
                }
                if (self.data.timeLeft < 0) {
                    self.elem.$label.html(self.data.textOver);
                } else {
                    self.elem.$label.html(self.data.textLeft);
                }
                self.debug('updated time:', self.data.timeLeft);
                var t = self.data.timeLeft < 0 ? self.data.timeLeft *-1 : self.data.timeLeft;
                if (self.data.timeLeft < 120 && !self.elem.$base.hasClass('timeout')) {
                    self.elem.$base.addClass('timeout');
                }
                var time = self.parseTime(t);
                self.elem.$delimiter.html( self.elem.$delimiter.html() == ":" ? "" : ":" );
                if (time.hours == 0) {
                    self.elem.$hours.html(time.minutes < 10 ? '0' + time.minutes : time.minutes);
                    self.elem.$minutes.html(time.seconds < 10 ? '0' + time.seconds : time.seconds);
                } else {
                    self.elem.$hours.html(time.hours < 10 ? '0' + time.hours : time.hours);
                    self.elem.$minutes.html(time.minutes < 10 ? '0' + time.minutes : time.minutes);
                }
            },
            parseTime: function(timeLeft) {
                var time = {
                    hours: 0,
                    minutes: 0,
                    seconds: 0,
                };
                time.hours = Math.floor(timeLeft / 60 / 60);
                timeLeft -= 60 * 60 * time.hours;
                time.minutes = Math.floor(timeLeft / 60 );
                time.seconds = timeLeft - 60 * time.minutes;
                self.debug('parsed time:', time);
                return time;
            },
            debug: function(msg, data) {
                if (!self.settings.debug) {
                    return false;
                }
                console.log(msg);
                if (typeof data !== 'undefined') {
                    console.log(data);
                }
            },
        };
        self.settings = $.extend(
           self.settings,
            options
        );
        self.elem.$base = $(baseElement);
        self.init();
        return self;
    }


    function loadModal(uri, $curr_elem, options) {
        var $modal   = $('<div class="modal fade lm_settinggoals_modal">'),
            $dialog  = $('<div class="modal-dialog">').appendTo($modal),
            $content = $('<div class="modal-content">').appendTo($dialog);

        $modal.on('hide.bs.modal', function () {
            $modal.remove();
        });
        $content.html('<div class="modal-body" style="text-align: center"><img src="/pix/i/loading.gif" /></div>');
        $content.on('click', '.close-modal', function () {
            $modal.modal('hide');
            return false;
        });
        if (uri != '') {
            $.ajax(uri)
            .done(function(data) {
                $content.html(data);
            })
            .fail(function () {
                $modal.modal('hide');
            });
        }
        if (typeof options != 'undefined') {
            if (typeof options.content != 'undefined') {
                $content.html(options.content);
            }
        }
        $modal.modal({keyboard: true});
        $('.modal-backdrop').click(function () {
            $modal.modal('hide');
        });
        return $modal;
    }

    $.fn.showSGModal = function( href, options ) {
        this.$ = this.$ || $(this);
        return loadModal(href, this.$, options);
    };

    $('body').on('svstarttimer', function() {
        this.$ = this.$ || $(this);
        var $c_modal = this.$.showSGModal('/blocks/manage/?__ajc=lm_settinggoals::modal&action=svstarttimer');
    });

    $(document).ready(function() {
        $('.settinggoals-timer').each(function() {
            var timer = new countdownTimer(
                $(this), { debug: false }
            );
        });
        $('body').on('click', '.settinggoals_send_agreement', function(e) {
            this.$ = this.$ || $(this);
            var positionid = this.$.attr('data-positionid');
            var time = this.$.attr('data-time');
            var $c_modal = this.$.showSGModal('/blocks/manage/?__ajc=lm_settinggoals::modal&action=send_agreement&positionid=' + positionid + '&time=' + time);

            $c_modal.on('click', '.reload', function(e) {
                location.reload();
            });
            $c_modal.on('click', '.submit', function(e) {
                $.ajax({
                    type: "POST",
                    url: '/blocks/manage/?__ajc=lm_settinggoals::modal',
                    data: 'action=send_agreement&force=1&positionid=' + positionid + '&time=' + time,
                    success: function(data) {
                        location.reload();
                    }
                })
                .fail(function () {
                    $c_modal.modal('hide');
                });
                e.preventDefault();
            });

            e.preventDefault();
        });
        $('body').on('click', '.settinggoals_show_comment', function() {
            this.$ = this.$ || $(this);
            var planid = this.$.attr('data-planid');
            var $c_modal = this.$.showSGModal('/blocks/manage/?__ajc=lm_settinggoals::modal&action=show_comment&planid=' + planid);
        });
        $('body').on('click', '.settinggoals_edit_timer', function() {
            this.$ = this.$ || $(this);
            var planid = this.$.attr('data-planid');
            var $c_modal = this.$.showSGModal('/blocks/manage/?__ajc=lm_settinggoals::modal&action=edit_timer&planid=' + planid);
        });
        $('body').on('click', '.settinggoals_edit_kpi_count', function(e) {
            this.$ = this.$ || $(this);
            var kpiid = this.$.attr('data-kpiid');
            var planid = this.$.attr('data-planid');
            var $c_modal = this.$.showSGModal(
                '/blocks/manage/?__ajc=lm_settinggoals::modal&action=edit_kpi_count&kpiid=' + kpiid + '&planid=' + planid
            );
            return false;
        });
        $('body').on('click', '.settinggoals_remove_kpi_count', function(e) {
            this.$ = this.$ || $(this);
            var kpiid = this.$.closest('td').attr('data-kpiid');
            var planid = this.$.closest('td').attr('data-planid');

            $.ajax({
                type: "POST",
                url: '/blocks/manage/?__ajc=lm_settinggoals::toggle_kpi_count',
                data: "kpiid="+kpiid+"&planid="+planid,
                success: function(data) {
                    location.reload();
                }
            });

            return false;
        });

        var start_search = function(e) {
            this.$ = $('#search_outlet');
            var posid = this.$.attr('data-posid');
            var tmp = location.href.split('#')[0];
            tmp = tmp.split('&');
            var url = tmp[0];
            for (var i = 1; i < tmp.length; i++) {
                if (tmp[i].split('=')[0] == 'search' || tmp[i].split('=')[0] == 'page')
                    continue;
                url += '&' + tmp[i];
            }
            url += '&search=' + $('#search_outlet').val();
            location.href = url;
        };

        $('body').on('click', '.start_search', function(e) {
            start_search();
            e.preventDefault();
        });

        $('body').on('keyup', '#search_outlet', function(e) {
            this.$ = this.$ || $(this);
            if (e.keyCode == 13) {
                start_search();
            }
            e.preventDefault();
        });
        $('body').on('change', '.cb_outlet', function(e) {
            if ($('.cb_outlet:checked').length == 0) {
                $('.save_outlets').addClass('disabled');
            } else {
                if (!$(this).hasClass('submited')) {
                    $('.save_outlets').removeClass('disabled');
                }
            }
        });
        $('.cb_outlet').trigger('change');
        $('body').on('click', '.save_outlets', function(e) {
            if ($(this).hasClass('disabled')) {
                return false;
            }
            $(this).addClass('disabled');
            $(this).addClass('submited');
            $("#loadImg").show();
            this.$ = this.$ || $(this);
            var posid = this.$.attr('data-posid');
            var tptime = this.$.attr('data-tptime');

            var postdata = {
                posid: posid,
                tptime: tptime,
                outlets: {}
            };
            $('.cb_outlet').each(function(k, v) {
                this.$ = this.$ || $(this);
                postdata.outlets[this.$.val()] = this.$.is(':checked');

            });
            $.ajax({
                type: "POST",
                url: '/blocks/manage/?__ajc=lm_settinggoals::save_outlets',
                data: postdata,
                success: function(data) {
                    location.href = '/blocks/manage/?_p=lm_settinggoals&subpage=today_plan&page=-1';
                }
            });
            e.preventDefault();
        });

        $('body').on('click', '.settinggoals-set-timer', function(e) {
            this.$ = this.$ || $(this);
            var svposid = this.$.attr('data-svposid');
            var time = this.$.attr('data-time');


            var $c_modal = this.$.showSGModal(
                '/blocks/manage/?__ajc=lm_settinggoals::modal&action=edit_timer&svposid=' + svposid + '&time=' + time
            );
            $c_modal.on('click', '.submit', function() {
                var postdata = {
                    svposid: svposid,
                    deadline: $c_modal.find('#deadline').val(),
                    comment: $c_modal.find('#comment').val()
                };
                $.ajax({
                    type: "POST",
                    url: '/blocks/manage/?__ajc=lm_settinggoals::save_timer',
                    data: postdata,
                    success: function(data) {
                        location.reload();
                    }
                });

            });
            e.preventDefault();
        });

        $('body').on('click', '.settinggoals_edit_kpi', function() {
            this.$ = this.$ || $(this);
            var kpiid = this.$.attr('data-kpiid');
            var planid = this.$.attr('data-planid');
            var $c_modal = this.$.showSGModal('/blocks/manage/?__ajc=lm_settinggoals::modal&action=edit_kpi&kpiid=' + kpiid + '&planid=' + planid);
            $c_modal.on('click', '.submit', function(e) {
                var value = $c_modal.find('[name="new_value"]').val();
                $.ajax({
                    type: "POST",
                    url: '/blocks/manage/?__ajc=lm_settinggoals::save_edit_kpi_value',
                    data: "value="+value+"&kpiid="+kpiid+"&planid="+planid,
                    success: function(data) {
                        location.reload();
                    }
                })
                .fail(function () {
                    $c_modal.modal('hide');
                });
                e.preventDefault();
            });

        });

        /* progress of predict value (gradient background) */
        $('.predicted-value').each(function () {
            this.$ = this.$ || $(this);

            var percent = Number(this.$.data('percent'));

            if (percent) {
                var critical = [254,  43,  43]; // fe2b2b
                var normal   = [240, 200,  32]; // f0c820
                var perfect  = [112, 177,  46]; // 70b12e

                var color;
                if (percent < 90)       color = critical;
                else if (percent < 100) color = normal;
                else                    color = perfect;

                color = color.join(',');

                percent -= 5;
                var gradientEnd = percent + 10;

                this.$
                    .css({ background: "rgb("+color+")" })
                    .css({ background: "-moz-linear-gradient(left,  rgba("+color+",1) 0%, rgba("+color+",1) "+percent+"%, rgba("+color+",0) "+gradientEnd+"%, rgba("+color+",0) 100%)" })
                    .css({ background: "-webkit-gradient(linear, left top, right top, color-stop(0%,rgba("+color+",1)), color-stop("+percent+"%,rgba("+color+",1)), color-stop("+gradientEnd+"%,rgba("+color+",0)), color-stop(100%,rgba("+color+",0)))" })
                    .css({ background: "-webkit-linear-gradient(left,  rgba("+color+",1) 0%,rgba("+color+",1) "+percent+"%,rgba("+color+",0) "+gradientEnd+"%,rgba("+color+",0) 100%)" })
                    .css({ background: "-o-linear-gradient(left,  rgba("+color+",1) 0%,rgba("+color+",1) "+percent+"%,rgba("+color+",0) "+gradientEnd+"%,rgba("+color+",0) 100%)" })
                    .css({ background: "-ms-linear-gradient(left,  rgba("+color+",1) 0%,rgba("+color+",1) "+percent+"%,rgba("+color+",0) "+gradientEnd+"%,rgba("+color+",0) 100%)" })
                    .css({ background: "linear-gradient(to right,  rgba("+color+",1) 0%,rgba("+color+",1) "+percent+"%,rgba("+color+",0) "+gradientEnd+"%,rgba("+color+",0) 100%)" });
            }
        });

        /* Make botstrap tooltip work */
        $('i.kpi-info[data-toggle="tooltip"]').tooltip();
    });

})( jQuery );