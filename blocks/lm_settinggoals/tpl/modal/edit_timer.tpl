<div style="padding:10px;">
    Завершение этапа: <input type="text" id="deadline" name="deadline" value="{$phase->deadline|date_format:'d.m.Y H:i:s'}" >
    <br />
    Надпись:
    <textarea id="comment" name="comment">{$phase->comment}</textarea>
    <br />
    <a href="#" class="submit">Сохранить</a>
</div>