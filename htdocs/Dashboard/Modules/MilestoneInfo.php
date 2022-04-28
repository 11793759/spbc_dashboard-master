<?php

class MilestoneInfo extends Module {
	function __construct($weight) {
		$this->weight = $weight;
	}
	
	function getName() {
		return "MilestoneInfo";
	}
	
	function populate($handle, &$values, &$sections) {		
		$config = $values["CONFIG"];
				
		// MILESTONES
		$revisions = get_each($handle, "subject LIKE '${config}-revision-%-por_date'");
		asort($revisions); // sort by por_date
		foreach($revisions as $revision => $por_date) {
			$revision = preg_replace("/^.*-revision-/","",$revision);
			$revision = preg_replace("/-por_date$/","",$revision);

			if($por_date === NULL || $por_date == "null") {
				continue;
			}

			$trend_date = get($handle, "${config}-revision-${revision}-trend_date");
			$complete = get($handle, "${config}-revision-${revision}-is_complete");
			$milestone_id = get($handle, "${config}-revision-${revision}-milestone_id");

			$milestone_id_str = "";
			if($milestone_id != 0) {
				$milestone_id_str = "(msid $milestone_id)";
			}

			$por_date_str = intel_strftime("%gww%V", $por_date);
			$trend_date_str = intel_strftime("%gww%V", $trend_date);
			$date_str = "";
			$completed_str = "";
			$completed_font = "<span>";

			if($por_date != $trend_date) {
				if($complete) {
					$date_str = $trend_date_str;
				} else {
					$date_str = sprintf("%0s, trending %0s", $por_date_str, $trend_date_str);
				}
			} else {
				$date_str = $por_date_str;
			}

			if($complete) {
				$completed_str = ", Completed"; 
				$completed_font = '<span style="color: #CCCCCC;">';
			} else {
				$completed_str = ""; 
			}

			$values["INFO_ROWS"]["rev_".$revision] = array(
				"name" => $revision,
				"value" => "$completed_font$date_str$completed_str $milestone_id_str",
			);

		}
	}
}