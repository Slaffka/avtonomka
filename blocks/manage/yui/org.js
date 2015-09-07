function fill_select(select){
    for (var i = 0; i < select.length; i++){
        (function(value){
            var url='/blocks/manage/?__ajc=orgstructure::get_list&type='+value;
            $.getJSON(url)
                .done(function(data) {
                    if(data.length) {
                        $.each(data, function (key, val) {
                            var $elem = $('<option></option>');
                            if (value != "user"){
                                $elem.text(val.name);
                            }else{
                                $elem.text(val.lastname + " " + val.firstname);
                            }
                            $elem.attr("value", val.id);
                            $elem.appendTo("#select_" + value);
                            $elem.clone().appendTo("#select_" + value + "2");
                        });
                    }
                    if((value != "user") && (value != "place")){
                        $("#select_" + value).chosen();
                    }

                });
        })(select[i]);
    }
    $('select').trigger("chosen:updated");
}

function show_window_edit(){
    var $edit_window=$("#edit_window");
    $edit_window.css("visibility", "visible");
    $edit_window.css("opacity", "1");
    if ($("div.popup").find(".chosen-container").length){
        $edit_window.find("select").trigger("chosen:updated");
    }else{
        $edit_window.find("select").chosen();
    }


}

$(document).ready(function(){
    fill_select(['region', 'partner', 'segment', 'post', 'distrib', 'place', 'user']);

    $("button").on("click", function(e){
        var $selected_node = $('div.selected_node');
        var $edit_window = $('#edit_window');


            var btn_name = e.target.id.substr(4);

            switch (btn_name) {
                case "save_changes":

                    if($('div.selected_node').data("export")){
                        var $data={
                            pos_export: true,
                            type: $("#changes_type").val(),
                            division: $("#divisionname").val(),
                            func_dir: $("#select_user2 :selected").attr("id")
                        }
                    }else{
                        var $data = {
                            pos_export: false,
                            type: $("#changes_type").val(),
                            company: $("#new_company_id").val(),
                            parent: $("#new_parent_id").val(),
                            division: $("#divisionname").val(),
                            user: $("#select_user :selected").attr("id"),//учесть создание нового
                            func_dir: $("#select_user2 :selected").attr("id"),
                            region: $("#select_region2 :selected").attr("id"),
                            post: $("#select_post2 :selected").attr("id"),
                            distrib: $("#select_distrib2 :selected").attr("id"),
                            segment: $("#select_segment2 :selected").attr("id"),
                            place: $("#select_place :selected").attr("id")
                        };
                    }

                    $.ajax({
                        url: "/blocks/manage/?__ajc=orgstructure::change_positions",
                        data: $data,
                        success: function(){
                            alert("Запись изменена");
                        },
                        error: function(){
                            alert("Ошибка");
                        }

                    });

                    break;

                case "cancel":
                    //скрыть родительский элемент
                    $(e.target).parent().parent().css('visibility', 'hidden');

                    break;

                case "add_new_user":
                    var $res = true;
                    $(".newuser-input-block").find("input").each(function(){
                        $res = $res & Boolean (this.value.length);
                    });
                    if($res){//если все поля заполнены
                        $.getJSON("/blocks/manage/?__ajc=orgstructure::add_new_user",
                            {
                                firstname: $("#new_firstname").val(),
                                lastname: $("#new_lastname").val(),
                                email: $("#new_email").val(),
                                password: $("#new_password").val(),
                                partnerid: $selected_node.data("partner_id"),
                                issendemail: $("#issendemail").prop("checked")
                            })
                            .done(function(data){
                                if(data.success){
                                    $("#addstaff-modal").css("visibility", "hidden");
                                    //заполнить
                                    $(data.html).appendTo("#select_user");
                                    $("#select_user option[id='" + data.id + "']").prop("selected", true);
                                    $('#select_user').trigger("chosen:updated");
                                    show_window_edit();
                                }else{
                                    //разбор полетов
                                    alert(data.html);
                                }


                            })
                            .fail(function(){

                                alert("error");
                            });
                    }else{
                        alert("Проверьте, все ли поля заполнены.");
                    }
                    break;


            }

    });

    $("#select_exp").chosen();

    $("#input_fio").on("change", function(){
        if($("#input_fio").val().length > 2){
            $("#gtreetable").find('tr').not('[data-level=0]').empty();
            var $arr;
                if($('#select_partner :selected').length){
                $arr=$('#select_partner :selected');
            }else{
                $arr=$('#select_partner option');
            }
            $arr.each(function(i, selected){
                var $company_tr=$('[data-id="company_' + selected.value + '"]');

                if($company_tr.hasClass("node-expanded")){//если ветвь открыта, ее необходимо свернуть
                    $company_tr.find('span.node-icon-ce').click();
                }
                //имитируем клик по компании
                $company_tr.find('span.node-icon-ce').click();
            });
        }
    });

    $('#gtreetable').gtreetable({
        "source":
            function (id){

                var parent = 0, partner = 0,  level = 0;
                if(id){
                    level=$("[data-id="+id+"]")[0].dataset.level;
                }


                if (id.toString().substr(0,7)=="company"){
                    partner=id.substr(8);
                    parent=0;
                }else{
                    parent=id;
                }

                var posts=[], cities=[], distributions=[], segments=[], experiences=[];

                $('#select_post :selected').each(function(i, selected){
                    posts[i] = $(selected).val();
                });

                $('#select_region :selected').each(function(i, selected){
                    cities[i] = $(selected).val();
                });

                $('#select_segment :selected').each(function(i, selected){
                    segments[i] = $(selected).val();
                });

                $('#select_distrib :selected').each(function(i, selected){
                    distributions[i] = $(selected).val();
                });

                return {
                    data:{
                        term: $("#input_fio").val(),
                        parent:parent,
                        partner:partner,
                        id:id,
                        level:level,
                        cities: cities,
                        posts: posts,
                        segments: segments,
                        distributions: distributions,
                        experiences: experiences
                    },
                    type: 'POST',
                    url: '/blocks/manage/?__ajc=orgstructure::get_nodes',
                    dataType: 'json',
                    error: function (XMLHttpRequest) {
                        alert(XMLHttpRequest.status + ': ' + XMLHttpRequest.responseText);
                    },
                    success:function(){
                        $("#input_fio").val("");
                    }

                };
            },
        //onDelete почему-то повторяется постоянно
        "onDelete": function(oNode){
            return{
                url: '/blocks/manage/?__ajc=orgstructure::delete_position',
                data: {
                    id: oNode.id
                },
                type: 'POST',
                dataType: 'json',
                error: function (XMLHttpRequest){
                    alert(XMLHttpRequest.status + ': ' + XMLHttpRequest.responseText);
                },
                complete:function(){
                    oNode.remove();
                }
            }
        },

        "language": 'ru',
        "cache": 0,
        "selectLimit": -1
    });



});