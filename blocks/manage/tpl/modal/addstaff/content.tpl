<div class="alert alert-error hide">
    <button type="button" class="close" data-dismiss="alert">×</button>
    <div class="content"></div>
</div>

<label class="radio">
    <input type="radio" name="addstafftype" value="existsuser" style="float:none" checked>
    Выбрать из уже зарегистрированных
</label>
<div class="existsuser-input-block">
    <input type="text" name="userfio" id="search-staff" data-relative="selecteduserid">
    <input type="hidden" name="userid" id="selecteduserid">
    {$staffmodalexists_ttlist}
</div>

<hr>

<label class="radio">
    <input type="radio" name="addstafftype" value="newuser" style="float:none">
    Зарегистрировать нового пользователя и добавить
</label>
<div class="newuser-input-block">
    <input type="text" name="lastname" value="" placeholder="Фамилия" disabled />
    <input type="text" name="firstname" value="" placeholder="Имя" disabled />
    <input type="text" name="email" value="" placeholder="Email" disabled />
    <input type="text" name="password" value="" placeholder="Пароль" disabled />
    {$staffmodalnew_ttlist}
</div>