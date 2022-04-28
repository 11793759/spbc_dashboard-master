<?php

class RegressionDecisionTable extends Module {
	function __construct($weight) {
		$this->weight = $weight;
	}
	
	function getName() {
		return "RegressionDecisionTable";
	}
	
	function populate($handle, &$values, &$sections) {
		$config = $values["CONFIG"];
		$v = array();
		
		
		$v["REGRESSION_DECISION_TABLE"] = "";
		$decision_table = get($handle, "regression-decision_table"); // CSV table format with headers on first row
		$decision_table = explode("\n",$decision_table);
		$num_columns = 100;
		foreach($decision_table as $i=>$row) {
			$columns = explode(",",$row,$num_columns);
			$bold = false;
			
			// count number of columns from header row - some "Decision" cells have commas in them and this is easier than actually escaping them
			if($i == 0) { $num_columns = count($columns); }
			
			foreach($columns as &$column) {
				if(preg_match("/${config}-regression/",$column)) {
					$bold = true;
				}
				if(!$bold && $i > 0) {
					$column = "<span style=\"color: #CCC;\">$column</span>";
				}
				if($i == 0) {
					$column = "<th>$column</th>";
				} else {
					$column = "<td>$column</td>";
				}
			}
			
			$tr = "<tr>".implode("",$columns)."</tr>\n";
			$v["REGRESSION_DECISION_TABLE"] .= $tr;
		}
		
		$sections["MAIN"][$this->getName()] = array(
			'weight' => $this->weight,
			'template' => "Dashboard/Modules/RegressionDecisionTable.html",
			'values' => $v,
		);
	}
}