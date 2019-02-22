<?php
require_once(__DIR__ . '/../config.php');
require_once(__DIR__ . '/../vendor/autoload.php');
require_once(__DIR__ . '/../credintal.php');
require_once(__DIR__ . '/analytics_functions.php');

$start_date = date("Y-m-d");
$end_date = date("Y-m-d");

if (isset($_GET['start_date'])) $start_date = $_GET['start_date'];
if (isset($_GET['end_date'])) $end_date = $_GET['end_date'];

$refreshToken = "";
$viewID = "";

if (isset($_GET['refreshToken'])) $refreshToken = $_GET['refreshToken'];
if (isset($_GET['viewID'])) $viewID = $_GET['viewID'];
if ($refreshToken == "") exit();
if ($viewID == "") exit();

$redirect_uri = SITE_URL . "google/analytics_callback.php";

$client = new Google_Client();
$client->setApplicationName($googleAppName);
$client->setAccessType("offline");
$client->setClientId($googleClientID);
$client->setClientSecret($googleClientSecret);
$client->setRedirectUri($redirect_uri);

$client->refreshToken($refreshToken);
$token = $client->getAccessToken();

$arr = array("sessions", "users", "pageviews", "pageviewsPerSession", "avgSessionDuration", "percentNewSessions", "bounceRate", "goalCompletionsAll", "goalValueAll", "goalConversionRateAll");
$arr_alias = array("Sessions", "Users", "Pageviews", "Pages/Session", "Avg. Session Duration", "% New Sessions", "Bounce Rate", "Goal Completions", "Goal Value", "Conversion Rate");

$analytics = new Google_Service_AnalyticsReporting($client);

$ret = new stdClass();

$response = getReport($analytics, $viewID, array(""));
$result = printTotals($response);
if (count($result) > 0) $ret = $result[0];

$response = getReport($analytics, $viewID, array(""), false, "ga:channelGrouping");
$result = printResults($response);
for ($i = 0; $i < count($result); $i++)
    $result[$i]->Channel = $result[$i]->dimensions;
$ret->result = $result;

foreach ($arr_alias as $key => $alias) {
    $fldName = $alias . "_Chart";
    $$fldName = "Date," . $alias . "\n";
}
$response = getReport($analytics, $viewID, array(""), true);
$result = printResults($response);
for ($i = 0; $i < count($result); $i++) {
    foreach ($arr_alias as $key => $alias) {
        $date_val = $result[$i]->dimensions;
        $date_val = substr($date_val, 0, 4) . "-" . substr($date_val, 4, 2) . "-" . substr($date_val, 6, 2);
        $fldValue = 0;
        if (isset($result[$i]->$alias)) $fldValue = $result[$i]->$alias;
        $fldName = $alias . "_Chart";
        $$fldName .= $date_val . "," . $fldValue . "\n";
    }
}

foreach ($arr_alias as $key => $alias) {
    $fldName = $alias . "_Chart";
    $ret->$fldName = $$fldName;
}

echo json_encode($ret);

?>