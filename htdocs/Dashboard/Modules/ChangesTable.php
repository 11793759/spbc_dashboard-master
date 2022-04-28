<?php

class ChangesTable extends Module {
	function __construct($weight) {
		$this->weight = $weight;
	}
	
	function getName() {
		return "ChangesTable";
	}
	
	function populate($handle, &$values, &$sections) {		
		$config = $values["CONFIG"];
		// Changes
		$v = array();
		$v["LAST_DROP_NAME"] = get_with_tooltip($handle, "${config}-last_drop-name");
		$regression_changes_str = get($handle, "${config}-changes");
		$regression_changes = explode(";", $regression_changes_str);
		$v["CHANGES_TABLE"] = "";
		foreach($regression_changes as $change) {
			if($change == "") { continue; }
			preg_match("/\[Proj:\s*([^\]]*)\]\s*/", $change, $matches);
			$proj = $matches[1];
			if( !( $proj == "PIC" || $proj == $config) ) {
				continue;
			}

			$change = preg_replace("/\[Proj:([^\]]*)\]\s*/","", $change);
			$change = preg_replace("/https:\/\/.*#\//","", $change);
			$change = ucfirst($change);

			$is_pcr = preg_match("/PCR/i",$change);
			$is_hsd = preg_match("/HSD/i",$change);
			$is_high = preg_match("/HIGH/",$change);
			$is_notes = preg_match("/NOTES/",$change);

			// do this after matching PCR/HSD
			$change = preg_replace("/([0-9]{11})/","<a href=\"https://hsdes.intel.com/appstore/article/#/$1/\" target=\"_blank\">$1</a>", $change);

			$change = "<span class=\"changes-proj\">$proj</span> ".$change;
			if($is_hsd) { $change = "<span class=\"changes-hsd\">HSD</span> ".$change; }
			if($is_pcr) { $change = "<span class=\"changes-pcr\">PCR</span> ".$change; }
			if($is_high) { $change = "<span class=\"changes-high\">HIGH</span> ".$change; }

			if($is_hsd || $is_pcr || $is_high || $is_notes) {
				$v["CHANGES_TABLE"] .= "<tr><td>$change</td></tr>";
			}
		}
		if($v["CHANGES_TABLE"] == "") {
			$v["CHANGES_TABLE"] = "<tr><td>No change information available.</td></tr>";
		}

		if(QUERY_DEBUG) {
			print "changes v: ";
			print_r($v);
		}
		
		$sections["MAIN"][$this->getName()] = array(
			'weight' => $this->weight,
			'template' => "Dashboard/Modules/ChangesTable.html",
			'values' => $v,
		);
	}
}