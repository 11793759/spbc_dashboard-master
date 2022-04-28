<?php

class RegressionStatus extends Module {

	function __construct($weight) {
		$this->weight = $weight;
	}
	
	function getName() {
		return "RegressionStatus";
	}
	
	function populate($handle, &$values, &$sections) {
		$config = $values["CONFIG"];
		$v = array();

		// Regressions
		// Show regressions in the last two days

		$regressions = array();

		$v["REGRESSION_TABLE"] = '<tr id="regression-table-spinner" style="height: 40px;"><td><div class="overlay"><i class="fa fa-refresh fa-spin"></i></div></td><td></td></tr>';
		$reg_count = array();
		if(subsection("regression-table")) {
			
			$where_terms = array();
			$where_clause = "";
			$regressions = get_regressions($handle,"WHERE cust='${config}' AND start_date > ".(time()-14 * 24 * 60 * 60)."");
			
			$saw_models=array();
			$saw_running_list=array();
			foreach($regressions as $reg) {
				$list = $reg["list"];
				$completed_str = $reg["completed"];
				$running = ($completed_str == 1) ? 0 : 1;

				@$nbstatus = $reg["nbstatus"];
				@$nbfeeder = $reg["nbfeeder"];
				if($running && preg_match('/WL=0;WR=0;Run=0/',$nbstatus)) { continue; } // old "-running" keys that finished but weren't cleared out
				if($running) {
					if(in_array($list, $saw_running_list)) {
						continue;
					} else {
						array_push($saw_running_list, $list);
					}
				}
				
				if(array_key_exists($list, $reg_count)) {
					$reg_count[$list] += 1;
				} else {
					$reg_count[$list] = 1;
				}
				
				// only include 3 items for each list
				if($reg_count[$list] > 3) {
					continue;
				}

				$pass_rate = array_key_exists("pass_rate", $reg) ? $reg["pass_rate"] : "N/A";
				$num_buckets = array_key_exists("number_of_buckets", $reg) ? $reg["number_of_buckets"] : "N/A";
				$model_name = $reg["model"];
				$name = $reg["name"];
				
				// TODO: Find how Karol is updating these
				$release_webpage = "";
				$release_webpage = querySingle($handle, "SELECT value FROM data WHERE subject='${config}-${model_name}-release_webpage' ORDER BY date DESC LIMIT 1");
				$release_notes = "";
				if ($release_webpage != "")
				{
					$release_notes = "<a href=\"$release_webpage\" target=\"_blank\">Release Information</a>";		
				}
				
				# calculate the percent complete
				if($running) {
					$pass_count = array_key_exists("pass_count", $reg) ? $reg["pass_count"] : 0;
					$fail_count = array_key_exists("fail_count", $reg) ? $reg["fail_count"] : 0;
					if($nbstatus != "") {
						preg_match('/Total=([0-9]*)/', $nbstatus, $matches);
						$total_count = $matches[1];

						$percent_complete = sprintf("%0.1f", 99.9*($pass_count+$fail_count)/$total_count);
					} else {
						$total_count = 0;
						$percent_complete = "Unknown";
						continue;
					}
				}

				$date = intel_strftime("ww%V.%u",$reg["start_date"]);

				$listuc = ucwords($list);
				$disposition = "<b>Disposition:</b> Not yet generated";
				$coverage = "<b>Coverage:</b> Not yet generated";
				if(array_key_exists("urls", $reg) && array_key_exists("psvsort_report", $reg["urls"])) {
					$disposition = "<b>Disposition:</b> <a href=\"".$reg["urls"]["psvsort_report"]."\" target=\"_blank\">${model_name}</a>";
				}
				if(array_key_exists("urls", $reg) && array_key_exists("cov-passing_merged", $reg["urls"])) {
					$coverage = "<b>Coverage:</b> <a href=\"".$reg["urls"]["cov-passing_merged"]."\" target=\"_blank\">Passing (merged)</a>";
				}

				if($running) {
					@$v["REGRESSION_TABLE"] .= "
						<tr>
						<td>
						$listuc <b>$percent_complete%</b> Complete $release_notes<br /><span style=\"color: #AAA; font-size: 0.9em;\">${name}<br /></span>
						<div class=\"progress progress-xs\">
						<div class=\"progress-bar progress-bar-aqua progress-bar-striped\" role=\"progressbar\" aria-valuenow=\"$percent_complete\" aria-valuemin=\"0\" aria-valuemax=\"100\" style=\"width: $percent_complete%;\">
						<span class=\"sr-only\">$percent_complete%</span>
						</div>
						</div>
						</td>
						<td>
						<b>Passing:</b> ${pass_rate}%<br />
						<b>Failing Buckets:</b> ${num_buckets}<br />
						<b>Status:</b> ${nbstatus}<br />
						<b>Feeder:</b> <a href=\"https://nbflow.intel.com/feeder/${nbfeeder}/tasks\" target=\"_blank\">${nbfeeder}</a><br />
						$disposition<br />
						$coverage<br />
						</td>
						</tr>
						";
				} else {
					@$v["REGRESSION_TABLE"] .= "
						<tr>
						<td>
						$listuc Completed $date $release_notes<br /><span style=\"color: #AAA; font-size: 0.9em;\">${name}<br /></span>
						</td>
						<td>
						<b>${pass_rate}%</b> (${num_buckets}) - <a href=\"".$reg["urls"]["psvsort_report"]."\" target=\"_blank\">${model_name}</a><br />
						<b>Coverage:</b> <a href=\"".$reg["urls"]["cov-passing_merged"]."\" target=\"_blank\">Passing (merged)</a>
						</td>
						</tr>
						";
				}
			}

			if($v["REGRESSION_TABLE"] == "") {
				$v["REGRESSION_TABLE"] = "<tr><td>No regressions in the past two weeks.</td><td></td></tr>";
			}
			
			print $v["REGRESSION_TABLE"];
			exit;
		}
		
		$sections["MAIN"][$this->getName()] = array(
			'weight' => $this->weight,
			'template' => "Dashboard/Modules/RegressionStatus.html",
			'values' => $v,
		);
	}
}