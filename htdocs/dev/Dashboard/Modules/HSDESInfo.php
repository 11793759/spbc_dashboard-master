<?php

class HSDESInfo extends Module {
	function __construct($weight) {
		$this->weight = $weight;
	}
	
	function getName() {
		return "HSDESInfo";
	}
	
	function populate($handle, &$values, &$sections) {		
		$config = $values["CONFIG"];
				
		$values["INFO_ROWS"]["hsdes_bugs"] = array(
			"name" => "HSD Bugs",
			"value" => sprintf('<a href="%0s" target="_blank">Bugs</a> (<a href="%0s" target="_blank">Open Only</a>)',
				$values["HSD_BUGS_LINK"],
				$values["HSD_OPEN_BUGS_LINK"]
			),
		);
				
		$values["INFO_ROWS"]["hsdes_pcrs"] = array(
			"name" => "HSD PCRs",
			"value" => sprintf('<a href="%0s" target="_blank">PCRs</a> (<a href="%0s" target="_blank">Open Only</a>)',
				$values["HSD_PCRS_LINK"],
				$values["HSD_OPEN_PCRS_LINK"]
			),
		);
				
		$values["INFO_ROWS"]["hsdes_sightings"] = array(
			"name" => "HSD Sightings",
			"value" => sprintf('<a href="%0s" target="_blank">PCRs</a>',
				$values["HSD_SIGHTINGS_LINK"]
			),
		);
	}
}