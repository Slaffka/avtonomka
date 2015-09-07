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
        exifNode = $('#exif'),
        saveBtn = $(".save-button"),
        hint = $(".snapshot-hint"),
        thumbNode = $('#thumbnail'),
        preloader = $(".snapshot-master .preloader"),
        currentFile,
        replaceResults = function (img) {
            var content;
            /*alert("test3");*/
            //alert("replace start");
            if (!(img.src || img instanceof HTMLCanvasElement)) {
                content = $('<span>Loading image file failed</span>');
            } else {
                canvas = img;
                content = $('<a target="_blank">').append(img)
                    .attr('download', currentFile.name)
                    .attr('href', img.src || img.toDataURL());
                saveBtn.css("display", "block");
            }
            //alert("replace end");
            $(".snapshot-preview").html(content);
            preloader.css("display", "none");
        },
        displayImage = function (file, options) {
            var indicatorbig = '<img src="http://'+window.location.host+'/pix/i/loading.gif" />';
            preloader.html(indicatorbig);
            hint.css("display", "none");
            saveBtn.css("display", "none");

            currentFile = file;
            //alert("display");
            if (!loadImage(
                    file,
                    replaceResults,
                    options
                )) {
                /*result.children().replaceWith(
                    $('<span>Your browser does not support the URL or FileReader API.</span>')
                );*/
            }else{
                //preloader.html("");
            }
        },
        displayExifData = function (exif) {
            var thumbnail = exif.get('Thumbnail'),
                tags = exif.getAll(),
                table = exifNode.find('table').empty(),
                row = $('<tr></tr>'),
                cell = $('<td></td>'),
                prop;
            if (thumbnail) {
                thumbNode.empty();
                loadImage(thumbnail, function (img) {
                    thumbNode.append(img).show();
                }, {orientation: exif.get('Orientation')});
            }
            for (prop in tags) {
                if (tags.hasOwnProperty(prop)) {
                    table.append(
                        row.clone()
                            .append(cell.clone().text(prop))
                            .append(cell.clone().text(tags[prop]))
                    );
                }
            }
            exifNode.show();
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
            exifNode.hide();
            thumbNode.hide();
            loadImage.parseMetaData(file, function (data) {
                if (data.exif) {
                    options.orientation = data.exif.get('Orientation');
                    displayExifData(data.exif);
                }
                //alert("load");
                displayImage(file, options);
            });
        },
        coordinates;
    // Hide URL/FileReader API requirement message in capable browsers:
    if (window.createObjectURL || window.URL || window.webkitURL || window.FileReader) {
        //result.hide();
    }

    $('#file-input').on('change', dropChangeHandler);
    $(".snapshot-imitation .save-button").on("click", function(){
        alert(canvas.toDataURL());
    });

});
