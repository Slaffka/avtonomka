/*globals  $: true, getUserMedia: true, alert:true, ccv:true */

/*! getUserMedia demo - v1.0
* for use with https://github.com/addyosmani/getUserMedia.js
* Copyright (c) 2012 addyosmani; Licensed MIT */

 (function () {
	'use strict';

	var App = {

		init: function () {

			// The shim requires options to be supplied for it's configuration,
			// which can be found lower down in this file. Most of the below are
			// demo specific and should be used for reference within this context
			// only

			if ( !!this.options ) {

				this.pos = 0;
				this.cam = null;
				this.filter_on = false;
				this.filter_id = 0;

				this.canvas = document.getElementById("canvas");
				this.ctx = this.canvas.getContext("2d");
				this.img = new Image();
				this.ctx.clearRect(0, 0, this.options.width, this.options.height);
				this.image = this.ctx.getImageData(0, 0, this.options.width, this.options.height);
				this.snapshotBtn = document.getElementById('takeSnapshot');
				this.saveBtn = document.getElementById('saveButton');


				// Initialize getUserMedia with options
				getUserMedia(this.options, this.success, this.deviceError);

				// Initialize webcam options for fallback
				window.webcam = this.options;

				// Trigger a snapshot
				this.addEvent('click', this.snapshotBtn, this.getSnapshot);

				// Trigger an upload
				$(".snapshot-normal .save-button").on("click", this.uploadSnapshot);



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
			el: "webcam",

			extern: null,
			append: true,

			// noFallback:true, use if you don't require a fallback

			width: 320,
			height: 240,


			mode: "callback",
			// callback | save | stream
			swffile: "fallback/jscam_canvas_only.swf",
			quality: 85,
			context: "",

			debug: function () {},
			onCapture: function () {
				window.webcam.save();
			},
			onTick: function () {},
			onSave: function (data) {
				var col = data.split(";"),
					img = App.image,
					tmp = null,
					w = this.width,
					h = this.height;


				for (var i = 0; i < w; i++) { 
					tmp = parseInt(col[i], 10);
					img.data[App.pos + 0] = (tmp >> 16) & 0xff;
					img.data[App.pos + 1] = (tmp >> 8) & 0xff;
					img.data[App.pos + 2] = tmp & 0xff;
					img.data[App.pos + 3] = 0xff;
					App.pos += 4;
				}

				if (App.pos >= 4 * w * h) {
					App.ctx.putImageData(img, 0, 0);
					App.pos = 0;
				}

			},
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
			var webcam = $("#webcam"),
				canvas = $("#canvas"),
				triggerico = $("#takeSnapshot i"),
				savebtn = $(".save-button");

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
				$("#canvas, #webcam").attr("width", 320);
				$("#canvas, #webcam").attr("height", 320);
				$("#canvas").css({width:"320px"});

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
		},

		uploadSnapshot: function(){
			alert(App.canvas.toDataURL());
		}

	};

	App.init();

})();


