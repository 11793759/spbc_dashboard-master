<?php

if(array_key_exists("QUERY_DEBUG", $_GET)) {
	define("QUERY_DEBUG", TRUE);
} else {
	define("QUERY_DEBUG", FALSE);
}
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/* print "The SPBC Dashboard is down due to some technical difficulties. Will be back up ww4.3. Thanks, Dipesh"; */
/* exit(); */

require("Dashboard/cfg.php");
require("Dashboard/qoe.php");

$local_run = FALSE;
if($_SERVER["HTTP_HOST"] == "SPBC_DASHBOARD_so") {
    $local_run = TRUE;
} else {
	if($_SERVER["HTTP_HOST"] != "spbc-dashboard.app.intel.com") {
		header("Location: https://spbc-dashboard.app.intel.com/");
		exit();
	}
}

try {
    if($cfg["db_type"] == "mysqli") {
        $mysqlUser = 'SPBC_DASHBOARD_so';
        $mysqlPass = 'Spbc2021';
        $mysqlDb = 'SPBC_DASHBOARD';
		
		$hostName = 'maria4011-lb-fm-in.dbaas.intel.com';
        $hostPort = '3307';
		
		if(preg_match("/\/dev/",$_SERVER['REQUEST_URI'])) {
			$hostName = 'maria4011-lb-fm-in.dbaas.intel.com';
			$mysqlDb = 'SPBC_DASHBOARD_so';
		}
		
		$conn = mysqli_init();
		
		if ($local_run)
		{
			$mysqlUser = 'root';
			$mysqlPass = '';
			$mysqlDb = 'karolek';
			
			$hostName = 'localhost';
			$hostPort = '3306';
			mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
		} else {
			mysqli_ssl_set($conn, NULL, NULL, '../intel_certs.pem', NULL, NULL);
		}
        mysqli_real_connect($conn, $hostName, $mysqlUser, $mysqlPass, $mysqlDb, $hostPort, NULL, MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
		
		if(!$conn->ping()) {
			http_response_code(503); // Service Unavailable
			print "<h3>SPBC Dashboard</h3><p><span style=\"color: red\">Backend database down - try again later</span>";
			exit();
		}

        $handle = $conn;
    } else {
        $handle = new SQLite3("Dashboard/dashboard_db.sqlite", SQLITE3_OPEN_READONLY);
    }
} catch( Exception $e) {
    print_r($e);
}

function querySingle($handle, $query, $default="") {
    global $cfg;
    $startTime = microtime(TRUE);

    if($cfg["db_type"] == "mysqli") {
        $res = $handle->query($query);
        if($res !== FALSE && $res->num_rows > 0) {
            $data = $res->fetch_all(MYSQLI_NUM);
            $val = $data[0][0];
            $res->free();
        } else {
            $val = FALSE;
        }
    } else {
        $val = $handle->querySingle($query);
    }

    if(QUERY_DEBUG) { printf("$query took %0.3f sec<br>", ((microtime(TRUE)-$startTime))); }

    if($val !== FALSE && $val != "") {
        return $val;
    } else {
        return $default;
    }
}

function get_comment($handle, $subject, $default="") {
    return querySingle($handle, "SELECT comment FROM data_latest WHERE subject='$subject' ORDER BY date DESC LIMIT 1", $default);
}

function get($handle, $subject, $default="") {
    return querySingle($handle, "SELECT value FROM data_latest WHERE subject='$subject' ORDER BY date DESC LIMIT 1", $default);
}

function get_with_tooltip($handle, $subject, $default="") {
    return "<span data-toggle=\"tooltip\" title=\"$subject\">".get($handle,$subject,$default)."</span>";
}

function get_each($handle, $where) {
    $values = array();
    $keys = get_array($handle, 'SELECT DISTINCT(subject) as subject FROM data_latest WHERE '.$where);

    foreach($keys as $row_num=>$data) {
        $subject = $data["subject"];
        $values[$subject] = get($handle, $subject);
    }

    return $values;
}

function get_array($handle, $query) {
    global $cfg;
    $rows = array();
    $startTime = microtime(TRUE);

    if($cfg['db_type'] == "mysqli") {
        $res = $handle->query($query);

        if(QUERY_DEBUG) { printf("$query took %0.3f sec<br>", ((microtime(TRUE)-$startTime))); }
        $startTime = microtime(TRUE);

        if($res !== FALSE) {
            $rows = $res->fetch_all(MYSQLI_ASSOC);
            $res->free();

			if(QUERY_DEBUG) { printf("fetch_all took %0.3f sec<br>", ((microtime(TRUE)-$startTime))); }
        } else {
            print "<b>MySQLi Error: </b>".$handle->error."<br />\n";
        }
    } else {
        $statement = $handle->prepare($query);
        $result = $statement->execute();

        while($row = $result->fetchArray(SQLITE3_ASSOC)) {
            array_push($rows, $row);
        }

        $result->finalize();
    }

    return $rows;
}

function subsection($name) {
	if(array_key_exists("subsection", $_GET) && $_GET["subsection"] == $name) {
		return true;
	}
	return false;
}

function populate_template($template_name, $values) {
    $template = file_get_contents($template_name);

    foreach($values as $key => $value) {
        if(!is_array($value)) {
            $template = str_replace("\${".$key."}",$value,$template);
        }
    }

    return $template;
}

function get_regressions($handle, $where) {
	$rows = get_array($handle, 'SELECT JSON_MERGE_PATCH( JSON_OBJECT("name", `name`, "completed", completed, "private", private, "start_date", start_date, "update_date", update_date, "cust", cust, "model", model, "list", list), data) as jsondata FROM regressions '.$where.' ORDER BY start_date DESC');
	
	$arrayrows = array();
	
	foreach($rows as $row) {
		$arrayrows[] = json_decode($row['jsondata'], true);
	}
	
	if(QUERY_DEBUG) { print_r($arrayrows); }
	
	return $arrayrows;
}

// Intel's 2021 calendar doesn't follow any PHP strftime specifier - it started a week early, so we have to add one to the week number
function intel_strftime($format, $date) {
    $year = strftime("%g", $date);
    $ww = strftime("%V", $date);

    if($year == "20" && $ww == "53") {
        $ww = 1;
        $format = str_replace("%V", $ww, $format);
    } else if($year == "21") {
        $ww += 1;
        $format = str_replace("%V", $ww, $format);
    }

    return strftime($format, $date);
}
$lastTime = microtime(TRUE);

$valid_configs = explode(" ", get($handle, "ConfigurationNames"));

sort($valid_configs);
if(isset($_GET["config"])) {
    $config = $_GET["config"];
} else if(isset($_COOKIE["last_config"])) {
    $config = $_COOKIE["last_config"];
} else {
    $config = $cfg["default_config"];
}
setcookie("last_config",$config);

if(!in_array($config, $valid_configs)) {
    $config = $cfg["default_config"];
}

$menu_items = "";
$config_to_fullname = array();
foreach ($valid_configs as $config_name) {
    $full_config_name = get($handle, "${config_name}-configuration_name");
    $config_to_fullname["$config_name"] = $full_config_name;
}

asort($config_to_fullname);
foreach ($config_to_fullname as $config_name=>$full_config_name) {
	$menu_items .= sprintf("            <li%0s><a href='?config=%s'><i class='fa fa-circle-o'></i> %s</a></li>\n", ($config_name == $config) ? " class='active'" : "", $config_name, $full_config_name);
}

$values["CONFIG_MENU_ITEMS"] = $menu_items;
$values["FULL_CONFIG_NAME"] = get($handle, "${config}-configuration_name");

// map cfg values to templates
foreach ($cfg as $key => $value) {
    $values[$key] = $value;
}

if(array_key_exists($config, $values)) {
    $values = array_merge($values, $values[$config]);
}

// Basic Template Stuff
$values["IP_NAME"] = get($handle, "IpName");
$values["PROJECT"] = "${config}";
$values["CONFIG"] = "${config}";

$values["DEV_DISPLAY"] = (get($handle, "lipid-dev") == 1) ? "inline" : "none";

// Regression
$list = "level1";
if(preg_match("/(_ANNA|_AE)/", $config)) {
	$list = "level1_AEEN";
}

$values["DEFAULT_LIST_NAME"] = $list;

$values["QC_DISPLAY"] = "block";
$values["AEDIS_DISPLAY"] = "none";

if(preg_match("/(_ANNA|_AE)/", $config)) {
	$values["QC_DISPLAY"] = "none";
	$values["AEDIS_DISPLAY"] = "block";
}

// ZQA
$values["ZQA_SCORE"] = sprintf("%0d", get($handle, "${config}-zqa_score"));
$values["ZQA_EXTRA"] = sprintf("(%0d/%0d/%0d)", get($handle, "${config}-zqa_passed"), get($handle, "${config}-zqa_failed"), get($handle, "${config}-zqa_waived"));
$values["ZQA_LINK"] = get_comment($handle, "${config}-zqa_score");
$values["ZQA_UPLOADED"] = @sprintf("Uploaded to %s Dashboard %0s", get($handle, "${config}-zqa_published") ? "SOC" : "IP", intel_strftime("%gww%V.%u",get($handle, "${config}-zqa_upload_time")));
$values["ZQA_RULESET"] = get($handle, "${config}-zqa_rule_version");



$modules = array();
$values["INFO_ROWS"] = array(); # indexed by key (which is sorted but not displayed) - each item has "name" and "value"
require("Dashboard/Modules/Module.php");
require("Dashboard/Modules/VersionInfo.php");
require("Dashboard/Modules/RegressionInfoBox.php");
require("Dashboard/Modules/ChangesTable.php");
require("Dashboard/Modules/RegressionGraph.php");
require("Dashboard/Modules/RegressionStatus.php");
require("Dashboard/Modules/RegressionDecisionTable.php");
require("Dashboard/Modules/InformationTable.php");
require("Dashboard/Modules/ReleaseInfo.php");
require("Dashboard/Modules/MilestoneInfo.php");
require("Dashboard/Modules/HSDESInfo.php");

// TOP_BOXES modules
$lists = array();
if(preg_match("/(_ANNA|_AE)/", $config)) {
	$modules[] = new RegressionInfoBox(50, "WHERE private = 0 AND completed = 1 AND cust='${config}' AND list='".$values["DEFAULT_LIST_NAME"]."'", true, true, true);
	$modules[] = new RegressionInfoBox(45, "WHERE private = 0 AND completed = 1 AND cust='${config}' AND list='level1'", true, false, false);
	
	# do not even use the results, but populate NUMBER_TESTS_(LIST)
	$modules[] = new RegressionInfoBox(-1, "WHERE private = 0 AND completed = 1 AND cust='${config}' AND list='level0'", false, false, false);
	$modules[] = new RegressionInfoBox(-2, "WHERE private = 0 AND completed = 1 AND cust='${config}' AND list='level0_AEEN'", false, false, false);
	
	$lists = array("level1", "level1_AEEN");
} else {
	$modules[] = new RegressionInfoBox(50, "WHERE private = 0 AND completed = 1 AND cust='${config}' AND list='level1'", true, true, true);
	
	$modules[] = new RegressionInfoBox(-1, "WHERE private = 0 AND completed = 1 AND cust='${config}' AND list='level0'", false, false, false);
	
	$lists = array("level1");
}

// MAIN modules
$modules[] = new RegressionGraph(50, $lists );
$modules[] = new RegressionStatus(45);
$modules[] = new RegressionDecisionTable(40);
$modules[] = new ChangesTable(35);

// SIDEBAR modules
$modules[] = new VersionInfo(25);
$modules[] = new ReleaseInfo(-1);
$modules[] = new MilestoneInfo(-1);
$modules[] = new HSDESInfo(-1);

// Initialize our blank section arrays
$sections = array(
	"TOP_BOXES" => array(),
	"MAIN" => array(),
	"SIDEBAR" => array(),
);

// Call each Module's populate() method
foreach($modules as $module) {
	$module->populate($handle, $values, $sections);
}

// Populate the InformationTable last as many other models add to its INFO_ROWS
$info_table = new InformationTable(50);
$info_table->populate($handle, $values, $sections);

// Rasterize templates for each section and concat the results
foreach($sections as $section=>$section_array) {
	// sort by weight highest to lowest
	$weights = array_column($section_array, 'weight');
	array_multisort($weights, SORT_DESC, $section_array);
	
	$values[$section] = "";
	foreach($section_array as $module_name=>$module_details) {
		$v = array_merge($values, $module_details["values"]);
		if(QUERY_DEBUG) {
			print "$section.$module_name: ";
			print_r($module_details);
			print_r($v);
		}
		$values[$section] .= populate_template($module_details["template"], $v);
	}
}

// Build the full page
$page = populate_template("Dashboard/dashboard_template.html", $values);

echo $page;


?>
