{if $is_sv && $phase->phase == 0}
<script type="text/javascript">
$(document).ready(function() {
    $('.lm-subnav .today_plan').click(function () {
        $(this).showSGModal('/blocks/manage/?__ajc=lm_settinggoals::modal&action=startplan');
        return false;
    });
});
</script>
{/if}