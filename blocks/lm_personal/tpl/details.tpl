{$subnav}

<div id="lm_personal_container" data-user_id="{$user_id}">

    <div id="lm_personal_fullname">{$fullname}</div>

    <div id="lm_personal_photo">
        {if $picture.readonly}
            <span class="picture">{$picture.value}</span>
        {else}
            <a class="lm_personal_edit picture" href="/blocks/manage/?__ajc=lm_personal::edit_field&id={$user_id}&field=picture">{$picture.value}</a>
        {/if}
        {if $loginas|default:false}
            <a href="{$loginas}">Войти под этим именем</a>
        {/if}
    </div>

    <div id="lm_personal_props">
        <div class="lm_personal_props_column">
            {$i = 0}
            {$half = count($props)/2}


            {$ico = ['city' => 'map-marker', 'phone' => 'phone', 'email' => 'envelope']}

            {foreach $props as $code => $prop}
                {if $i++ == $half}</div><div class="lm_personal_props_column">{/if}
                <div class="lm_personal_prop {$code}">
                    <div class="lm_personal_label">{$prop.label}</div>
                    <div class="lm_personal_value">
                        {if !$prop.readonly}
                            <a class="lm_personal_edit {$prop.type} {$prop.type}-{$code}"
                               href="/blocks/manage/?__ajc=lm_personal::edit_field&id={$user_id}&field={$code}">
                                <i class="icon-pencil"></i>
                            </a>
                        {/if}

                        {if $ico[$code]|default:false} <i class="icon-{$ico[$code]}"></i> {/if}

                        {if $prop.uri}
                            <a href="{$prop.uri}" class="lm_personal_text clearfix">
                                {$prop.value}
                            </a>
                        {else}
                            <span class="lm_personal_text clearfix">
                                {$prop.value}
                            </span>
                        {/if}
                    </div>
                </div>
            {/foreach}
        </div>
    </div>
</div>
