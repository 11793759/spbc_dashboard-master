<?php

class RegressionGraph extends Module {

	function __construct($weight, $lists) {
		$this->weight = $weight;
		$this->lists = $lists;
	}
	
	function getName() {
		return "RegressionGraph";
	}
	
	function build_regression_comment($reg, &$notes, $key, $prev_val, $val) {
		if($reg != null && array_key_exists("comments", $reg) && array_key_exists($key, $reg["comments"])) {
			if(QUERY_DEBUG) {
				echo $reg["name"]." $key comment: ".$reg["comments"][$key]."<br>";
			}
			
			$delta_str = "";
			if($prev_val != -1) {
				$diff = $val - $prev_val;
				if(QUERY_DEBUG) {
					echo $reg["name"]." went from ".$prev_val." to ".$val."<br>";
				}
				if($diff > 0.2 || $diff < -0.2) {
					$delta_str = sprintf(" %+.2f%%", $diff);
				}
			}
			
			array_push($notes, "<li>".$reg["short_model"]." ".ucfirst($key).$delta_str.": ".$reg["comments"][$key]."</li>");
			return '"~'.count($notes).'~"'; // return a number reference, but were going to strreplace it with the reverse later
		}
		return "null";
	}
	
	function populate($handle, &$values, &$sections) {
		$config = $values["CONFIG"];
		$v = array();
		$v["FEATURE_SIDELOAD_JS"] = "";
		
		$v["REGRESSION_GRAPHS"] = "";
		$v["FEATURE_GRAPHS"] = "";
		
		foreach($this->lists as $list) {
			$this->populate_regression_graphs($handle, $v, $config, $list, ($list == $values["DEFAULT_LIST_NAME"]) );
		}
		
		$sections["MAIN"][$this->getName()."_REG"] = array(
			'weight' => $this->weight,
			'template' => "Dashboard/Modules/RegressionGraph.html",
			'values' => $v,
		);
		$sections["MAIN"][$this->getName()."_FEATURE"] = array(
			'weight' => $this->weight - 1,
			'template' => "Dashboard/Modules/FeatureHealth.html",
			'values' => $v,
		);
		
		$values["FEATURE_SIDELOAD_JS"] = $v["FEATURE_SIDELOAD_JS"];
		
		if(QUERY_DEBUG) {
			print "RegressionGraph values: ";
			print_r($v);
		}
		
		if(subsection("regression-graphs")) {
			print $v["REGRESSION_GRAPHS"];
			print $v["FEATURE_GRAPHS"];
			exit;
		}
	}
	
