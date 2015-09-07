/**
 * Created by FullZero on 4/27/2015.
 */

(function(undefined) {
    window.chart = window.chart || {};

    function elm(type) {
        return document.createElementNS('http://www.w3.org/2000/svg', type);
    }

    chart.Line = function (target, options) {
        var self = this;
        var chart = self;

        /*svg object*/
        var svg = elm('svg'),
            $svg = $(svg),
            width,
            height,

            data = options.data,

        /*svg defs*/
            defs = svg.appendChild(elm('defs')),
        /*gradient*/
            gradient,
        /*plot*/
            plot,
        /*line*/
            line,
        /*vertices*/
            vertexGroup,
            vertices = [],
            vertexSize = options.vertices && options.vertices.size ? options.vertices.size/2 : 4,
        /*axis*/
            xAxis,
            yAxis,
        /*current object*/
            C,

            values,
            points = [],

            max,
            min,

            colors = options.colors ? options.colors : [{threshold: Number.NEGATIVE_INFINITY, color: options.color ? options.color : '#000000'}],

            xPadding = (typeof options.xPadding === 'number' ? options.xPadding : 15),
            yPadding = (typeof options.yPadding === 'number' ? options.yPadding : 10),
            plotPadding = Math.max(0, options.width ? options.width/2 : 0, options.vertices ? vertexSize : 0),

            labelsWidth  = 0,
            labelsHeight = 0,

            scrollBar,
            scrollBarWidth = 0;

        var range = {
            left:  options.range && options.range.left  ? options.range.left  : 0,
            right: options.range && options.range.right ? options.range.right : data.length - 1
        };
        if (range.left < 0) range.left = 0;
        if (range.right > data.length - 1) range.right = range.right > data.length - 1;
        if (range.right < range.left) range.right = range.left;

        svg.setAttributeNS(null, "style", "width: 100%; height: 100%");

        values = $.map(data, function(value, index) {return typeof value === 'object' ? value.value : value;});

        max = typeof options.max === "undefined" ? Math.max.apply(null, values) : options.max;
        min = typeof options.min === "undefined" ? Math.min.apply(null, values) : options.min;

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

        // Return the x pixel for a graph point
        function getXPixel(val) {
            var plotWidth = .95*width - 2*xPadding - 2*plotPadding - labelsWidth;
            var plotLeft = xPadding + labelsWidth + plotPadding + .025*width;
            var scale = plotWidth / (range.right - range.left);
            return plotLeft + (val - range.left) * scale;
        }
        // Return the y pixel for a graph point
        function getYPixel(val) {
            var plotHeight = height - scrollBarWidth - labelsHeight - 2*yPadding - 2*plotPadding;
            return plotHeight + yPadding + plotPadding - plotHeight * (val - min) / (max - min);
        }

        function easeOutQuad(t) { return t*(2-t) }
        function easeInOutQuad(t) { return t<.5 ? 2*t*t : -1+(4-2*t)*t }

        var scrollAnimTimer;
        function scrollTo(value, animate) {
            var self = this;
            clearInterval(scrollAnimTimer);

            var scale = range.right - range.left;
            var maxLeft = values.length - scale - 1;
            if (value < 0) value = 0;
            else if (value > maxLeft) value = maxLeft;

            if ( ! animate && animate !== undefined) {
                range.left = value;
                range.right = range.left + scale;
                self.redraw();
            } else {
                var step = .1*scale;
                if (Math.abs(range.left - value) > step) {
                    scrollAnimTimer = setInterval(function() {
                        range.left += step*Math.sign(value - range.left);
                        if (Math.abs(range.left - value) < step) {
                            range.left = value;
                            clearInterval(scrollAnimTimer);
                        }
                        range.right = range.left + scale;
                        self.redraw();
                    }, 20);
                }
            }
        }
        this.scrollTo = scrollTo;

        /*saves elements as variables*/
        function init() {

            width = $svg.width();
            height = $svg.height();

            svg.setAttributeNS(null, 'style',  'width: 100%; height: 100%');

            plot = createPlot();

            /* create control points */
            calculatePoints();
            createGradient();

            /*create line*/
            if (options.type !== 'none') line = createPath();

            /*create control points*/
            createVertices();

            if (options.xAxis) createXAxis();
            if (options.yAxis) createYAxis();
            if (options.scrollBar) createScrollBar();

            self.redraw();
        }

        function calculatePoints() {
            points = [];
            if (options.flexEdge) {
                points.push({x: getXPixel(0) - xPadding + options.width, y: getYPixel(min), value: min});
            }
            for(var index in values) {
                points.push({x: getXPixel(index), y: getYPixel(values[index]), value: values[index]});
            }
            if (options.flexEdge) {
                points.push({x: getXPixel(index) + xPadding - options.width, y: getYPixel(min), value: min});
            }
        }

        /*creates an SVG gradient */
        function createGradient() {
            gradient = elm('linearGradient');
            var stop, color;

            colors = Array.prototype.sort.apply(colors, function(a, b){return a.threshold - b.threshold});

            gradient.setAttributeNS(null, "id", "gradient-" + Date.now() + Math.round(99999*Math.random()));

            // горизонтально: вариант когда линия окрашивается двумя цветами при перепаде от min до max
            if (true) {
                gradient.setAttributeNS(null, "x1", "0%");
                gradient.setAttributeNS(null, "y1", "0%");
                gradient.setAttributeNS(null, "x2", "100%");
                gradient.setAttributeNS(null, "y2", "0");
                var width = points[points.length - 1].x - points[0].x;
                for (var i = 0, l = points.length - 1, j; i <= l; i++) {
                    stop = elm('stop');
                    stop.setAttributeNS(null, "offset", (100*(points[i].x-points[0].x)/width) + "%");
                    if (data[i] && data[i].color) {
                        color = data[i].color;
                    } else {
                        color = colors.length ? colors[0].color : '#000000';
                        for(j in colors) {
                            if (points[i].value > colors[j].threshold) color = colors[j].color;
                            else break;
                        }
                    }
                    points[i].color = color;
                    stop.setAttributeNS(null, "stop-color", color);
                    gradient.appendChild(stop);
                }

                // вертикально: вариант когда линия окрашивается всеми указаными цветами при перепаде от min до max
            } else {
                gradient.setAttributeNS(null, "x1", "0");
                gradient.setAttributeNS(null, "y1", "100%");
                gradient.setAttributeNS(null, "x2", "0");
                gradient.setAttributeNS(null, "y2", "0");
                for(j in colors) {
                    stop = elm('stop');
                    stop.setAttributeNS(null, "offset", (100*(colors[j].threshold/max)) + "%");
                    stop.setAttributeNS(null, "stop-color", colors[j].color);
                    gradient.appendChild(stop);
                }
            }
            defs.appendChild(gradient);
        }

        /*creates an xAxis */
        function createXAxis() {
            xAxis = elm('g');
            xAxis.axis = elm('line');

            var axisWidth = options.xAxis.width ? options.xAxis.width : 1;

            if (options.xAxis.axis) {
                xAxis.axis.setAttributeNS(null, "stroke-width", axisWidth);
                xAxis.axis.setAttributeNS(null, "x2", width - xPadding);
                xAxis.axis.setAttributeNS(null, "stroke", options.xAxis.color ? options.xAxis.color : '#002060');
                xAxis.appendChild(xAxis.axis);

                if (options.xAxis.arrow) {
                    var arrowMarker = elm('marker');

                    arrowMarker.setAttributeNS(null, "id", "arrowMarker-" + Date.now() + Math.round(99999*Math.random()));
                    arrowMarker.setAttributeNS(null, "stroke", 'none');
                    arrowMarker.setAttributeNS(null, "fill", options.xAxis.color ? options.xAxis.color : '#002060');
                    arrowMarker.setAttributeNS(null, "markerWidth", 13);
                    arrowMarker.setAttributeNS(null, "markerHeight", 13);
                    arrowMarker.setAttributeNS(null, "refX", 9);
                    arrowMarker.setAttributeNS(null, "refY", 5);
                    arrowMarker.setAttributeNS(null, "orient", 'auto');

                    arrowMarker.path = elm('path');
                    arrowMarker.path.setAttributeNS(null, "d", 'M0,0 L5, 5 L0,10 L13, 5 L0,0');
                    arrowMarker.path.setAttributeNS(null, "stroke", 'none');
                    arrowMarker.path.setAttributeNS(null, "fill", options.xAxis.color ? options.xAxis.color : '#002060');
                    arrowMarker.appendChild(arrowMarker.path);

                    defs.appendChild(arrowMarker);

                    xAxis.axis.setAttributeNS(null, "marker-end", "url(#" + arrowMarker.getAttributeNS(null, "id") + ")");
                }

                if (options.xAxis.measures) {
                    xAxis.measures = [];
                    for(var i in points) {
                        xAxis.measures[i] = elm('line');
                        xAxis.measures[i].setAttributeNS(null, "stroke-width", options.xAxis.width ? options.xAxis.width : 1);
                        xAxis.measures[i].setAttributeNS(null, "stroke", options.xAxis.color ? options.xAxis.color : '#002060');
                        xAxis.appendChild(xAxis.measures[i]);
                    }
                }
            }

            if (options.xAxis.labels) {
                xAxis.labelGroup = createXLabels();
                xAxis.appendChild(xAxis.labelGroup);
            }

            svg.appendChild(xAxis);
        }

        function placeXAxis() {

            if (options.xAxis.labels) placeXLabels();

            var basis = getYPixel(min) + plotPadding;
            var axisWidth = options.xAxis.width ? options.xAxis.width : 1;

            xAxis.axis.setAttributeNS(null, "x1", xPadding + labelsWidth);
            xAxis.axis.setAttributeNS(null, "x2", width - xPadding);
            xAxis.axis.setAttributeNS(null, "y1", basis);
            xAxis.axis.setAttributeNS(null, "y2", basis);

            if (options.xAxis.measures) {
                var x;
                for(var i in values) {
                    x = getXPixel(i);
                    xAxis.measures[i].setAttributeNS(null, "x1", x);
                    xAxis.measures[i].setAttributeNS(null, "x2", x);
                    xAxis.measures[i].setAttributeNS(null, "y1", basis - 4*axisWidth);
                    xAxis.measures[i].setAttributeNS(null, "y2", basis + 4*axisWidth);
                    xAxis.measures[i].setAttributeNS(null, "visibility", x > xPadding + labelsWidth && x < width - xPadding ? 'visible ' : 'hidden');
                }
            }
        }

        /*creates an yAxis */
        function createYAxis() {
            yAxis = elm('g');
            yAxis.axis = elm('line');

            var axisWidth = options.yAxis.width ? options.yAxis.width : 1;

            if (options.yAxis.axis) {
                yAxis.axis.setAttributeNS(null, "stroke-width", axisWidth);
                yAxis.axis.setAttributeNS(null, "y2", yPadding);
                yAxis.axis.setAttributeNS(null, "stroke", options.yAxis.color ? options.yAxis.color : '#002060');
                yAxis.appendChild(yAxis.axis);

                if (options.yAxis.arrow) {
                    var arrowMarker = elm('marker');

                    arrowMarker.setAttributeNS(null, "id", "arrowMarker-" + Date.now() + Math.round(99999*Math.random()));
                    arrowMarker.setAttributeNS(null, "stroke", 'none');
                    arrowMarker.setAttributeNS(null, "fill", options.yAxis.color ? options.yAxis.color : '#002060');
                    arrowMarker.setAttributeNS(null, "markerWidth", 13);
                    arrowMarker.setAttributeNS(null, "markerHeight", 13);
                    arrowMarker.setAttributeNS(null, "refX", 9);
                    arrowMarker.setAttributeNS(null, "refY", 5);
                    arrowMarker.setAttributeNS(null, "orient", 'auto');

                    arrowMarker.path = elm('path');
                    arrowMarker.path.setAttributeNS(null, "d", 'M0,0 L5, 5 L0,10 L13, 5 L0,0');
                    arrowMarker.path.setAttributeNS(null, "stroke", 'none');
                    arrowMarker.path.setAttributeNS(null, "fill", options.yAxis.color ? options.yAxis.color : '#002060');
                    arrowMarker.appendChild(arrowMarker.path);

                    defs.appendChild(arrowMarker);

                    yAxis.axis.setAttributeNS(null, "marker-end", "url(#" + arrowMarker.getAttributeNS(null, "id") + ")");
                }

                if (options.yAxis.measures) {
                    var step = options.yAxis.step ? options.yAxis.step : 1;
                    yAxis.measures = {};
                    for(var value = min + step; value <= max; value += step) {
                        yAxis.measures[value] = elm('line');
                        yAxis.measures[value].setAttributeNS(null, "stroke-width", options.yAxis.width ? options.yAxis.width : 1);
                        yAxis.measures[value].setAttributeNS(null, "stroke", options.yAxis.color ? options.yAxis.color : '#002060');
                        yAxis.appendChild(yAxis.measures[value]);
                    }
                }
            }

            if (options.yAxis.labels) {
                yAxis.labelGroup = createYLabels();
                yAxis.appendChild(yAxis.labelGroup);
            }

            svg.appendChild(yAxis);
        }

        function placeYAxis() {

            if (options.yAxis.labels) placeYLabels();

            var basis = getYPixel(min) + plotPadding;
            var axisWidth = options.yAxis.width ? options.yAxis.width : 1;

            yAxis.axis.setAttributeNS(null, "x1", xPadding + labelsWidth);
            yAxis.axis.setAttributeNS(null, "x2", xPadding + labelsWidth);
            yAxis.axis.setAttributeNS(null, "y1", basis);

            if (options.yAxis.measures) {
                var y;
                for(var value in yAxis.measures) {
                    y = getYPixel(value);
                    yAxis.measures[value].setAttributeNS(null, "x1", xPadding + labelsWidth - 4*axisWidth);
                    yAxis.measures[value].setAttributeNS(null, "x2", xPadding + labelsWidth + 4*axisWidth);
                    yAxis.measures[value].setAttributeNS(null, "y1", y);
                    yAxis.measures[value].setAttributeNS(null, "y2", y);
                }
            }
        }

        /*xLabels*/
        function createXLabels() {
            var labelGroup = elm('g');

            labelGroup.labels = [];
            for (var i = 0, label, l = values.length; i < l; i++) {
                label = labelGroup.labels[i] = elm('text');
                label.setAttributeNS(null, "text-anchor", "middle");
                label.setAttributeNS(null, "fill", "#01416a");
                label.value = label.appendChild(elm('tspan'));
                label.value.textContent = Math.round(10*values[i])/10;
                label.text  = label.appendChild(elm('tspan'));
                label.text.textContent = data[i] && data[i].caption ? data[i].caption : '';
                labelGroup.appendChild(label);
            }

            return labelGroup
        }

        function placeXLabels() {
            var labelGroup = xAxis.labelGroup;

            labelsHeight = (labelsHeight ? 1 : 2)*labelGroup.getBBox().height;

            labelGroup.setAttributeNS(null, "transform", 'translate(0,' + (height - scrollBarWidth - yPadding - labelsHeight/2 - 5) +')');

            for (var i = 0, x, label, l = values.length; i < l; i++) {
                label = labelGroup.labels[i];
                x = getXPixel(i);
                label.setAttributeNS(null, "x", x);
                label.value.setAttributeNS(null, "x", x);
                label.text.setAttributeNS(null, "x", x);
                label.text.setAttributeNS(null, "dy", String(.5*labelsHeight));
                label.setAttributeNS(null, "visibility", x > xPadding + labelsWidth && x < width - xPadding ? 'visible ' : 'hidden');
            }

            // margin-top
            labelsHeight += 5;
        }

        /*yLabels*/
        function createYLabels() {
            var labelGroup = elm('g');

            labelGroup.labels = {};
            var step = options.yAxis.step ? options.yAxis.step : 1;
            for(var value = min + step; value <= max; value += step) {
                label = labelGroup.labels[value] = elm('text');
                label.setAttributeNS(null, "text-anchor", "end");
                label.setAttributeNS(null, "dominant-baseline", "central");
                label.setAttributeNS(null, "fill", "#01416a");
                label.textContent = Math.round(10*value)/10;
                labelGroup.appendChild(label);
            }

            return labelGroup
        }

        function placeYLabels() {
            var labelGroup = yAxis.labelGroup;

            labelsWidth = 0;
            var width;
            var step = options.yAxis.step ? options.yAxis.step : 1;
            for(var value = min + step; value <= max; value += step) {
                width = labelGroup.labels[value].getBBox().width;
                if (width > labelsWidth) labelsWidth = width;
            }

            labelsWidth +=6;

            labelGroup.setAttributeNS(null, "transform", 'translate(' + (xPadding + labelsWidth - 6) + ', 0)');

            for(var value = min + step; value <= max; value += step) {
                labelGroup.labels[value].setAttributeNS(null, "y", getYPixel(value));
            }
        }

        /*creates a plot */
        function createPlot() {
            var plot = elm('g');
            var clip = elm('clipPath');
            plot.area = elm('rect');

            clip.setAttributeNS(null, "id", "clippath-" + Date.now() + Math.round(99999*Math.random()));
            plot.setAttributeNS(null, "clip-path", "url(#" + clip.getAttributeNS(null, "id") + ")");

            clip.appendChild(plot.area);
            defs.appendChild(clip);

            svg.appendChild(plot);

            return plot;
        }

        function placePlot() {
            plot.area.setAttributeNS(null, "x", xPadding + labelsWidth);
            plot.area.setAttributeNS(null, "y", yPadding);
            plot.area.setAttributeNS(null, "width", Math.round(width - labelsWidth - 2*xPadding));
            plot.area.setAttributeNS(null, "height", Math.round(height - labelsHeight - 2*yPadding));
        }

        /*creates and adds an SVG circle to represent vertices*/
        function createVertices() {
            vertexGroup = elm('g');
            if (options.vertices) {
                var color;
                if (typeof options.vertices === 'string') color = options.vertices;
                else if (options.vertices.color) color = options.vertices.color;
                for(var i in values) {
                    vertices[i] = elm('circle');
                    vertices[i].setAttributeNS(null, "r", vertexSize);
                    vertices[i].setAttributeNS(null, "cx", points[i].x);
                    vertices[i].setAttributeNS(null, "cy", points[i].y);
                    vertices[i].setAttributeNS(null, "fill", color === undefined ? points[i].color : color);
                    vertices[i].setAttributeNS(null, "stroke", "none");

                    if (data[i].label) {
                        vertices[i].label = elm('text');
                        vertices[i].label.setAttributeNS(null, "text-anchor", "middle");
                        vertices[i].label.setAttributeNS(null, "fill", "#01416a");
                        vertices[i].label.textContent = data[i].label;
                        vertexGroup.appendChild(vertices[i].label);
                    }

                    vertexGroup.appendChild(vertices[i]);
                }
            }
            plot.appendChild(vertexGroup);
        }

        /*creates and adds an SVG path without defining the nodes*/
        function createPath() {
            var P = elm('path');
            P.setAttributeNS(null, "fill", "none");
            P.setAttributeNS(null, "stroke", "url(#" + gradient.getAttributeNS(null, "id") + ")");
            P.setAttributeNS(null, "stroke-width", options.width);
            plot.appendChild(P);

            P.area = elm('path');
            P.area.setAttributeNS(null, "fill", "url(#" + gradient.getAttributeNS(null, "id") + ")");
            P.area.setAttributeNS(null, "fill-opacity", options.opacity);
            P.area.setAttributeNS(null, "stroke", 'none');
            plot.appendChild(P.area);
            return P;
        }

        /*computes line control points*/
        function updateLine() {
            if (options.type !== 'none') {

                var path = '';

                if (options.type == 'line' || options.type == 'area') {
                    for (i = 1; i < points.length; i++) {
                        path += " L " + points[i].x + " " + points[i].y;
                    }
                } else if (options.type == 'spline' || options.type == 'areaspline') {
                    /*computes control points p1 and p2 for x and y direction*/
                    linePoints();

                    /*updates path settings, the browser will draw the new line*/
                    for (i = 1; i < points.length; i++) {

                        path += " C " +
                            (points[i-1].rightContX ? points[i-1].rightContX : points[i-1].x) + " " +
                            (points[i-1].rightContY ? points[i-1].rightContY : points[i-1].y) + " " +
                            (points[i].leftContX ? points[i].leftContX : points[i].x) + " " +
                            (points[i].leftContY ? points[i].leftContY : points[i].y) + " " +
                            points[i].x + " " + points[i].y;
                        //path += " L " + points[i].x + " " + points[i].y;
                    }
                }

                line.setAttributeNS(null, 'd', 'M '+points[0].x + ' ' + points[0].y + path);

                // area
                if (options.type === 'area' || options.type === 'areaspline') {
                    line.area.setAttributeNS(null, 'd',
                        'M ' + points[0].x + ' ' + (getYPixel(min) + plotPadding) + ' ' +
                        'L ' + points[0].x + ' ' + points[0].y + ' ' +
                        path + ' ' +
                        'L ' + points[points.length-1].x + ' ' + (getYPixel(min) + plotPadding)
                    );
                }
            }

            if (options.vertices) {
                var labelSize, labelY;
                for (i = 0; i < vertices.length; i++) {
                    vertices[i].setAttributeNS(null, "cx", points[i].x);
                    vertices[i].setAttributeNS(null, "cy", points[i].y);
                    if (vertices[i].label) {
                        vertices[i].label.setAttributeNS(null, "x", points[i].x);
                        labelSize = vertices[i].label.getBBox().height;
                        labelY = points[i].y - vertexSize - 7;
                        if (labelY - labelSize < yPadding) labelY = points[i].y + vertexSize + labelSize;
                        vertices[i].label.setAttributeNS(null, "y", labelY);
                    }
                }
            }

        }

        function linePoints() {
            var smoothing = 1.2, // 1 means control points midway between points, 2 means 1/3 from the point, 3 is 1/4 etc
                denom = smoothing + 1,
                point,
                lastPoint,
                nextPoint,
                leftContX,
                leftContY,
                rightContX,
                rightContY,
                correction;

            for (var i = 1, l = points.length-1; i < l; i++) {
                point = points[i];
                lastPoint = points[i-1];
                nextPoint = points[i+1];

                leftContX  = (smoothing * point.x + lastPoint.x) / denom;
                leftContY  = (smoothing * point.y + lastPoint.y) / denom;
                rightContX = (smoothing * point.x + nextPoint.x) / denom;
                rightContY = (smoothing * point.y + nextPoint.y) / denom;

                // have the two control points make a straight line through main point
                correction = ((rightContY - leftContY) * (rightContX - point.x)) /
                    (rightContX - leftContX) + point.y - rightContY;

                leftContY += correction;
                rightContY += correction;

                // to prevent false extremes, check that control points are between
                // neighbouring points' y values
                if (leftContY > lastPoint.y && leftContY > point.y) {
                    leftContY = Math.max(lastPoint.y, point.y);
                    rightContY = 2 * point.y - leftContY; // mirror of left control point
                } else if (leftContY < lastPoint.y && leftContY < point.y) {
                    leftContY = Math.min(lastPoint.y, point.y);
                    rightContY = 2 * point.y - leftContY;
                }
                if (rightContY > nextPoint.y && rightContY > point.y) {
                    rightContY = Math.max(nextPoint.y, point.y);
                    leftContY = 2 * point.y - rightContY;
                } else if (rightContY < nextPoint.y && rightContY < point.y) {
                    rightContY = Math.min(nextPoint.y, point.y);
                    leftContY = 2 * point.y - rightContY;
                }

                // record for drawing in next point
                points[i].leftContX = leftContX;
                points[i].leftContY = leftContY;

                points[i].rightContX = rightContX;
                points[i].rightContY = rightContY;

            }

        }

        function ScrollBar() {
            var self = this;

            var scroll = elm('line'),
                bar    = elm('line');

            scroll.setAttributeNS(null, 'stroke-linecap', 'round');
            scroll.setAttributeNS(null, 'stroke', '#d5ddea');
            scroll.setAttributeNS(null, 'stroke-width', scrollBarWidth);

            bar.setAttributeNS(null, 'stroke-linecap', 'round');
            bar.setAttributeNS(null, 'stroke', '#102f6f');
            bar.setAttributeNS(null, 'stroke-width', scrollBarWidth);

            bar.addEventListener('mousedown', startDrag);

            self.redraw = function resize() {
                var scrollLeft = xPadding + labelsWidth;
                var scrollWidth = width - scrollLeft - xPadding;
                var y = height - yPadding - scrollBarWidth/2;
                var maxValue = values.length - 1;

                scroll.setAttributeNS(null, 'x1', scrollLeft);
                scroll.setAttributeNS(null, 'x2', scrollLeft + scrollWidth);
                scroll.setAttributeNS(null, 'y1', y);
                scroll.setAttributeNS(null, 'y2', y);

                var barLeft = (scrollLeft + scrollWidth*range.left/maxValue);
                var barSize = scrollWidth*(range.right - range.left)/maxValue;
                bar.setAttributeNS(null, 'x1', barLeft);
                bar.setAttributeNS(null, 'x2', barLeft + barSize);
                bar.setAttributeNS(null, 'y1', y);
                bar.setAttributeNS(null, 'y2', y);
            };

            function stopEvent(e) {
                if(e.stopPropagation) e.stopPropagation();
                if(e.preventDefault) e.preventDefault();
                e.cancelBubble=true;
                e.returnValue=false;
                return false;
            }

            function startDrag(e) {
                var scale = range.right - range.left,
                    scrollBox    = scroll.getBBox(),
                    barBox       = bar.getBBox(),
                    scrollLeft = xPadding + labelsWidth,
                    scrollWidth  = scrollBox.width - barBox.width,
                    offset       = scrollBox.x + e.pageX - barBox.x;

                var dragHandler = function drag(e) {
                    var pos = e.pageX - offset;
                    if (pos < 0) pos = 0;
                    else if (pos > scrollWidth) pos = scrollWidth;

                    range.left = (values.length - 1 - scale) * pos/scrollWidth;
                    range.right = range.left + scale;
                    chart.redraw();
                };

                window.addEventListener('mousemove', dragHandler, false);
                window.addEventListener('mouseup', function () {
                    window.removeEventListener('mousemove', dragHandler, false);
                    window.removeEventListener('mouseup', arguments.callee, false);
                }, false);
                return stopEvent(e || window.event);
            }

            svg.appendChild(scroll);
            svg.appendChild(bar);
        }

        function createScrollBar() {
            scrollBarWidth = typeof options.scrollBar.width === 'number' ? options.scrollBar.width : 7;
            scrollBar = new ScrollBar();
        }

        this.redraw = function redraw() {
            width = $svg.width();
            height = $svg.height();

            placeYAxis();
            placeXAxis();
            placeYAxis();

            if (options.scrollBar) scrollBar.redraw();

            calculatePoints();

            updateLine();

            placePlot();
        };

        init();
    };

    // extend jQuery
    (function( $ ) {

        var defaults = {
            data: {},
            width: 3,
            type: 'areaspline',
            xAxis: {label: true},
            yAxis: {label: false},
            opacity: 0.2,
            flexEdge: false,
            vertices: false,
            color: '#ff0000'
        };

        /**
         * Рисует SVG график
         * @param options object
         *                data - массив вида [{label: 'label1', value: 11}, ...]
         *                min - минимальное значение (для масштабированя графика)
         *                max - минимальное значение (для масштабированя графика)
         *                range - объект, масштаб графика
         *                        left: float - левая граница (желательно от 0 до right)
         *                        right: float - правая граница (желательно от left до data.length - 1)
         *                scale: false - показывать кнопки изменения масштаба
         *                scrollBar: false - объект, рисовать "ползунок" для перемещения по графику
         *                        width: 7 - толщина скроллбара
         *                type - тип линии: none, line, spline, area, areaspline
         *                xAxis - объект, рисовать ось абсцисс
         *                        width: 1 - ширина линии
         *                        arrow: false - рисовать стрелку на конце оси
         *                        measures: false - засечки на оси
         *                yAxis - то же, что и xAxis, только для ординат
         *                width: 3 - ширина линии
         *                opacity: 0.2 - прозрачность градиентного фона
         *                vertices: false - (объект/boolean/string) показывать вершины графика/их цвет
         *                        color - цвет вершин
         *                        size  - размер
         *                сolor - цвет линии
         *                сolors - перекрывает color. объект, где ключ - порог значения, значение - цвет
         */
        $.fn.chartLine = function (options) {
            var chart = this.data('chart');
            if (!chart) {
                options = $.extend({}, defaults, options);
                for (var i = 0, l = this.length; i < l; i++) {
                    chart = new window.chart.Line(this[i], options);
                    $.data(this[i], 'chart', chart);
                }
            }
            return chart;
        };
    })(jQuery);

})();
