<div class="check-mode">
    <label class="radio inline">
        <input type="radio" name="mode{$n}" value="new"> Создать новый
    </label>
    <label class="radio inline">
        <input type="radio" name="mode{$n}" value="exists" checked> Связать с существующим
    </label>
</div>

<div class="mode-controls mode-controls-exists">
    {$regions}
</div>

<div class="mode-controls mode-controls-new hide">
    <div>{$mainregions}</div>
    <div><input type="text" class="cityname span6" value="" placeholder="Введите название города" /></div>
</div>