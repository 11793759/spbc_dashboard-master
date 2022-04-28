<?php

date_default_timezone_set("America/Los_Angeles");

class QoeFeature
{
    public $friendly_name;
    public $hsd;
    public $design_items = array();
    public $verif_items = array();
    public $regression_goal_items = array();
    public $regression_count_items = array();
    public $coverage_goal_items = array();
    public $coverage_count_items = array();

    public function add_item($item) {
        switch($item->type) {
        case "verif":
            array_push($this->verif_items, $item);
            break;
        case "design":
            array_push($this->design_items, $item);
            break;
        case "regression_goal":
            array_push($this->regression_goal_items, $item);
            break;
        case "regression_count":
            array_push($this->regression_count_items, $item);
            break;
        case "coverage_goal":
            array_push($this->coverage_goal_items, $item);
            break;
        case "coverage_count":
            array_push($this->coverage_count_items, $item);
            break;
        }
    }

    public function find_actual_at($item_array, $date) {
        return $this->find_value_at($item_array, $date, 1, 0);
    }

    public function find_expected_at($item_array, $date) {
        return $this->find_value_at($item_array, $date, 0, 1);
    }

    public function find_value_at($item_array, $date, $is_actual, $interpolate) {
        $sum = 0;

        if($is_actual && $date > new DateTime()) {
            return "null";
        }

        foreach($item_array as $item) {
            $next_point = NULL;

            if($is_actual) {
                $points_array = $item->actual_points;
            } else {
                $points_array = $item->expected_points;
            }
            $pt = $item->find_point_before($points_array, $date, $next_point);

            if($pt == NULL) {
                $val = 0;
            } else if($interpolate && $next_point != NULL) {
                $val = $this->interpolate($pt, $next_point, $date);
            } else {
                /* echo "\nItem: ".$item->friendly_name.": "; */
                $val = $pt->value;
                /* echo "$val\n"; */
            }

            $sum += $val;
        }

        return sprintf("%0.1f",$sum);
    }

    public function interpolate($pt, $next, $date) {
        $date_diff = $pt->date->diff($next->date);
        $days_diff_all = $date_diff->days;

        $date_diff = $pt->date->diff($date);
        $days_diff_target = $date_diff->days;

        $value_diff = $next->value - $pt->value;

        $value = ($value_diff * ($days_diff_target/$days_diff_all)) + $pt->value;

        /* var_dump($date); */
        /* var_dump($pt); */
        /* var_dump($next); */
        /* var_dump($value); */
        /* print "\n\n"; */

        return $value;
    }
}

class QoeItem
{
    public $friendly_name;
    public $hsd;
    public $feature;
    public $ingredient;
    public $owner;
    public $expected_points = array();
    public $actual_points = array();
    public $type = "";

    public static function from_json($json) {
        $tags = explode(",",$json[0]["tag"]);

        $item = new QoeItem;
        $item->friendly_name = $json[0]["title"];
        $item->friendly_name = preg_replace("/\[.*\]/","",$item->friendly_name);
        $item->hsd = $json[0]["id"];
        foreach($tags as $tag) {
            if($tag == "REGRESSION_GOAL") {
                $item->type = "regression_goal";
            } else if($tag == "REGRESSION_COUNT") {
                $item->type = "regression_count";
            } else if($tag == "COVERAGE_GOAL") {
                $item->type = "coverage_goal";
            } else if($tag == "COVERAGE_COUNT") {
                $item->type = "coverage_count";
            } else if(strpos($tag, "GNA_") !== FALSE) {
                $item->feature = str_replace("GNA_","",$json[0]["tag"]);
            } 
        }
        $item->ingredient = str_replace("ip.gna.","",$json[0]["ingredient"]);
        $item->owner = $json[0]["owner"];

        sscanf($json[0]["trend_start"],"%0dww%0d",$start_year, $start_ww);
        sscanf($json[0]["trend_end"],"%0dww%0d",$end_year, $end_ww);
        $start = new DateTime();
        $end = new DateTime();
        array_push($item->expected_points, new QoePoint($start->setISODate($start_year, $start_ww, 1), 0));
        array_push($item->expected_points, new QoePoint($end->setISODate($end_year, $end_ww, 6), $json[0]["effort"]));

        foreach(array_reverse($json) as $rev) {
            array_push($item->actual_points, QoePoint::from_json($rev));

            // eliminate same-day entries for only the last one
            $size = sizeof($item->actual_points);
            if($size >= 2) {
                if($item->actual_points[$size-1]->date == $item->actual_points[$size-2]->date) {
                    unset($item->actual_points[$size-2]); // remove the duplicate-day data
                    $item->actual_points = array_values($item->actual_points); // re-index the array
                }
            }
        }

        foreach(array_merge($item->expected_points, $item->actual_points) as $point) {
            $point->date = $point->date->sub(new DateInterval("P14D"));
        }

        if($item->type == "") {
            if(preg_match("/verif/", $item->ingredient) > 0) {
                $item->type = "verif";
            } else {
                $item->type = "design";
            }
        }

        /* echo "PointBefore: \n"; */
        /* var_dump(QoeItem::find_point_before($item->actual_points, new DateTime("2020-06-22"))); */
        /* echo "After \n"; */

        return $item;
    }

