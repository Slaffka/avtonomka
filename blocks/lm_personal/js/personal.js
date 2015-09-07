/**
 * Created by FullZero on 4/10/2015.
 */

$(function () {
    function loadModal(uri, $value) {
        var $modal   = $('<div class="modal fade lm_personal_edit">'),
            $dialog  = $('<div class="modal-dialog">').appendTo($modal),
            $content = $('<div class="modal-content">').appendTo($dialog);

        $modal.on('hide.bs.modal', function () {
            $modal.remove();
        });
        $content.html('<div class="modal-body" style="text-align: center"><img src="/pix/i/loading.gif" /></div>');

        function storeData(data) {
            //TODO: плохой способ определить, что пришла форма или джейсон
            var $form = $content.html(data).find('form.mform');
            if ($form.length) {
                loadForm($form);
                $content.find('.take-a-picture').click(function () {
                    $(this).parent().hide();
                    $('#fitem_id_repo_upload_file, .modal-info').hide();
                    $('#lm_personal_snapshot').show();
                    lm_personal_Snapshot.init(function (data) {
                        $form.find('input[type="file"]').fileupload('add', {files: [data]});
                    });
                    return false;
                });
            } else {
                data = $.parseJSON(data);
                $value.html(data.value);
                if (data.uri) {
                    $value.attr(
                        'href',
                        data.uri
                            .replace(/&amp;/g, '&')
                            .replace(/&lt;/g, '<')
                            .replace(/&gt;/g, '>')
                            .replace(/&quot;/g, '"')
                            .replace(/&#039;/g, "'")
                    );
                }
                $modal.modal('hide');
            }
        }

        function loadForm ($form) {
            $form.submit(function () {
                var formData = $form.serializeArray();
                formData.push({name: 'submitbutton', value: 'true'});
                $content.addClass('loading');
                $.ajax({url: this.action, method: this.method, data: formData})
                    .done(storeData)
                    .fail(function () {
                        $modal.modal('hide');
                    })
                    .always(function () {
                        $content.removeClass('loading');
                    })
                ;
                return false;
            });

            var $input = $form.find('#id_value');
            if ($input.data('mask')) {
                $input.mask($input.data('mask'));
            }

            var formData = $form.serializeArray();
            formData.push({name: 'submitbutton', value: 'true'});
            console.log(formData);
            $form.find('input[type="file"]').fileupload({
                url: $form.attr('action'),
                formData: formData,
                send: function () {
                    $content.addClass('loading');
                },
                done: function (e, data) {
                    storeData(data.result);
                },
                fail: function () {
                    $modal.modal('hide');
                },
                always: function () {
                    $content.removeClass('loading');
                }
            });
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
    }

    //TODO: переписать modalpicker нормально
    $('.lm_personal_edit.modalpicker-trainer').modalpicker({
        onpick: function(target, id) {
            $.ajax({url: target.attr('href'), method: 'post', data: {value: id}})
                .done(function (data) {
                    data = $.parseJSON(data);
                    var $value = target.next('.lm_personal_text');
                    $value.html(data.value);
                    if (data.uri) {
                        $value.attr(
                            'href',
                            data.uri
                                .replace(/&amp;/g, '&')
                                .replace(/&lt;/g, '<')
                                .replace(/&gt;/g, '>')
                                .replace(/&quot;/g, '"')
                                .replace(/&#039;/g, "'")
                        );
                    }
                })
                .fail(function () {
                    modal.modal('hide');
                })
            ;
        }
    });

    $('.lm_personal_edit.modalpicker-parentf').modalpicker({
        onpick: function(target, id) {
            $.ajax({url: target.attr('href'), method: 'post', data: {value: id}})
                .done(function (data) {
                    data = $.parseJSON(data);
                    var $value = target.next('.lm_personal_text');
                    $value.html(data.value);
                    if (data.uri) {
                        $value.attr(
                            'href',
                            data.uri
                                .replace(/&amp;/g, '&')
                                .replace(/&lt;/g, '<')
                                .replace(/&gt;/g, '>')
                                .replace(/&quot;/g, '"')
                                .replace(/&#039;/g, "'")
                        );
                    }
                })
                .fail(function () {
                    modal.modal('hide');
                })
            ;
        }
    });

    $('.lm_personal_edit:not(.modalpicker)').click(function () {
        this.$ = this.$ || $(this);
        var $value = this.$.hasClass('picture') ? this.$ : this.$.next('.lm_personal_text');
        loadModal(this.href, $value);
        return false;
    });
});