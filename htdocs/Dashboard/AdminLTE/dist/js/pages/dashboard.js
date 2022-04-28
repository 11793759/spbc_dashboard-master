/*
 * Author: Abdullah A Almsaeed
 * Date: 4 Jan 2014
 * Description:
 *      This is a demo file used only for the main dashboard (index.html)
 **/

$(function () {

  'use strict';

  // Make the dashboard widgets sortable Using jquery UI
  $('.connectedSortable').sortable({
    placeholder         : 'sort-highlight',
    connectWith         : '.connectedSortable',
    handle              : '.box-header, .nav-tabs',
    forcePlaceholderSize: true,
    zIndex              : 999999
  });
  $('.connectedSortable .box-header, .connectedSortable .nav-tabs-custom').css('cursor', 'move');

  /* jQueryKnob */
  $('.knob').knob();

  // Sparkline charts
  // var myvalues = [1000, 1200, 920, 927, 931, 1027, 819, 930, 1021];
  // $('#sparkline-1').sparkline(myvalues, {
  //   type     : 'line',
  //   lineColor: '#92c1dc',
  //   fillColor: '#ebf4f9',
  //   height   : '50',
  //   width    : '80'
  // });
  // myvalues = [515, 519, 520, 522, 652, 810, 370, 627, 319, 630, 921];
  // $('#sparkline-2').sparkline(myvalues, {
  //   type     : 'line',
  //   lineColor: '#92c1dc',
  //   fillColor: '#ebf4f9',
  //   height   : '50',
  //   width    : '80'
  // });
  // myvalues = [15, 19, 20, 22, 33, 27, 31, 27, 19, 30, 21];
  // $('#sparkline-3').sparkline(myvalues, {
  //   type     : 'line',
  //   lineColor: '#92c1dc',
  //   fillColor: '#ebf4f9',
  //   height   : '50',
  //   width    : '80'
  // });

  /* Morris.js Charts */
  // Sales chart
  var area = new Morris.Area({
    element   : 'qov-chart',
    resize    : true,
    data      : [
      { y: '2011 Q1', item1: 2666, item2: 2666 },
      { y: '2011 Q2', item1: 2778, item2: 2294 },
      { y: '2011 Q3', item1: 4912, item2: 1969 },
      { y: '2011 Q4', item1: 3767, item2: 3597 },
      { y: '2012 Q1', item1: 6810, item2: 1914 },
      { y: '2012 Q2', item1: 5670, item2: 4293 },
      { y: '2012 Q3', item1: 4820, item2: 3795 },
      { y: '2012 Q4', item1: 15073, item2: 5967 },
      { y: '2013 Q1', item1: 10687, item2: 4460 },
      { y: '2013 Q2', item1: 8432, item2: 5713 }
    ],
    xkey      : 'y',
    ykeys     : ['item1', 'item2'],
    labels    : ['Item 1', 'Item 2'],
    lineColors: ['#a0d0e0', '#3c8dbc'],
    hideHover : 'auto'
  });
  var line = new Morris.Line({
    element          : 'line-chart',
    resize           : true,
    data             : [
      { y: '2011 Q1', item1: 2666 },
      { y: '2011 Q2', item1: 2778 },
      { y: '2011 Q3', item1: 4912 },
      { y: '2011 Q4', item1: 3767 },
      { y: '2012 Q1', item1: 6810 },
      { y: '2012 Q2', item1: 5670 },
      { y: '2012 Q3', item1: 4820 },
      { y: '2012 Q4', item1: 15073 },
      { y: '2013 Q1', item1: 10687 },
      { y: '2013 Q2', item1: 8432 }
    ],
    xkey             : 'y',
    ykeys            : ['item1'],
    labels           : ['Item 1'],
    lineColors       : ['#efefef'],
    lineWidth        : 2,
    hideHover        : 'auto',
    gridTextColor    : '#fff',
    gridStrokeWidth  : 0.4,
    pointSize        : 4,
    pointStrokeColors: ['#efefef'],
    gridLineColor    : '#efefef',
    gridTextFamily   : 'Open Sans',
    gridTextSize     : 10
  });

  // Fix for charts under tabs
  $('.box ul.nav a').on('shown.bs.tab', function () {
    area.redraw();
    qov-chart.redraw();
    line.redraw();
  });

});
