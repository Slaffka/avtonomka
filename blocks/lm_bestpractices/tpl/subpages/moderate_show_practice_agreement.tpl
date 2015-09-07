<div id="practice_detail_page" class="lm_bestpractices_block" style="min-height: 480px;">
    <div class="block_row">
        <div class="big_block"><a class="btn go_back_from_detailpage" href="{$back_url}">назад</a></div>
    </div>
    {if $practice->id > 0}
        <div class="block_row">
            <table class="table-bestpractices no-bg full-length">
                <tr>
                    <td style="width:10%;">
                        <input type="radio" name="action" value="accept" id="action_accept" checked="checked">
                        Принять<br />
                        <input type="radio" name="action" value="reject" id="action_reject">
                        Отклонить
                    </td>
                    <td class="vertical-top" style="width:60%;">
                        <textarea name="comment" id="comment" style="height:151px;width:95%"></textarea>
                    </td>
                    <td class="vertical-top" style="width:30%;">
                        <a href="#" class="btn submit_moderate_result">Отправить</a>
                    </td>
                </tr>
            </table>
        </div>
        {if count($practice->history) > 0}
        <div class="block_row">
            <h3>История согласований:</h3>
            <table class="table table-bestpractices full-length">
                <tr>
                    <td class="vertical-top" style="width:20%;">Дата версии</td>
                    <td class="vertical-top" style="width:10%;">Статус</td>
                    <td class="vertical-top" style="width:70%;">Комментарий</td>
                </tr>
                {foreach $practice->history as $version}
                <tr>
                    <td class="vertical-top">
                        <a href="#" class="show_detailpage" data-detailpage="show_practice_info" data-back-detailpage="show_practice_agreement"  data-id="{$practice->id}" data-history-id="{$version->id}">Версия от {$version->dateStr}</a>


                    </td>
                    <td class="vertical-top">{$version->stateStr}</td>
                    <td class="vertical-top">{$version->comment}</td>
                </tr>
                {/foreach}
            </table>
        </div>
        {/if}
    {else}
        <div class="lm_bestpractices_message info">По данному запросу нет результатов!</div>
    {/if}
</div>

