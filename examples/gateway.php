<?php
/**
 * @file  gateway.php
 * @brief Simple gateway, using the default shared secret
 *         This simple demo gateway is available here : https://1-2-3-4-5-6.net/smsgateway/
 *
 * PHP 5.3.0 or higher is supported.
 *
 * @author    Andre Liechti (SysCo systemes de communication sa) <info@multiotp.net>
 * @version   1.1.1
 * @date      2023-03-30
 * @since     2022-09-10
 * @copyright (c) 2022-2023 SysCo systemes de communication sa
 * @copyright GNU Lesser General Public License
 *
 *********************************************************************/

// Import SMSGateway classes into the global namespace
// These must be at the top of your script, not inside a function
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
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" .$_SERVER['HTTP_HOST'].($_SERVER['PHP_SELF']);

// Retrieve some parameters
$command = isset($_GET["m"]) ? "m" : (isset($_GET["i"]) ? "i" : (isset($_GET["e"]) ? "e" : ""));
$h = isset($_GET["h"]) ? $_GET["h"] : "";
$mid = isset($_GET["mid"]) ? $_GET["mid"] : "";

// Correct the international format of the phone number if needed
$to = isset($_GET["to"]) ? $_GET["to"] : "";
if ("00" == substr($to, 0, 2)) {
  $to = "+" . substr($to, 2, strlen($to) -2);
}

// Define a default message if needed
$message = isset($_GET["message"]) ? $_GET["message"] : ""; // Hello World 😉

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
} else {
  $smsgateway->updateDataStructure($id);
}

if ((!empty($mid)) && (!empty($device_id))) {
  $message_state = "MISSING";
  $message_array = $smsgateway->readSentStatus($id, $mid);
  if (isset($message_array[0]['status'])) {
    $message_state = $message_array[0]['status'];
  }
  echo $message_state;

} elseif ("e" == $command) {

  // An enhanced command can be implemented here

} elseif (("m" == $command) && (!empty($device_id))) {

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
    } else {
      $pre_status = "";
      $post_status = "";
    }
    echo date("Y-m-d H:i:s", $message['last_update'] / 1000)." ".$message['to']." [" . $pre_status . "<a target=\"track_" . $message['message_id'] . "\" href=\"$url?id=$device_id&h=$h&mid=" . $message['message_id'] . "\">" . $message['status'] . "</a>" . $post_status . "]: ".$message['content']. "<br />";
    $sent_messages_count++;
  }
  if (0 == $sent_messages_count) {
    echo "none<br />";
  }

} elseif (empty($device_id) || ("i" == $command)) {

  // Display basic usage info
  if ("" == $to) {
    $autofocus_to = "autofocus=\"autofocus\"";
    $autofocus_message = "";
  } else {
    $autofocus_to = "";
    $autofocus_message = "autofocus=\"autofocus\"";
  }
  
  $output = "<html><head><title>SMSGateway ".$smsgateway->getVersion()."</title></head><body>\n";
  
  $output.= "<h1>SMSGateway ".$smsgateway->getVersion()."</h1>";
  $output.= "<br />";
  $output.= "<form action=\"".htmlspecialchars($_SERVER["PHP_SELF"])."\" method=\"get\">\n";
  if ("" != $id) {
    $output.= "  <label for=\"to\">Device identification: </label>\n";
    $output.= "  <input size=\"18\" type=\"text\" name=\"id\" placeholder=\"e.g. 01234567890abcdef\" value=\"$id\">\n";
    $output.= "<br />\n";
    if ("" != $h) {
      $output.= "  <label for=\"to\">Secret hash for the device: </label>\n";
      $output.= "  <input size=\"8\" type=\"text\" name=\"h\" placeholder=\"e.g. abcdef\" value=\"$h\">\n";
      $output.= "<br />\n";
    }
    $output.= "<br />";
  }
  $output.= "  <label for=\"to\">Destination mobile phone number: </label>\n";
  $output.= "  <input size=\"20\" $autofocus_to type=\"tel\" name=\"to\" placeholder=\"e.g. 00123456789012\" value=\"$to\">\n";
  $output.= "<br />\n";
  $output.= "<br />\n";
  $output.= "  <textarea $autofocus_message columns=\"40\" rows=\"5\" placeholder=\"e.g. Please call me back asap !\" name=\"message\" autocomplete=\"on\" maxlength=\"300\" cols=\"80\" wrap=\"soft\">$message</textarea>\n";
  $output.= "  <br /><br />\n";
  $output.= "  <input type=\"submit\" value=\"Send SMS message\">\n";
  $output.= "  <br /><br />\n";
  if ("" == $h) {
    $output.= "... or send a first message calling this URL: <b>$url?to=001234567890&message=Hello+world</b>";
  } else {
    $output.= "... or send a direct message calling this URL: <b>$url?id=$id&h=$h&to=".(("" != $to) ? $to : "001234567890")."&message=".urlencode(("" != $message) ? $message : "Hello world 🙂")."</b>";
  }
  $output.= "</form>\n";

  echo $output;

} elseif (!empty($to)) {
  
  // Push the message on the server
  $message_id = $smsgateway->sendMessage($device_id, $to, $message);
  
  $output = "<html><head><title>SMSGateway ".$smsgateway->getVersion()."</title>\n";

  if (empty($message_id)) {
    header('X-SMSGateway-State: FAILED');
    $output.= "<meta name=\"X-SMSGateway-State\" content=\"0\">\n";
  } else {
    header('X-SMSGateway-State: NEW');
    header('X-SMSGateway-State-Url: $url?id=$id&h=$h&mid=$message_id');
    header('X-SMSGateway-Message-Id: '.$message_id);
    $output.= "<meta name=\"X-SMSGateway-State\" content=\"NEW\">\n";
    $output.= "<meta name=\"X-SMSGateway-State-Url\" content=\"$url?id=$id&h=$h&mid=$message_id\">\n";
    $output.= "<meta name=\"X-SMSGateway-Message-Id\" content=\"$message_id\">\n";
  }

  $output.= "</head><body>\n";

  // Display usage information
  $output.= "<h1>SMSGateway ".$smsgateway->getVersion()."</h1>";
  $output.= "Message '<i>$message</i>' for '$to' with id '<b>$message_id</b>' pushed on the server.";
  $output.= "<br /><br />";
  $output.= "If not done yet, please install the Android SMSGatewayApp available here : <a href=\"https://github.com/medic/cht-gateway/releases/latest\" target=\"blank\">medic-gateway-X.X.X-generic-release.apk</a>";
  $output.= "<br /><br />";
  $output.= "URL to set in the Settings of the Android App: <b>$url?id=$device_id&h=$device_h</b>";
  $output.= "<br />";
  $output.= "<i>(don't forget to enable polling)</i>";
  $output.= "<br /><br />";
  $output.= "<a target=\"_sms_gateway_check\" href=\"$url?id=$device_id&h=$device_h&m\"><button>Click here to check SMS messages</button></a>";
  $output.= "<br /><br />";
  $output.= "<a target=\"_sms_send\" href=\"$url?id=$device_id&h=$device_h&to=&message=&i\"><button>Click here to send more SMS messages</button>";
  
  $output.= "\n";
  $output.= "</body>\n";
  $output.= "</html>\n";
  
  echo $output;
  
} else {

  // Run the API server
  $smsgateway->apiServer();
}