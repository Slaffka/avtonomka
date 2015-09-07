/**
 * Created by Dominik on 22.06.2015.
 */

$(document).ready(function(){

    // Элемент select, который будет замещаться:
    var select = $('select.filter-subject');
    var selectBoxContainer = $('<div>',{
        class	    : 'select_theme',
        html		: '<input type="text" placeholder="Содержание" class="search-subject" />'
    });

    var dropDown = $('<ul>',{class:'dropDown'});
    var selectBox = selectBoxContainer.find('.search-subject');

    // Цикл по оригинальному элементу select

    select.find('option').each(function(i){
        var option = $(this);

        if ( option.attr('selected') ){
            selectBox.attr('placeholder', option.text());
        }

        if(option.data('skip')){
            return true;
        }

        var li = $('<li>',{html: '<span data-subjid="'+option.val()+'">'+option.data('html-text')+'</span>'});

        li.click(function(){
            selectBox.html(option.text());
            dropDown.trigger('hide');
            select.val(option.val());

            subjid = option.val();
            paramsubj = getUrlVar('subj');
            if ( typeof paramsubj === "undefined") {
                url = location.href+"&subj=" + subjid;
            } else {
                url = location.href.replace(/(subj=)[^&]+/ig, '$1' + subjid);
            }
            window.location.href = url;
        });

        dropDown.append(li);
    });

    selectBoxContainer.append(dropDown.hide());
    select.hide().after(selectBoxContainer);

    // Привязываем пользовательские события show и hide к элементу dropDown:

    dropDown.bind('show',function(){
        if(dropDown.is(':animated')){
            return false;
        }
        selectBox.addClass('expanded');
        $(".select_theme").css({
            "background":"url(/blocks/lm_feedback/img/select_close.png) no-repeat right #FFFFFF",
            "background-position-x": "99%"
        });
        dropDown.slideDown(100);
    }).bind('hide',function(){
        if(dropDown.is(':animated')){
            return false;
        }
        $(".select_theme").css({
            "background":"url(/blocks/lm_feedback/img/select_down.png) no-repeat right #FFFFFF",
            "background-position-x": "99%"
        });
        selectBox.removeClass('expanded');
        dropDown.slideUp(100);
    }).bind('toggle',function(){
        if(selectBox.hasClass('expanded')){
            dropDown.trigger('hide');
            $(".select_theme").css({
                "background":"url(/blocks/lm_feedback/img/select_down.png) no-repeat right #FFFFFF",
                "background-position-x": "99%"
            });
        } else  {
            dropDown.trigger('show');
            $(".select_theme").css({
                "background":"url(/blocks/lm_feedback/img/select_close.png) no-repeat right #FFFFFF",
                "background-position-x": "99%"
            });
        }
    });

    selectBox.click(function(){
        dropDown.trigger('toggle');
        return false;
    });

    $(document).click(function(){
        dropDown.trigger('hide');
    });

    $(document).keyup(function(eventObject){
        q = $(".search-subject").val();

        $.ajax({
            type: "POST",
            url: '/blocks/manage/?__ajc=lm_feedback::get_subjects',
            data: "q="+q,
            success: function (a) {
                a = $.evalJSON(a);

                dropDown.html('');
                if ( a.length != 0 ) {
                    $.each(a, function (key, value) {
                        var li = $('<li>',{html: '<span data-subjid="'+value.id+'">'+value.name+'</span>'});
                        li.click(function(){
                            subjid = $(this).find("span").attr('data-subjid');
                            paramsubj = getUrlVar('subj');
                            if ( typeof paramsubj === "undefined") {
                                url = location.href+"&subj=" + subjid;
                            } else {
                                url = location.href.replace(/(subj=)[^&]+/ig, '$1' + subjid);
                            }
                            window.location.href = url;
                        });
                        dropDown.append(li);
                    });
                } else {
                    var li = $('<li>',{html: '<span data-subjid="0">Ничего не найдено</span>'});
                    dropDown.append(li);
                }
            }
        });
        return false;
    });


    function getUrlVars(){
        var vars = [], hash;
        var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
        for(var i = 0; i < hashes.length; i++){
            hash = hashes[i].split('=');
            vars.push(hash[0]);
            vars[hash[0]] = hash[1];
        }
        return vars;
    }

    function getUrlVar(name){
        return getUrlVars()[name];
    }


});