    public static function find_point_before($pt_array, $date, &$next_point) {
        $bottom = 0;
        $top = sizeof($pt_array)-1;
        $result = null;


        while($bottom <= $top) {
            $half = floor(($top+$bottom)/2.0);

            /* var_dump($half); */
            /* var_dump($date); */
            /* var_dump($pt_array[$half]); */

            if($pt_array[$half]->date < $date) {
                $result = $pt_array[$half];
                /* print "got result $half; next ".($half+1)." ".sizeof($pt_array)."\n"; */
                if($half+1 < sizeof($pt_array)) { $next_point = $pt_array[$half+1];  } else { $next_point = null; }
                $bottom = $half+1; 
            }
            else if($pt_array[$half]->date > $date) { $top = $half-1; }
            else {
                /* print "got equal result $half; next ".($half+1)." ".sizeof($pt_array)."\n"; */
                if($half+1 < sizeof($pt_array)) { $next_point = $pt_array[$half+1];  } else { $next_point = null; }
                return $pt_array[$half];
            }

            /* echo "\n\n"; */
        }
        
        /* print "next: "; */
        /* print_r($next_point); */
        /* print "\n"; */
        return $result;
    }
}

class QoePoint
{
    public $date;
    public $value;
    public $notes;

    function __construct($date=null, $value=0, $notes="") {
        if($date != null) $date->setTime(0,0,0);
        $this->date = $date;
        $this->value = $value;
        $this->notes = $notes;
    }

    public static function from_json($json) {
        $point = new QoePoint;

        $point->date = new DateTime;
        $point->date->setTimestamp(strtotime($json["updated_date"]))->setTime(0,0,0);
        $point->value = intval($json["value"]);
        $point->notes = $json["notes"];
        /* print_r($point); */

        return $point;
    }
}

$qoe_features = array();
function parse_qoe_hsdes($filename) {
    global $qoe_features;

    /* $json_str = file_get_contents($filename); */
    $json_str = '[
        [{"owner":"briankel","effort":"8","trend_start":"2020ww22","ingredient":"ip.gna.anna.verif","value":"7","trend_end":"2020ww24","notes":"","rev":"6","updated_date":"2020-06-02 22:37:49.1","id":"22010812019","title":"[GNA] Dummy AR [MTL_BW_LATENCY] NMEM 8K Verif Updates","tag":"GNA_MTL_BW_LATENCY"},{"effort":"8","notes":"","value":"7","rev":"5","updated_date":"2020-06-02 22:35:31.283"},{"effort":"","notes":"","value":"7","rev":"4","updated_date":"2020-06-10 23:07:03.483"},{"effort":"","notes":"","value":"5","rev":"3","updated_date":"2020-06-07 17:10:07.107"},{"effort":"","notes":"","value":"4","rev":"2","updated_date":"2020-06-05 17:09:34.89"},{"effort":"","notes":"","value":"0","rev":"1","updated_date":"2020-06-03 16:25:38.95"}],
        [{"owner":"briankel","effort":"24","trend_start":"2020ww20","ingredient":"ip.gna.anna.design","value":"16","trend_end":"2020ww23","notes":"","rev":"6","updated_date":"2020-06-02 22:37:49.1","id":"22010812019","title":"[GNA] Dummy AR [MTL_BW_LATENCY] NMEM 8K Design Updates","tag":"GNA_MTL_BW_LATENCY"},{"effort":"8","notes":"","value":"14","rev":"5","updated_date":"2020-06-02 22:35:31.283"},{"effort":"","notes":"","value":"13","rev":"4","updated_date":"2020-06-01 23:07:03.483"},{"effort":"","notes":"","value":"12","rev":"3","updated_date":"2020-05-29 17:10:07.107"},{"effort":"","notes":"","value":"8","rev":"2","updated_date":"2020-05-09 17:09:34.89"},{"effort":"","notes":"","value":"5","rev":"1","updated_date":"2020-05-05 16:25:38.95"}]
    ]';
    $json = json_decode($json_str,TRUE);

    foreach($json as $json_item) {
        $item = QoeItem::from_json($json_item);

        if(!array_key_exists($item->feature, $qoe_features)) {
            $qoe_features[$item->feature] = new QoeFeature;
        }

        $qoe_features[$item->feature]->add_item($item);
    }
}

?>
