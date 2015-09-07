<div id="practice_detail_page" class="lm_bestpractices_block" style="min-height: 480px;">
    <div class="block_row">
        <div class="big_block">
            <a class="btn go_back_from_detailpage" href="{$back_url}" data-detailpage="{$back_detailpage}" data-id="{$practice->id}">
                назад
            </a>
        </div>
        {if $practice->isFavorite != true}
        <div class="smal_block" style="float:right;">
            <a href="#" style="float:right;" class="add_favorites" data-id="{$practice->id}">В избранное</a>
        </div>
        {/if}
    </div>
    <div class="block_row">
        <div class="big_block vertical-top">
            <h3>Описание проекта:</h3>
            <table class="table-bestpractices no-bg">
                <tr>
                    <td class="vertical-top">Название проекта:</td>
                    <td class="value vertical-top">{$practice->name}</td>
                </tr>
                <tr class="space"><td colspan="2"></td></tr>
                <tr>
                    <td class="vertical-top">Цель проекта:</td>
                    <td class="value vertical-top">{$practice->goal}</td>
                </tr>
                <tr class="space"><td colspan="2"></td></tr>
                <tr>
                    <td class="vertical-top">Тип проекта:</td>
                    <td class="value vertical-top">{$practice->typeStr}</td>
                </tr>
                <tr class="space"><td colspan="2"></td></tr>
                <tr>
                    <td class="vertical-top">Использованные Финансовые ресурсы:</td>
                    <td class="value vertical-top">{$practice->resourcesfinance}</td>
                </tr>
                <tr class="space"><td colspan="2"></td></tr>
                <tr>
                    <td class="vertical-top">Прочие ресурсы (персонал, материалы):</td>
                    <td class="value vertical-top">{$practice->resourcesother}</td>
                </tr>
                <tr class="space"><td colspan="2"></td></tr>
                <tr>
                    <td class="vertical-top">Период внедрения:</td>
                    <td class="value vertical-top">{$practice->period}</td>
                </tr>
            </table>
        </div>
        <div class="smal_block vertical-top" style="float: right;">
            <h3>Показатели:</h3>
            <table class="table-bestpractices no-bg">
                <tr>
                    <td class="vertical-top">Прибыль:</td>
                    <td class="value vertical-top">{$practice->profit|number_format:0:",":"‘"|default:0} Руб.</td>
                </tr>
                <tr class="space"><td colspan="2"></td></tr>
                <tr>
                    <td class="vertical-top">Количество внедрений:</td>
                    <td class="value vertical-top">{$practice->introduceOtherCount|number_format:0:",":"‘"|default:0}</td>
                </tr>
                <tr class="space"><td colspan="2"></td></tr>
                <tr>
                    <td class="vertical-top">Суммарно принесенная прибыль:</td>
                    <td class="value vertical-top">{$practice->introduceOtherProfit|number_format:0:",":"‘"|default:0}</td>
                </tr>
                <tr class="space"><td colspan="2"></td></tr>
                <tr>
                    <td class="vertical-top">Количество респектов:</td>
                    <td class="value vertical-top">{$practice->respects|number_format:0:",":"‘"|default:0}</td>
                </tr>
            </table>
        </div>
    </div>
    <div class="block_row">
        <div class="smal_block vertical-top">
            <h3>Материалы:</h3>
            {if count($practice->pdfFiles) > 0}
                <label class="lm_bestpractices-block-label left">Презинтация (pdf):</label>
                {foreach $practice->pdfFiles as $file}
                <a class="btn" href="/blocks/manage/?_p=lm_bestpractices&subpage=download&file={$file->id}" target="_blank">
                    {$file->filename}
                </a>
                {/foreach}
            {/if}
            {if count($practice->excelFiles) > 0}
                <label class="lm_bestpractices-block-label left">Эффективность – расчет в Excel:</label>
                {foreach $practice->excelFiles as $file}
                <a class="btn" href="/blocks/manage/?_p=lm_bestpractices&subpage=download&file={$file->id}" target="_blank">
                    {$file->filename}
                </a>
                {/foreach}
            {/if}
            {if count($practice->otherFiles) > 0}
                <label class="lm_bestpractices-block-label left">Прочие файлы:</label>
                {foreach $practice->otherFiles as $file}
                <a class="btn" href="/blocks/manage/?_p=lm_bestpractices&subpage=download&file={$file->id}" target="_blank">
                    {$file->filename}
                </a>
                {/foreach}
            {/if}
            {if count($practice->photoFiles) > 0}
                <label class="lm_bestpractices-block-label left">Несколько фото:</label>
                {foreach $practice->photoFiles as $file}
                <a class="btn" href="/blocks/manage/?_p=lm_bestpractices&subpage=download&file={$file->id}" target="_blank">
                    {$file->filename}
                </a>
                {/foreach}
            {/if}
        </div>
        <div class="big_block vertical-top" style="float: right;">
            <h3>Подробное описание:</h3>
            {$practice->description}
        </div>
    </div>
</div>

<script type="text/javascript">
    $().ready(function ($) {
        var form = $("#lm_bestpracices_my_practices_create");
        if (form.length == 0) {
            $('body').append('<form id="lm_bestpracices_my_practices_create" action="/blocks/manage/?__ajc=lm_bestpractices::my_practices_create"></form>');
            form = $("#lm_bestpracices_my_practices_create");
        }
        form.ajax_form({ debug: true });
        var creat_form = $("#practice_create_form");
        creat_form.on('bank_practices_create.params-changed', function(e, params) {
                form.ajax_form('set_post_form', params);
        });
        creat_form.on('bank_practices_create.form-submitted', function(e, params) {
                form.submit();
        });
        form.on('ajax-form.response-parsed',function(e, data) {
            if (typeof data.result.view != 'undefined') {
                $("#lm_bestpracices_my_practices").find('.form-content').html(data.result.view);
            }
        });
        creat_form.bank_practices_create();
    });
</script>