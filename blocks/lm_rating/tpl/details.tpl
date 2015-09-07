<div class="clearfix">
    <div class="lm-subnav lm-subnav-geo clearfix">
        <div class="lm-subnav-geo-inner">
            {if $moder}
                <ul class="clearfix posts">
                    {foreach $posts as $postid => $post}
                        <li {if $postid == $active_post}class="current"{/if}><a {if $postid == $active_post}class="current"{/if}href="{$link}&post={$postid}">{$post}</a></li>
                    {/foreach}
                </ul>
                <ul class="clearfix type">
                    <li {if $newbies|default:false}class="current"{/if}><a href="{$link_newbies}">Новички</a></li>
                    <li {if $staff|default:false}class="current"{/if}><a href="{$link_staff}">Сотрудники</a></li>
                </ul>

                <div class="timepicker clearfix"></div>
            {else}
                <div class="subnav-left">
                    <ul class="clearfix place">
                        <li {if $city|default:false}class="current"{/if}><a href="{$link_city}">В городе</a></li>
                        <li {if $region|default:false}class="current"{/if}><a href="{$link_region}">В регионе</a></li>
                        <li {if $country|default:false}class="current"{/if}><a href="{$link_country}">В стране</a></li>
                    </ul>
                </div>

                {if $newbies|default:false}
                    <span class="newbies-alert">Вы соревнуетесь среди новичков!</span>
                {/if}
            {/if}

            {*<div class="user-search">Найдено пользователей: {$all_users}</div>*}
        </div>
    </div>
</div>



{$percent = 99/$count_metrics}
<div class="table-responsive">
<table class="table current-rating table-myteam">
    <thead>
        <tr>
            <th class="point">Место</th>
            <th class="fio">ФИО</th>
            <th class="bal">Итог. бал</th>
            {if $title_metrics|default:false}
                {foreach $title_metrics as $title}
                    <th>
                        <span class="">{$title}</span>
                    </th>
                {/foreach}
            {/if}
        </tr>
    </thead>
    {if $data|default:false}
        {foreach $data as $user}
            {if $fixed|default:false}{$tr_class = "fixed"}{else}{$tr_class = "tr"}{/if}
                <tr>
                    <td>{if $user->point < 10}0{/if}{$user->point}</td>
                    <td>{$user->ava} <a class="table-user" href="/blocks/manage/?_p=profile&subpage=index&details=lm_profile_mini&id={$user->id}">{$user->fio}</a></td>
                    <td>{$user->avg}</td>

                {foreach $user->metrics as $metric}
                    <td style="width: {56/$count_metrics}%" class="metric{$metric['id']} {if !$fixed}metric{/if} moder"
                         data-toggle="modal"
                         data-target="#myModal"
                         data-usernumber="{$user->number}"
                         data-metricid="{$metric['id']}"
                         data-metric_value_id="{$metric['mvid']}"
                         data-userid="{$user->id}">
                        {$metric['bal']}
                    </td>
                {/foreach}

                </tr>
            {$fixed = false}
        {/foreach}
    {else}
        <tr><td colspan="{($title_metrics|count)+3}" class="nodata">Нет данных</td></tr>
    {/if}
</table>
</div>


{if $count_pages > 1}
    <div class="pagination text-center" style="">
        <div style="">
            <ul>
                {if $end > 5}
                    {$p = $end - $limit}
                    <li class="start"><a href="{$link_navig}&p={$p}" style="margin-right: 17px;"><</a></li>
                {else} {$p = 1} {/if}
                {if $active > 7}
                    <li class="start"><a href="{$link_navig}&p=1" style="">1</a></li>
                    <li class=""><a href="{$link_navig}&p={$p}">...</a></li>
                {/if}
                {for $i = $start; $i <= $end; $i++}
                    {if $i == $active}
                        <li><a href="#" class="active">{$i}</a> </li>
                    {else}
                        <li>
                            {if $i == 1}
                                {$url = "$link_navig&p=1"}
                            {else}
                                {$url = "$link_navig&p=$i"}
                            {/if}
                            <a href="{$url}">{$i}</a> </li>
                    {/if}
                {/for}
                {if $end + $limit > $count_pages} {$p = $count_pages} {else} {$p = $end + 1} {/if}
                {if $active != $count_pages && $active < $count_pages - 2 }
                    <li><a href="{$link_navig}&p={$p}">...</a></li>
                    <li><a href="{$link_navig}&p={$count_pages}">{$count_pages}</a></li>
                {/if}
                <li class="end"><a href="{$link_navig}&p={$p}" style="margin-left: 17px;">></a></li>

            </ul>
        </div>
    </div>
{/if}
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" data-backdrop="true" aria-labelledby="" aria-hidden="true"  data-keyboard="true" >
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <a class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">Закрыть</span>
                </a>
                <h4 class="modal-title" id="myModalLabel"></h4>
            </div>
            <div class="modal-body"></div>
            <div class="modal-footer"></div>
        </div>
    </div>
</div>
<img id="loadImg" src="/pix/i/loading.gif" />

<style>
    #loadImg{
        position:absolute;
        z-index:1000;
        display:none;
    }

    #region-main {
        background-color: rgba(226, 226, 226,0);
        webkit-box-shadow: inset 0 0px 0px rgba(0,0,0,0) !important;
        -moz-box-shadow: inset 0 0px 0px rgba(0,0,0,0) !important;
        box-shadow: inset 0 0px 0px rgba(0,0,0,0) !important;
    }
</style>