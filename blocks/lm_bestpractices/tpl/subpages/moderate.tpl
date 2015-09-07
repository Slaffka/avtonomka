<form id="lm_bestpracices_moderate" action="/blocks/manage/?__ajc=lm_bestpractices::moderate">
    {include './moderate_submenu.tpl'}
    <div class="form-content">
        {include './moderate_vote.tpl'}
    </div>
</form>
<script type="text/javascript">
    $().ready(function ($) {
        $("#lm_bestpracices_moderate").best_practices_form({
            debug:true,
            custom_init: function(_this) {
                _this.obj.$form.on('ajax-form.response-parsed',function(e, data) {
                    if (data.result.do_action_result) {
                        _this.obj.$form.ajax_form(
                            'set_post_params',
                            {
                                do_action: null,
                                comment: null,
                                detailpage: null,
                                practiceid: null,
                            }
                        );
                        _this.obj.$form.submit();
                    }
                });
                _this.obj.$form.on('click', '.submit_moderate_result', function () {
                    _this.obj.$form.ajax_form(
                        'set_post_params',
                        {
                            do_action: _this.obj.$form.find('#practice_detail_page').find('input[name="action"]').val(),
                            comment: _this.obj.$form.find('#practice_detail_page').find('textarea[name="comment"]').val(),
                        }
                    );
                    _this.obj.$form.submit();
                });

            }
        });
    });
</script>