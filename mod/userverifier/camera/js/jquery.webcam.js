/**
 * @license jQuery webcam plugin v1.0.0 09/12/2010
 * http://www.xarg.org/project/jquery-webcam-plugin/
 *
 * Copyright (c) 2010, Robert Eisele (robert@xarg.org)
 * Dual licensed under the MIT or GPL Version 2 licenses.
 **/

navigator.getUserMedia = navigator.webkitGetUserMedia || navigator.getUserMedia;
window.URL = window.URL || window.webkitURL;

(function ($) {

    var webcam = {
		// General options
		"width": 320,
		"height": 240,
		videoEl: "",
		context: "",


		// Swf mode options
		"trigger": "#takeSnapshot",
		canvas: "canvas",
		"extern": null, // external select token to support jQuery dialogs
		"append": true, // append object instead of overwriting
		"mode": "callback", // callback | save | stream
		"swffile": "/mod/userverifier/camera/fallback/jscam_canvas_only.swf",
		"quality": 85,
		isstream: false, // Включено ли видео?


		// HTML5 mode options
		video: true,
		audio: false,


		init: function(){
			this.canvas = document.getElementById(this.canvas);
			this.ctx = this.canvas.getContext("2d");
			this.ctx.clearRect(0, 0, this.width, this.height);

			this.image = new Image();
			this.image = this.ctx.getImageData(0, 0, this.width, this.height);

			this.pos = 0;
			this.run = 3;
			this.interval = 0;
		},

		debug:	function () {},
		onCapture:	function () {
			this.save();
		},
		onTick:	function () {},
		onSave:	function (data) {
			var col = data.split(";");
			var img = this.image;
			var prevtmp = parseInt(col[0]);

			for(var i = 0; i < 320; i++) {
				var tmp = parseInt(col[i]);

				if(this.interval && !this.isstream && this.context == "flash"){
					if(tmp != prevtmp){
						this.startStream();
					}
					prevtmp = tmp;
				}

				img.data[this.pos + 0] = (tmp >> 16) & 0xff;
				img.data[this.pos + 1] = (tmp >> 8) & 0xff;
				img.data[this.pos + 2] = tmp & 0xff;
				img.data[this.pos + 3] = 0xff;
				this.pos+= 4;
			}


			if (this.pos >= 4 * 320 * 240) {

				this.ctx.putImageData(img, 0, 0);
				this.pos = 0;
			}

		},

		onLoad:	function () {
			console.log("onLoad");
			if(!this.isIE()) {
				this.listenStream();
			}
		},

		listenStream: function(){
			console.log("startListen");
			this.interval = window.setInterval(function () {
				window.webcam.capture();
			}, 1000);
		},

		stopListenStream: function(){
			window.clearInterval(this.interval);
			this.interval = 0;
		},








		registerWebrtc: function() {
			// constructing a getUserMedia config-object and
			// an string (we will try both)
			var option_object = {};
			var option_string = '';
			var container, temp, video, ow, oh;

			if (this.video === true) {
				option_object.video = true;
				option_string = 'video';
			}
			if (this.audio === true) {
				option_object.audio = true;
				if (option_string !== '') {
					option_string = option_string + ', ';
				}
				option_string = option_string + 'audio';
			}

			container = document.getElementById("webcam");
			temp = document.createElement('video');

			// Fix for ratio
			ow = parseInt(container.offsetWidth, 10);
			oh = parseInt(container.offsetHeight, 10);


			if (this.width < ow && this.height < oh) {
				this.width = ow;
				this.height = oh;
			}

			// configure the interim video
			if( /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
				temp.width = this.width;
				//temp.height = options.height;
			}else{
				temp.width = this.width;
				temp.height = this.height;
			}


			temp.autoplay = true;
			container.appendChild(temp);
			video = temp;


			// referenced for use in your applications
			this.videoEl = video;
			this.context = 'webrtc';


			// first we try if getUserMedia supports the config object
			var app = this;
			try {
				// try object
				navigator.getUserMedia = navigator.webkitGetUserMedia || navigator.getUserMedia;
				window.URL = window.URL || window.webkitURL;
				navigator.getUserMedia({video: true}, function(s){app.startStream(s)}, function(e){app.noStream(e)});
				//navigator.getUserMedia({video: true}, gotStream, noStream);
			} catch (e) {
				// option object fails
				try {

					// try string syntax
					// if the config object failes, we try a config string
					navigator.getUserMedia(option_string, function(s){app.startStream(s)}, function(e){app.noStream(e)});
				} catch (e2) {
					// both failed
					// neither object nor string works
					return undefined;
				}
			}
		},


		registerFlash: function() {

			var cam = document.getElementById('XwebcamXobjectX');

			if (cam && cam.capture !== undefined) {
				/* Simple callback methods are not allowed :-/ */
				webcam.capture = function(x) {
					try {
						return cam.capture(x);
					} catch(e) {}
				}
				webcam.save = function(x) {
					try {
						return cam.save(x);
					} catch(e) {}
				}
				webcam.setCamera = function(x) {
					try {
						return cam.setCamera(x);
					} catch(e) {}
				}
				webcam.getCameraList = function() {
					try {
						return cam.getCameraList();
					} catch(e) {}
				}
				webcam.pauseCamera = function() {
					try {
						return cam.pauseCamera();
					} catch(e) {}
				}
				webcam.resumeCamera = function() {
					try {
						return cam.resumeCamera();
					} catch(e) {}
				}
				webcam.context = 'flash';
				webcam.onLoad();
			} else if (0 == this.run) {
				webcam.debug("error", "Flash movie not yet registered!");
			} else {
				/* Flash interface not ready yet */
				this.run--;
				window.setTimeout(this.registerFlash, 1000 * (4 - this.run));
			}
		},

		startStream: function(stream){
			// В Safari невозможно расположить html-элементы поверх object, поэтому испольуем этот хак
			// Как только пользователь разрешил использование камеры, сдвигаем флеш объект за пределы экрана
			// А так же устанавливаем высоту и ширину равной 0, чтобы функция jquery  .is(":visible") возвращала false

			this.isstream = true;

			if (this.context === 'webrtc') {
				console.log("webrtc stream");
				var video = this.videoEl;

				if ((typeof MediaStream !== "undefined" && MediaStream !== null) && stream instanceof MediaStream) {
					if (video.mozSrcObject !== undefined) { //FF18a
						video.mozSrcObject = stream;
					} else { //FF16a, 17a
						video.src = stream;
					}

					return video.play();

				} else {

					video.src = window.URL ? window.URL.createObjectURL(stream) : stream;
				}


				video.onerror = function (e) {
					webcam.isstream = false;
					stream.stop();
					//streamError();
				};

				stream.onended = this.noStream;

			}else if(this.context == 'flash') {
				console.log("flash stream");
				this.stopListenStream();
				this.resumeStream();

			}
		},

		pauseStream: function(){
			var container = $("#webcam"),
				canvas = $("#canvas"),
				triggerico = $("#takeSnapshot i"),
				savebtn = $(".save-button");

			this.isstream = false;
			if (this.context == 'flash' && !this.isIE()) {
				if (this.interval) {
					console.log("clear " + this.interval);
					window.clearInterval(this.interval);
					this.interval = 0;

				}
			}else {
				container.css("display", "none");
			}

			canvas.css("display", "block");
			triggerico.removeClass("icon-photo").addClass("icon-sync");
			savebtn.css("display", "block");
		},

		resumeStream: function(){
			var container = $("#webcam"),
				canvas = $("#canvas"),
				triggerico = $("#takeSnapshot i"),
				savebtn = $(".save-button");

			this.isstream = true;

			if (this.context == 'flash' && !this.isIE()) {
				this.interval = window.setInterval(function () {

					window.webcam.capture();
					console.log("capture");
				}, 500);

				$("#webcam").css({height: "0px", width: "0px", position: "absolute", left: "-9999px"});
				$("canvas").css({display: "block"});
			}else {
				container.css("display", "block");
				canvas.css("display", "none");
			}

			triggerico.removeClass("icon-sync").addClass("icon-photo");
			savebtn.css("display", "none");
		},



		getSnapshot: function () {
			var canvas = $("#canvas");

			console.log("isstream: "+this.isstream);
			if( this.isstream) {
				console.log("click stop stream");
				this.pauseStream();
			}else{
				console.log("click resume stream");
				this.resumeStream();
				console.log("isstream fater resume: "+webcam.isstream);
			}

			// If the current context is WebRTC/getUserMedia (something
			// passed back from the shim to avoid doing further feature
			// detection), we handle getting video/images for our canvas
			// from our HTML5 <video> element.
			if (this.context === 'webrtc') {
				var video = document.getElementsByTagName('video')[0];
				$("#canvas").css({width:this.width+"px"});

				this.canvas.width = video.videoWidth;
				this.canvas.height = video.videoHeight;

				this.ctx.drawImage(video, 0, 0, this.canvas.width, this.canvas.height);

				// Otherwise, if the context is Flash, we ask the shim to
				// directly call window.webcam, where our shim is located
				// and ask it to capture for us.
			} else if(this.context === 'flash'){
				window.webcam.capture();
			}
			else{
				alert('No context was supplied to getSnapshot()');
			}
		},

		noStream: function (e) {
			var msg = 'No camera available.';
			if (e.code == 1) {
				msg = 'User denied access to use camera.';
			}

			console.error('An error occurred: [CODE ' + e.code + ']');
		},

		uploadSnapshot: function(btn){
			var url = $(btn).data("action");
			console.log(this.canvas.toDataURL());

			$.ajax({
				type: "POST",
				url: url,
				data: {"image": this.canvas.toDataURL()},
				success: function(a){
					a = eval('('+a+')');
					if(a.success){
						window.location = a.redirect;
					}else{
						alert(a.errormessage);
					}
				}
			});
		},

		isIE: function(){
			if(/MSIE/i.test(navigator.userAgent)){
				return true;
			}
			if(navigator.userAgent.match(/Trident\/7.0; | rv 11.0/)){
				return true;
			}

			return false;
		}

	};
	webcam.init();

	window.webcam = webcam;


	$(webcam.trigger).on("click", function(){webcam.getSnapshot()});

	$.fn.webcam = function(options) {

		if (typeof options === "object") {
			for (var ndx in webcam) {
				if (options[ndx] !== undefined) {
					webcam[ndx] = options[ndx];
				}
			}
		}

		// getUserMedia() feature detection
		//navigator.getUserMedia_ = (navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia);
		//if ( !! navigator.getUserMedia_ ) {
		if ( !!navigator.getUserMedia ) {
			webcam.registerWebrtc();
		} else {

			if( /Android|webOS|iPhone|iPad|iPod|BlackBerry/i.test(navigator.userAgent) ) {
				$("body").addClass("no-getusermedia-mobile");
			}else{
				var source = '<object id="XwebcamXobjectX" type="application/x-shockwave-flash" data="'+webcam["swffile"]+'" width="'+webcam["width"]+'" height="'+webcam["height"]+'"><param name="movie" value="'+webcam["swffile"]+'" /><param name="FlashVars" value="mode='+webcam["mode"]+'&amp;quality='+webcam["quality"]+'" /><param name="allowScriptAccess" value="always" /></object>';

				if (null !== webcam.extern) {
					$(webcam.extern)[webcam.append ? "append" : "html"](source);
				} else {
					this[webcam.append ? "append" : "html"](source);
				}

				webcam.registerFlash();
			}
		}





		$(".snapshot-normal .save-button").on("click", function(){
			webcam.uploadSnapshot(this);
		});


	}

})(jQuery);
