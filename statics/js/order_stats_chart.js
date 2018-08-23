// JavaScript Document
;
(function (app, $) {
	app.chart = {
		init: function () {
			app.chart.order_general();
			//			app.chart.ship_status();
			//			app.chart.pay_status();
		},
		order_general: function () {
			var dataset = [];
			var ticks = [];
			if (data == 'null') {
				var nodata = "<div style='width:100%;height:100%;line-height:500px;text-align:center;overflow: hidden;'>" + js_lang.no_stats_data + "<\/div>";
				$("#order_general").append(nodata);
			} else {
				$.each(JSON.parse(data), function (key, value) {
					ticks.push(parseInt(value));
				});

				// 基于准备好的dom，初始化echarts实例
				var myChart = echarts.init(document.getElementById('order_general'));

				option = {
					color: ['#6DCEEE'],
					xAxis: {
						type: 'category',
						data: [js_lang.await_pay_order, js_lang.await_ship_order, js_lang.shipped_order, js_lang.returned_order, js_lang.canceled_order, js_lang.succeed_order]
					},
					yAxis: {
						type: 'value'
					},
					tooltip: {
						show: "true",
						trigger: 'item',
						backgroundColor: 'rgba(0,0,0,0.7)', // 背景
						padding: [8, 10], //内边距
						extraCssText: 'box-shadow: 0 0 3px rgba(255, 255, 255, 0.4);', //添加阴影
						formatter: function (params) {
							if (params.seriesName != "") {
								return params.name + ' ：  ' + params.value;
							}
						},
					},
					grid: {
				        left: '2%',
				        right: '2%',
				        bottom: '5%',
				        top: '5%',
				        containLabel: true
				    },
					series: [{
						data: ticks,
						type: 'bar',
						barWidth: '50%',
					}]
				};

				// 使用刚指定的配置项和数据显示图表。
				myChart.setOption(option);

				window.onresize = myChart.resize;
			}
			app.chart.order_type();
		},

		ship_status: function () {
			var dataset = [];
			var ticks = [];
			var tpl = [];
			if (data == 'null') {
				var nodata = "<div style='width:100%;height:100%;line-height:500px;text-align:center;overflow: hidden;'>" + js_lang.no_stats_data + "<\/div>";
				$("#ship_status").append(nodata);
			} else {
				$.each(JSON.parse(data), function (key, value) {
					tpl.push(parseInt(value.order_num));
					dataset.push(value.ship_name);
				});
				var chart = new AChart({
					theme: AChart.Theme.SmoothBase,
					id: 'ship_status',
					width: 1000,
					height: 500,
					plotCfg: {
						margin: [50, 50, 50] //画板的边距
					},
					xAxis: {
						categories: dataset,
					},
					yAxis: {
						min: 0
					},
					seriesOptions: { //设置多个序列共同的属性
						/*columnCfg : { //公共的样式在此配置
                      
                		}*/
					},
					tooltip: {
						pointRenderer: function (point) {
							return point.yValue;
						}
					},
					legend: null,
					series: [{
						name: js_lang.number,
						type: 'column',
						data: tpl,
					}]
				});
				chart.render();
			}
		},

		pay_status: function () {
			var dataset = [];
			var ticks = [];
			var tpl = [];
			if (data == 'null') {
				var nodata = "<div style='width:100%;height:100%;line-height:500px;text-align:center;overflow: hidden;'>" + js_lang.no_stats_data + "<\/div>";
				$("#pay_status").append(nodata);
			} else {
				$.each(JSON.parse(data), function (key, value) {
					tpl.push(parseInt(value.order_num));
					dataset.push(value.pay_name);
				});
				var chart = new AChart({
					theme: AChart.Theme.SmoothBase,
					id: 'pay_status',
					width: 1000,
					height: 500,
					plotCfg: {
						margin: [50, 50, 50] //画板的边距
					},
					xAxis: {
						categories: dataset,
					},
					yAxis: {
						min: 0
					},
					seriesOptions: { //设置多个序列共同的属性
						/*columnCfg : { //公共的样式在此配置
							
						}*/
					},
					tooltip: {
						pointRenderer: function (point) {
							return point.yValue;
						}
					},
					legend: null,
					series: [{
						name: js_lang.number,
						type: 'column',
						data: tpl,
					}]
				});
				chart.render();
			}
		},

		order_type: function () {
			var dataset = [];
			var ticks = [];
			var tpl = [];
			if (order_stats_json == 'null') {
				var nodata = "<div style='width:100%;height:100%;line-height:500px;text-align:center;overflow: hidden;'>" + js_lang.no_stats_data + "<\/div>";
				$("#order_type_chart").append(nodata);
			} else {
				var myChart = echarts.init(document.getElementById('order_type_chart'));

				dataset = JSON.parse(order_stats_json);

				option = {
					backgroundColor: '#fff',
					color: ['#91BE79', '#F0567D', '#4EB2C9', '#DF9D5E'],
					tooltip: {
						trigger: 'item',
						formatter: "{a} <br/>{b} : {c} ({d}%)"
					},
					legend: {
						bottom: 25,
						data: ['配送', '团购', '到店', '自提']
					},
					series: [{
						tooltip: {
			                trigger: 'item',
			                formatter: "{b} : {c} ({d}%)"
			            },
						type: 'pie',
						radius: '70%',
						center: ['50%', '40%'],
				        label: {
				            normal: {
				                position: 'inner',
				                formatter: function(param) {
				                    if (!param.percent) return ''
				                    var f = Math.round(param.percent * 10) / 10;
				                    var s = f.toString();
				                    var rs = s.indexOf('.');
				                    if (rs < 0) {
				                        rs = s.length;
				                        s += '.';
				                    }
				                    while (s.length <= rs + 1) {
				                        s += '0';
				                    }
				                    return s + '%';
				                },
				                textStyle: {
				                    color: '#fff',
				                    fontSize: 12
				                }
				            }
				        },
						labelLine: {
							normal: {
								show: false
							}
						},
						data: dataset
					}]
				};

				myChart.setOption(option);

				window.onresize = myChart.resize;
			}
		}
	};

})(ecjia.admin, jQuery);

// end