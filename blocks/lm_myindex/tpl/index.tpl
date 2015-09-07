{if $stage|default:false}
    <a href="{$details_url}" class="">
        <div class="status-indicator status-{$stage->code}" data-progress="{$stage->progress}">
            <div class="status-indicator0 status-{$stage->code}0">
                <img src="/blocks/manage/tpl/cherkizovo/img/{$stage->code}0.svg">
            </div>
            <div class="status-indicator1 status-{$stage->code}1" style="display:none;height: 170px; margin-top: -170px;">
                <img src="/blocks/manage/tpl/cherkizovo/img/{$stage->code}1.svg" style="height: 170px; margin-top: 0px;">
            </div>
        </div>
        <div class="status-name">{$stage->name}</div>
    </a>



    {literal}
        <script>
            $(document).ready(function(){
                $.each($(".status-indicator"), function(){
                    var $this = $(this);
                    var update_height = function () {
                        var progress = $this.data("progress"),
                            total_height = $(".block_lm_myindex").height()-70,
                            height = total_height/100 * progress;

                        $this.find(".status-indicator0 img").css({height:total_height});
                        $(".block_lm_myindex .content").css({padding:5});
                        $this.find(".status-indicator1").css({height:height, "margin-top":-height});
                        $this.find(".status-indicator1 img").css({height:total_height, "margin-top":-(total_height-height)});
                        $this.find(".status-indicator1").css({display:"block"});
                    };
                    update_height();
                    $(window).resize(update_height);
                });
            });
        </script>
    {/literal}
{/if}

&nbsp; {*чтобы этот виджет добавлялся даже если пуст*}