	function populate_regression_graphs($handle, &$values_ext, $config, $list, $startup_list=FALSE) {
		$r_values["PASS_RATE"] = "";
		$r_values["COVERAGE"] = "";
		$r_values["QOV"] = "";
		$r_values["LABELS"] = "";
		$r_values["PASS_RATE_COMMENTS"] = "";
		$r_values["COVERAGE_COMMENTS"] = "";
		$r_values["QOV_COMMENTS"] = "";
		
		if(subsection("regression-graphs") || subsection("feature-health-${list}")) {
			$regressions = get_regressions($handle, "WHERE completed = 1 AND cust='${config}' AND list='${list}' AND start_date > '".(time()-52*7*24*60*60)."' ");
			$pass_rates_raw = get_array($handle, "SELECT subject, value, comment, date FROM data WHERE subject='${config}-regression-$list-pass_rate' AND date > '".(time()-52*7*24*60*60)."' ORDER BY date DESC");
			$pass_rates = array();
			$pass_rates_sort_column = array();
			$models = array();

			/* sort by the truncated comment */
			foreach ($pass_rates_raw as $key => $row) {
				preg_match("/([0-9]+ww[0-9]+[a-g])/", $row["comment"], $matches);
				$name = $matches[1];

				if(!in_array($name, $models)) {
					array_push($models, $name);
				} else {
					continue;
				}

				$row["name"] = $name;
				array_push($pass_rates, $row);
				array_push($pass_rates_sort_column, $name);
			}

			array_multisort($pass_rates_sort_column, SORT_DESC, $pass_rates);

			$num_models = 0;
			$feature_strs = array();
			$prev_pass_rate = -1;
			$prev_coverage = -1;
			$prev_qov = -1;
			
			if(subsection("regression-graphs") || subsection("feature-health-${list}")) {
				$notes = array();
				$prev_reg = NULL;
				
				foreach ($regressions as $reg) {
					preg_match("/(ww[0-9]+[a-n])/", $reg["model"], $matches);
					$short_model = "";
					if(count($matches) == 0) {
						$short_model = $reg["model"]; 
					} else {
						$short_model = $matches[1];	
					}
					$name = $reg["name"];
					$reg["short_model"] = $short_model;

					$r_values["LABELS"] = "\"".$short_model."\", " . $r_values["LABELS"];
					@$pass_rate = $reg["pass_rate"];
					$r_values["PASS_RATE"] = $pass_rate.", " . $r_values["PASS_RATE"];

					@$feature_str = $reg["features"];
					if($feature_str != "") $feature_strs[$name] = $feature_str;

					# now select the Coverage and QoV numbers from the time (plus five minutes) that regression finished
					@$coverage = $reg["cov"]["score"];
					if(QUERY_DEBUG) {
						print $reg['model'].": $coverage<br>";
					}
					$r_values["COVERAGE"] = ((trim($coverage) == "") ? "null" : $coverage) . ", " . $r_values["COVERAGE"];
					@$qov = $reg["qov"];
					$r_values["QOV"] = ((trim($qov) == "") ? "null" : $qov) . ", " . $r_values["QOV"];
											
					// since we are iterating from newest regression to oldest, build comments for the previous regression so we can calculate delta percent
					if($prev_reg != NULL) {
						$r_values["PASS_RATE_COMMENTS"] = $this->build_regression_comment($prev_reg, $notes, "regression", $pass_rate, $prev_pass_rate).", " . $r_values["PASS_RATE_COMMENTS"];
						$r_values["COVERAGE_COMMENTS"] = $this->build_regression_comment($prev_reg, $notes, "coverage", $coverage, $prev_coverage).", " . $r_values["COVERAGE_COMMENTS"];
						$r_values["QOV_COMMENTS"] = $this->build_regression_comment($prev_reg, $notes, "qov", $qov, $prev_qov).", " . $r_values["QOV_COMMENTS"];
					}
					
					$prev_pass_rate = $pass_rate;
					$prev_coverage = $coverage;
					$prev_qov = $qov;
					$prev_reg = $reg;

					$num_models++;
					if($num_models == 20) break;
				}
				// build the comments for the last regression
				$r_values["PASS_RATE_COMMENTS"] = $this->build_regression_comment($prev_reg, $notes, "regression", -1, $prev_pass_rate).", " . $r_values["PASS_RATE_COMMENTS"];
				$r_values["COVERAGE_COMMENTS"] = $this->build_regression_comment($prev_reg, $notes, "coverage", -1, $prev_coverage).", " . $r_values["COVERAGE_COMMENTS"];
				$r_values["QOV_COMMENTS"] = $this->build_regression_comment($prev_reg, $notes, "qov", -1, $prev_qov).", " . $r_values["QOV_COMMENTS"];
				
				$r_values["REGRESSION_COMMENTS"] = "";
				if(count($notes) > 0) {
					
					// Reverse the number system - since we looped from newer to older above, reverse the number labels because
					// graphs are read left-to-right
					$notes = array_reverse($notes);
					for($i=0;$i<count($notes);$i++) {
						$rev = "~".($i+1)."~";
						$new = count($notes)-$i;
						$r_values["PASS_RATE_COMMENTS"] = str_replace($rev, $new, $r_values["PASS_RATE_COMMENTS"]);
						$r_values["COVERAGE_COMMENTS"] = str_replace($rev, $new, $r_values["COVERAGE_COMMENTS"]);
						$r_values["QOV_COMMENTS"] = str_replace($rev, $new, $r_values["QOV_COMMENTS"]);
					}
					$r_values["REGRESSION_COMMENTS"] = "<ol>".implode("",$notes)."</ol>";
				}
				
				$r_values["GRAPH_ID"] = "$list";
				$values_ext["REGRESSION_${list}"] = $r_values;

				# Feature Table
				$features = array();
				$feature_avg = array();
				$num_feature_models = count($feature_strs);
				foreach ($feature_strs as $model=>$feature_str) {
					$feature_array = explode(";", $feature_str);
					sort($feature_array);
					foreach($feature_array as $one_feature) {
						$one_feature_array = explode(",", $one_feature);
						$feature_name = $one_feature_array[0];

						$features[$model][$feature_name] = array("passing"=>$one_feature_array[1], "run"=>$one_feature_array[2], "expected"=>$one_feature_array[2]); // todo: lookup expected
						if($features[$model][$feature_name]["run"] > 0) {
							$features[$model][$feature_name]["percent"] = (1.0*$one_feature_array[1]/$one_feature_array[2])*100;
						} else {
							$features[$model][$feature_name]["percent"] = 0;
						}

						if(!array_key_exists($feature_name, $feature_avg) || !array_key_exists("run", $feature_avg[$feature_name])) {
							$feature_avg[$feature_name]["run"] = 0;
							$feature_avg[$feature_name]["percent"] = 0;
						}
						$feature_avg[$feature_name]["run"] += $features[$model][$feature_name]["run"]/$num_feature_models;
						$feature_avg[$feature_name]["percent"] += $features[$model][$feature_name]["percent"]/$num_feature_models;
					}
				}

				reset($features);
				$feature_model = key($features);
				$f_values["TABLE"] = "";
				$f_values["LABELS"] = "";
				$f_values["PASS"] = "";
				$f_values["FAIL"] = "";
				$f_values["NOT_ENABLED"] = "";
				$f_values["PASS_NS"] = "";
				$f_values["FAIL_NS"] = "";
				$f_values["NOT_ENABLED_NS"] = "";
				if(count($features) > 0) {
					foreach($features[$feature_model] as $feature=>$fval) {
						$pct_change_str = "";
						$pct_change = $fval["percent"]-$feature_avg[$feature]["percent"];
						if($pct_change > 0.5) {
							$pct_change_str = "<div class=\"feature-up\"><span class=\"feature-up material-icons\">keyboard_arrow_up</span> ".sprintf("%.1f",$pct_change)."%</div>";
						} else if($pct_change < -0.5) {
							$pct_change_str = "<div class=\"feature-down\"><span class=\"feature-down material-icons\">keyboard_arrow_down</span> ".sprintf("%.1f",abs($pct_change))."%</div>";
						}

						$tests_change_str = "";
						$tests_change = $fval["run"]-$feature_avg[$feature]["run"];
						if($feature_avg[$feature]["run"] == 0) { $feature_avg[$feature]["run"] = 1; }
						$tests_change_pct = $tests_change/$feature_avg[$feature]["run"];
						if($tests_change_pct > 0.05) {
							$tests_change_str = "<div class=\"feature-up\"><span class=\"feature-up material-icons\">keyboard_arrow_up</span> ".sprintf("%.0f",$tests_change)."</div>";
						} else if($tests_change_pct < -0.05) {
							$tests_change_str = "<div class=\"feature-down\"><span class=\"feature-down material-icons\">keyboard_arrow_down</span> ".sprintf("%.0f",abs($tests_change))."</div>";
						}

						$f_values["TABLE"] .= "<tr><td>$feature</td><td class=\"feature-passing\">".$fval["passing"]."</td><td class=\"feature-run\">/".$fval["run"]."</td><td>$tests_change_str</td><td>".sprintf("%.1f",$fval["percent"])."%</td><td>${pct_change_str}</td></tr>\n";

						$f_values["LABELS"] .= "\"$feature\", ";
						if($fval["expected"] == 0) { $fval["expected"] = 1; };
						$f_values["PASS"] .= sprintf("%.1f, ", 99.9*$fval["passing"]/$fval["expected"]);
						$f_values["PASS_NS"] .= $fval["passing"].", ";
						$f_values["FAIL"] .= sprintf("%.1f, ", 99.9*($fval["run"]-$fval["passing"])/$fval["expected"]);
						$f_values["FAIL_NS"] .= $fval["run"]-$fval["passing"] . ", ";
						$f_values["NOT_ENABLED"] .= sprintf("%.1f, ", 99.9*($fval["expected"]-$fval["run"])/$fval["expected"]);
						$f_values["NOT_ENABLED_NS"] .= $fval["expected"]-$fval["run"] . ", ";
					}
				}
					
				$f_values["GRAPH_ID"] = "$list";
				$values_ext["FEATURE_${list}"] = $f_values;
					
				if($f_values["TABLE"] == "") {
					$f_values["TABLE"] = "<tr><td>No feature data available.</td><td></td><td></td></tr>\n";
				}

				$f_values["TABLE"] = '<div class="box-body"><table class="table table-bordered table-hover no-margin"><thead><tr><th>Name</th><th class="feature-passing">Passing Tests</th><th>Total Tests</th><th># Passing Change</th><th>Pass Rate</th><th>Pass Rate % change</th></tr></thead><tbody>'.$f_values["TABLE"].'</tbody></table></div>';
				
				$values_ext["FEATURE_${list}_TABLE"] = $f_values["TABLE"];
				
				if(subsection("feature-health-${list}")) {
					print $f_values["TABLE"];
					exit;
				}
			}
			
			$values_ext["REGRESSION_GRAPHS"] = $values_ext["REGRESSION_GRAPHS"] . populate_template("Dashboard/regression_graph_template.js", $values_ext["REGRESSION_${list}"]);
			$values_ext["FEATURE_GRAPHS"] = $values_ext["FEATURE_GRAPHS"] . populate_template("Dashboard/feature_graph_template.js", $values_ext["FEATURE_${list}"]);
		}

		$active = ($startup_list) ? "active" : "";

		@$values_ext["REGRESSION_METRICS_TABS"] .= '<li class="'.$active.'"><a href="#tab_regression_metrics_'.$list.'" data-toggle="tab">'.ucwords($list).'</a></li>'."\n";
		@$values_ext["REGRESSION_METRICS_CANVASES"] .= '
		<div class="tab-pane '.$active.'" id="tab_regression_metrics_'.$list.'">
			<div class="overlay regression-graph-spinner" style="height: 40px;"><i class="fa fa-refresh fa-spin"></i></div>
			<div class="chart">
				<canvas id="'.$list.'_regressionChart" style="height: 250px;"></canvas>
			</div>
			<div id="regression-footnotes-'.$list.'"></div>
		</div>'."\n";
		@$values_ext["FEATURE_HEALTH_TABS"] .= '<li class=""><a href="#tab_feature_health_table_'.$list.'" data-toggle="tab">'.ucwords($list).' Table</a></li>'."\n";
		@$values_ext["FEATURE_HEALTH_TABS"] .= '<li class="'.$active.'"><a href="#tab_feature_health_'.$list.'" data-toggle="tab">'.ucwords($list).' Chart</a></li>'."\n";
		@$values_ext["FEATURE_HEALTH_CANVASES"] .= '<div class="tab-pane" id="tab_feature_health_table_'.$list.'"><div class="overlay" id="tab_feature_health_table_'.$list.'-spinner" style="height: 40px;"><i class="fa fa-refresh fa-spin"></i></div></div>'."\n";
		@$values_ext["FEATURE_HEALTH_CANVASES"] .= '<div class="tab-pane '.$active.'" id="tab_feature_health_'.$list.'"><div class="overlay regression-graph-spinner" style="height: 40px;"><i class="fa fa-refresh fa-spin"></i></div><div class="chart"><canvas id="'.$list.'_featureHealthChart" style="height: 250px;"></canvas></div></div>'."\n";
		
		# add a JQuery statement that will load this list's feature table into the table in FEATURE_HEALTH_CANVASES above
		@$values_ext["FEATURE_SIDELOAD_JS"] .= '$("#tab_feature_health_table_'.$list.'").load("index.php?config='.$config.'&subsection=feature-health-'.$list.'", function() { $("#tab_feature_health_table_'.$list.'-spinner").remove(); });'."\n";

	}
}