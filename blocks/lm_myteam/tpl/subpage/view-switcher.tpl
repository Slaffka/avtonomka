{$query=$smarty.server.QUERY_STRING|regex_replace:'/&?view=([^&]*)/':''}
<ul id="view-switcher" class="unstyled">
    <li {if $smarty.get.view === 'table'}class="active"{/if}>
        <a href="?{$query}&view=table" class="bt-icon icon-list"></a>
    </li>
    <li {if $smarty.get.view !== 'table'}class="active"{/if}>
        <a href="?{$query}&view=graph" class="bt-icon icon-graph"></a>
    </li>
</ul>