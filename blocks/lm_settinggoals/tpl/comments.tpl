<div id="lm_settinggoals_plan_comments" data-phaseid="{$phase->id}" data-tpid="{$tpid}">
    <div id="lm_settinggoals_plan_comments-toggle">
        <div class="show-hide">
            <span class="show">Показать комментарии</span>
            <span class="hide">Скрыть комментарии</span>
        </div>
    </div>
    <div id="lm_settinggoals_plan_comments-window">
        <div id="lm_settinggoals_plan_comments-comments">
            <div id="lm_settinggoals_plan_comments-list">
                {foreach $comments as $comment}
                    <div class="lm_settinggoals_plan_comments-comment">{$comment->text}</div>
                {/foreach}
            </div>
        </div>
        {if $may_comment}
            <div id="lm_settinggoals_plan_comments-new">
                <button id="lm_settinggoals_plan_comments-add">Добавить комментарий</button>
                <img id="lm_settinggoals_plan_comments-loading" src="/pix/i/loading.gif" />
            </div>
        {/if}
    </div>
</div>