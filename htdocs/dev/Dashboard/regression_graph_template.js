
// Get context with jQuery - using jQuery's .get() method.
if( $('#${GRAPH_ID}_regressionChart').length ) {
var ${GRAPH_ID}_regressionChartCanvas = $('#${GRAPH_ID}_regressionChart').get(0).getContext('2d');

var ${GRAPH_ID}_regressionChartData = {
labels  : [${LABELS}],
datasets: [
{
label               : 'Regression Passrate',
borderColor         : 'rgb(0, 192, 239)',
backgroundColor     : 'rgba(0, 192, 239,1)',
fill                : false,
pointColor          : 'rgb(0, 192, 239)',
pointStrokeColor    : '#c1c7d1',
pointHighlightFill  : '#fff',
pointHighlightStroke: 'rgb(220,220,220)',
data                : [${PASS_RATE}],
comments			: [${PASS_RATE_COMMENTS}],
//borderDash          : [10, 10],
},
{
label               : 'Coverage',
borderColor         : 'rgba(0,166,90,1)',
backgroundColor     : 'rgba(0,166,90,1)',
fill                : false,
pointColor          : 'rgba(0,166,90,1)',
pointStrokeColor    : 'rgba(0,166,90,1)',
pointHighlightFill  : '#fff',
pointHighlightStroke: 'rgba(0,166,90,1)',
data                : [${COVERAGE}],
comments			: [${COVERAGE_COMMENTS}],
},
{
label               : 'QoV',
borderColor         : 'rgba(243,156,18,1)',
backgroundColor     : 'rgba(243,156,18,1)',
fill                : false,
pointColor          : 'rgba(243,156,18,1)',
pointStrokeColor    : 'rgba(243,156,18,1)',
pointHighlightStroke: 'rgba(243,156,18,1)',
pointHighlightFill  : '#fff',
data                : [${QOV}],
comments			: [${QOV_COMMENTS}],
}
]
};

var ${GRAPH_ID}_regressionChartOptions = {
// Boolean - If we should show the scale at all
showScale               : true,
// Boolean - Whether grid lines are shown across the chart
scaleShowGridLines      : false,
// String - Colour of the grid lines
scaleGridLineColor      : 'rgba(0,0,0,.05)',
// Number - Width of the grid lines
scaleGridLineWidth      : 1,
// Boolean - Whether to show horizontal lines (except X axis)
scaleShowHorizontalLines: true,
// Boolean - Whether to show vertical lines (except Y axis)
scaleShowVerticalLines  : true,
// Boolean - Whether the line is curved between points
bezierCurve             : true,
// Number - Tension of the bezier curve between points
bezierCurveTension      : 0.2,
// Boolean - Whether to show a dot for each point
pointDot                : true,
// Number - Radius of each point dot in pixels
pointDotRadius          : 4,
// Number - Pixel width of point dot stroke
pointDotStrokeWidth     : 1,
// Number - amount extra to add to the radius to cater for hit detection outside the drawn point
pointHitDetectionRadius : 20,
// Boolean - Whether to show a stroke for datasets
datasetStroke           : true,
// Number - Pixel width of dataset stroke
datasetStrokeWidth      : 3,
// Boolean - Whether to fill the dataset with a color
datasetFill             : false,
// String - A legend template
legendTemplate          : '<ul class=\'<%=name.toLowerCase()%>-legend\'><% for (var i=0; i<datasets.length; i++){%><li><span style=\'background-color:<%=datasets[i].lineColor%>\'></span><%=datasets[i].label%></li><%}%></ul>',
// Boolean - whether to maintain the starting aspect ratio or not when responsive, if set to false, will take up entire container
maintainAspectRatio     : true,
// Boolean - whether to make the chart responsive to window resizing
responsive              : true,
spanGaps                : true,
tooltips: {
mode: "index",
},

scales : { 
yAxes: [
{
id: 'first-y-axis',
type: 'linear',
ticks: {
suggestedMin: 80,
suggestedMax: 100
}
},
],
},
          plugins: {
          datalabels: {
            backgroundColor: function(context) {
              return context.dataset.backgroundColor;
            },
            borderRadius: 4,
            color: 'white',
            font: {
              weight: 'bold'
            },
			display: function(context) {
				if(context.dataset.comments == undefined || context.dataset.comments[context.dataIndex] == null) {
					return false;
				}
				return true;
			},
            formatter: function(value, context) {
				return context.dataset.comments[context.dataIndex];
			},
			anchor: "end",
			align: "bottom",
			offset: 7,
          }
        },
		annotation: {
        events: ["click"],
        annotations: [
          {
            drawTime: "afterDatasetsDraw",
            id: "vline",
            type: "line",
            mode: "vertical",
            scaleID: "x-axis-0",
            value: "ww04f",
            endValue: "ww04f",
            borderColor: "rgba(0,0,0,.25)",
            borderWidth: 5,
            label: {
              backgroundColor: "rgba(0, 192, 239, 0.65)",
              content: "NB Issue",
              enabled: true,
              position: "start",
              xAdjust: 0,
			  yAdjust: 50,
            },
            onClick: function(e) {
              // The annotation is is bound to the `this` variable
              console.log("Annotation", e.type, this);
            }
          },
        ]
      },
};

$("#regression-footnotes-${GRAPH_ID}").append("${REGRESSION_COMMENTS}");

// Create the line chart
// regressionChart.Line(regressionChartData, regressionChartOptions);
// This will get the first returned node in the jQuery collection.
var ${GRAPH_ID}_regressionChart       = new Chart(${GRAPH_ID}_regressionChartCanvas, {
type: 'line',
data: ${GRAPH_ID}_regressionChartData,
options: ${GRAPH_ID}_regressionChartOptions
});

allCharts.push(${GRAPH_ID}_regressionChart);
}
