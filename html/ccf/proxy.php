<?php

// $endpoint = "https://vocabularies.clarin.eu/clavas/public/api/autocomplete/";

$endpoint = "https://skosmos.sd.di.huc.knaw.nl/" ;

// DEPENDANT UPON $endpoint

$type = 'skos'; // from url?
if($type === 'skos') { // switch
    $specifics = 'rest/v1/iso639-3/search?unique=1amp;query=';
} else {

    // stub
}


// echo $url;
$endpoint = $endpoint . $specifics;

$restQuery = $endpoint . $_GET["q"] . "*";
// echo $restQuery;

// die;
// $ch = curl_init($url . $_GET["q"]);

$ch = curl_init($restQuery);
// die('lul');

// https://skosmos.sd.di.huc.knaw.nl/rest/v1/iso639-3/search?query=dut*&unique=1
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/text'));

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$json = curl_exec($ch);
curl_close($ch);
// echo $json;
// die;
$json = json_decode($json);
if (!$json) {
    $json = "";
}
$retArray = array("query" => "Unit", "suggestions" => $json);

header('Content-Type: application/json; charset=utf-8');
// waarom is bovenstaande json header er niet?

echo json_encode($retArray);
