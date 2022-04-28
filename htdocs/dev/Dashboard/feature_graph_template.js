
// Get context with jQuery - using jQuery's .get() method.
if( $('#${GRAPH_ID}_featureHealthChart').length ) {
var ${GRAPH_ID}_featureHealthChartCanvas = $('#${GRAPH_ID}_featureHealthChart').get(0).getContext('2d');
  // This will get the first returned node in the jQuery collection.
  var ${GRAPH_ID}_featureHealthChart       = new Chart(${GRAPH_ID}_featureHealthChartCanvas);

  var ${GRAPH_ID}_featureHealthChartData = {
    labels  : [${LABELS}],
    datasets: [
      {
        label               : 'Tests Passing',
        backgroundColor     : 'rgb(42, 205, 75)',
        borderColor         : 'rgb(42, 205, 75)',
        pointColor          : 'rgb(42, 205, 75)',
        pointStrokeColor    : '#c1c7d1',
        pointHighlightFill  : '#fff',
        pointHighlightStroke: 'rgb(220,220,220)',
        data                : [${PASS}],
        nonscaled_data      : [${PASS_NS}]
      },
      {
        label               : 'Tests Failing',
        backgroundColor     : 'rgba(193,54,39,1)',
        borderColor         : 'rgba(193,54,39,1)',
        pointColor          : 'rgba(193,54,39,1)',
        pointStrokeColor    : 'rgba(193,54,39,1)',
        pointHighlightFill  : '#fff',
        pointHighlightStroke: 'rgba(0,166,90,1)',
        data                : [${FAIL}],
        nonscaled_data      : [${FAIL_NS}]
      },
      {
        label               : 'Tests Not Enabled (WIP; random data for now)',
        backgroundColor     : 'rgba(230,230,230,1)',
        borderColor         : 'rgba(230,230,230,1)',
        pointColor          : 'rgba(230,230,230,1)',
        pointStrokeColor    : 'rgba(230,230,230,1)',
        pointHighlightStroke: 'rgba(230,230,230,1)',
        pointHighlightFill  : '#fff',
        data                : [${NOT_ENABLED}],
        nonscaled_data      : [${NOT_ENABLED_NS}]
      }
    ]
  };

  var ${GRAPH_ID}_featureHealthChartOptions = {
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
    scales : { 
      xAxes: [{
                stacked: true
            }],
        yAxes: [{
            id: 'first-y-axis',
            type: 'linear',
            min: 0,
            max: 100,
            stacked: true,
        }],
    },

    tooltips: {
            mode: "index",
            callbacks: {
                label: function(tooltipItem, data) {
                    var label = data.datasets[tooltipItem.datasetIndex].label || '';

                    if (label) {
                        label += ': ';
                    }
                    //label += Math.round(tooltipItem.yLabel * 100) / 100;
                    //label += "%";
                    label += data.datasets[tooltipItem.datasetIndex].nonscaled_data[tooltipItem.index];
                    return label;
                }
            }
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
			if(dataLabelsState == true) {
				return true;
			}
			return false;
		},
        formatter: Math.round
      }
    },
  };

  // This will get the first returned node in the jQuery collection.
  var ${GRAPH_ID}_featureHealthChart = new Chart(${GRAPH_ID}_featureHealthChartCanvas, {
        type: 'bar',
        data: ${GRAPH_ID}_featureHealthChartData,
        options: ${GRAPH_ID}_featureHealthChartOptions
      });

  allCharts.push(${GRAPH_ID}_featureHealthChart);
}
