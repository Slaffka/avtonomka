<form id="lm_bestpracices_bank_form" class="lm_bestpracices_bank_form" action="/blocks/manage/?__ajc=lm_bestpractices::bank_practices">
    {include './bank_practices_search.tpl'}
    <div class="form-content">
        {include './bank_practices_table.tpl'}
    </div>
</form>
<script type="text/javascript">
    $().ready(function ($) {
        $("#lm_bestpracices_bank_form").best_practices_form({
            custom_init: function (_this) {
                // передаём параметры при изминение в форму
                var search_block = $("#lm_bestpractices-search");
                search_block.on(
                    "bank_practices_search_block.params-changed",
                    function (e, params) {
                        _this.obj.$form.ajax_form('set_post_params',params);
                    }
                );
                // инициализируем модуль поиска
                search_block.bank_practices_search_block({
                    debug: _this.settings.debug,
                })
                $('body').on("click", ".lm_bestpractices-search-detail-details-last-days .btn", function() {
                    var last_days = $(this).attr('data-days');
                    if ($(this).hasClass('selected')) {
                        last_days = 0;
                    }
                    $(".lm_bestpractices-search-detail-details-last-days .btn").removeClass('selected');
                    if (last_days > 0) {
                        $(this).addClass('selected');
                    }
                    _this.obj.$form.ajax_form(
                        'set_post_params',
                        {
                            last_days: last_days
                        }
                    );
                    _this.obj.$form.submit();
                });
                // запускаем поиск
                $('body').on("click", ".lm_bestpractices-search-btn", function(e) {
                    e.preventDefault();
                    _this.obj.$form.submit();
                });
            }
        });
    });
</script>