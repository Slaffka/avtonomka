/**
  * Created by FullZero on 05/06/2015.
  */

(function (undefined) {
	window.chart = window.chart || {};
	
	function elm(type) {
		return document.createElementNS('http://www.w3.org/2000/svg', type);
	}
	
	chart.Pie = function (target, options) {
		var self = this;

		/*svg object*/
		var svg = elm('svg'),
			$svg = $(svg);

        svg.setAttributeNS(null, 'style', 'width: 100%; height: 100%');

        /*svg defs*/
        var defs = svg.appendChild(elm('defs'));
        var circleMarker = defs.appendChild(elm('marker'));

        circleMarker.setAttributeNS(null, 'id', 'marker-' + Date.now());
        circleMarker.setAttributeNS(null, 'viewBox', '0 0 12 12');
        circleMarker.setAttributeNS(null, 'refX', '6');
        circleMarker.setAttributeNS(null, 'refY', '6');
        circleMarker.setAttributeNS(null, 'markerWidth', '12');
        circleMarker.setAttributeNS(null, 'markerHeight', '12');

        circleMarker.appendChild(createCircle(6, 6, 6, 1, 'none'));
        circleMarker.appendChild(createCircle(6, 6, 2));

        var data = options.data || [];
		
		var values = $.map(data, function(row) {return row.value;});

        var sum = 0, i = values.length;
        while (i--) sum += values[i];

		var xPadding = options.xPadding || 10;
		var yPadding = options.yPadding || 10;

		var caption;
		var total;
		var sectors = [];
		var labels  = [];

		var defColors = ['#34D35C', '#75B290', '#FEC200', '#6190D6', '#7cb5ec', '#90ed7d', '#f7a35c', '#8085e9', '#f15c80', '#2b908f', '#f45b5b', '#91e8e1'];
		var colors = options.colors || defColors;

        this.appendTo = function appendTo(node) {
            // clear parent elemtnt
            while (node.hasChildNodes()) node.removeChild(node.lastChild);
            // and append
            if (node instanceof Node) node.appendChild(svg);
        };
		this.appendTo(target);
		
		this.getNode = function getNode() {
			return svg;
		};

		function init() {
			svg.setAttributeNS(null, 'style',  'width: 100%; height: 100%');
            if (options.caption) createCaption();
            createSectors();
            createLabels();
            if (options.total) createTotal();

			self.redraw();
		}

        function createCaption() {
            caption = elm('text');
            caption.setAttribute('class', 'caption');
            caption.setAttributeNS(null, 'text-anchor', 'middle');

            caption.textContent = options.caption;

            svg.appendChild(caption);
        }

        function createTotal() {
            total = elm('text');
            $.data(total, options.total);
            total.setAttribute('class', 'total');
            total.setAttributeNS(null, 'text-anchor', 'middle');

            if (options.total.caption) {
                total.caption = total.appendChild(elm('tspan'));
                total.caption.textContent = options.total.caption;
            }

            var value = Number(options.total.value || options.total);
            total.value = total.appendChild(elm('tspan'));
            total.value.textContent = Math.round(10*value)/10;

            if (options.unit) {
                total.unit = total.value.appendChild(elm('tspan'));
                total.unit.setAttributeNS(null, 'class', 'graph-pie-unit');
                total.unit.textContent = options.unit;
            }

            svg.appendChild(total);
        }

        var selectedSector = null;
        function selecSector() {
            var params = {
                duration: 150,
                progress: animateSector
            };
            if (selectedSector) selectedSector.$.animate({radius: 0}, params);
            if (selectedSector === this) {
                selectedSector = null;
            } else {
                this.$
                    .addClass('selected')
                    .animate({radius: 1}, params);
                selectedSector = this;
            }
        }
        function animateSector(a, now) {
            now = a.props.radius ? now : 1 - now;
            var a = this.$.data('_angle');
            var r = 0.1 * now * this.$.data('_radius');
            var x = r * Math.cos(a);
            var y = - r * Math.sin(a);
            this.setAttributeNS(null, 'transform', 'matrix(1,0,0,1,' + x + ',' + y + ')');
        }
        function createSectors() {
            var color = 0, sector;
            for (var i = 0, l = data.length; i < l; i++) {
                sector = null;
                if (values[i]) {
                    sector = createPath(null, colors[color++%colors.length], null, 0);
                    sector.setAttribute('class', 'sector');
                    sector.$ = $(sector)
                        .data(data[i])
                        .click(selecSector);
                }
                sectors.push(sector);
            }
        }

        function createLabels() {
            var label;
			for (var i = 0, l = data.length; i < l; i++) {
                if (values[i]) {
                    label = createLabel(data[i].label, values[i]);
                    label.setAttribute('class', 'label');
                } else {
                    label = null;
                }
                labels.push( label);
			}
		}

		this.redraw = function redraw() {
			/* init */
			var width  = $svg.width();
			var height = $svg.height();
		
            var center = {
                x: width/2,
                y: height/2
            };

            var captionHeight = 0;
            if (caption) {
                captionHeight = caption.getBBox().height;
                caption.setAttributeNS(null, 'x', center.x);
                caption.setAttributeNS(null, 'y', yPadding + captionHeight);
                center.y += yPadding + captionHeight/2;
            }

            var radius = {};
            radius.max = Math.min((height/2 - 2*yPadding - captionHeight/2)/1.2, 2*(width - xPadding)/3);
            radius.min = options.total ? .4*radius.max : 0;
            radius.label = 1.2*radius.max;

            var labelPos = yPadding + captionHeight;

            var path;
            //var angle = Math.PI*Math.random();
            var angle = .6*Math.PI;
            var sector, size, pos, labelAngle;
			for (var i = 0, l = data.length; i < l; i++) if (values[i]) {

                /* sectors */
				path = 'M ' + (center.x + radius.min*Math.cos(angle)) + ' '
                            + (center.y - radius.min*Math.sin(angle));

                path += ' L ' + (center.x + radius.max*Math.cos(angle)) + ' '
                              + (center.y - radius.max*Math.sin(angle));

                sector = values[i]*2*Math.PI/sum;

                path += ' A ' + radius.max +' ' + radius.max +' 0 ' +
                        ' ' + (sector > Math.PI ? '1' : '0') + ' 1 ' +
                        (center.x + radius.max*Math.cos(angle - sector + 0.0001)) + ' ' +
                        (center.y - radius.max*Math.sin(angle - sector + 0.0001));

                path += ' L ' + (center.x + radius.min*Math.cos(angle - sector)) + ' '
                              + (center.y - radius.min*Math.sin(angle - sector));

                if (options.total) {
                    path += ' A ' + radius.min + ' ' + radius.min + ' 0 ' +
                            ' ' + (sector > Math.PI ? '1' : '0') + ' 0 ' +
                            (center.x + radius.min * Math.cos(angle)) + ' ' +
                            (center.y - radius.min * Math.sin(angle));
                }

                angle -= sector;

                sectors[i].$.data({_angle: angle + sector/2, _radius: radius.max});
                sectors[i].setAttributeNS(null, 'd', path);

                /* total */
                if (options.total) {
                    size = 1 * radius.min;
                    pos = {
                        x: center.x,
                        y: center.y - 0.3 * radius.min
                    };
                    total.setAttributeNS(null, 'x', String(pos.x));
                    total.setAttributeNS(null, 'y', String(pos.y));

                    total.value.setAttributeNS(null, 'x', String(pos.x));
                    if (total.caption) {
                        total.caption.setAttributeNS(null, 'font-size', String(size * .3));
                        total.caption.setAttributeNS(null, 'x', String(pos.x));

                        total.value.setAttributeNS(null, 'font-size', String(size * .7));
                        total.value.setAttributeNS(null, 'dy', String(size * .7));
                    } else {
                        total.value.setAttributeNS(null, 'font-size', String(size));
                        total.value.setAttributeNS(null, 'dy', String(size * .5));
                    }
                }

                /* labels */
                size = labels[i].getBBox().height;
                labelAngle = angle + sector/2;
                pos = {
                    x: center.x + radius.label*Math.cos(labelAngle),
                    y: center.y - radius.label*Math.sin(labelAngle)
                };
                // prevent label overlap
                /*if (Math.abs(pos.y - labelPos) < .6*size) {
                    labelAngle = Math.acos((pos.y + .6*size*Math.sign(pos.y - labelPos))/radius.label);
                    pos = {
                        x: center.x + radius.label*Math.cos(labelAngle),
                        y: center.y - radius.label*Math.sin(labelAngle)
                    };
                    labelPos = pos.y;
                }*/

                labels[i].setAttributeNS(null, 'x', String(pos.x));
                labels[i].setAttributeNS(null, 'y', String(pos.y));
                labels[i].setAttributeNS(null, 'text-anchor', pos.x > center.x ? 'start' : 'end');
                labels[i].value.setAttributeNS(null, 'dx', String(.015*(width - 2*xPadding)));

                path = 'M ' + (pos.x + (pos.x > center.x ? 1 : -1)*labels[i].getBBox().width/2)  + ' ' + (pos.y + .4*size) +
                       'L ' + pos.x  + ' ' + (pos.y + 0.4*size) +
                       'L ' + (center.x + .8*radius.max*Math.cos(angle + sector/2)) + ' '
                            + (center.y - .8*radius.max*Math.sin(angle + sector/2));

                labels[i].arrow.setAttributeNS(null, 'd', path);
			}
			
		};
		
		function createCircle(x, y, r, w, fill, stroke) {
			var c = svg.appendChild(elm('circle'));
            c.setAttributeNS(null, 'cx', x || '0');
            c.setAttributeNS(null, 'cy', y || '0');
            c.setAttributeNS(null, 'r', r || '1');
			c.setAttributeNS(null, 'fill', fill || '#fff');
			c.setAttributeNS(null, 'stroke', stroke || '#fff');
			c.setAttributeNS(null, 'stroke-width', w || '0');

			return c;
		}

		function createPath(path, fill, stroke, width) {
			var p = svg.appendChild(elm('path'));
			p.setAttributeNS(null, 'fill', fill || 'none');
			p.setAttributeNS(null, 'stroke', stroke || '#000');
			p.setAttributeNS(null, 'stroke-width', width === undefined ? '1' : width);
			p.setAttributeNS(null, 'd', path || '');

			return p;
		}

		function createLabel(label, value) {
			var l = elm('text');
			l.setAttributeNS(null, 'fill', '#213685');
			
			l.label = l.appendChild(elm('tspan'));
			l.label.setAttributeNS(null, 'class', 'graph-pie-label');
			l.label.textContent = label;

			l.value = l.appendChild(elm('tspan'));
			l.value.setAttributeNS(null, 'class', 'graph-pie-value');
			l.value.textContent = Math.round(10*value)/10;

			if (options.unit) {
				l.unit = l.value.appendChild(elm('tspan'));
				l.unit.setAttributeNS(null, 'class', 'graph-pie-unit');
				l.unit.textContent = options.unit;
			}

            l.arrow = createPath(null, null, '#033282');
            l.arrow.setAttributeNS(null, 'marker-end', 'url(#'+circleMarker.getAttributeNS(null, 'id')+')');
			return svg.appendChild(l);
		}

        init();
	// /Graph
	};

	// extend jQuery
	(function( $ ) {

		var defaults = {};

        /**
         * Рисует SVG график
         * @param {object} options
         *                data - массив типа [{label: 'label1', value: 11}, ...]
         *                xPadding - горизонтальный отступ от краев
         *                xPadding - вертикальный отступ от краев
         *                colors - массив цветов для полос
         *                caption - заголовок графика
         *                total - объект, итого:
         *                      total.caption - текст в центре пирога (например "Всего")
         *                      total.value - суммирующее значение (цифра) для заголовка (сумма/среднее/максимальное)
         *                unit - единица измерения (шт, %, руб...)
         */
		$.fn.chartPie = function(options) {
            var chart = this.data('chart');
            if (!chart) {
                options = $.extend({}, defaults, options);
                for (var i = 0, l = this.length; i < l; i++) {
                    chart = new window.chart.Pie(this[i], options);
                    $.data(this[i], 'chart', chart);
                }
            }
            return chart;
		};

	})(jQuery);

})();
