{if $timeleft > (-3600)}
{if ($is_sv and $phase->id > 0) or ($is_tp and $phase->phase > 0)}
<div class="settinggoals-timer{if $timeleft < -120} timeout{/if}" data-time-left="{$timeleft}" data-text-left="Осталось времени:" data-text-over="Просрочено времени:">
    <span class="settinggoals-timer-label">{if $timeleft < 0}Просрочено времени:{else}Осталось времени:{/if}</span>
    <div class="settinggoals-timer-hours">{$timeleft_h|default:"00"}</div>
    <div class="settinggoals-timer-delimiter">:</div>
    <div class="settinggoals-timer-minutes">{$timeleft_m|default:"00"}</div>
</div>
{/if}
{/if}
{if $is_sv and $phase->id == 0}
<script type="text/javascript">
    $(document).ready(function() {
        $('body').trigger('svstarttimer');
    });
</script>
{/if}