<div id="practice_create_form" class="lm_bestpractices_block lm_bestpractices_my_practices_create">
    <div class="practice_about foreign">
        <div class="half-row vertical-top">
            <h3>О проекте:</h3>
            <label class="lm_bestpractices-block-label left" for="practice_name">Название проекта:</label>
            <span class="lm_bestpractices-block-detail">{$practice->name}</span>

            <label class="lm_bestpractices-block-label left" for="practice_name">Прибыль проекта:</label>
            <span class="lm_bestpractices-block-detail">{$practice->profit|number_format:0:",":"‘"|default:0} Руб.</span>

            <label class="lm_bestpractices-block-label left" for="practice_type">Тип проекта:</label>
            <span class="lm_bestpractices-block-detail">{$practice->typeStr}</span>

            <label class="lm_bestpractices-block-label left" for="practice_type">Автор проекта:</label>
            <span class="lm_bestpractices-block-detail">{$practice->authorName}</span>
            <input type="hidden" name="practice_parentid" id="practice_parentid" value="{$practice-id}">
        </div>
        <div class="half-row vertical-top" style="float: right;">
            <h3>Информация:</h3>
            <label class="lm_bestpractices-block-label" for="practice_resourcesfinance">Период внедрения:</label>
            <input type="text" name="practice_from" id="practice_from" class="form-control half" placeholder="Укажите начало внедрения">
            <input type="text" name="practice_to" id="practice_to" class="form-control half" placeholder="Укажите завершение внедрения">

            <label class="lm_bestpractices-block-label" for="practice_tt">ТТ, где внедрялась практика:</label>
            <div id="practice_tt" class="lm_bestpractices-block">
                <div class="lm_bestpractices-filter-values">
                    {foreach $tt as $t}
                    <span class="lm_bestpractices-block-value full-row" data-value="{$t->id}">
                        {$t->name}
                    </span>
                    {/foreach}
                </div>
            </div>

            <label class="lm_bestpractices-block-label" for="practice_description">Особые комментарии:</label>
            <textarea name="practice_comments" id="practice_comments" class="form-control" placeholder="Особые комментарии реализованого проекта"></textarea>

            <label class="lm_bestpractices-block-label" for="practice_resourcesfinance">Финансовые ресурсы:</label>
            <textarea name="practice_resourcesfinance" id="practice_resourcesfinance" class="form-control" placeholder="Использованные финансовые ресурсы"></textarea>

            <label class="lm_bestpractices-block-label" for="practice_resourcesother">Прочие ресурсы:</label>
            <textarea name="practice_resourcesother" id="practice_resourcesother" class="form-control" placeholder="Прочие ресурсы (персонал, материалы)"></textarea>
        </div>
    </div>
    <div class="practice_attachments">
        <div class="full-row vertical-top">
            <div><h3>Добавьте материалы:</h3></div>
            <label class="lm_bestpractices-block-label">Презинтация (pdf):</label>
            <a href="#" class="btn file-uploader add-file" data-name="pdf" data-accept="application/pdf" data-max-count="1">Добавить</a>

            <label class="lm_bestpractices-block-label">Эффективность – расчет в Excel:</label>
            <a href="#" class="btn file-uploader add-file" data-name="excel" data-max-count="1">Добавить</a>

            <label class="lm_bestpractices-block-label">Несколько фото (не более 1 БМ/штука):</label>
            <a href="#" class="btn file-uploader add-file" data-name="photo">Добавить</a>

            <label class="lm_bestpractices-block-label">Прочие файлы:</label>
            <a href="#" class="btn file-uploader add-file" data-name="other">Добавить</a>
        </div>
    </div>

    <div style="width:100%;text-align: center;">
        <a href="#" class="btn disabled submit">Отправить на рассмотрение</a>
        <input type="checkbox" name="agree" id="agree">
        <label for="agree">Согласен с правилами</label>
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