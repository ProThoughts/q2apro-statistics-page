<?php

	class qa_statistics_page {
		
		var $directory;
		var $urltoroot;
		
		function load_module($directory, $urltoroot)
		{
			$this->directory=$directory;
			$this->urltoroot=$urltoroot;
		}
		
		// for display in admin interface under admin/pages
		function suggest_requests() 
		{	
			return array(
				array(
					'title' => 'Uploads', // title of page
					'request' => 'statistiken', // request name
					'nav' => 'M', // 'M'=main, 'F'=footer, 'B'=before main, 'O'=opposite main, null=none
				),
			);
		}
		
		// for url query
		function match_request($request)
		{
			if ($request=='statistiken') {
				return true;
			}

			return false;
		}

		function process_request($request)
		{
	
			/* start content */
			$qa_content = qa_content_prepare();

			// page title
			$qa_content['title'] = 'Statistiken'; 

			// return if not admin!
			$level=qa_get_logged_in_level();
			if ($level<QA_USER_LEVEL_EXPERT) {
				$qa_content['custom0']='<div>'.qa_lang_html('qa_list_uploads_lang/not_allowed').'</div>';
				return $qa_content;
			}
			
			$daysToShow = 30;
			
			$userhandle = qa_get('user');
			$useridY = '';
			if(isset($userhandle)) {
				// get id from handle
				$userid = qa_db_read_one_value(qa_db_query_sub('SELECT userid FROM ^users WHERE handle = # LIMIT 1;', $userhandle), true);
				$useridY = isset($userid) ? 'AND `userid`='.$userid : '';
				$qa_content['title'] = 'Statistiken für '.$userhandle;
			}
			$start_date = qa_get('start');
			$end_date = qa_get('end');
			$startdate = ($start_date!='') ? trim($start_date) : date("Y-m-01"); // if not set take recent month
			$enddate = ($end_date!='') ? trim($end_date) : date("Y-m-d"); // if not set take today
			
			if($start_date) {
				// get all questions
				$queryQuestions = qa_db_query_sub('SELECT created 
														FROM `^posts`
														WHERE `type`="Q"
														AND DATE(`created`) BETWEEN DATE(#) AND DATE(#)'
														.$useridY.'
														ORDER BY created DESC', 
															$start_date.' 00:00:00',
															$end_date.' 00:00:00'
														);
				// get all answers
				$queryAnswers = qa_db_query_sub('SELECT created 
														FROM `^posts`
														WHERE `type`="A"
														AND DATE(`created`) BETWEEN DATE(#) AND DATE(#)'
														.$useridY.'
														ORDER BY created DESC', 
															$start_date.' 00:00:00',
															$end_date.' 00:00:00'
														);
				// get all comments
				$queryComments = qa_db_query_sub('SELECT created 
														FROM `^posts`
														WHERE `type`="C"
														AND DATE(`created`) BETWEEN DATE(#) AND DATE(#)'
														.$useridY.'
														ORDER BY created DESC', 
															$start_date.' 00:00:00',
															$end_date.' 00:00:00'
														);
														// AND created > NOW() - INTERVAL '.($daysToShow-1).' DAY
			}
			else {
				// default to this month
				// get all questions
				$queryQuestions = qa_db_query_sub('SELECT created 
														FROM `^posts`
														WHERE `type`="Q"
														AND MONTH(`created`) = MONTH( CURDATE() )'
														.$useridY.'
														ORDER BY created DESC
														');
				
				// get all answers
				$queryAnswers = qa_db_query_sub('SELECT created 
														FROM `^posts`
														WHERE `type`="A"
														AND MONTH(`created`) = MONTH( CURDATE() )'
														.$useridY.'
														ORDER BY created DESC
														');
				// get all comments
				$queryComments = qa_db_query_sub('SELECT created 
														FROM `^posts`
														WHERE `type`="C"
														AND MONTH(`created`) = MONTH( CURDATE() )'
														.$useridY.'
														ORDER BY created DESC
														');
			}
			

			// prepare questions count
			$questions = array();
			$weekAskTime = array(0,1,2,3,4,5,6);
			$jsData_forTableQ = '';
			$jsData_forTableQ2 = '';
			$jsData_forTableA = '';
			$jsData_forTableC = '';
			$q = 0;
			while( ($row = qa_db_read_one_assoc($queryQuestions,true)) !== null ) {
				// take substring, i. e. only date from "2013-01-04 15:18:03"
				$questions[++$q] = substr($row['created'],0,10);
				$weekday = date( 'w', strtotime($row['created']) ); // w, N, D
				// write time 12:12 to array, according to weekday number (0-6) (Sun-Sat)
				$weekAskTime[$weekday] .= substr($row['created'],11,5).',';
				// data for jquery flot plotting
				$jsData_forTableQ2 .= '['.($weekday+1).', '.$this->convertTimeToSec(substr($row['created'],11,7)).'], ';
			}
			
			// $qa_content['custom'.++$c] = implode($weekAskTime);
			
			// prepare answers count
			$answers = array();
			$a = 0;
			while( ($row = qa_db_read_one_assoc($queryAnswers,true)) !== null ) {
				$answers[++$a] = substr($row['created'],0,10);
			}
			// prepare comments count
			$comments = array();
			$c = 0;
			while( ($row = qa_db_read_one_assoc($queryComments,true)) !== null ) {
				$comments[++$c] = substr($row['created'],0,10);
			}
			
			// sort array by values
			$questionsDays = array_count_values($questions);
			$answersDays = array_count_values($answers);
			$commentsDays = array_count_values($comments);
			
			$sumQ = 0;
			$sumA = 0;
			$sumC = 0;
			$countDays = 0;
			$tableHTML = '';

			$day = $startdate;
			while (strtotime($day) <= strtotime($enddate)) {
				// get questions
				$questionsThatDay = (isset($questionsDays[$day]) && $questionsDays[$day]>0) ? $questionsDays[$day] : 0;
				// get answers
				$answersThatDay = (isset($answersDays[$day]) && $answersDays[$day]>0) ? $answersDays[$day] : 0;
				// get comments
				$commentsThatDay = (isset($commentsDays[$day]) && $commentsDays[$day]>0) ? $commentsDays[$day] : 0;
				// l for full weekday, D for short weekday
				$tableHTML .= '<tr> <td>'.date('D', strtotime($day)).'</td> <td>'.$day.'</td> <td>'.$questionsThatDay.'</td> <td>'.$answersThatDay.'</td> <td>'.$commentsThatDay.'</td> </tr>';
				$sumQ += $questionsThatDay;
				$sumA += $answersThatDay;
				$sumC += $commentsThatDay;
				$countDays++;
				$dayjs = str_replace('-', '/', $day);
				$jsData_forTableQ .= '[(new Date("'.$dayjs.'")).getTime(), '.$questionsThatDay.'], ';
				$jsData_forTableA .= '[(new Date("'.$dayjs.'")).getTime(), '.$answersThatDay.'], ';
				$jsData_forTableC .= '[(new Date("'.$dayjs.'")).getTime(), '.$commentsThatDay.'], ';
				// iterate to next day
				$day = date ("Y-m-d", strtotime("+1 day", strtotime($day)));
			}

			// comments disabled
			// $jsData_forTableC = '[]';
			

			// catch wrong date, e.g. 2013-05-01 to 2013-04-01
			if($countDays==0) {
				$qa_content['custom0']='<p>Datum falsch angegeben: '.$start_date.' - '.$end_date.'</p>';
				$qa_content['custom1']='<a class="btnblue" href="../statistiken">zurück zur Statistik</a>';
				return $qa_content;
			}
			// counter for custom html output
			$c = 2;
			$imgCount = 1;
			$imgDelCount = 1;
			
			// initiate output string
						//  html
			$listStatistics = '
					<script src="'.$this->urltoroot.'tablesorter//jquery.tablesorter.min.js"></script>
					<style type="text/css"> 
						table.tablesorter { font-family: Arial, Tahoma, Verdana, sans-serif; background-color: #CDCDCD; margin:10px 0pt 15px; width: 100%; text-align: left; } 
						table.tablesorter thead tr th, table.tablesorter tfoot tr th { background-color: #e6EEEE; border: 1px solid #FFF; padding: 4px; } 
						table.tablesorter thead tr .header { background:#FFFFCC; background-image: url(bg.gif); background-repeat: no-repeat; background-position: center right; cursor: pointer; border:1px solid #BBBBBB; } 
						table.tablesorter tbody td { color: #3D3D3D; padding: 4px; vertical-align: top; } 
						table.tablesorter tbody tr { background:#FFF; border: 1px solid #CCC; } 
						table.tablesorter tbody tr.odd td { background-color:#F0F0F6; } table.tablesorter thead tr .headerSortUp { background-image: url(asc.gif); } 
						table.tablesorter thead tr .headerSortDown { background-image: url(desc.gif); } table.tablesorter thead tr .headerSortDown, table.tablesorter thead tr .headerSortUp { background-color: #FFDDAA; } 
						table.tablesorter tbody tr:hover td { background-color:#FFFAAA; cursor:default; }
					</style>
					<script type="text/javascript" src="'.$this->urltoroot.'graphTable.flot.min.js"></script>';
			
			$lastmonthstart = date("Y-m-d", mktime(0, 0, 0, date("m")-1, 1, date("Y")));
			$lastmonthend = date("Y-m-d", mktime(0, 0, 0, date("m"), 0, date("Y")));
			
			$listStatistics .= '<p style="font-size:14px;">Zeitraum: '.$startdate. ' - '.$enddate.'</p>';
			$listStatistics .= '<p style="font-size:10px;">
									<a href="../statistiken">dieser Monat</a> |
									<a href="?start='.$lastmonthstart.'&end='.$lastmonthend.'">letzter Monat</a> | 
									<a href="?user=Unknown&start='.$lastmonthstart.'&end='.$lastmonthend.'">Unknown letzten Monat</a> |
									<a href="?user=Unknown">Unknown diesen Monat</a>
									</p>';
			$listStatistics .= '<p><span style="color:#33F;">Fragen: <b>'.$sumQ.'</b></span> | <span style="color:#F33;">Antworten: <b>'.$sumA.'</b></span> | <span style="color:#555;">Kommentare: <b>'.$sumC.'</b></span>';
			
			$listStatistics .= '<div id="placeholder" style="width:100%;height:400px;">.</div>';
			
			$listStatistics .= '<p style="font-size:18px;line-height:150%;margin:20px 0 30px 0;">Durchschnitt: <br />
				 <span style="color:#33F;">' . round($sumQ/$countDays,1) . ' Fragen je Tag</span> | 
				 <span style="color:#F33;">' . round($sumA/$countDays,1) . ' Antworten je Tag</span> | 
				 <span style="color:#555;">' . round($sumC/$countDays,1) . ' Kommentare je Tag</span></p>';
			
			$listStatistics .= '<table class="tablesorter"><thead><tr> <th>Wochentag</th> <th>Datum</th> <th>Fragen</th> <th>Antworten</th> <th>Kommentare</th> </tr></thead>';
			$listStatistics .= $tableHTML;
			$listStatistics .= '</table>';
			
			$qa_content['custom'.++$c] = $listStatistics;
			
		if(!isset($userhandle)) {
			$qa_content['custom'.++$c] = '<h2 style="margin-top:40px;">Fragendichte</h2> 
											<div id="placeholder2" style="width:780px;height:780px;margin-top:30px;">.</div>';
		}
			$qa_content['custom'.++$c] = '<script type="text/javascript">
				$(document).ready(function() {
					$(".tablesorter").tablesorter( {sortList: [[1,0]]} );
					
					// see flot API: https://github.com/flot/flot/blob/master/API.md
					// $.plot($("#placeholder"), data, options);
					// $.plot($("#placeholder"), [ [[0, 0], [1, 1]] ], { yaxis: { max: 1 } });
					
					// for 1st and 2nd plot
					var weekdays = ["So","Mo","Di","Mi","Do","Fr","Sa"];

					/*var markings = [
						{ color: "#f6f6f6", yaxis: { from: 1 } },
						{ color: "#f6f6f6", yaxis: { to: -1 } },
						{ color: "#000", lineWidth: 1, xaxis: { from: 2, to: 2 } },
						{ color: "#000", lineWidth: 1, xaxis: { from: 8, to: 8 } }
					];*/

					// helper for returning the weekends in a period
					function weekendAreas(axes) {
						var markings = [],
							d = new Date(axes.xaxis.min);

						// go to the first Saturday
						d.setUTCDate(d.getUTCDate() - ((d.getUTCDay() + 1) % 7))
						d.setUTCSeconds(0);
						d.setUTCMinutes(0);
						d.setUTCHours(0);

						var i = d.getTime();

						// when we do not set yaxis, the rectangle automatically extends to infinity upwards and downwards
						do {
							markings.push({ xaxis: { from: i, to: i + 1 * 24 * 60 * 60 * 1000 }, color: "#DDD" });
							i += 7 * 24 * 60 * 60 * 1000;
						} while (i < axes.xaxis.max);

						return markings;
					}

					var options = {
						xaxis: {
							mode: "time",
							minTickSize: [1, "day"],
							/*ticks: [
								0, [ 4, "\u03c0/2" ], [ 8, "\u03c0" ],
							]*/
						},
						/*
						// http://www.jqueryflottutorial.com/how-to-make-jquery-flot-time-series-chart.html
						xaxes: [{
							mode: "time",       
							tickFormatter: function(val, axis) {           
								return weekdays[new Date(val).getDay()];
							},
							color: "black",
							position: "top",       
							axisLabelUseCanvas: true,
							axisLabelFontSizePixels: 12,
							axisLabelFontFamily: "Verdana, Arial",
							axisLabelPadding: 5
						},
						{
							mode: "time",
							timeformat: "%m/%d",
							tickSize: [3, "day"],
							color: "black",       
							position: "bottom",       
							axisLabelUseCanvas: true,
							axisLabelFontSizePixels: 12,
							axisLabelFontFamily: "Verdana, Arial",
							axisLabelPadding: 10
						}],
						*/
						yaxis: {
							min: 0
						},
						series: {
						  lines: { show: true,
							lineWidth: 2,
							fill: false,
							fillColor: "rgba(255, 255, 255, 0.8)"
						  },
						  points: { 
							show: true,
						  },
						  shadowSize: 3,
						},
						//bars: { show: true, barWidth: 0.5, fill: 0.9 },
						grid: {
							hoverable: true, 
							clickable: true,
							markings: weekendAreas, 
							backgroundColor: { colors: [ "#fff", "#eee" ] },
							//markings: markings
						},
						/*
						zoom: {
							interactive: true
						},
						pan: {
							interactive: true
						},
						*/
						/*legend: {
							position: "ne",
							show: true
						},*/
					}
					// for tooltip, i.e. label on hover: http://people.iola.dk/olau/flot/examples/interacting.html
					
					var plotData = [{ data: ['.$jsData_forTableQ.'], 
										color: "#34F", 
										grid: {
											show: true,
										}
									},
									{ data: ['.$jsData_forTableA.'], 
										color: "rgba(255, 100, 100, 0.8)", 
									},
									{ data: ['.$jsData_forTableC.'], 
										color: "rgba(190, 190, 190, 0.5)", 
										grid: {
											show: true,
										}
									},
								];

					$.plot($("#placeholder"), plotData, options);
					
					
					// show tooltip with data on mouseover
					/*****
					function showTooltip(x, y, contents) {
						$("<div id=\'flotTooltip\'>" + contents + "</div>").css({
							position: "absolute",
							display: "none",
							top: y + 5,
							left: x + 5,
							border: "1px solid #fdd",
							padding: "2px",
							"background-color": "#fee",
							opacity: 0.80
						}).appendTo("body").fadeIn(200);
					}
					var previousPoint = null;
					$("#placeholder").bind("plothover", function (event, pos, item) {
						if (item) {
							//console.log(previousPoint + "!=" + item.dataIndex);
							//console.log(item.datapoint[0].toFixed(2) + " + " + item.datapoint[0].toFixed(2));
							if (previousPoint != item.dataIndex) {

								previousPoint = item.dataIndex;

								$("#flotTooltip").remove();
								var x = item.datapoint[0].toFixed(2),
									y = item.datapoint[1].toFixed(2);

								// showTooltip(item.pageX, item.pageY, item.series.label + " of " + x + " = " + y);
								showTooltip(item.pageX, item.pageY, ">" + x +": " + y);
							}
						} else {
							$("#flotTooltip").remove();
							previousPoint = null;            
						}
					});
					*****/ 
					
					
					/**** 2nd plot ****/
					var options2 = {
						xaxis: {
							// mode: "day",
							// minTickSize: 1,
							zoomRange: [0.1, 10],
							panRange: [-10, 10],
							tickDecimals: 0,
						    tickFormatter: function (val, axis) {
							  return weekdays[val-1];
						    }
						},
						yaxis: {
							mode: "time",
							timeformat: "%H:%M",
							min: 0, 
							max: 24*60*60*1000,
							// tickSize: 1,
							// tickDecimals: 0,
							minTickSize: [1, "hour"],
							zoomRange: [0.1, 10],
							panRange: [-10, 10],
						},
						bars: {
							show: true,
							lineWidth: 1,
							fill: false,
							fillColor: "rgba(0, 0, 255, 0.8)"
						},
						grid: {
							show: true,
							hoverable: true, 
							clickable: true,
							color: "#474747",
							tickColor: "#474747",
							borderWidth: 1,
							autoHighlight: true,
							mouseActiveRadius: 2,
						},
						// zoom + pan: http://www.flotcharts.org/flot/examples/navigate/index.html
						/*zoom: {
							interactive: true
						},
						pan: {
							interactive: true
						}*/
					}
					
					var plotData2 = [
									{ data: ['.$jsData_forTableQ2.'], 
										color: "#34F", 
										grid: {
											show: true,
										}
									},
								];

					'.(isset($userhandle) ? '' : 
						'$.plot($("#placeholder2"), plotData2, options2);').'
				});
			</script>';
			
			
			$qa_content['custom'.++$c] = '<style type="text/css">
				#placeholder2 .tickLabel { margin-left:50px; }
				#placeholder2 .tickLabel:last-child { display:none; }
			</style>';
			
			return $qa_content;
		}
		
		function convertTimeToSec($str_time) {
			// $str_time = "23:12:95";
			$str_time = preg_replace("/^([\d]{1,2})\:([\d]{2})$/", "00:$1:$2", $str_time);
			sscanf($str_time, "%d:%d:%d", $hours, $minutes, $seconds);
			return 1000*($hours * 3600 + $minutes * 60 + $seconds); // $time_seconds
		}

	};
	

/*
	Omit PHP closing tag to help avoid accidental output
*/