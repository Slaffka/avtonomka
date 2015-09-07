<div class="modal-window"><div class="header">
        <div class="title">
            Статус
        </div>
        <div class="section">
            <a class="close-modal" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">Закрыть</span>
            </a>
        </div>
    </div>
    <div class="content">
        <div style="padding-top:20px;text-align:center;">
        {if $status == 0}
            Задачи требуют Вашей корректировки.
        {elseif $status == 2}
            Находится на согласовании у руководителя.
        {elseif $status == 3}
            Требуются корректировка.<br />
            {$comment_count} комментария от руководителя.
        {elseif $status == 4}
            Задачи согласованы!
        {/if}
        </div>
    </div>
    <div class="footer">
    {if $status == 0}
        <a href="#" class="close-modal">Остаться на странице</a>
        {if $no_plan}
            <a href="/blocks/manage/?_p=lm_settinggoals&subpage=today_plan&tptime={$tptime}&tpid={$tpid}&do_action=setoutlets" style="margin-left: 10px;" class="btn">Добавить точку и начать корректировку</a>
        {else}
            <a href="/blocks/manage/?_p=lm_settinggoals&subpage=today_plan&tptime={$tptime}&tpid={$tpid}&do_action=startcorrect" style="margin-left: 10px;" class="btn">Начать корректировку</a>
        {/if}
    {elseif $status == 3}
        <a href="#" class="close-modal">Остаться на странице</a>
        <a href="/blocks/manage/?_p=lm_settinggoals&subpage=today_plan&tptime={$tptime}&tpid={$tpid}&do_action=startcorrectagain" style="margin-left: 10px;" class="btn">Перейти к корректировке</a>
    {else}
        <a href="#" class="btn close-modal">Ok</a>
    {/if}
    </div>
</div>
