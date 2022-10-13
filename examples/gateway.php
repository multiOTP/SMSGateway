<?php

// Simple gateway, using the default shared secret
// This simple demo gateway is available here : https://1-2-3-4-5-6.net/smsgateway/

//Import SMSGateway classes into the global namespace
//These must be at the top of your script, not inside a function
use multiOTP\SMSGateway\SMSGateway;

// Some tricks to load the SMSGateway class in different situations
if (!class_exists('multiOTP\SMSGateway\SMSGateway')) {
  if (file_exists('../src/SMSGateway.php')) {
    // Quick load of SMSGateway without using composer
    require_once '../src/SMSGateway.php';
  } else {
    // Composer autoload
    require '../vendor/autoload.php';
  }
}

// Create an SMSGateway instance if not done yet, and define the flat-file data folder
if (!isset($smsgateway)) {
  $smsgateway = new SMSGateway();
  $smsgateway->setDataPath(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR);
}

// Detect the URL
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" .$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);

// Retrieve some parameters
$command = isset($_GET["m"]) ? "m" : "";
$h = isset($_GET["h"]) ? $_GET["h"] : "";

// Correct the international format of the phone number if needed
$to = isset($_GET["to"]) ? $_GET["to"] : "";
if ("00" == substr($to, 0, 2)) {
  $to = "+" . substr($to, 2, strlen($to) -2);
}

// Define a default message if needed
$message = isset($_GET["message"]) ? $_GET["message"] : "Hello World 😉";

// Retrieve the device id
$id = isset($_GET["id"]) ? $_GET["id"] : "";
$device_id = $id;
if ((!empty($to)) && empty($device_id)) {
  $device_id = substr(md5(uniqid("", true)), 0, 16);
} elseif ((empty($to)) && (!empty($device_id)) && (!file_exists($smsgateway->getDataPath() .$device_id))) {
  $device_id = "";
}

// Calculate the device hash based on the secret
$device_h = $smsgateway->calculateAuthenticationHash($device_id);

// Check if device hash is valid for an existing device, otherwise flush the device id
if ((!empty($id)) && ($h != $device_h)) {
  $device_id = "";
}

if (("m" == $command) && (!empty($device_id))) {

  // Display messages resume for the "m" command
  echo "<h1>SMSGateway ".$smsgateway->getVersion()."</h1>";
  echo "<h2>New SMS messages received</h2>";
  $new_messages_count = 0;
  foreach ($smsgateway->readNewMessages($id) as $message) {
    echo date("Y-m-d H:i:s", $message['sms_received'] / 1000)." ".$message['from'].": ".$message['content']. "<br />";
    $new_messages_count++;
  }
  if (0 == $new_messages_count) {
    echo "none<br />";
  }
  echo "<h2>All SMS messages received</h2>";
  $messages_count = 0;
  foreach ($smsgateway->readAllMessages($id) as $message) {
    echo date("Y-m-d H:i:s", $message['sms_received'] / 1000)." ".$message['from'].": ".$message['content']. "<br />";
    $messages_count++;
  }
  if (0 == $messages_count) {
    echo "none<br />";
  }
  echo "<h2>All SMS messages sent</h2>";
  $sent_messages_count = 0;
  foreach ($smsgateway->readAllSentStatus($id) as $message) {
    if ("DELIVERED" == $message['status']) {
      $pre_status = "<b>";
      $post_status = "</b>";
    }
    echo date("Y-m-d H:i:s", $message['last_update'] / 1000)." ".$message['to']." [" . $pre_status . $message['status']. $post_status . "]: ".$message['content']. "<br />";
    $sent_messages_count++;
  }
  if (0 == $sent_messages_count) {
    echo "none<br />";
  }

} elseif (empty($device_id)) {

  // Display basic usage info
  echo "<h1>SMSGateway ".$smsgateway->getVersion()."</h1>";
  echo "Please send a first message like this: <b>$url/?to=001234567890&message=Hello+world</b>";

} elseif (!empty($to)) {

  // Push the message on the server
  $message_id = $smsgateway->sendMessage($device_id, $to, $message);
  
  // Display usage information
  echo "<h1>SMSGateway ".$smsgateway->getVersion()."</h1>";
  echo "Message '<i>$message</i>' for '$to' with id '<b>$message_id</b>' pushed on the server.";
  echo "<br /><br />";
  echo "If not done yet, please install the Android SMSGatewayApp available here : <a href=\"https://github.com/medic/cht-gateway/releases/latest\" target=\"blank\">medic-gateway-X.X.X-generic-release.apk</a>";
  echo "<br /><br />";
  echo "URL to set in the Settings of the Android App: <b>$url/?id=$device_id&h=$device_h</b>";
  echo "<br />";
  echo "<i>(don't forget to enable polling)</i>";
  echo "<br /><br />";
  echo "URL to check SMS messages: <b>$url/?id=$device_id&h=$device_h&m</b>";
  echo "<br /><br />";
  echo "URL to send more SMS messages: <b>$url/?id=$device_id&h=$device_h&to=001234567890&message=Hello+world</b>";
} else {

  // Run the API server
  $smsgateway->apiServer("secret");
}