(function (root, factory) {
    if (typeof define === 'function' && define.amd) {
        define( factory );
    } else {
        root.donut = factory();
    }
})(this, function () {
	var doc = document,
		M = Math,
		donutData = {},
		dataIndex = 0;

	var donut = function( options ) {
		var div = doc.createElement( 'div' ),
			size = options.size || 100,
			data = options.data || [{value: 1}],
			weight = options.weight || options.size/100*15,
			colors = options.colors || ['#555'],
			el = options.el,
			title = options.title || "",
			datalbl = options.datalbl || "",
			type = options.type || "circle", // Тип графика circle|semi-circle (круг или полу-круг)
			useoverflow = options.useoverflow || true, //В этом режиме последний элемент будет отрисовываться с нулевой точки
			lbl_font_size = 14,
			r = size/2,
			PI = M.PI,
			sin = M.sin,
			cos = M.cos,
			sum = 0,
			i,
			value,
			arc,
			setAttribute = function( el, o ) {
				for( j in o ) {
					el.setAttribute( j, o[ j ] );
				}
			};

		for( i = 0; i < data.length; i++ ) {
			if(!useoverflow || useoverflow && data.length-1 > i) {
				sum += data[i].value;
			}
		}

		div.className = 'donut';

		// Тип графика полукруг
		if( type == "semi-circle" ) {
			div.style.height = r + 'px'
		}else {
			div.style.height = size + 'px';
		}

		div.style.width = size + 'px';
		div.style.position = 'relative';


		var NS = 'http://www.w3.org/2000/svg',
			svg = doc.createElementNS( NS, 'svg' ),
			startAngle = -PI/2,
			arcRadius = r - weight/2;

        svg.style.display = 'inline-block';
        svg.style.verticalAlign = 'middle';

		// Тип графика полукруг
		if( type == "semi-circle" ) {
			startAngle = PI;
			svg.setAttribute('height', r + 'px');
		}else {
			svg.setAttribute('height', size + 'px');
		}

		svg.setAttribute( 'width', size + 'px' );
		div.appendChild( svg );


		for( i = 0; i < data.length; i++ ) {
			if( type == "semi-circle" && useoverflow && data.length-1 <= i ){
				startAngle = PI;
			}

			value = data[ i ].value/sum;
			value = value === 1 ? .9999 : value;
			arc = doc.createElementNS( NS, 'path' );

			var segmentAngle = value * PI * 2;

			// Тип графика полукруг
			if( type == "semi-circle" ) {
				segmentAngle = value * PI;
			}

			var endAngle = segmentAngle + startAngle,
				largeArc = ((endAngle - startAngle) % (PI * 2)) > PI ? 1 : 0,
				startX = r + cos(startAngle) * arcRadius,
				startY = r + sin(startAngle) * arcRadius,
				endX = r + cos(endAngle) * arcRadius,
				endY = r + sin(endAngle) * arcRadius;

			startAngle = endAngle;

			setAttribute( arc, {
				d: [
					'M', startX, startY,
					'A', arcRadius, arcRadius, 0, largeArc, 1, endX, endY
				].join(' '),
				stroke: colors[ i % colors.length ],
				'stroke-width': weight,
				fill: 'none',
				'data-name': data[ i ].name,
				'class': 'donut-arc'
			});
			donut.data( arc, data[ i ] );
			svg.appendChild( arc );

			donut.setPattern(svg, colors[ i ]);
		}


		var base_size = type == "semi-circle" ? 160 : 140,// Базовый размер графика, для которого норма размера текста 16px
			scale_k = size/base_size; // Коэфициент изменения размера шрифта

		lbl_font_size = scale_k*lbl_font_size;

		var lbl = doc.createElement( 'div' );
		lbl.className = 'donut-caption';
		lbl.innerHTML = title;

		lbl.style.display    = 'inline-block';
		lbl.style.marginLeft = -size + 'px';
		lbl.style.width      = (size - 2*weight) + 'px';
		lbl.style.padding    = '0 ' + weight + 'px';

		lbl.style.lineHeight = 'normal';
		lbl.style.textAlign  = 'center';
		lbl.style.fontSize   = lbl_font_size + 'px';
		lbl.style.fontWeight = 'bold';

		// Тип графика полукруг
		lbl.style.verticalAlign = type == "semi-circle" ? 'bottom' : 'middle';

        if( datalbl ) {
            //$(doc.createElementNS(NS, 'text'))
            $('<div>')
                //.attr({x: '50%', y: '95%', style: 'text-anchor: middle;font-size:'+lbl_font_size+'px', fill:"#01416a"})
                .css({'font-size': lbl_font_size, color: '#01416a', 'font-weight' :'normal'})
                .text( datalbl ).appendTo(lbl);
        }

        div.appendChild( lbl );
		if( el ) el.appendChild( div );

		return div;
	};
		
	donut.data = function( arc, data ) {
		if( typeof data === 'undefined' ) {
			return donutData[ arc._DONUT ];
		} else {
			donutData[ arc._DONUT = arc._DONUT || ++dataIndex ] = data;
			return arc;
		}
	};
	
	donut.setColor = function( arc, color ) {
		arc.setAttribute( 'stroke', color );
		return arc;
	};

	donut.setPattern = function(svg, pattern_name){
		var NS = 'http://www.w3.org/2000/svg';
		var patterns = {
            success:  {color:"rgba(8,168,12, 1)", size:10, type:'dotted'},
            warning:  {color:"rgba(255,192,0, 1)", size:10, type:'dotted'},
            critical: {color:"rgba(255,0,0, 1)", size:10, type:'dotted'},
            overflow: {color:"rgba(31, 73, 125,1)", size:200, type:'lines'}
        };

		pattern_name = pattern_name.replace('url(', '').replace('#', '').replace(')', '');

		if( !(pattern_name in patterns) ){
			return false;
		}

		var pattern = patterns[pattern_name],
            nc = doc.createElementNS(NS, 'pattern');

        nc.setAttribute('id', pattern_name);
        nc.setAttribute('x', '0');
        nc.setAttribute('y', '0');
        nc.setAttribute('width', pattern.size);
        nc.setAttribute('height', pattern.size);
        nc.setAttribute('patternUnits', 'userSpaceOnUse');

		var g = doc.createElementNS(NS, 'g');
		nc.appendChild(g);

		var rect = doc.createElementNS(NS, 'rect');
		rect.setAttribute('width', pattern.size);
		rect.setAttribute('height', pattern.size);
		rect.setAttribute('fill', pattern.color);
		g.appendChild(rect);

        var coords = [];
        if(pattern.type == 'lines') {
            var x1 = 0, y1 = 0, x2 = pattern.size, y2 = pattern.size, step = 5, point = [];
            coords = [[0, 0, x2, y2]];
            while (x2 > 0 || y2 > 0) {
                // Теорема Пифагора, находим длину катета, т.е. отступ между линиями
                n = Math.sqrt(step * step + step * step);
                x1 += n;
                y1 += n;
                x2 -= n;
                y2 -= n;
                coords.push([x1, 0, 200, y2]);
                coords.push([0, y1, x2, 200]);
            }

            for (var i = 0; i < coords.length; i++) {
                var line = doc.createElementNS(NS, 'line');
                line.setAttribute('x1', coords[i][0]);
                line.setAttribute('y1', coords[i][1]);
                line.setAttribute('x2', coords[i][2]);
                line.setAttribute('y2', coords[i][3]);
                line.setAttribute('style', 'stroke:rgba(8,168,12, 1);stroke-width:2px');
                g.appendChild(line);
            }
        }else {
             coords = [[1,1], [6,1], [3,6], [8,6]];
             for(var i=0; i < coords.length; i++){
                 var circle = doc.createElementNS(NS, 'circle');
                 circle.setAttribute('cx', coords[i][0]);
                 circle.setAttribute('cy', coords[i][1]);
                 circle.setAttribute('r', 0.8);
                 circle.setAttribute('fill', '#fff');
                 g.appendChild(circle);
             }
        }

		svg.appendChild(nc);

		return true;
	};

	return donut;
});
