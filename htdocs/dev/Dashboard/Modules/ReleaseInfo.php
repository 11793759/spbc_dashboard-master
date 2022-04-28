<?php

class ReleaseInfo extends Module {

	function __construct($weight) {
		$this->weight = $weight;
	}
	
	function getName() {
		return "ReleaseInfo";
	}
	
	function populate($handle, &$values, &$sections) {
		$config = $values["CONFIG"];
				
		// Additional Info
		$carbon_id = get($handle, "${config}-carbon-id");
		$carbon_id_w_tt = get_with_tooltip($handle, "${config}-carbon-id");
		$values["INFO_ROWS"]["release_carbon_id"] = array(
			"name" => "Carbon Configuration ID",
			"value" => sprintf('<a href="https://hsdes.intel.com/appstore/carbon/overview/%0s" target="_blank">%0s</a>', $carbon_id, $carbon_id_w_tt),
		);
		$values["INFO_ROWS"]["release_dss_id"] = array(
			"name" => "DSS Legacy Configuration ID",
			"value" => get_with_tooltip($handle, "${config}-dss-id")
		);
		$ip_milestone = get_with_tooltip($handle, "${config}-milestone");
		$dss_id = get($handle, "${config}-dss-id");
		$ip_milestone_id = get($handle, "${config}-dss-milestone_id");
		$values["INFO_ROWS"]["release_pearl"] = array(
			"name" => "PEARL Quality",
			"value" => sprintf('<a href="https://ipquality.swiss.intel.com/checklist?checklist=10&milestone=%0s&mode=1&ip=%0s">%0s</a>', $ip_milestone_id, $dss_id, $ip_milestone),
		);
		$values["INFO_ROWS"]["release_milestone"] = array(
			"name" => "Current Milestone",
			"value" => $ip_milestone,
		);
		
		$values["INFO_ROWS"]["repo_branch"] = array(
			"name" => "Branch",
			"value" => get_with_tooltip($handle, "${config}-branch"),
		);
		/* $values["HSD_BUGS_LINK"] = get($handle, "hsd_bugs_link"); */
		/* $values["HSD_OPEN_BUGS_LINK"] = get($handle, "hsd_open_bugs_link"); */
		/* $values["HSD_PCRS_LINK"] = get($handle, "hsd_pcrs_link"); */
		/* $values["HSD_OPEN_PCRS_LINK"] = get($handle, "hsd_open_pcrs_link"); */
		
		// no $sections additions - handled by InformationTable
	}
}