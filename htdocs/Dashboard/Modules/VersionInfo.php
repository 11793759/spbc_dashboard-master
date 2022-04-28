<?php

class VersionInfo extends Module {
	function __construct($weight) {
		$this->weight = $weight;
	}
	
	function getName() {
		return "VersionInfo";
	}
	
	function populate($handle, &$values, &$sections) {		
		$config = $values["CONFIG"];
		// VC Versions
		$vc_versions = get_each($handle, "subject LIKE '${config}-vc_version-%'");
		ksort($vc_versions);
		uksort($vc_versions, "strnatcasecmp");
		$v["VC_VERSION_TABLE"] = "";
		foreach($vc_versions as $sql_key => $value) {
			if($value != "") {
				$tool = preg_replace("/^.*-vc_version-/","",$sql_key); // remove the prefix from the version string

				$v["VC_VERSION_TABLE"] .= "<tr><td>$tool</td><td>$value</td></tr>\n";
			}
		}
		if($v["VC_VERSION_TABLE"] == "") {
			$v["VC_VERSION_TABLE"] = "<tr><td>No VC version information available.</td><td></td></tr>";
		}

		// Tool Versions
		$tool_versions = get_each($handle, "subject LIKE '${config}-tool_version-%'");
		ksort($tool_versions);
		uksort($tool_versions, "strnatcasecmp");
		$v["TOOL_VERSION_TABLE"] = "";
		foreach($tool_versions as $sql_key => $value) {
			if($value != "") {
				$tool = preg_replace("/^.*-tool_version-/","",$sql_key); // remove the prefix from the version string

				$v["TOOL_VERSION_TABLE"] .= "<tr><td>$tool</td><td>$value</td></tr>\n";
			}
		}
		if($v["TOOL_VERSION_TABLE"] == "") {
			$v["TOOL_VERSION_TABLE"] = "<tr><td>No tool version information available.</td><td></td></tr>";
		}
		
		$sections["SIDEBAR"][$this->getName()] = array(
			'weight' => $this->weight,
			'template' => "Dashboard/Modules/VersionInfo.html",
			'values' => $v,
		);
	}
}