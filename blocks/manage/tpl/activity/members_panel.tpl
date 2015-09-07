<h2>Участники</h2>
<span class="members-panel">{$memberspanel}</span>

<div id="addmember-modal" class="modal fade hide" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="myModalLabel">Добавить участника</h3>
    </div>
    <div class="modal-body">
        <div class="alert alert-error hide">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <div class="content"></div>
        </div>
        {$select_partners}
        {$select_members}
        <a href="#addstaff-modal" class="btn btn-link btn-addstaff" data-toggle="modal">
            <i class="icon icon-plus"></i> Добавить сотрудника
        </a>

    </div>
    <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Отмена</button>
        <button class="btn btn-primary">Добавить</button>
    </div>
</div>

{include file="../modal/addstaff/wrap.tpl"}

<a href="#addmember-modal" class="btn btn-link btn-addmember" data-toggle="modal"><i class="icon icon-plus"></i> Добавить участника</a>
