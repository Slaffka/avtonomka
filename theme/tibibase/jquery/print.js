var launching_print = false;
$(document).ready(function() {
    $(".btn-print").click(function () {
        // Подготавливаем страницу для печати
        lm_prepare_print();

        // Запускаем диалоговое окно печати
        window.print();

        return false;
    });

    function lm_prepare_print(){
        // Ставим ширину документа 1100px, чтобы все влезло на А4
        $("body").css({width: 1100});

        // Включаем запуск режим печати, чтобы ширина body не откатилась в auto на событии resize()
        launching_print = true;

        // Необходимо для того, чтобы выровнялись элементы,
        // которые позиционируются с помощью js
        $(window).trigger("lm.print");

        // Завершаем запуск режима печати, окно уже вот-вот появится, теперь нам ничего не страшно ;)
        // Внимание: в некоторых браузерах состояние переменной не поменяется, если поместить после window.print()!
        launching_print = false;
    }



    var isCtrl = false;
    $(document).keyup(function (e) {
        if(e.which == 17) isCtrl=false;
    }).keydown(function (e) {
        if(e.which == 17) isCtrl=true;
        if(e.which == 80 && isCtrl == true) lm_prepare_print();
    });


    // Это событие запускается, когда:
    // - окно печати откроется (Яндекс.браузер)
    // - пользователь закроет окно печати (Grome)
    $(window).resize(function(){
        if(!launching_print){
            $("body").css({width:"auto"});
        }
    });
});