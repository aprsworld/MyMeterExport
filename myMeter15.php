<?

header("Cache-Control: no-cache");

/* sets the content type */
if (isset($_REQUEST["contType"])) {
	header(sprintf("Content-Type: %s",$_REQUEST["contType"]));
}else {
	header("Content-Type: text/plain");
}




/*
ini_set('display_errors',1);
ini_set('display_startup_errors',1);
error_reporting(-1);
//*/

require_once $_SERVER['DOCUMENT_ROOT'] . '/world_config.php';

$db = _open_mysql('worldData');


/* json is for simplified debugging */
$json=false;
if (isset($_REQUEST["json"])) $json=true;



function auth($station_id, $db){
	/* if not public, then we need to be authorized */
	if ( 0==authPublic($station_id,$db) ) {
		require $_SERVER["DOCUMENT_ROOT"] . "/auth.php";
	}
}



/* The names of the tables to be queried */
$tableName = $_REQUEST["tableName"];

/* The station_ids of the tables */
$station_ids=$_REQUEST["station_id"];

foreach ($station_ids as $key=>$station_id) {
	
	auth($station_id, $db);

}


/* The columns to be queried from the tables */
$colName  = $_REQUEST["colName"];

/*  */
$meterNumber = $_REQUEST["meterNumber"];
$readingType = $_REQUEST["readingType"];
$quality     = $_REQUEST["quality"];

/* scale factor used in case we need to do a simple conversion, like watts to kW */
$scaleFactor = $_REQUEST["scaleFactor"];

if ( !isset($_REQUEST["startDate"]) ) die("Error: No date specified.");

/* get the size of the arrays. They should all be the same size */
$size = count($tableName);


/* checks if the tables are all the same size, if not we cannot continue */
if ( count($colName) != $size ||count($meterNumber) != $size ||count($readingType) != $size ||count($quality) != $size ||count($scaleFactor) != $size ) die("Arrays are not all the same size");

/* double checks to make sure the date is valid */
if ( !preg_match("/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/",$_REQUEST["startDate"]) ) {
	die("Error: Invalid date.");
}


$startDate = $_REQUEST["startDate"]." 00:00:00";

$date = DateTime::createFromFormat("Y-m-d",$startDate);

/* add a day to the start date to get the stop date */
$stopDate = date('Y-m-d', strtotime($startDate. ' + 1 days'))." 00:00:00";

/* timezone offset */
$tzOff = 0;
 
/* apply offset to the start date if the tzoffset is set and is not 0 */
if (isset($_REQUEST["tzOff"]) && 0 != $_REQUEST["tzOff"] ) {
	$tzOff = $_REQUEST["tzOff"];
	
	if($tzOff > 0){
		$startDate = date('Y-m-d H:i:s', strtotime($startDate. ' - '.$tzOff.' hours'));
	} else {
		$startDate = date('Y-m-d H:i:s', strtotime($startDate. ' + '.abs($tzOff).' hours'));
	}


	$stopDate = date('Y-m-d H:i:s', strtotime($startDate. ' + 1 days'));

}


/* iterate through the list of tables, getting fifteen minute summaries of the columns specified in colName */
for ($i = 0 ; $i < count($tableName) ; $i++ ) {

	
	$sql=sprintf("SELECT DATE_ADD(packet_date, INTERVAL %s HOUR) as packet_date, sec_to_time(time_to_sec(packet_date)- time_to_sec(packet_date)%%(15*60)) as minuteinterval, %s as value FROM %s WHERE packet_date>=\"%s\" AND packet_date < \"%s\" GROUP BY minuteinterval ORDER BY packet_date ASC",mysql_real_escape_string($tzOff),mysql_real_escape_string($colName[$i]),mysql_real_escape_string($tableName[$i]),mysql_real_escape_string($startDate),mysql_real_escape_string($stopDate));
	
	//echo $sql;

	$query=mysql_query($sql,$db);

	if ( mysql_num_rows($query) == 0 ) die();

	$last=false;

	while($x=mysql_fetch_array($query,MYSQL_ASSOC)){
		$x["readingType"]=$readingType[$i];
		$x["quality"]=$quality[$i];
		$x["meterNumber"]=$meterNumber[$i];
		$x["scaleFactor"]=$scaleFactor[$i];
		/* if reading type is 1 then we will need a meterRead added to it and it's value will be based off of the previous entry - current entry */
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
		$x["value"]=round($x["value"]*$x["scaleFactor"],3);
		$r[intval(cleanInterval($x["minuteinterval"]))][$colName[$i]]=$x;

	}
		
}

/* debug
for ($i = 0 ; $i < 2400; $i++) {
	if ( array_key_exists($i, $r) ) {
		print_r($r[$i]);
	}
}
*/

/* print out the rows */
if(!$json){
	/* print out the rows */
	for ($i = 0 ; $i < 2400; $i++) {
		if ( array_key_exists($i, $r) ) {
			foreach ( $r[$i] as $key => $column ) {
			
				//$date=str_replace("-","",$column["packet_date"])."<br>";
				if ( $column["readingType"] == "1" ) {		
					printf("%s|%s|%d|%s|%s|%s\n",$column["meterNumber"],cleanDate($column["packet_date"],$column["minuteinterval"]),$column["readingType"],$column["meterRead"],$column["value"],$column["quality"]);
				} else {
					/* for reading types that don't equal 1, we just print out the value and have the meterRead blank */				
					printf("%s|%s|%d||%s|%s\n",$column["meterNumber"],cleanDate($column["packet_date"],$column["minuteinterval"]),$column["readingType"],$column["value"],$column["quality"]);
				}
			}
		}
	}
}

/* removes spaces, dashes and colons from the date */
function cleanDate($date, $minInt){
	$date=str_replace("-","",$date);
	$date=str_replace(" ","",$date);
	$date=str_replace(":","",$date);
	$minInt=str_replace("-","",$minInt);
	$minInt=str_replace(" ","",$minInt);
	$minInt=str_replace(":","",$minInt);
	return substr($date,0,-6).substr($minInt,0,-2);
}

/* removes spaces, dashes and colons from the date */
function cleanInterval($date) {
	$date=str_replace("-","",$date);
	$date=str_replace(" ","",$date);
	$date=str_replace(":","",$date);
	return substr($date,0,-2);
}

//die();
if($json)
echo json_encode($r);

?>
