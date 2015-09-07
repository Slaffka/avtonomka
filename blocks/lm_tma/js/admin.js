$(document).ready(function(){
    $('.edit').click(function(){
        btn = $(this);
        if ( btn.hasClass('not_edit') ) {
            $('.active').editable('destroy');
            btn.closest("tr").find(".editing").removeClass("active editable editable-click");
            btn.removeClass("not_edit");
        } else {
            btn.addClass("not_edit");

            btn.closest("tr").find(".editing").addClass("active");
            $.fn.editable.defaults.mode = 'inline';
            $('.active.title_action').editable({
                type: 'text',
                url: '/blocks/manage/?__ajc=lm_tma::save_action',
                success: function(response, newValue) {
                    a = $.evalJSON(response);
                    console.log(a);

                    console.log(a.title);
                    if ( a.status == 'error' ) {
                        alert(a.text);
                        return a.title;
                    }
                }
            });
            $('.active.descr_action').editable({
                type: 'textarea',
                rows: 5,
                cols: 250,
                url: '/blocks/manage/?__ajc=lm_tma::save_action'
            });
            $('.active.reward_action').editable({
                type: 'text',
                inputclass: 'rewardactive',
                url: '/blocks/manage/?__ajc=lm_tma::save_action'
            });
        }
    });

    $('.all_users').each(function(){
        var tmaid = $(this).data('tmaid');

        $('.all_users'+tmaid).modalpicker({
            customdata: function(trigger){
                var tmaid = trigger.data('tmaid');
                return {id: tmaid};
            },
            onpick: function (a, id) {
                location.href = '/blocks/manage/?_p=lm_bank&userid='+id
            }
        });
    });

    $('.all_tts').each(function(){
        var tmaid = $(this).data('tmaid');
        //console.log(tmaid);
        $('.all_tts'+tmaid).modalpicker({
            customdata: function(trigger){
                var tmaid = trigger.data('tmaid');
                return {id: tmaid};
            }
        });
    });

});