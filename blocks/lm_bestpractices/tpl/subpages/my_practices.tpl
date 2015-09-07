<form id="lm_bestpracices_my_practices" class="lm_bestpracices_my_practices" action="/blocks/manage/?__ajc=lm_bestpractices::my_practices">
    {include './my_practices_submenu.tpl'}
    <div class="form-content">
        {include './my_practices_created.tpl'}
    </div>
</form>
<script type="text/javascript">
    $().ready(function ($) {
        $("#lm_bestpracices_my_practices").best_practices_form({
            debug:true
        });
    });
</script>