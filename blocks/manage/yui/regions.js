var cityid = 0;

$(document).ready(function() {
    $('.trainerassignment').on('click', function () {
        var btn = $(this),
            content = $("#assignedtrainers-modal .modal-body-content");

        cityid = getDataFromStr('regionid', btn.parents("tr").attr("class"));
        content.html(indicatorbig);

        if(cityid){
            $.ajax({
                type: "POST",
                url: '/blocks/manage/ajax.php?ajc=get_assinged_trainers',
                data: "cityid="+cityid,
                success: function(a){
                    a = getJSON(a);
                    if(a.success){
                        content.html(a.html);
                    }
                }
            });
        }
    });

    $('#assignedtrainers-modal .btn-close').on('click', function(){
        window.location = window.location;
    });

    $('#region-main').on('change', '.assigntrainerlist', function(){
        var trainerid = $(this).val(),
            newrow = null;

        if(cityid && trainerid && !$("#trainer-"+trainerid).length) {
            newrow = $("#trainerlist tr.clone.hide").clone().removeClass("clone hide").appendTo("#trainerlist tbody");
            newrow.find(".c0").html(indicator);
            $.ajax({
                type: 'POST',
                url: '/blocks/manage/ajax.php?ajc=assign_region_to_trainer',
                data: 'cityid=' + cityid + '&trainerid=' + trainerid,
                success: function (a) {
                    a = getJSON(a);
                    if (a.success) {
                        newrow.find(".c0").html($("tr.trainer").length-1);
                        newrow.find(".c1").html(a.fullname);
                        newrow.attr("id", "trainer-" + trainerid);
                    }
                }
            });
        }
    });

    $("#region-main").on("click", '.removetrainer', function(){
        var btn = $(this),
            row = btn.parents('tr'),
            trainerid = getDataFromStr('trainer', row.attr("id") );

        if(trainerid && cityid){
            row.remove();
            $.ajax({
                type: "POST",
                url: '/blocks/manage/ajax.php?ajc=remove_trainer_from_city',
                data: 'cityid='+cityid+'&trainerid='+trainerid
            });
        }
        event.preventDefault();
    });


    $("#region-main").on("click", ".editregion", function(){
        var btn = $(this),
            regionid = getDataFromStr('regionid', btn.parents("tr").attr("class")),
            row = $(".regionid-"+regionid+".showrow"),
            editrow = $(".regionid-"+regionid+".editrow"),
            state = row.attr("data-state"),
            name = editrow.find("td.c1 input").val(),
            n = row.find(".c0").html();

        if(state == 'edit'){
            row.removeClass('hide').removeAttr("data-state");
            editrow.addClass('hide');
            row.find(".c0").html(indicator);

            $.ajax({
                type: 'POST',
                url: '/blocks/manage/ajax.php?ajc=update_region_name',
                data: 'regionid='+regionid+'&name='+encodeURIComponent(name),
                success: function(a) {
                    a = getJSON(a);
                    if(a.success){
                        editrow.find("td.c1 input").val(name);
                        row.find("td.c1").html(name);
                        row.find(".c0").html(n);
                    }

                    btn.removeAttr("disabled");
                }
            });
        }else if(!state){
            row.addClass('hide').attr("data-state", "edit");
            editrow.removeClass('hide');
        }

        event.preventDefault();
    });

    $("#menuregion").on("change", function(){
        if($(this).val()){
            $("#button-addcity, #input-addcity").removeAttr("disabled");
        }else{
            $("#button-addcity, #input-addcity").attr("disabled", "disabled");
        }
    });

    $("#button-addcity").on("click", function(){
        var btn = $(this),
            input = $("#input-addcity"),
            select = $("#menuregion"),
            ciyname = input.val(),
            regionid = select.val(),
            regionlist = $("#regionlist-"+regionid);

        if(ciyname && regionid){
            btn.attr("disabled", "disabled");

            $.ajax({
                type: 'POST',
                url: '/blocks/manage/ajax.php?ajc=region_add_city',
                data: 'regionid='+regionid+'&name='+ciyname,
                success: function(a){
                    window.location = window.location;
                }
            });
        }
    });
});
