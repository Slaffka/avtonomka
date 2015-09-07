<div class="modal-header">
    <a class="close" data-dismiss="modal">Закрыть</a>
    <h4 class="modal-title">{$title}</h4>
</div>
<div class="modal-body">
    <div class="modal-info">{$info}</div>
    {$form}
    {if $snapshot}{include "./snapshot.tpl"}{/if}
</div>
