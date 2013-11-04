<?php
require 'settings.php';

function fatalError( $message ) {
	$err = array('error' => $message);
	die ( json_encode( $err ) );
}

function checkRequired( $parameterNames ) {
	foreach ( $parameterNames as $name ) {
		if ( !isset ( $_GET[ $name ] ) ) {
			fatalError ( "Missing required parameter " . $name);
		} 
	}
}

function getDateParameter( $paramName ) {
	$dateString = $_GET[$paramName];
	$dateValue = DateTime::createFromFormat("Y-m-d", $dateString);
	if ( $dateValue === false ) {
		fatalError ( $dateString . " is not a valid date format. Please use ISO8601, e.g. 2013-11-02" );
	}
	return $dateString;
}

checkRequired( array( "from", "to", "category", "count" ) );

$fromDate = getDateParameter ( "from" );
$toDate = getDateParameter ( "to" );
$category = $_GET[ "category" ];
$count = $_GET[ "count" ]; //validate numeric!

$sql =  "select p.page_title, count(*) as edits from revision AS r " .
		"join page p on r.rev_page = p.page_id " .
		"join categorylinks cl on cl.cl_from = p.page_id " .
		"where cl.cl_to = :category " .
		"and r.rev_timestamp > :fromDate " .
		"and r.rev_timestamp < :toDate " .
		"group by p.page_title " .
		"order by count(*) desc " .
		"limit :count";

$db = new PDO( "mysql:host=" . Settings::db_host . ";dbname=" . Settings::db_name ,
		Settings::db_user, Settings::db_pass );
$command = $db->prepare( $sql );
$command->bindValue( ":category", $category, PDO::PARAM_STR);
$command->bindValue( ":fromDate", $fromDate, PDO::PARAM_STR);
$command->bindValue( ":toDate", $toDate, PDO::PARAM_STR);
$command->bindValue( ":count", (int) $count, PDO::PARAM_INT);

$start = microtime(true);

$command->execute ();
$result = $command->fetchAll();

$time = microtime(true) - $start;

$response = array( "queryTime" => $time);
$response[ "pages" ] = array_map( function($row) { return array("title" => $row[ "page_title" ], "edits" => $row[ "edits" ]); }, $result );

print ( json_encode ( $response ) );

