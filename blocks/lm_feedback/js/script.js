$(document).ready(function(){


    $("#mform2").on("click", ".btn-upload-new", function(){
       $(".fileupload").click();
    });

    /**
     * загрузка файла
     */
    if($('#mform2').length) {
        var formData = $("#mform2").serializeArray();

        /*formData.splice(1,1);
        itemid = $("#mform2 [name='itemid']").val();
        formData.push({name: 'itemid', value: itemid});*/

        formData.push({name: 'submitbutton', value: 'true'});

        $('#mform2').fileupload({
            url: '/blocks/manage/?__ajc=lm_feedback::uploadfile',
            formData: formData,
            send:  function (e, data) {
                $(".feedback_widget .btnsubmit").button('loading');
            },
            done: function (e, data) {
                a = $.evalJSON(data.result);
                if ( !a.error ) {
                    $(".upload-alert").removeClass('hide');
                    $(".upload-alert .count-files").html(a.count);
                } else {
                    alert(a.text);
                }
            },
            progressall: function (e, data) {
                $('#mform2').hide();
                $(".feedback_widget .info_files").hide();
                /*$(".feedback_widget .btn-upload-new").hide('slow');
                */
                $('#progress').show(300);
                var progress = parseInt(data.loaded / data.total * 100, 10);
                $('#progress .progress-bar').css('width', progress + '%');
            },
            stop: function (e) {
                $('#mform2').show();
                $(".feedback_widget .info_files").show();
                /*$(".feedback_widget .btn-upload-new").show('slow');
                */
                $('#progress').hide();
                $(".feedback_widget .btnsubmit").button('reset');
            }
        }).prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled');
    }

    /**
     * Создание тикета
     */
    //$(".block_lm_feedback").on("click", ".btnsubmit", function(){
    $(".block_lm_feedback").submit(".btnsubmit", function(){
        $(".btnsubmit").button('loading');

        var subject = $("#id_subject").val();
        var message = $("#id_message").val();
        if ( subject == '0' ) {
            $("#id_subject").css("border-color","#a94442");
            // alert('Не выбрана тема обращения!');
            $(".btnsubmit").button('reset');
            return false;
        }
        $("#id_subject").css("border-color","#3c763d");
        if ( message == "" ) {
            //alert('Поле "сообщение" не может быть пустым!');
            $("#id_message").css("border-color","#a94442");
            $(".btnsubmit").button('reset');
            return false;
        }
        $("#id_message").css("border-color","#3c763d");

        var data = "subjectid="+subject+"&message="+message;
        if ( data ) {
            $.ajax({
                type: "POST",
                url: '/blocks/manage/?__ajc=lm_feedback::add_ticket',
                data: data,
                success: function (a) {
                    a = $.evalJSON(a);
                    if (a.success) {
                        mes = a.success;
                        $('#id_subject option').eq(0).prop('selected', true);
                        $("#id_message").val('');
                        $(".upload-alert .count-files").html(0);
                        /*if (a.newitemid) {
                            $("#mform2 [name='itemid']").val(a.newitemid);
                        }*/
                        $(".block_lm_feedback .content").html("<div class='addticket_complete'>Ваше обращение отправлено! <br>В ближайшее время вы получите ответ.</div> ");
                        setTimeout(function(){
                            var url = window.location.href;
                            window.location.href = url;
                        }, 1500);

                    } else {
                        mes = a.error;
                    }
                    $(".btnsubmit").button('reset');
                    //alert(mes);
                }
            });
            return false;
        }
    });

    /**
     * Если заполняют форму обращения
     */
    $("#id_message").keyup(function(eventObject){
        message = $("#id_message").val();
        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_feedback::update_ticket',
            data: "message="+message,
            success: function(a) {
                console.log(a);
                if ( a ) {
                    $("#id_message").css("border-color","#3c763d");
                }
            }
        });
        return false;
    });


    $('#id_subject').change(function(){
        subject = $("#id_subject").val();
        $("#id_subject").css("border-color","#a94442");
        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_feedback::update_ticket',
            data: "subjectid="+subject,
            success: function(a) {
                if ( a ) {
                    $("#id_subject").css("border-color","#3c763d");
                }
            }
        });
        return false;
    });



    // ********************************* //
    ///////////////////////////////////////
    ///// Пользовательский интерфейс! /////
    ///////////////////////////////////////
    // ********************************* //

    /**
     * Открываем данное обращение с полным описанием
     */
    $(".tickets").on("click", ".message", function(){
        $("#OneTicket .alert-message .alert").remove();
        $(".form-message").elastic();
        $("#OneTicket .modal-body .all_messages").html('');
        var imgObj = $("#loadImg"); // заглушка
        $("#loadImg").show();
        // вычислим в какие координаты нужно поместить изображение загрузки, чтобы оно оказалось в серидине страницы:
        var centerY = $(window).scrollTop() + ($(window).height() + $("#loadImg").height())/2;
        var centerX = $(window).scrollLeft() + ($(window).width() + $("#loadImg").width())/2;
        // поменяем координаты изображения на нужные:
        $("#loadImg").offset({top:centerY, left:centerX});
        ticketid = $(this).data('ticketid');
        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_feedback::get_data_ticket',
            data: "ticketid="+ticketid,
            success: function(a) {
                a = $.evalJSON(a);
                if ( a.id ) {
                    $('#OneTicket').modal('show');
                    $('#OneTicket').removeClass('hide');
                    $("#OneTicket .modal-body .feedback .avatar").html(a.avatar);
                    $("#OneTicket .modal-body .feedback .username").html(a.username);
                    $("#OneTicket .modal-body .feedback .time").html(a.date);
                    $("#OneTicket .modal-body .feedback .message").html(a.message);
                    $("#OneTicket .feedback .modal-title").html(a.name);

                    if ( a.files ) {
                        $("#OneTicket .modal-body .feedback #files").removeClass('hide');
                        $("#OneTicket .feedback ul.files").html(a.files);
                    }
                    $("#OneTicket .all_messages").html(a.messages);

                    $(".btn-cnage-status").attr("data-ticketid", a.id);
                    $(".btn-send-message").attr("data-ticketid", a.id);
                } else {
                    alert("Oopps! Ошибка! Вероятно Вы пытаетесь посмотреть чужой тикет!")
                }
                $("#loadImg").hide();
            }
        });
        return false;
    });

    /**
     * Меняем статус обращения в модальном окне (в архив / из архива)
     */
    $("#OneTicket").on("click", ".btn-cnage-status", function() {
        $(".btn-cnage-status").button('loading');
        ticketid = $(this).attr("data-ticketid");
        status = $(this).attr("data-status");
        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_feedback::change_status_ticket',
            data: "ticketid="+ticketid+"&status="+status,
            success: function(a) {
                a = $.evalJSON(a);
                if ( a.success ) {
                    $(".btn-cnage-status").button('reset');
                    //$("#OneTicket .status").attr('data-status', a.status).html(a.title);

                    mes = a.success;
                    $("#OneTicket .alert-message").html('<div class="alert" role="alert"></div>');
                    $("#OneTicket .alert").addClass("alert-success").html(mes);
                } else {
                    mes = a.error;
                    $("#OneTicket .alert-message").html('<div class="alert" role="alert"></div>');
                    $("#OneTicket .alert").addClass("alert-danger").html(mes);
                }
                var scrollTop = $("#OneTicket .alert").offset().top; // получаем позицию элемента относительно документа
                $("#OneTicket .modal-body").scrollTop(scrollTop);
                setTimeout(function() {
                      // скроллим страницу на значение равное позиции элемента
                    $("#OneTicket").modal("hide");
                    $("#ticket"+a.ticketid).remove();
                }, 1000);
            }
        });
    });

    /**
     * Отправляем сообщение пользователю на его обращение
     */
    $("#OneTicket").on("click", ".btn-send-message", function(){
        $(".btn-send-message").button('loading');
        ticketid = $(this).attr("data-ticketid");
        message = $("#OneTicket .form-message").val();
        if ( message != "" ) {
            $.ajax({
                type: "POST",
                url: '/blocks/manage/?__ajc=lm_feedback::send_message',
                data: "ticketid=" + ticketid+"&message="+message,
                success: function (a) {
                    a = $.evalJSON(a);
                    if ( a.send ) {
                        mes = "Сообщение отправлено";
                        $("#OneTicket .alert-message").html('<div class="alert" role="alert"></div>');
                        $("#OneTicket .alert").addClass("alert-success").html(mes);
                        $(".btn-send-message").button('reset');
                        $("#OneTicket .all_messages").append(a.message);
                        $("#OneTicket .form-message").val('');
                    } else {
                        mes = "Сообщение не отправлено";
                        $("#OneTicket .alert-message").html('<div class="alert" role="alert"></div>');
                        $("#OneTicket .alert").addClass("alert-danger").html(mes);
                    }
                }
            });
        } else {
            alert("Сообщение не может быть пустым!");
            $(".btn-send-message").button('reset');
        }
    });

    /**
     * Меняем статус обращения непосредственно в таблице (в архив / из архива)
     */
    /*$(".status").on("click", function(){
        ticketid = $(this).attr("data-ticketid");
        status = $(this).attr("data-status");
        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_feedback::change_status_ticket',
            data: "ticketid="+ticketid+"&status="+status,
            success: function(a) {
                a = $.evalJSON(a);
                if ( a.success ) {
                    mes = a.success;
                    $("#OneTicket .alert-message").html('<div class="alert" role="alert"></div>');
                    $("#OneTicket .alert").addClass("alert-success").html(mes);
                } else {
                    mes = a.error;
                    $("#OneTicket .alert-message").html('<div class="alert" role="alert"></div>');
                    $("#OneTicket .alert").addClass("alert-danger").html(mes);
                }
                $("#ticket"+a.ticketid).remove();
            }
        });
    });*/

    /**
     * Обработчик события при клике вне модального окна - закрывает модальное окно
     */
    $(document).click(function(event) {
        if ($(event.target).closest("#OneTicket").length) return;
        $("#OneTicket").modal("hide");
        $("#OneTicket .modal-body #files").addClass('hide');
        $("#OneTicket .modal-body .all_messages").html('');
        event.stopPropagation();
    });

});
