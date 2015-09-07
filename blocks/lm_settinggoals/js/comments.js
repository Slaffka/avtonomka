/**
 * Created by FullZero on 7/20/2015.
 */

$(function (undefined) {
    var $root     = $('#lm_settinggoals_plan_comments');
    var $toggle   = $('#lm_settinggoals_plan_comments-toggle');
    var $comments = $('#lm_settinggoals_plan_comments-comments');
    var $list     = $('#lm_settinggoals_plan_comments-list');
    var $new      = $('#lm_settinggoals_plan_comments-new');
    var $add      = $('#lm_settinggoals_plan_comments-add');

    $toggle.click(function () {
        $root.toggleClass('open');
        setTimeout(function () {
            resize();
            $list.scrollBar('scrollToBottom');
        }, 100);
    });

    function resize() {
        $comments.height($root.height() - $new.height());
        $list.scrollBar('resize');
    }
    resize();

    $list.scrollBar();

    $add.click(function () {
        var newComment = document.createElement('div');
        var form       = newComment.appendChild(document.createElement('form'));
        var textarea   = form.appendChild(document.createElement('textarea'));
        var saveBtn    = form.appendChild(document.createElement('button'));
        var cancelBtn  = form.appendChild(document.createElement('button'));

        var phaseid = $root.data('phaseid');
        var tpid    = $root.data('tpid');

        newComment.className = 'lm_settinggoals_plan_comments-comment';
        newComment.$ = $(newComment);

        $add.hide();

        textarea.placeholder = 'Введите текст комментария';
        textarea.className   = 'text';

        saveBtn.textContent = 'Сохранить';
        saveBtn.type        = 'submit';
        saveBtn.className   = 'save';

        $(form).submit(function () {
            if (textarea.value.length) {
                var text =  textarea.value;
                textarea.readOnly = true;
                $new.addClass('loading');
                $.ajax({
                    url: '/blocks/manage/?__ajc=lm_settinggoals::comment_save',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        phaseid: phaseid,
                        tpid:    tpid,
                        text:    text
                    }
                })
                    .done(function (result) {
                        newComment.textContent = text;
                        $list.append(newComment);
                        $add.show();
                        resize();
                        $list.scrollBar('scrollToBottom');
                    })
                    .fail(function () {
                        textarea.readOnly = false;
                    })
                    .always(function () {
                        $new.removeClass('loading');
                    });
            } else {
                newComment.$.remove();
                $add.show();
                resize();
                $list.scrollBar('scrollToBottom');
            }
            return false;
        });

        cancelBtn.textContent = 'Отменить';
        cancelBtn.type          = 'button';
        cancelBtn.className   = 'cancel';
        cancelBtn.addEventListener('click', function () {
            $add.show();
            newComment.$.remove();
            resize();
        });

        $new.prepend(newComment);

        resize();
        $list.scrollBar('scrollToBottom');
    });
});