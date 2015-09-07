<h2>
    <div class="pull-right close-instance">
        <button class="btn"><i class="icon icon-checkmark"></i> OK</button>
    </div>
    {if $canedit}
        <div class="pull-right delete-instance">
            <a class="btn btn-link">удалить</a>
        </div>
    {/if}
</h2>

{foreach from=$fields item=field}
    {if $field->type == 'headline'}
        <h4>{$field->name}</h4>
    {elseif $field->type == 'html'}
        <div class="control-row">
            <div class="lbl">{$field->name} </div>
            <div class="val">
                {$field->html}
            </div>
        </div>
    {else}
        <div class="control-row">
            <label>
                <span class="lbl">{$field->name} </span>
                <span class="val">
                <a href="#" id="field-{$field->code}" class="editable-{$field->type}" data-title="{$field->title}"
                data-emptytext="{$field->emptytext}" {if $field->source}data-source="{$field->source}"{/if}
                data-type="{$field->type}" {if $field->val}data-value="{$field->val}"{/if}></a>
                </span>
            </label>
        </div>
    {/if}
{/foreach}