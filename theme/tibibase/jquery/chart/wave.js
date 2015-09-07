/**
  * Created by FullZero on 05/06/2015.
  */

(function (undefined) {
	window.chart = window.chart || {};
	
	function elm(type) {
		return document.createElementNS('http://www.w3.org/2000/svg', type);
	}
	
	chart.Wave = function (target, options) {
		var self = this;

		/*svg object*/
		var svg = elm('svg'),
			$svg = $(svg);

        svg.setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:xlink", "http://www.w3.org/1999/xlink");
        svg.setAttributeNS(null, 'style', 'width: 100%; height: 100%');

		/*svg defs*/
		var defs = svg.appendChild(elm('defs'));

		var data = options.data || [];
		
		var values = $.map(data, function(row) {return row.value;});

		var max = options.max === undefined ? Math.max.apply(null, values) : options.max;
		var min = options.min === undefined ? Math.min.apply(null, values) : options.min;

		var xPadding = options.xPadding || 10;
		var yPadding = options.yPadding || 10;

		var caption;
		var total;
		var progress = [];
		var bars     = [];
		var labels   = [];

		var defColors = ['#34D35C', '#75B290', '#FEC200', '#6190D6', '#7cb5ec', '#90ed7d', '#f7a35c', '#8085e9', '#f15c80', '#e4d354', '#2b908f', '#f45b5b', '#91e8e1'];
		var colors = options.colors || defColors;
		var gradients = [];

        self.appendTo = function appendTo(node) {
            // clear parent elemtnt
            while (node.hasChildNodes()) node.removeChild(node.lastChild);
            // and append
            if (node instanceof Node) node.appendChild(svg);
        };
		self.appendTo(target);
		
		self.getNode = function getNode() {
			return svg;
		};

		function init() {
			svg.setAttributeNS(null, 'style',  'width: 100%; height: 100%');

			if (options.caption) createCaption();
            createGradients();
            createTotal();
			createWaves();
            createLabels();

			self.redraw();
		}

		function createGradients() {
			var gradient;
			for (var i = 0; i < values.length; i++) {
				gradient = elm('linearGradient');
				gradient.setAttributeNS(null, "id", "gradient-" + Date.now() + i);
                gradient.setAttributeNS(null, "x2", "100%");

				var start = gradient.appendChild(elm('stop'));
                start.setAttributeNS(null, 'offset', '0%');
				start.setAttributeNS(null, 'stop-opacity', '0.2');
                start.setAttributeNS(null, 'stop-color', colors[i%colors.length]);

				var stop = gradient.appendChild(elm('stop'));
                stop.setAttributeNS(null, 'offset', '50%');
				stop.setAttributeNS(null, 'stop-opacity', '1');
                stop.setAttributeNS(null, 'stop-color', colors[i%colors.length]);

				gradients.push('url(#' + defs.appendChild(gradient).getAttributeNS(null, 'id') + ')');
			}
		}
		
		function createCaption() {
			caption = elm('text');
			caption.setAttribute('class', 'chart-wave-caption');
			caption.setAttributeNS(null, 'text-anchor', 'middle');

			caption.textContent = options.caption;

			svg.appendChild(caption);
		}

		function createTotal() {
			total = elm('text');
			total.setAttributeNS(null, 'class', 'chart-wave-total');
			total.setAttributeNS(null, 'text-anchor', 'middle');

			if (options.total.label) {
				total.label = total.appendChild(elm('tspan'));
				total.label.setAttributeNS(null, 'class', 'chart-wave-total-label');
				total.label.textContent = options.total.label;
			}

            var totalValue = typeof options.total === 'number' ? options.total : options.total.value;

            if (typeof  totalValue === 'number') {
				total.value = total.appendChild(elm('tspan'));
				total.value.setAttributeNS(null, 'class', 'chart-wave-total-value');
				total.value.textContent = Math.round(10 * (totalValue)) / 10;

                if (options.unit) {
					total.unit = total.value.appendChild(elm('tspan'));
					total.unit.setAttributeNS(null, 'class', 'chart-wave-unit');
					total.unit.textContent = options.unit;
                }
            }

			svg.appendChild(total);
		}

		function createWaves() {
			for (var i = 0, l = data.length; i < l; i++) {
                bars.push(createPath());
                progress.push(createPath());
			}
		}

		function createLabels() {
			for (var i = 0, l = data.length; i < l; i++) {
                labels.push(createLabel(data[i].label, values[i], data[i].href, data[i].unit));
			}
		}

		this.redraw = function redraw() {
            /* init */
            var width = $svg.width();
            var height = $svg.height();

            var padding = {
                x: xPadding,
                y: yPadding
            };
            if (height - 2 * padding.y > width / 2 - padding.x) {
                padding.y = (height - width / 2 - padding.x) / 2;
            }

            var captionHeight = 0;
            if (caption) {
                captionHeight = caption.getBBox().height;
                caption.setAttributeNS(null, 'x', (width - 2 * padding.x) / 2);
                caption.setAttributeNS(null, 'y', yPadding + captionHeight);
            }
            captionHeight *= 1.3;

            var radius = {
                value: 0,
                min: 0.15 * (height - 2 * padding.y - captionHeight),
                max: (height - 2 * padding.y - captionHeight) / 2
            };
            var center = {
                //x: padding.x + 3 * (width - 2 * padding.x) / 4,
                x: width - padding.x - radius.max,
                y: padding.y + captionHeight + radius.max
            };

            /* total */
            var size = .6 * (height - 2 * padding.y - captionHeight) / 2;
            var pos = {
                x: padding.x + (width - 2 * padding.x) / 4,
                y: padding.y + captionHeight + (height - 2 * padding.y - captionHeight) / 4 - size / 4
            };
            total.setAttributeNS(null, 'x', pos.x);
            total.setAttributeNS(null, 'y', pos.y);
            if (total.label) {
                total.label.setAttributeNS(null, 'font-size', String(size * .3));
                total.label.setAttributeNS(null, 'x', String(pos.x));
            }
            if (total.value) {
                if (total.label) size *= .7;
                total.value.setAttributeNS(null, 'font-size', String(size));
                total.value.setAttributeNS(null, 'x', String(pos.x));
                total.value.setAttributeNS(null, 'dy', String(total.label ? size : 0.6*size));
            }

			/* lines */
			var lineWidth = (radius.max - radius.min)/data.length - 3;

            /* calc font-size for labels */
            var labelX = padding.x + .08*(width - 2*padding.x);
            var labelS = .8*lineWidth;
            for (var i = 0, l = data.length; i < l; i++) {
                labels[i].setAttributeNS(null, 'font-size', String(labelS));

                try {
                    var textWidth = labels[i].getBBox().width;
                    if (labelX + textWidth > center.x) labelS *= (center.x - labelX)/textWidth;
                } catch (e) {
                    // getBBox fails
                    // if svg invisible in ff
                }
            }

			var path, barPath, progressPath, angle;
			for (i = 0, l = data.length; i < l; i++) {
				radius.value = radius.min + i*(lineWidth+3) + lineWidth/2;

				path =  'M ' + padding.x + ' ' + (center.y + radius.value + 0.0001);
				path += ' L ' + center.x + ' ' + (center.y + radius.value);

				barPath = path + ' A ' + radius.value +' ' + radius.value +' 0 ' +
				          '1 0 ' + (center.x - radius.value) + ' ' + center.y;

				bars[i].setAttributeNS(null, 'stroke', gradients[i%gradients.length]);
				bars[i].setAttributeNS(null, 'opacity', '0.5');
				bars[i].setAttributeNS(null, 'stroke-width', String(lineWidth));
				bars[i].setAttributeNS(null, 'd', barPath);

				angle = ((values[i] - min)*3*Math.PI/2)/(max - min);
				progressPath = path + ' A ' + radius.value +' ' + radius.value +' 0 ' +
				               ' ' + (angle > Math.PI ? '1' : '0') + ' 0 ' +
							   (center.x + radius.value*Math.sin(angle)) + ' ' +
							   (center.y + radius.value*Math.cos(angle));

				progress[i].setAttributeNS(null, 'stroke', gradients[i%gradients.length]);
				progress[i].setAttributeNS(null, 'stroke-width', String(lineWidth));
				progress[i].setAttributeNS(null, 'd', progressPath);

                /* labels */
                labels[i].setAttributeNS(null, 'x', String(labelX));
                labels[i].setAttributeNS(null, 'y', String(center.y + radius.value + labelS/3));
                labels[i].setAttributeNS(null, 'font-size', String(labelS));
                labels[i].value.setAttributeNS(null, 'dx', String(.015*(width - 2*padding.x)));
			}

		};

		function createPath(path, color, width) {
			var p = svg.appendChild(elm('path'));
			p.setAttributeNS(null, 'fill', 'none');
			p.setAttributeNS(null, 'stroke', color || '#000');
			p.setAttributeNS(null, 'stroke-width', width || 3);
			p.setAttributeNS(null, 'd', path || '');

			return p;
		}

		function createLabel(label, value, href, unit) {
			var l = elm('text');
			l.setAttributeNS(null, 'text-anchor', 'left');
			l.setAttributeNS(null, 'fill', '#213685');

			l.label = l.appendChild(elm('tspan'));
			l.label.setAttributeNS(null, 'class', 'chart-wave-label');
			l.label.textContent = label;

			l.value = l.appendChild(elm('tspan'));
			l.value.setAttributeNS(null, 'class', 'chart-wave-value');
			l.value.textContent = Math.round(10*value)/10;

			if (unit || options.unit) {
				l.unit = l.value.appendChild(elm('tspan'));
				l.unit.setAttributeNS(null, 'class', 'chart-wave-unit');
				l.unit.textContent = unit || options.unit;
			}

            if (href) {
                l.link = elm('a');
                l.link.setAttributeNS('http://www.w3.org/1999/xlink', 'xlink:href', href);
                l.link.appendChild(l);
                svg.appendChild(l.link);
            } else {
                svg.appendChild(l)
            }
			return l;
		}

        init();
	// /Graph
	};

	// extend jQuery
	(function( $ ) {

		var defaults = {
			data: []
		};

        /**
         * Рисует SVG график
         * @param {object} options
         *                data - массив типа [{label: 'label1', value: 11[[, href: '/foobar'], unit: '%']}, ...]
         *                min - минимальное значение (для масштабированя графика)
         *                max - минимальное значение (для масштабированя графика)
         *                xPadding - горизонтальный отступ от краев
         *                xPadding - вертикальный отступ от краев
         *                colors - массив цветов для полос
         *                caption - заголовок
         *                total - итого. либо цифра, либо объект вида {label: 'Итого', value: 557}
         *                         суммирующее значение (цифра) для заголовка (сумма/среднее/максимальное)
         *                unit - единица измерения (шт, %, руб...)
         */
		$.fn.chartWave = function(options) {
            var chart = this.data('chart');
            if ( ! chart) {
                options = $.extend({}, defaults, options);
                for (var i = 0, l = this.length; i < l; i++) {
                    chart = new window.chart.Wave(this[i], options);
                    $.data(this[i], 'chart', chart);
                }
            }
            return chart;
		};

	})(jQuery);

})();
