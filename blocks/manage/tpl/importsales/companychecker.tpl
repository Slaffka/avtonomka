<div class="check-mode">
    <label class="radio inline">
        <input type="radio" name="mode{$n}" value="new" checked /> Добавить новую компанию
    </label>
    <label class="radio inline">
        <input type="radio" name="mode{$n}" value="exists" /> Связать с существующей
    </label>
</div>

<div class="mode-controls mode-controls-exists hide">
    {$companies}
</div>

<div class="mode-controls mode-controls-new">
    <div><input type="text" class="companyname span6" value="{$companyname}" placeholder="Введите название компании" /></div>
</div>