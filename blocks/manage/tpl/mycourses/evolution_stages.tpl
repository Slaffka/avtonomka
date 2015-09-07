{*<h3>Этапы профессионального развития</h3>*}
{$result}
<div class="lm-stages clearfix">
    {foreach from=$stages item=stage}
        <a href="/my/?_p=mycourses&pos={$stage->code}" class="lm-stage lm-stage-{$stage->code} {if $pos == $stage->code}active{/if}">
            <div class="status-indicator status-{$stage->code}" data-progress="{$stage->progress}">
                <div class="status-indicator0 status-{$stage->code}0">
                    <img src="/blocks/manage/tpl/cherkizovo/img/{$stage->code}0.svg">
                </div>
                <div class="status-indicator1 status-{$stage->code}1">
                    <img src="/blocks/manage/tpl/cherkizovo/img/{$stage->code}1.svg">
                </div>
            </div>
            <div class="lm-stage-name-wrapper">
                <div class="lm-stage-name">{$stage->name}</div>
            </div>
        </a>
    {/foreach}
</div>

<div class="lm-mycourses-list-wrapper">
    <div class="lm-mycourses-list clearfix">
        {foreach from=$programs item=program}
            <div class="lm-mycourse clearfix {if !$program->available}lm-course-locked{/if}">
                <div class="lm-course-name">
                    <span class="valign-middle">
                        {if $program->courseid}
                            <a href="/course/view.php?id={$program->courseid}">{$program->name}</a>
                        {else}
                            {$program->name}
                        {/if}
                    </span>
                </div>
                <div class="lm-course-progress">
                    <div class="lm-indicator-wrapper">
                        {if $program->available}

                            {$progress = 0}
                            {if $program->progress >= 100}
                                {$progress = 100}
                            {elseif $program->progress >= 90}
                                {$progress = 75}
                            {elseif $program->progress >= 80}
                                {$progress = 50}
                            {elseif $program->progress > 0}
                                {$progress = 25}
                            {/if}
                            <div class="lm-indicator lm-indicator{$progress}"></div>
                        {else}
                            <div class="fa fa-lock"></div>
                        {/if}
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
</div>


<!-- Modal -->
<div class="modal fade" id="modal-locked-courses" tabindex="-1" role="dialog" aria-labelledby="modal-locked-courses-label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                Этот курс Вам недоступен. Пожалуйста, пройдите сначала предыдущие!
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>


{literal}
<script>
    $(document).ready(function(){
        $.each($(".status-indicator"), function(){
            var progress = $(this).data("progress"),
                total_height = $(this).height(),
                height = total_height/100 * progress;

            $(this).find(".status-indicator1").css({height:height, "margin-top":-height});
            $(this).find(".status-indicator1 img").css({height:total_height, "margin-top":-(total_height-height)});
        });

        $(".lm-course-locked a").on("click", function(){
            $('#modal-locked-courses').modal('show');
            return false;
        });
    });
</script>
{/literal}