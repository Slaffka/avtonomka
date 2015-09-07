$(function  () {
    $("ul.matrix-program-list").sortable({
        itemSelector: "li.program-item",
        handle: ".program-move",

        onDrop: function ($item, container, _super, event) {
            $item.removeClass("dragged").removeAttr("style");
            $("body").removeClass("dragging");

            update_matrix(container.el.parent("form"));
        }
    });

    $(".matrix-list").on("click", ".btn-del-program", function() {
        var btn = $(this),
            form = btn.parents("form"),
            li = btn.parents(".program-item");

        $(li).remove();
        update_matrix(form);

        return false;
    });

    $(".matrix-list").on("change", ".program-item select", function() {
        var select = $(this),
            form = select.parents("form"),
            val = select.val(),
            ul = select.parents(".matrix-program-list"),
            tplli = select.parents(".program-item-tpl"),
            selects = ul.find(".program-item select").removeClass("check-program"),
            errors = false;

        select.data("skip", 1);
        $.each(selects, function(i, e) {
            if($(this).data("skip") != 1 && $(this).val() == val){
                $(this).addClass("check-program");
                select.val("");
                errors = true;
                return false;
            }
        });

        if(!errors) {
            // Если добавляем новое значение
            if(tplli.length) {
                tplli.clone().appendTo(ul);
                tplli.removeClass("program-item-tpl").addClass("program-item");
                tplli.find(".btn-del-program").removeClass("hide");
                tplli.find(".program-move").removeClass("hide");
            }

            update_matrix(form);
        }
    });

    function update_matrix(form){
        var data = form.serialize();
        $.ajax({
            type: "POST",
            url: "/blocks/manage/?__ajc=matrix::save",
            data: data
        });
    }
});