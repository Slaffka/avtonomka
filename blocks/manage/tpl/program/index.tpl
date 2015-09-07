<div class="check-mode">
    Добавить:
    <label class="radio inline">
        <input type="radio" name="mode" value="category" checked> Категорию
    </label>
    <label class="radio inline">
        <input type="radio" name="mode" value="program"> Программу
    </label>
    <label class="radio inline">
        <input type="radio" name="mode" value="linkedprogram"> Программу с привязкой к курсу
    </label>
</div>
<div class="form form-category" style="margin-top:10px">
    <input type="text" class="input-name input-xlarge" placeholder="Название категории">
    <button class="btn">Добавить</button>
</div>

<div class="form form-program hide" style="margin-top:10px">
    {$selectcategory}
    <input type="text" class="input-name input-xlarge" placeholder="Введите название программы" disabled>
    <button class="btn" disabled>Добавить</button>
</div>

<div class="form form-linkedprogram hide" style="margin-top:10px">
    {$selectcategory}
    <input type="text" id="input-addprogramm" class="input-name input-xlarge" placeholder="Начните вводить название курса и выберите подходящий из списка" disabled>
    <button class="btn" disabled>Добавить</button>
</div>
{foreach from=$tables item=table}
    {$table}
{/foreach}

<div id="editprogram-modal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3>Редактировать программу</h3>
    </div>
    <div class="modal-body">
        <div class="alert alert-error hide">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <div class="content"></div>
        </div>
        <div class="modal-body-content"></div>
    </div>
    <div class="modal-footer">
        <button class="btn btn-close" data-dismiss="modal" aria-hidden="true">Отмена</button>
        <button class="btn btn-primary">Изменить</button>
    </div>
</div>