/**
 * Created by FullZero on 4/20/2015.
 */
/*globals  $: true, getUserMedia: true, alert:true, ccv:true */

/*! getUserMedia demo - v1.0
 * for use with https://github.com/addyosmani/getUserMedia.js
 * Copyright (c) 2012 addyosmani; Licensed MIT */

(function () {
    'use strict';

    var App = {

        init: function (callback) {

            // The shim requires options to be supplied for it's configuration,
            // which can be found lower down in this file. Most of the below are
            // demo specific and should be used for reference within this context
            // only

            var self = this;
            if ( !!this.options ) {

                this.pos = 0;
                this.cam = null;
                this.filter_on = false;
                this.filter_id = 0;

                this.canvas = document.getElementById("lm_personal_snapshot_preview");
                this.ctx = this.canvas.getContext("2d");
                this.img = new Image();
                this.ctx.clearRect(0, 0, this.options.width, this.options.height);
                this.image = this.ctx.getImageData(0, 0, this.options.width, this.options.height);
                this.snapshotBtn = document.getElementById('lm_personal_takeSnapshot');
                this.saveBtn = document.getElementById('lm_personal_saveButton');


                // Initialize getUserMedia with options
                getUserMedia(this.options, this.success, this.deviceError);

                // Initialize webcam options for fallback
                window.webcam = this.options;

                // Trigger a snapshot
                this.addEvent('click', this.snapshotBtn, this.getSnapshot);

                // Trigger an upload
                if (callback instanceof Function) {
                    this.addEvent('click', this.saveBtn, function () {
                        self.canvas.toBlob(callback);
                    });
                }

                if( /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
                    $("body").addClass("mobile");
                }

            } else {
                alert('No options were supplied to the shim!');
            }

        },

        addEvent: function (type, obj, fn) {
            if (obj.attachEvent) {
                obj['e' + type + fn] = fn;
                obj[type + fn] = function () {
                    obj['e' + type + fn](window.event);
                }
                obj.attachEvent('on' + type, obj[type + fn]);
            } else {
                obj.addEventListener(type, fn, false);
            }
        },

        // options contains the configuration information for the shim
        // it allows us to specify the width and height of the video
        // output we're working with, the location of the fallback swf,
        // events that are triggered onCapture and onSave (for the fallback)
        // and so on.
        options: {
            "audio": false, //OTHERWISE FF nightlxy throws an NOT IMPLEMENTED error
            "video": true,
            el: "lm_personal_webcam",

            extern: null,
            Append: true,

            // noFallback:true, use if you don't require a fallback

            width: 320,
            height: 240,


            mode: "callback",
            // callback | save | stream
            swffile: "/blocks/manage/yui/camera/fallback/jscam_canvas_only.swf",
            quality: 85,
            context: "",

            debug: function () {},
            onCapture: function () {
                window.webcam.save();
            },
            onTick: function () {},
            onSave: function (data) {},
            onLoad: function () {}
        },

        success: function (stream) {

            if (App.options.context === 'webrtc') {

                var video = App.options.videoEl;

                if ((typeof MediaStream !== "undefined" && MediaStream !== null) && stream instanceof MediaStream) {

                    if (video.mozSrcObject !== undefined) { //FF18a
                        video.mozSrcObject = stream;
                    } else { //FF16a, 17a
                        video.src = stream;
                    }

                    return video.play();

                } else {
                    var vendorURL = window.URL || window.webkitURL;
                    video.src = vendorURL ? vendorURL.createObjectURL(stream) : stream;
                }

                video.onerror = function () {
                    stream.stop();
                    streamError();
                };

            } else{
                // flash context
            }

        },

        deviceError: function (error) {
            alert('No camera available.');
            console.error('An error occurred: [CODE ' + error.code + ']');
        },

        getSnapshot: function () {
            var webcam = $("#lm_personal_webcam"),
                canvas = $("#lm_personal_snapshot_preview"),
                triggerico = $("#lm_personal_takeSnapshot i"),
                savebtn = $("#lm_personal_saveButton");

            if(webcam.is(":visible")) {
                webcam.css("display", "none");
                canvas.css("display", "block");
                triggerico.removeClass("icon-photo").addClass("icon-sync");
                savebtn.css("display", "block");
            }else{
                webcam.css("display", "block");
                canvas.css("display", "none");
                triggerico.removeClass("icon-sync").addClass("icon-photo");
                savebtn.css("display", "none");
            }

            // If the current context is WebRTC/getUserMedia (something
            // passed back from the shim to avoid doing further feature
            // detection), we handle getting video/images for our canvas
            // from our HTML5 <video> element.
            if (App.options.context === 'webrtc') {
                var video = document.getElementsByTagName('video')[0];
                $("#lm_personal_snapshot_preview, #lm_personal_webcam").attr("width", 320);
                $("#lm_personal_snapshot_preview, #lm_personal_webcam").attr("height", 240);
                $("#lm_personal_snapshot_preview").css({width:"320px"});

                App.canvas.width = video.videoWidth;
                App.canvas.height = video.videoHeight;
                App.canvas.getContext('2d').drawImage(video, 0, 0, App.canvas.width, App.canvas.height);

                // Otherwise, if the context is Flash, we ask the shim to
                // directly call window.webcam, where our shim is located
                // and ask it to capture for us.
            } else if(App.options.context === 'flash'){
                window.webcam.capture();
            }
            else{
                alert('No context was supplied to getSnapshot()');
            }
        }

    };

    window.lm_personal_Snapshot = App;
})();


