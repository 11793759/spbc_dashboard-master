<?php

class RegressionInfoBox extends Module {
	public static $call_count = 0;
	public static $colors = array(
		"bg-aqua",
		"bg-green",
		"bg-yellow",
		"bg-purple",
		"bg-green",
		"bg-yellow",
	);
	
	function __construct($weight, $regression_where_clause, $reg_en = true, $cov_en = true, $qov_en = true) {
		$this->weight = $weight;
		$this->where = $regression_where_clause;
		$this->reg_en = $reg_en;
		$this->cov_en = $cov_en;
		$this->qov_en = $qov_en;
	}
	
	function getName() {
		return "LatestRegression";
	}
	
	function populate_info_row(&$values, $list) {
		$non_aeen_list = preg_replace('/_AEEN/','', $list);
		$key = strtoupper("NUMBER_TESTS_" . $non_aeen_list);
		$key_aeen = strtoupper("NUMBER_TESTS_" . $non_aeen_list);
		
		$value = "";
		if(array_key_exists($key, $values) && array_key_exists($key_aeen, $values)) {
			$value = sprintf("%0d (AEDIS %0d)", $values[$key_aeen], $values[$key]);
		} else if(array_key_exists($key, $values)) {
			$value = sprintf("%0d", $values[$key]);
		} else if(array_key_exists($key, $values)) {
			$value = sprintf("%0d (AEDIS ?)", $values[$key_aeen]);
		}
		
		if($list != "") {
			$values["INFO_ROWS"]["reg_".$non_aeen_list] = array(
				"name" => ucwords($non_aeen_list) . " Test Count",
				"value" => $value,
			);
		}
	}
	
	function populate($handle, &$values, &$sections) {		
		$regressions = get_regressions($handle, $this->where);
		@$last_reg = $regressions[0];
		
		@$pass_rate = $last_reg["pass_rate"];
		@$pass_count = $last_reg["pass_count"];
		@$fail_count = $last_reg["fail_count"];
		@$unsorted_count = $last_reg["unsorted_count"];
		@$number_buckets = $last_reg["number_of_buckets"];
		@$list = ucwords($last_reg["list"]);
		@$regression_name = $last_reg["name"];
		@$link = $last_reg["urls"]["psvsort_report"];
		
		$values["NUMBER_TESTS_".strtoupper($list)] = $pass_count + $fail_count + $unsorted_count;
		
		// Regression Pass-Rate Box
		if($this->reg_en) {
			$v = array();
			$v["VALUE"] = sprintf('%.2f<sup style="font-size: 20px">%% (%0d)</sup>', $pass_rate, $number_buckets);
			$v["DESCRIPTION"] = $list . " Pass Rate (# buckets)";
			$v["TOOLTIP"] = "";
			$v["BOX_COLOR_CLASS"] = self::$colors[self::$call_count];
			$v["ICON"] = "ion-stats-bars";
			$v["LINK_DISPLAY"] = "block"; // block or none
			$v["LINK"] = $link;
			$v["LINK_TEXT"] = $regression_name;
			
			$sections["TOP_BOXES"][$this->getName()."_".$list."_REG"] = array(
				'weight' => $this->weight,
				'template' => "Dashboard/Modules/InfoBox.html",
				'values' => $v,
			);
			
			self::$call_count++;
		}
		
		// Coverage Rate Box
		@$score = $last_reg["cov"]["score"];
		@$cov_func_score = $last_reg["cov"]["func_score"];
		@$cov_pcr_score = $last_reg["cov"]["pcr_score"];
		@$cov_wb_score = $last_reg["cov"]["wb_score"];
		@$cov_code_score = $last_reg["cov"]["code_score"];
		@$cov_link = $last_reg["urls"]["cov-passing_merged"];
		
		if($this->cov_en) {
			$tooltip_text = sprintf("85%% Functional/PCR - (%0.1f%% + %0.1f%%)/2<br>10%% AssertionScore - %0.1f%%<br>5%% CodeCovScore - %0.1f%%", $cov_func_score, $cov_pcr_score, $cov_wb_score, $cov_code_score);
			$tooltip = 'data-toggle="tooltip" data-placement="bottom" data-html="true" title="'.$tooltip_text.'"';
			
			$v = array();
			$v["VALUE"] = sprintf('%.2f<sup style="font-size: 20px">%%</sup>', $score);
			$v["DESCRIPTION"] = $list . " Coverage Score";
			$v["TOOLTIP"] = $tooltip;
			$v["BOX_COLOR_CLASS"] = self::$colors[self::$call_count];
			$v["ICON"] = "ion-grid";
			$v["LINK_DISPLAY"] = "block"; // block or none
			$v["LINK"] = $cov_link;
			$v["LINK_TEXT"] = "Full Coverage Report";
			
			self::$call_count++;
			
			$sections["TOP_BOXES"][$this->getName()."_".$list."_COV"] = array(
				'weight' => $this->weight-1,
				'template' => "Dashboard/Modules/InfoBox.html",
				'values' => $v,
			);
		}
		
		// QOV Box
		//@$val_planned = get($handle, "${config}-val_planned");
		//@$val_done = get($handle, "${config}-val_done");
		@$qov = $last_reg["qov"];
		$tooltip_text = sprintf("25%% Val Completion - %0d/%0d<br>25%% Regression Pass Rate - (%0.1f%% - 75%%)<br>50%% Overall Coverage - %0.1f%%", $val_done, $val_planned, $pass_rate, $score);
		$tooltip = 'data-toggle="tooltip" data-placement="bottom" data-html="true" title="'.$tooltip_text.'"';
		
		if($this->qov_en) {
			$v = array();
			$v["VALUE"] = sprintf('%.2f<sup style="font-size: 20px">%%</sup>', $qov);
			$v["DESCRIPTION"] = $list." QoV";
			$v["TOOLTIP"] = $tooltip;
			$v["BOX_COLOR_CLASS"] = self::$colors[self::$call_count];
			$v["ICON"] = "ion-code";
			$v["LINK_DISPLAY"] = "block"; // block or none
			$v["LINK"] = $values["QOV_LINK"];
			$v["LINK_TEXT"] = "More Info";
			
			self::$call_count++;
			
			$sections["TOP_BOXES"][$this->getName()."_".$list."_QOV"] = array(
				'weight' => $this->weight-2,
				'template' => "Dashboard/Modules/InfoBox.html",
				'values' => $v,
			);
		}
		
		$this->populate_info_row($values, $list);
	}
}