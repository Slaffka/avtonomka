$(document).ready(function(){

    // Выбор режима создания города "Создать новый" или "Связать с существующим"
    $(".check-mode input").on("change", function(){
        var mode = $(this).val(),
            td = $(this).parents("td");

        td.find(".mode-controls").addClass("hide");
        td.find(".mode-controls-"+mode).removeClass("hide");
    });

    /**
     * Нажатие на кнопку "Далее" - обработка импорта данных
     */
    $(".form-actions-importsales .btn-next").on("click", function(){
        var btn = $(this),
            items = null,
            mode = null,
            errors = false,
            data = [];

        btn.attr("disabled", "disabled");

        if($(".regionscorrector").length) mode = 'regionscorrector';
        if($(".companycorrector").length) mode = 'companycorrector';
        if($(".partnercorrector").length) mode = 'partnercorrector';
        if($(".multiexistscorrector").length) mode = 'multiexistscorrector';
        if($(".staffcorrector").length) mode = "staffcorrector";

        items = $("."+mode + " tr");

        switch(mode){
            case "regionscorrector":
                $.each(items, function(n, item){
                    var itemmode = $(this).find(".check-mode input:checked").val(),
                        controls = $(this).find(".mode-controls-"+itemmode),
                        itemobj = {};

                    itemobj.sourcename = $(this).find(".c1").text();
                    itemobj.mode = itemmode;
                    if(itemmode == "exists"){
                        var citylist = controls.find(".citylist");
                        itemobj.linkedcity = citylist.val();
                        citylist.removeClass("fieldmarker-error");
                        if(!itemobj.linkedcity){
                            citylist.addClass("fieldmarker-error");
                            errors = true;
                        }
                    }else if(itemmode == "new"){
                        var regionlist = controls.find(".regionlist"),
                            cityname = controls.find(".cityname");

                        itemobj.region = regionlist.val();
                        itemobj.cityname = cityname.val();

                        regionlist.removeClass("fieldmarker-error");
                        if(!itemobj.region){
                            regionlist.addClass("fieldmarker-error");
                            errors = true;
                        }

                        cityname.removeClass("fieldmarker-error");
                        if(!itemobj.cityname){
                            cityname.addClass("fieldmarker-error");
                            errors = true;
                        }
                    }

                    data.push(itemobj);
                });
                break;

            case "companycorrector":
                $.each(items, function(n, item){
                    var itemmode = $(this).find(".check-mode input:checked").val(),
                        controls = $(this).find(".mode-controls-"+itemmode),
                        itemobj = {};

                    itemobj.sourcename = $(this).find(".c1").text();
                    itemobj.mode = itemmode;

                    if(itemmode == "exists"){ // Связывание с существующей компанией
                        var companylist = controls.find(".companylist");
                        itemobj.linkedcompany = companylist.val();

                        companylist.removeClass("fieldmarker-error");
                        if(!itemobj.linkedcompany){
                            companylist.addClass("fieldmarker-error");
                            errors = true;
                        }

                    }else if(itemmode == "new"){ // Создание новой компании
                        var companyname = controls.find(".companyname");

                        itemobj.companyname = companyname.val();

                        companyname.removeClass("fieldmarker-error");
                        if(!itemobj.companyname){
                            companyname.addClass("fieldmarker-error");
                            errors = true;
                        }
                    }

                    data.push(itemobj);
                });
                break;

            case "multiexistscorrector":
                $.each(items, function(n, item){
                    var itemobj = {},
                        userlist = $(this).find(".menuuserlist");

                    if(userlist.length) {
                        itemobj.sourcename = $(this).find(".c1").text();
                        itemobj.linkeduser = userlist.val();

                        /*userlist.removeClass("fieldmarker-error");
                        if (!itemobj.linkeduser) {
                            userlist.addClass("fieldmarker-error");
                            errors = true;
                        }*/

                        if(itemobj.linkeduser) {
                            data.push(itemobj);
                        }
                    }
                });
                if(!data.length || isEmpty(data)){
                    window.location = btn.data("href");
                }
                break;


            default:
                // Если такого шага нет, то значит корректировать нечего - просто переходим дальше
                window.location = btn.data("href");
                break;
        }


        if(!errors && data.length || !errors && !isEmpty(data)){
            $.ajax({
                type: "POST",
                url: "/blocks/manage/?__ajc=importsales::"+mode,
                data: {items: $.toJSON(data)},
                success: function(a){
                    window.location = btn.data("href");
                    btn.removeAttr("disabled");
                }
            });
        }else if(errors){
            btn.removeAttr("disabled");
        }
    });

    /**
     * Нажатие на кнопку "Назад"
     */
    $(".form-actions-importsales .btn-prev").on("click", function(){
        window.location = $(this).data("href");
    });

});

function isEmpty(obj) {
    for(var key in obj) {
        return false;
    }
    return true;
}



