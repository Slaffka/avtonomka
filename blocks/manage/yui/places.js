var placeid = $("#vars").data("placeid"),
    searchaddon = Y.one(".places-search .add-on").getHTML(),
    searchingnow = false,
    type = $("ul.nav-placetypes li.active").data("type");

if(placeid){
    open_instance_trigger();
}

$(".places-search").on('keyup', function(){

    goSearch();
});

function goSearch(){

    if(!searchingnow) {

        searchingnow = true;
        var input = $(".places-search input"),
            addon = $(".places-search .add-on"),
            value = input.val();

        console.log(value);
        addon.html(indicator);
        $.ajax({
            type: "POST",
            url: "/blocks/manage/?__ajc=places::search",
            data: {type:type, value:value},
            success: function(a){
                a = getJSON(a);

                addon.html(searchaddon);
                searchingnow = false;
                resetpage = true;

                var rows = '',
                    paging = '',
                    html = $(a.html);

                if ($(".placestable-wrapper tbody").length) {
                    if(a.html) {
                        rows = html.find("table.flexible tbody");
                        paging = html.siblings(".paging");

                        rows = rows.html();
                        $(".placestable-wrapper tbody").html(rows);
                        $(".placestable-wrapper .paging").html(paging.html());
                    }else{
                        $(".placestable-wrapper tbody").html(a.html);
                        $(".placestable-wrapper .paging:last-child").html('');
                    }
                } else {
                    window.location = window.location;
                }
            }
        });
    }
}

/**
 * Добавление нового места
 */
if($(".btn-addplace").length) {
    $(".btn-addplace").on("click", function () {
        var btn = $(this);
        btn.attr("disabled", "disabled");

        openInstance();
        $.ajax({
            type: 'POST',
            url: '/blocks/manage/ajax.php?ajc=get_place_info',
            data: 'placeid=0',
            success: function(a){
                a = getJSON(a);
                if (a) {
                    placeid = a.placeid;
                    $('.placeinfo').html(a.html);
                    open_instance_trigger();
                }
                btn.removeAttr("disabled");
            }
        });
    });
}

/**
 * Удаление места проведения
 */
$("#region-main").on("click", ".delete-instance", function(){
    if(confirm("Вы действительно хотите удалить это место?")){
        $.ajax({
            type: "POST",
            url: "/blocks/manage/ajax.php?ajc=remove_place",
            data: "placeid="+placeid
        });

        closeInstance();
    }
});

/**
 * Клик по таблице с местами проведения - открывает детальную информацию
 */
$('table.flexible').on("click", '.row-place', function(){
    placeid = this.getAttribute('class');
    placeid = placeid.split('place-')[1];
    openInstance();
    $.ajax({
        type: "POST",
        url: "/blocks/manage/ajax.php?ajc=get_place_info",
        data: "placeid="+placeid+"&type="+type,
        success: function(a){
            a = getJSON(a);
            if(a){
                $('.placeinfo').html(a.html);
                open_instance_trigger();
            }
        }
    });
});


/**
 * Назначение/переназначение ТМа
 */
$(".manager .btn-appoint").modalpicker({
    pickerlist: "/blocks/manage/?__ajc=base::userpicker_list",
    onpick: function(target, id){
        target = target.parents(".manager");
        var type = target.data("type");
        target.addClass("processing");
        $.ajax({
            type: "POST",
            url: "/blocks/manage/?__ajc=places::appoint_manager",
            data: {type:type, placeid: placeid, userid: id},
            success: function(a){
                a = getJSON(a);
                if(a.html)
                    target.replaceWith(a.html);
            }
        });
    }
});


function open_instance_trigger(){

    $('.editable-text').editable({
        mode: 'inline',
        pk: placeid,
        url: '/blocks/manage/ajax.php?ajc=place_update_field'
    });

    $('.editable-select').editable({
        mode: 'inline',
        pk: placeid,
        url: '/blocks/manage/ajax.php?ajc=place_update_field'
    });

    $('.editable-checklist').editable({
        mode: 'inline',
        pk: placeid,
        url: '/blocks/manage/ajax.php?ajc=place_update_equipment'
    });

    $('.editable-address').editable({
        pk: placeid,
        url: '/blocks/manage/ajax.php?ajc=place_update_address',
        value: '/blocks/manage/?__ajc=places::place_get_address&pk='+placeid,
        validate: function(value) {
            if(!value.cityid.value) return 'Выберите город из списка!';
        }
    });
}

