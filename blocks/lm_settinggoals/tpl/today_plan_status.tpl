{if $status == 0 or ($status > 1 and $status < 5)}
<script type="text/javascript">
$(document).ready(function() {
    $().showSGModal(
        '/blocks/manage/?__ajc=lm_settinggoals::modal&action=tp_status&tpid={$tpid}&tptime={$tptime}&status={$status}'
    );
    var status = '{$status}';
    var update = function() {
        setTimeout(function() {
            $.ajax({
                dataType: "json",
                url: '/blocks/manage/?__ajc=lm_settinggoals::get_tp_state',
                data: {},
                success: function(data) {
                    if ($('.lm_settinggoals_modal').length == 0 && status != data.status) {
                        $().showSGModal(
                            '/blocks/manage/?__ajc=lm_settinggoals::modal&action=tp_status&tpid={$tpid}&tptime={$tptime}&status='+data.status
                        );
                    } else {
                        update();
                    }
                }
            });
        }, 5000);
    }
    update();
});
</script>
{/if}

