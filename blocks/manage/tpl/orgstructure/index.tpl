<div id = "editor">
    <div id="fio">
        <input type="text" placeholder="Найти сотрудника" class="form-control search_user" autocomplete="off" id = "input_fio">
        <i class="icon-magnifier orgstructure" style="color:#0d316e; left: -26px; top: -7px; position: relative;"></i>
    </div>
    <div class = "buttons">
        <button id = "btn_add" >Добавить</a></button>
        <button id = "btn_edit">Редактировать</button>
        <button id = "btn_delete">Удалить</button>
    </div>
</div>

<div class = "block">
    <div id="add_company">
        Компаний еще нет, вы можете <a href="/blocks/manage/?_p=partners">добавить</a>
    </div>

    <div id = "list_pos"></div>
    <table class="table gtreetable" id="gtreetable"></table>
</div>

<div class = "filters">
    <div>
        <select multiple id = "select_partner" data-placeholder="Компания">
            <option value = "0"></option>
        </select>
    </div>
    <div>
        <select multiple id = "select_region" data-placeholder="Регион">
            <option value = "0">Не указан регион</option>
        </select>
    </div>
    <div>
        <select multiple id = "select_segment" data-placeholder="Сегмент">
            <option value = "0">Не указан сегмент</option>
        </select>
    </div>
    <div>
        <select multiple id = "select_distrib" data-placeholder="Канал сбыта">
            <option value = "0">Не указан канал сбыта</option>
        </select>
    </div>
    <div>
        <select multiple id = "select_post" data-placeholder="Должность">
            <option value="0">Не указана должность</option>
        </select>
    </div>
    <div>
        <select multiple id = "select_exp" data-placeholder="Стаж">
            <option value = "0">Менее 3 месяцев</option>
            <option value = "3">> 3 месяца</option>
            <option value = "6">> 6 месяцев</option>
            <option value = "12">> 1 год</option>
            <option value = "36">> 3 года</option>
        </select>
    </div>
</div>

<div class="popup" id="edit_window">
    <input id="divisionname" placeholder="Подразделение">
    <select data-placeholder="Сотрудник" id = "select_user">
        <option id="0"></option>
    </select><button id="btn_new" title="Создать нового сотрудника">+</button>

    <select data-placeholder="Функ. рук." id = "select_user2"><option id="0"></option></select>
    <select data-placeholder="Регион" id = "select_region2"><option id="0"></option></select>
    <select data-placeholder="Сегмент" id = "select_segment2"><option id="0"></option></select>
    <select data-placeholder="Канал сбыта" id = "select_distrib2"><option id="0"></option></select>
    <select data-placeholder="Должность" id = "select_post2"><option id="0"></option></select>
    <select data-placeholder="Территория" id = "select_place"><option id="0"></option></select>
    <input id = "changes_type" readonly>
    <input id = "new_parent_id" readonly>
    <input id = "new_company_id" readonly>

    <div class="modal-footer">
        <button id = "btn_cancel">Отмена</button>
        <button id = "btn_save_changes">Сохранить</button>
    </div>

</div>

<div id="addstaff-modal" class="modal fade in" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="false">
    <div class="modal-header">
        <h3 id="myModalLabel">Добавить сотрудника</h3>
    </div>

    <div class="modal-body">
        <div class="newuser-input-block">
            <input type="text" id="new_lastname" value="" placeholder="Фамилия">
            <input type="text" id="new_firstname" value="" placeholder="Имя">
            <input type="text" id="new_email" value="" placeholder="Email">
            <input type="text" id="new_password" value="" placeholder="Пароль">
        </div>
    </div>
    <div class="modal-footer">
        <label class="pull-left"><input type="checkbox" id="issendemail"> Не отправлять email пользователям </label>
        <button id = "btn_cancel" class="btn" data-dismiss="modal" aria-hidden="true">Отмена</button>
        <button id="btn_add_new_user" class="btn">Добавить</button>
    </div>
</div>