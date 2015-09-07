/**
 * Created by FullZero on 6/18/2015.
 */

(function(undefined) {
    window.chart = window.chart || {};

    function elm(type) {
        return document.createElementNS('http://www.w3.org/2000/svg', type);
    }

    // Position class
    function Vector(x, y) {
        var onchange;
        Object.defineProperties(this, {
            x: {
                enumerable: true,
                get: function getX() {
                    return x;
                },
                set: function setX(newValue) {
                    x = Number(newValue) || 0.0;
                    if (onchange) onchange.call(this, x, y);
                }
            },
            y: {
                enumerable: true,
                get: function getY() {
                    return y;
                },
                set: function setY(newValue) {
                    y = Number(newValue) || 0.0;
                    if (onchange) onchange.call(this, x, y);
                }
            },
            angle: {
                enumerable: true,
                get: function getAngle() {
                    return Math.atan2(this.y, this.x);
                },
                set: function setAngle(newValue) {
                    this.x = this.length*Math.cos(newValue);
                    this.y = this.length*Math.sin(newValue);
                }
            },
            length: {
                enumerable: true,
                get: function getLength() {
                    return Math.sqrt(this.x*this.x + this.y*this.y);
                }
            },
            onchange: {
                enumerable: false,
                get: function getOnChange() {
                    return onchange;
                },
                set: function setOnChange(listener) {
                    if (listener instanceof Function) onchange = listener;
                    else throw new TypeError('listener myst be a Function');
                }
            }
        });

        this.x = x;
        this.y = y;
    }
    Vector.prototype.copy = function copy() {
        return new Vector(this.x, this.y);
    };
    Vector.prototype.normalize = function normalize() {
        return new Vector(this.x/this.length, this.y/this.length);
    };
    Vector.prototype.add = function add(x, y) {
        if (x instanceof Vector) {
            y = this.y + x.y;
            x = this.x + x.x;
        } else {
            x = this.x + Number(x) || 0.0;
            y = this.y + Number(y) || 0.0;
        }
        return new Vector(x, y);
    };
    Vector.prototype.sub = function add(x, y) {
        if (x instanceof Vector) {
            y = this.y - x.y;
            x = this.x - x.x;
        } else {
            x = this.x - Number(x) || 0.0;
            y = this.y - Number(y) || 0.0;
        }
        return new Vector(x, y);
    };
    Vector.prototype.multiply = function multiply(value) {
        // scalar
        if (value instanceof Vector) {
            return this.x*value.x + this.y*value.y;
        } else {
            return new Vector(this.x * Number(value) || 0.0, this.y * Number(value) || 0.0);
        }
    };
    Vector.prototype.divide = function divide(value) {
        return new Vector(this.x / Number(value) || 0.0, this.y / Number(value) || 0.0);
    };
    Vector.prototype.flip = function flip() {
        return new Vector(this.y, this.x);
    };
    Vector.prototype.distance = function distance(x, y) {
        if (x instanceof Vector) {
            y = x.y;
            x = x.x;
        }
        return Math.sqrt((x-=this.x)*x + (y-=this.y)*y);
    };
    Vector.prototype.angleTo = function angleTo(x, y) {
        if (x instanceof Vector) {
            y = x.y;
            x = x.x;
        }
        return Math.atan2(this.x - x, this.y - y);
    };
    Vector.prototype.turn = function turn(angle, isDegrees) {
        if (isDegrees) {
            angle = angle*Math.PI/180;
        }
        angle -= this.angle;
        return new Vector(Math.cos(angle), -Math.sin(angle)).multiply(this.length);
    };

    // Ball class
    function Ball(parent, options) {
        options = options || {};

        var self = this;
        var group = elm('g');
        group.ball = group.appendChild(elm('circle'));
        group.text = group.appendChild(elm('text'));
        var radius = 0.0;
        var color  = 'blue';
        var pos = new Vector();

        group.setAttribute('class', 'ball');

        //group.ball.setAttributeNS(null, 'stroke', 'black');
        //group.ball.setAttributeNS(null, 'stroke-width', '1');
        group.ball.setAttributeNS(null, 'fill', color);

        group.text.setAttribute('class', 'caption');
        group.text.setAttributeNS(null, 'stroke', 'none');
        group.text.setAttributeNS(null, 'text-anchor', 'middle');
        group.text.setAttributeNS(null, 'dominant-baseline', 'central');

        Object.defineProperties(this, {
            radius: {
                enumerable: true,
                get: function getRadius() {
                    return radius;
                },
                set: function setRadius(newValue) {
                    radius = Number(newValue) || 0.0;
                    group.ball.setAttributeNS(null, 'r', radius);
                    group.text.setAttributeNS(null, 'x', pos.x);
                    group.text.setAttributeNS(null, 'y', pos.y);
                }
            },
            pos: {
                enumerable: true,
                get: function getPos() {
                    return pos;
                },
                set: function setPos(newValue) {
                    if (newValue instanceof Object) {
                        self.pos.x = newValue.x;
                        self.pos.y = newValue.y;
                    } else {
                        throw new TypeError('new position must be an Object');
                    }
                }
            },
            color: {
                enumerable: true,
                get: function getColor() {
                    return color;
                },
                set: function setColor(newValue) {
                    if (typeof newValue === 'string') {
                        color = newValue;
                        group.ball.setAttributeNS(null, 'fill', color);
                    } else {
                        throw new TypeError('color must be a String');
                    }
                }
            },
            text: {
                enumerable: true,
                get: function getText() {
                    return group.text.innerHTML;
                },
                set: function setText(newValue) {
                    if (typeof newValue === 'string') {
                        group.text.innerHTML = newValue;
                    } else {
                        throw new TypeError('text must be a String');
                    }
                }
            },
            textColor: {
                enumerable: true,
                get: function getTextColor() {
                    return group.text.getAttributeNS(null, 'stroke');
                },
                set: function setTextColor(newValue) {
                    if (typeof newValue === 'string') {
                        group.text.setAttributeNS(null, 'fill', newValue);
                    } else {
                        throw new TypeError('text color must be a String');
                    }
                }
            },
            textSize: {
                enumerable: true,
                get: function getTextSize() {
                    return group.text.getAttributeNS(null, 'font-size');
                },
                set: function setTextSize(newValue) {
                    if (typeof newValue === 'number') {
                        group.text.setAttributeNS(null, 'font-size', newValue);
                    } else {
                        throw new TypeError('text size must be a Number');
                    }
                }
            }
        });

        this.appendTo = function appendTo(node) {
            if (node instanceof Node) node.appendChild(group);
        };
        if (parent) this.appendTo(parent);

        this.getNode = function getNode() {
            return group;
        };

        this.remove = function remove() {
            group.parentNode.removeChild(group);
            group.innerHTML = null;
        };

        pos.onchange = function (x, y) {
            group.ball.setAttributeNS(null, 'cx', x);
            group.ball.setAttributeNS(null, 'cy', y);
            group.text.setAttributeNS(null, 'x', x);
            group.text.setAttributeNS(null, 'y', y);
        };

        if (options.pos)   this.pos   = options.pos;
        if (options.x)     this.pos.x = options.x;
        if (options.y)     this.pos.y = options.y;

        this.color  = options.color || '#06A709';
        this.radius = Number(options.radius) || 1.0;

        if (options.text)      this.text  = options.text;
        if (options.textSize)  this.textSize = options.textSize;
        this.textColor = options.textColor || 'white';
    }

    // Chart class
    chart.Balls = function (target, options) {
        var self = this;

        /*svg object*/
        var svg = document.createElementNS("http://www.w3.org/2000/svg", "svg"),
            $svg = $(svg);

        svg.setAttributeNS(null, "style", "width: 100%; height: 100%");

        var data;

        /*svg defs*/
        var defs = svg.appendChild(document.createElementNS("http://www.w3.org/2000/svg", "defs"));
        /*balls*/
        var balls = [];

        var xPadding = Number(options.xPadding) || 20;
        var yPadding = Number(options.yPadding) || 20;

        var relativeFontSize = options.relativeFontSize !== false;

        var defColors = ['#34D35C', '#75B290', '#FEC200', '#6190D6', '#7cb5ec', '#90ed7d', '#f7a35c', '#8085e9', '#f15c80', '#2b908f', '#f45b5b', '#91e8e1'];
        var colors = options.colors || defColors;

        var angle = (options.cup && options.cup.angle ? options.cup.angle : 10)*Math.PI/180;

        var cupBorderWidth = options.cup && options.cup.width ? options.cup.width : 3;
        var cupBorderColor = options.cup && options.cup.color ? options.cup.color : '#143270';

        var values;

        var sum;

        var max;
        var min;

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

        this.getNode = function getNode() {
            return svg;
        };

        /*saves elements as variables*/
        function init(newData) {

            for(var i in balls) balls[i].remove();

            balls = [];
            data = newData;
            values = $.map(data, function(value, index) {return Math.abs(typeof value === 'object' ? value.value : value);});
            sum = 0;
            var i = values.length;
            while (i--) sum += values[i];

            max = typeof options.max === "undefined" ? Math.max.apply(null, values) : options.max;
            min = typeof options.min === "undefined" ? Math.min.apply(null, values) : options.min;

            for(var i in values) {
                balls.push(new Ball(svg, {
                    radius: 5,
                    color: data[i].color,
                    text: data[i].label ? data[i].label : String(Math.round(100*values[i])/100)
                }));
                balls[i].prevPos = new Vector();
                balls[i].speed = new Vector();
                balls[i].diff = new Vector();
                $.data(balls[i].getNode(), data[i]);
            }

            /* create cup */
            createCup();

            self.redraw(true);
        }
        this.setData = init;

        var border;
        function createCup() {
            if ( ! border) {
                border = elm('path');
                border.setAttribute('class', 'border');
                border.setAttributeNS(null, 'stroke-width', cupBorderWidth);
                border.setAttributeNS(null, 'stroke', cupBorderColor);
                border.setAttributeNS(null, 'fill', 'none');
                svg.appendChild(border);
            }
        }
        function getDx(y) {
            return (y - yPadding)*Math.tan(angle);
        }

        /**
         * @var int время предыдущей перерисовки графика
         */
        var lastRedrawTime = 0;
        /**
         * @var int идентификатор таймера анимации или задержки прорисовки
         */
        var timer;
        /**
         * Перерисовывает изображение двигая при этом шары (запускает анимацию)
         * @param {Boolean} resetBalls поместить шары в первоначальное положение
         * @returns undefined
         */
        self.redraw = function redraw(resetBalls) {
            // поэкономим ресурсы при частой перерисовке
            clearTimeout(timer);
            var now = Date.now();
            var minInterval = 50;
            if (now - lastRedrawTime < minInterval) {
                timer = setTimeout(self.redraw.bind(self, resetBalls), minInterval - now + lastRedrawTime);
                return;
            }
            lastRedrawTime = now;

            var width  = $svg.width();
            var height = $svg.height();
            var timeout = 20;
            var g = 0.98;
            var friction = 0.7;
            var airFriction = 0.98;
            var length = balls.length;

            /* redraw cup borders */
            var path = 'M ' + xPadding + ' ' + yPadding
                + 'L ' + (xPadding + getDx(height - yPadding)) + ' ' + (height - yPadding)
                + 'L ' + (width - xPadding - getDx(height - yPadding)) + ' ' + (height - yPadding)
                + 'L ' + (width - xPadding) + ' ' + yPadding;
            border.setAttributeNS(null, 'd', path);

            /* start positions of balls */
            var s = (width - 2*xPadding - getDx(height - yPadding))*(height - 2*yPadding);
            var radius;
            var maxRadius = 0;
            var minRadius = Infinity;
            var radiusKoef = .75;
            var step = 2;
            var dx = 0;
            var dy = 0;
            var j, ld, rd, d, relPos, collide;
            var bottom;
            var overflow = true;
            if (balls.length) while(overflow) {
                for(var i in balls) {
                    radius = radiusKoef*Math.sqrt((values[i]/max) * (s/values.length));
                    if (radius > maxRadius) maxRadius = radius;
                    if (radius < minRadius) minRadius = radius;

                    bottom = height - yPadding - cupBorderWidth/2 - radius;

                    var pos = new Vector(
                        xPadding + getDx(bottom) + radius + cupBorderWidth/2 - step,
                        bottom
                    );

                    collide = true;
                    while(collide) {
                        pos.x += dx;
                        dx = step;

                        relPos = pos.sub(xPadding, yPadding);
                        ld = radius + cupBorderWidth/2 - relPos.length*Math.sin(Math.PI/2 - angle - relPos.angle);

                        relPos = pos.sub(width - xPadding, yPadding);
                        rd = radius + cupBorderWidth/2 - relPos.length*Math.sin(relPos.angle - Math.PI/2 - angle);

                        collide = ld > 0 || rd > 0;

                        if (rd > 0) {
                            pos.y -= step;
                            pos.x = xPadding + getDx(pos.y) + radius + cupBorderWidth/2;
                        }
                        if ( ! collide) {
                            j = i;
                            while(j-- > 0 && ! collide) {
                                d = pos.distance(balls[j].tmp_pos);
                                rd = radius + balls[j].tmp_radius;

                                collide = d < rd;
                                if (collide) {
                                    dy = Math.abs(pos.y - balls[j].tmp_pos.y);
                                    dx = Math.abs(balls[j].tmp_pos.x + Math.sqrt(rd*rd - dy*dy) - pos.x);
                                    if (dx <= 0) collide = false;
                                }
                            }
                        }
                    }
                    overflow = pos.y - radius < yPadding/4;
                    if (overflow) break;

                    balls[i].tmp_pos = pos;
                    balls[i].tmp_radius = radius;
                }
                radiusKoef -= .1;
            }
            for (var i in balls) {
                balls[i].pos = balls[i].tmp_pos;
                balls[i].radius = balls[i].tmp_radius;
                balls[i].textSize = 0.6*balls[i].radius;
            }
        };

        init(options.data);
    };

    // extend jQuery
    (function( $ ) {

        var defaults = {};

        /**
         * Рисует SVG график
         * @param {object} options
         *                data - массив типа [{label: 'label1', value: 11, color: 'red'}, ...]
         *                xPadding - горизонтальный отступ от краев
         *                xPadding - вертикальный отступ от краев
         *                relativeFontSize - bool размер шрифта относительно текущего шара (иначе наименьшего)
         *                cup - объект, параметры стакана:
         *                      cup.angle - угол наклона стен сосуда (по умолчанию 10°)
         *                      cup.width - ширина линий, которыми он нарисован
         *                      cup.color - цвет стакана (линий)
         */
        $.fn.chartBalls = function(options) {
            var chart = this.data('chart');
            if (!chart) {
                options = $.extend({}, defaults, options);
                for (var i = 0, l = this.length; i < l; i++) {
                    chart = new window.chart.Balls(this[i], options);
                    $.data(this[i], 'chart', chart);
                }
            }
            return chart;
        };

    })(jQuery);

})();
