<?php

class InformationTable extends Module {
	function __construct($weight) {
		$this->weight = $weight;
	}
	
	function getName() {
		return "InformationTable";
	}
	
	function populate($handle, &$values, &$sections) {
		$config = $values["CONFIG"];
		
		ksort($values["INFO_ROWS"]);
		
		$rows = "";
		
		foreach($values["INFO_ROWS"] as $key=>$val) {
			$rows .= "<tr><td>".$val["name"]."</td><td>".$val["value"]."</td></tr>\n";
		}
		
		$values["INFO_TABLE_ROWS"] = $rows;
				
		$sections["SIDEBAR"][$this->getName()] = array(
			'weight' => $this->weight,
			'template' => "Dashboard/Modules/InformationTable.html",
			'values' => $values,
		);
	}
}