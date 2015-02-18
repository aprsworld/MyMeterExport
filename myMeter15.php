
<?
header("Cache-Control: no-cache");
header("Content-Type: text/plain");
/*

sample args

?tableName%5B%5D=ps2tap_A3497&tableName%5B%5D=ps2tap_A3497&colName%5B%5D=output_power&colName%5B%5D=energy_produced&readingType%5B%5D=2&readingType%5B%5D=1&quality%5B%5D=1&quality%5B%5D=1&meterNumber%5B%5D=wind0&meterNumber%5B%5D=solar0

?tableName%5B%5D=ps2tap_A3497&tableName%5B%5D=ps2tap_A3497&colName%5B%5D=output_power&colName%5B%5D=bus_voltage&readingType%5B%5D=1&readingType%5B%5D=1&quality%5B%5D=1&quality%5B%5D=1&meterNumber%5B%5D=wind0&meterNumber%5B%5D=solar0

?tableName%5B%5D=ps2tap_A3497&tableName%5B%5D=ps2tap_A3497&tableName%5B%5D=wnc_basic_A3508_52256&tableName%5B%5D=wnc_basic_A3508_52256
&colName%5B%5D=energy_produced&colName%5B%5D=output_power&colName%5B%5D=energySumNR&colName%5B%5D=powerSum
&readingType%5B%5D=1&readingType%5B%5D=2&readingType%5B%5D=1&readingType%5B%5D=2
&quality%5B%5D=1&quality%5B%5D=1&quality%5B%5D=1&quality%5B%5D=1
&meterNumber%5B%5D=wind0&meterNumber%5B%5D=wind0&meterNumber%5B%5D=solar0&meterNumber%5B%5D=solar0
&startDate=2015-02-01
&tzOffset=6

http://ian.aprsworld.com/myMeter/myMeter15.php?tableName%5B%5D=ps2tap_A3497&tableName%5B%5D=ps2tap_A3497&tableName%5B%5D=wnc_basic_A3508_52256&tableName%5B%5D=wnc_basic_A3508_52256&colName%5B%5D=energy_produced&colName%5B%5D=output_power&colName%5B%5D=energySumNR&colName%5B%5D=powerSum&readingType%5B%5D=1&readingType%5B%5D=2&readingType%5B%5D=1&readingType%5B%5D=2&quality%5B%5D=1&quality%5B%5D=1&quality%5B%5D=1&quality%5B%5D=1&meterNumber%5B%5D=wind0&meterNumber%5B%5D=wind0&meterNumber%5B%5D=solar0&meterNumber%5B%5D=solar0


*/

/*
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
//*/

/*
TODO decimal formats



*/

require_once $_SERVER['DOCUMENT_ROOT'] . '/world_config.php';

$db = _open_mysql('worldData');


/* json is for simplified debugging */
$json=false;
if (isset($_REQUEST["json"])) $json=true;


$tableName = $_REQUEST["tableName"];


$colName  = $_REQUEST["colName"];

$meterNumber = $_REQUEST["meterNumber"];
$readingType = $_REQUEST["readingType"];
$quality     = $_REQUEST["quality"];

if ( !isset($_REQUEST["startDate"]) ) die("Error: No date specified.");

$startDate = $_REQUEST["startDate"]." 00:00:00";

$date = DateTime::createFromFormat("Y-m-d",$startDate);

$stopDate = date('Y-m-d', strtotime($startDate. ' + 1 days'))." 00:00:00";

$tzOff = 0;
 
if (isset($_REQUEST["tzOff"]) && 0 != $_REQUEST["tzOff"] ) {
	$tzOff = $_REQUEST["tzOff"];
	
	if($tzOff > 0){
		$startDate = date('Y-m-d H:i:s', strtotime($startDate. ' - '.$tzOff.' hours'));
	} else {
		$startDate = date('Y-m-d H:i:s', strtotime($startDate. ' + '.abs($tzOff).' hours'));
	}


	$stopDate = date('Y-m-d H:i:s', strtotime($startDate. ' + 1 days'));

}



for ($i = 0 ; $i < count($tableName) ; $i++ ) {

	
	$sql=sprintf("SELECT DATE_ADD(packet_date, INTERVAL %s HOUR) as packet_date, sec_to_time(time_to_sec(packet_date)- time_to_sec(packet_date)%%(15*60)) as minuteinterval, %s as value FROM %s WHERE packet_date>=\"%s\" AND packet_date < \"%s\" GROUP BY minuteinterval ORDER BY packet_date ASC",mysql_real_escape_string($tzOff),mysql_real_escape_string($colName[$i]),mysql_real_escape_string($tableName[$i]),mysql_real_escape_string($startDate),mysql_real_escape_string($stopDate));
	
	//echo $sql;

	$query=mysql_query($sql,$db);
	$last=false;
	while($x=mysql_fetch_array($query,MYSQL_ASSOC)){
		$x["readingType"]=$readingType[$i];
		$x["quality"]=$quality[$i];
		$x["meterNumber"]=$meterNumber[$i];
		if ( $readingType[$i] == "1" ) {
			$x["meterRead"]=$x["value"];
			
			if($last) {			
				$x["value"]=round($x["value"]-$last,3);
			} else {
				$x["value"]="0";
			}
			$last=$x["meterRead"];
			$x["meterRead"]=round($x["meterRead"],2);
		}
		$x["value"]=round($x["value"],3);
		$r[$x["minuteinterval"]][$colName[$i]]=$x;

	}
		
}


if(!$json){
foreach ($r as $minuteInterval) {
	foreach ( $minuteInterval as $key => $column ) {
		//$date=str_replace("-","",$column["packet_date"])."<br>";
		if ( $column["readingType"] == "1" ) {		
			printf("%s|%s|%d|%s|%s|%s<br>",$column["meterNumber"],cleanDate($column["packet_date"]),$column["readingType"],$column["meterRead"],$column["value"],$column["quality"]);
		} else {
			printf("%s|%s|%d||%s|%s<br>",$column["meterNumber"],cleanDate($column["packet_date"]),$column["readingType"],$column["value"],$column["quality"]);
		}
	}
}
}

function cleanDate($date){
	$date=str_replace("-","",$date);
	$date=str_replace(" ","",$date);
	$date=str_replace(":","",$date);
	return substr($date,0,-2);
}

//die();
if($json)
echo json_encode($r);

?>
