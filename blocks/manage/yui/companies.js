Y.one('#button-addcompany').on("click", function(){
    var btn = this,
        input = Y.one("#input-addcompany"),
        name = input.get('value'),
        table = Y.one("table#companieslist"),
        lastrow = false;

    if(name){
        btn.setAttribute("disabled", "disabled");
        lastrow = addNewRowIn(table);
        lastrow.one("td.c1 input").set("value", name);
        lastrow.previous("tr").one(".c0").setHTML(indicator);
        lastrow.previous("tr").one(".c1").setHTML(name);

        Y.io('/blocks/manage/ajax.php?ajc=create_company', {
            method: 'POST',
            data: 'name='+encodeURIComponent(name),
            on: {
                complete: function (id, response) {
                    var data = eval('('+response.responseText+')');
                    if(data.success){
                        n = table.all("tbody tr.showrow")._nodes.length-1;
                        lastrow.one("td.c0").setHTML(n);
                        lastrow.previous("tr").one(".c0").setHTML(n);

                        lastrow.one("td.c2 select").set("value", data.values.type);
                        lastrow.previous("tr").one(".c2").setHTML(data.values.typename);

                        lastrow.addClass("companyid-"+data.values.id).previous("tr").addClass("companyid-"+data.values.id);
                        input.set("value", "");
                    }

                    btn.removeAttribute("disabled");
                }
            }
        });
    }

    event.preventDefault();
});

Y.one("#region-main").delegate("click", function(){
    var btn = this,
        companyid = getDataFromStr('companyid', btn.ancestor('tr').getAttribute("class")),
        row = Y.one(".companyid-"+companyid+".showrow"),
        editrow = Y.one(".companyid-"+companyid+".editrow"),
        state = row.getAttribute("data-state"),
        name = editrow.one("td.c1 input").get("value"),
        type = editrow.one("td.c2 select").get("value"),
        n = row.one(".c0").getHTML();

    if(state == 'edit'){
        row.removeClass('hide').removeAttribute("data-state");
        editrow.addClass('hide');
        row.one(".c0").setHTML(indicator);

        Y.io('/blocks/manage/ajax.php?ajc=update_company', {
            method: 'POST',
            data: 'companyid='+companyid+'&name='+encodeURIComponent(name)+'&type='+type,
            on: {
                complete: function (id, response) {
                    data = getJSON(response.responseText);
                    if(data.success){
                        editrow.one("td.c1 input").set("value", data.values.name);
                        editrow.one("td.c2 select").set("value", data.values.type);
                        row.one("td.c1").setHTML(data.values.name);
                        row.one("td.c2").setHTML(data.values.typename);
                        row.one(".c0").setHTML(n);
                    }

                    btn.removeAttribute("disabled");
                }
            }
        });
    }else if(!state){
        row.addClass('hide').setAttribute("data-state", "edit");
        editrow.removeClass('hide');
    }

    event.preventDefault();
}, ".editcompany");
