/*
 * JavaScript Load Image Demo JS 1.9.1
 * https://github.com/blueimp/JavaScript-Load-Image
 *
 * Copyright 2013, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*global window, document, loadImage, HTMLCanvasElement, $ */

$(function () {
    'use strict';

    var result = $('.snapshot-imitation canvas'),
        canvas = null,
        saveBtn = $(".save-button"),
        hint = $(".snapshot-hint"),
        preloader = $(".snapshot-master .preloader"),
        currentFile,

        replaceResults = function (img) {
            var content;

            if (!(img.src || img instanceof HTMLCanvasElement)) {
                content = $('<span>Loading image file failed</span>');
            } else {
                canvas = img;
                content = $('<a target="_blank">').append(img)
                    .attr('download', currentFile.name)
                    .attr('href', img.src || img.toDataURL());
                saveBtn.css("display", "block");
            }

            $(".snapshot-preview").html(content);
            preloader.css("display", "none");
        },
        displayImage = function (file, options) {
            var indicatorbig = '<img src="http://'+window.location.host+'/pix/i/loading.gif" />';
            preloader.html(indicatorbig).css("display", "block");
            hint.css("display", "none");
            saveBtn.css("display", "none");
            currentFile = file;

            if (!loadImage(
                    file,
                    replaceResults,
                    options
                )) {
                /*result.children().replaceWith(
                    $('<span>Your browser does not support the URL or FileReader API.</span>')
                );*/
            }
        },
        dropChangeHandler = function (e) {
            e.preventDefault();
            e = e.originalEvent;
            var target = e.dataTransfer || e.target,
                file = target && target.files && target.files[0],
                options = {
                    maxWidth: result.width(),
                    canvas: true
                };
            if (!file) {
                return;
            }

            loadImage.parseMetaData(file, function (data) {
                if (data.exif) options.orientation = data.exif.get('Orientation');
                displayImage(file, options);
            });
        };


    $('#file-input').on('change', dropChangeHandler);
    $(".snapshot-imitation .save-button").on("click", function(){
        var url = $(this).data("action");

        $.ajax({
            type: "POST",
            url: url,
            data: {"image": canvas.toDataURL()},
            success: function(a){
                a = eval('('+a+')');
                if(a.success){
                    window.location = a.redirect;
                }else{
                    alert(a.errormessage);
                }
            }
        });
    });

});
