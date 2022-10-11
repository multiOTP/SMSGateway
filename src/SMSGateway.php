<?php
/**
 * @file  smsgateway.class.php
 * @brief SMSGateway - flat-file based SMS gateway
 *         PHP class using an open source Android app
 *
 * @mainpage
 *
 * SMSGateway PHP class
 *
 * https://github.com/multiOTP/SMSGateway
 *
 * The SMSGateway PHP class is a flat-file based SMS gateway for sending and
 *  receiving SMS on an Android device using an open source SMS Gateway app.
 *  (https://github.com/medic/cht-gateway)
 *
 * The Readme file contains additional information.
 *
 * PHP 7.0.0 or higher is supported.
 *
 * @author    Andre Liechti (SysCo systemes de communication sa) <info@multiotp.net>
 * @version   1.0.3
 * @date      2022-10-11
 * @since     2022-09-10
 * @copyright (c) 2022 SysCo systemes de communication sa
 * @copyright GNU Lesser General Public License
 *
 *//*
 *
 * LICENCE
 *
 *   Copyright (c) 2022 SysCo systemes de communication sa
 *   SysCo (tm) is a trademark of SysCo systemes de communication sa
 *   (http://www.sysco.ch/)
 *   All rights reserved.
 * 
 *   SMSGateway PHP class is free software; you can redistribute it and/or
 *   modify it under the terms of the GNU Lesser General Public License as
 *   published by the Free Software Foundation, either version 3 of the License,
 *   or (at your option) any later version.
 * 
 *   SMSGateway PHP class is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *   GNU Lesser General Public License for more details.
 * 
 *   You should have received a copy of the GNU Lesser General Public
 *   License along with SMSGateway PHP class.
 *   If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * Usage
 *
 *   Public methods available:
 *    setDataPath($data_path)
 *    getDataPath()
 *    setDeviceId($device_id)
 *    getDeviceId()
 *    setDeviceTimeout($device_timeout)
 *    getDeviceTimeout()
 *    setSuccessArchiveTime($success_archive_time)
 *    getSuccessArchiveTime()
 *    setPurgeArchiveTime($purge_archive_time)
 *    getPurgeArchiveTime()
 *    setDeviceFolder($device_folder)
 *    getDeviceFolder()
 *    getDeviceFolderLogs()
 *    getDeviceFolderSend()
 *    getDeviceFolderReceive()
 *    getDeviceFolderArchive()
 *    getDevicePathLogs()
 *    getDevicePathSend()
 *    getDevicePathReceive()
 *    getDevicePathArchive()
 *    handleMessages($post_data)
 *    handleUpdates($post_data)
 *    updateDataStructure($device_id)
 *    readNewMessages($device_id = "")
 *    readAllMessages($device_id = "")
 *    readMessage($device_id = "", $message_id = "*", $message_filter = "*")
 *    readAllSentStatus($device_id = "")
 *    readSentStatus($device_id = "", $message_id = "*", $message_filter = "*")
 *    sendMessage($device_id, $to, $content)
 *    reactivatePushedMessages($pushed_timeout = 0)
 *    getMessagesToSend($device_id = "")
 *    getTimeoutDevices()
 *    archiveSuccessMessages($device_id = "")
 *    purgeArchiveMessages($device_id = "")
 *    calculateHash($hash_secret, $device_id)
 *    writeLog($log_message)
 *    apiServer($hash_secret          = "",
 *              $new_message_callback = "",
 *              $update_callback      = "",
 *              $timeout_callback     = "",
 *              $post_raw             = "")
 *
 *
 * Examples
 *
 *   // Example 1 - Send message using Android phone with "demo" id
 *   use multiOTP\SMSGateway\SMSGateway;
 *   require_once('SMSGateway.php');
 *   $smsgateway = new SMSGateway();
 *   $smsgateway->setDataPath(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR);
 *   $hash_secret = "secret";
 *   $device_id   = "demo";
 *   $to = "+1234567890";
 *   $message = "Demo message";
 *   $device_h = $smsgateway->calculateHash($hash_secret, $device_id);
 *   $message_id = $smsgateway->sendMessage($device_id, $to, $message);
 *   echo "Full URL for Android app URL: https://......./?id=$device_id&h=$device_h";
 *
 *
 *   // Example 2 - API server with call back function for new messages
 *   use multiOTP\SMSGateway\SMSGateway;
 *   require_once('SMSGateway.php');
 *   function new_message_handling($array) {
 *     // Handling $array
 *     //[["id"           => "message_id",
 *     //  "from"         => "from_phone",
 *     //  "sms_sent"     => "sms_sent_timestamp",
 *     //  "sms_received" => "sms_received_timestamp",
 *     //  "content"      => "message_content",
 *     //  "status"       => "message-status"
 *     // ],
 *     // [...]
 *     //]
 *   }
 *   $smsgateway = new SMSGateway();
 *   $smsgateway->setDataPath(__DIR__ . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR);
 *   $smsgateway->apiServer("secret", "new_message_handling");
 *
 *
 * External device needed
 * 
 *   Android phone with SMS Gateway app installed
 *    (https://github.com/medic/cht-gateway)
 *
 *
 * Users feedbacks and comments
 *
 * 2022-10-11 SysCo/al (CH)
 *   First public version
 *
 *
 * Change Log
 *
 *   2022-10-11 1.0.3 SysCo/al First public version
 *   2022-09-10 0.0.1 SysCo/al Initial implementation
 *********************************************************************/

namespace multiOTP\SMSGateway;

/**
 * SMSGateway - flat-file based SMS gateway PHP class using an open source Android app
 *
 * @author Andre Liechti (SysCo systemes de communication sa) <info@multiotp.net>
 */
class SMSGateway
{
  /**
   * The SMSGateway Version number.
   *
   * @var string
   */
  const VERSION = '1.0.3';
  
  /**
   * The device timeout in seconds.
   * Default of 5 minutes (300sec).
   *
   * @var int
   */
  private $DeviceTimeout = 300;
  
  /**
   * The success archive time in seconds.
   * Default of 1 day (1 * 86400 sec).
   *
   * @var int
   */
  private $SuccessArchiveTime = 1 * 86400;
  
  /**
   * The purge archive time in seconds.
   * Default of 90 days (90 * 86400 sec).
   *
   * @var int
   */
  private $PurgeArchiveTime = 90 * 86400;
  
  /**
   * The purge log time in seconds.
   * Default of 365 days (365 * 86400 sec).
   *
   * @var int
   */
  private $PurgeLogTime = 365 * 86400;

  /**
   * The flat-file based data path (with terminal directory separator)
   *
   * @var string
   */
  private $DataPath = '';
  
  /**
   * The Android device id
   *
   * @var string
   */
  private $DeviceId = '';

  /**
   * The flat-file based device folder (without terminal directory separator)
   *
   * @var string
   */
  private $DeviceFolder = '';

  /**
   * Class constructor.
   */
  public function __construct()
  {
    // Define a default data path in the system temporary folder
    $this->setDataPath(sys_get_temp_dir() . DIRECTORY_SEPARATOR);
  }

  /**
   * Class destructor.
   */
  public function __destruct()
  {
    // $this->...;
  }

  /**
   * Set the flat-file data path
   *
   * @param string $data_path The flat-file data path (with terminal directory separator)
   *
   * @return bool true on success, false if folder is not available
   */
  public function setDataPath(
    $data_path
  ) {
    if (file_exists($data_path)) {
      if (substr($data_path, -strlen(DIRECTORY_SEPARATOR)) != DIRECTORY_SEPARATOR) {
        $data_path.= DIRECTORY_SEPARATOR;
      }
      $this->DataPath = $data_path;
      return true;
    } else {
      return false;
    }
  }

  /**
   * Get the flat-file data path
   *
   * @return string The flat-file data path (with terminal directory separator)
   */
  public function getDataPath()
  {
    return $this->DataPath;
  }
  
  /**
   * Set device id.
   *
   * @param string $device_id The Android device id (which is in the URL)
   */
  public function setDeviceId(
    $device_id
  ) {
    $this->DeviceId = $device_id;
  }

  /**
   * Get device id.
   *
   * @return string The Android device id (which is in the URL)
   */
  public function getDeviceId()
  {
    return $this->DeviceId;
  }
  
  public function setDeviceTimeout(
    $device_timeout
  ) {
    $this->DeviceTimeout = intval($device_timeout);
  }

  public function getDeviceTimeout()
  {
    return intval($this->DeviceTimeout);
  }

  public function setSuccessArchiveTime(
    $success_archive_time
  ) {
    $this->SuccessArchiveTime = intval($success_archive_time);
  }

  public function getSuccessArchiveTime()
  {
    return intval($this->SuccessArchiveTime);
  }

  public function setPurgeArchiveTime(
    $purge_archive_time
  ) {
    $this->PurgeArchiveTime = intval($purge_archive_time);
  }

  public function getPurgeArchiveTime()
  {
    return intval($this->PurgeArchiveTime);
  }

  public function setPurgeLogTime(
    $purge_log_time
  ) {
    $this->PurgeLogTime = intval($purge_log_time);
  }

  public function getPurgeLogTime()
  {
    return intval($this->PurgeLogTime);
  }

  public function setDeviceFolder(
    $device_folder
  ) {
    $this->DeviceFolder = $device_folder;
  }

  public function getDeviceFolder()
  {
    return $this->DeviceFolder;
  }
  
  public function getDeviceFolderLogs()
  {
    return $this->DeviceFolder . DIRECTORY_SEPARATOR . "logs";
  }
  
  public function getDeviceFolderSend()
  {
    return $this->DeviceFolder . DIRECTORY_SEPARATOR . "send";
  }
  
  public function getDeviceFolderReceive()
  {
    return $this->DeviceFolder . DIRECTORY_SEPARATOR . "receive";
  }
  
  public function getDeviceFolderArchive()
  {
    return $this->DeviceFolder . DIRECTORY_SEPARATOR . "archive";
  }

  public function getDevicePathLogs()
  {
    return $this->getDeviceFolderLogs() . DIRECTORY_SEPARATOR;
  }
  
  public function getDevicePathSend()
  {
    return $this->getDeviceFolderSend() . DIRECTORY_SEPARATOR;
  }
  
  public function getDevicePathReceive()
  {
    return $this->getDeviceFolderReceive() . DIRECTORY_SEPARATOR;
  }

  public function getDevicePathArchive()
  {
    return $this->getDeviceFolderArchive() . DIRECTORY_SEPARATOR;
  }

  public function handleMessages(
    $post_data
  ) {
    $result_array = array();
    $extract_data = json_decode($post_data, true);
    if (null != $extract_data) {
      if (isset($extract_data["messages"])) {
        foreach($extract_data["messages"] as $message) {
          if (isset($message["id"])) {
            $from = (isset($message["from"]) ? $message["from"] : "");
            $sms_sent = (isset($message["sms_sent"]) ? $message["sms_sent"] : "");
            $sms_received = (isset($message["sms_received"]) ? $message["sms_received"] : "");
            $content = (isset($message["content"]) ? $message["content"] : "");
            $message_data = "from:$from\n";
            $message_data.= "sms_sent:$sms_sent\n";
            $message_data.= "sms_received:$sms_received\n";
            $message_data.= $content;
            file_put_contents($this->getDevicePathReceive() . $message["id"] . ".UNREAD", $message_data);

            array_push($result_array, ["device_id"    => $this->getDeviceId(),
                                       "message_id"   => $message["id"],
                                       "from"         => $from,
                                       "sms_sent"     => $sms_sent,
                                       "sms_received" => $sms_received,
                                       "content"      => $content,
                                       "status"       => "UNREAD"
                                      ]);

          }
        }
      }
    }
    return $result_array;
  }

  public function handleUpdates(
    $post_data
  ) {
    $result_array = array();
    $extract_data = json_decode($post_data, true);
    if (null != $extract_data) {
      if (isset($extract_data["updates"])) {
        foreach($extract_data["updates"] as $update) {
          if (isset($update["id"])) {
            if (isset($update["status"])) {
              $message_array = glob($this->getDevicePathSend() . $update["id"] . ".*");
              if (1 == count($message_array)) {
                array_push($result_array, ["device_id"    => $this->getDeviceId(),
                                           "message_id" => $update["id"],
                                           "status"     => $update["status"]
                                          ]);
                $updated_message = $this->getDevicePathSend() . $update["id"] . "." . $update["status"];
                rename($message_array[0], $updated_message);
              }
            }
          }
        }
      }
    }
    return $result_array;
  }

  public function updateDataStructure(
    $device_id
  ) {
    $result = false;
    $this->setDeviceId($device_id);
    if (file_exists($this->getDataPath()) && (!empty($device_id))) {
      $this->setDeviceFolder($this->getDataPath() . $this->getDeviceId());
      if (!file_exists($this->getDeviceFolder())) {
        mkdir($this->getDeviceFolder());
      }
      touch($this->getDeviceFolder());
      if (!file_exists($this->getDeviceFolderLogs())) {
        mkdir($this->getDeviceFolderLogs());
      }
      if (!file_exists($this->getDeviceFolderSend())) {
        mkdir($this->getDeviceFolderSend());
      }
      if (!file_exists($this->getDeviceFolderReceive())) {
        mkdir($this->getDeviceFolderReceive());
      }
      if (!file_exists($this->getDeviceFolderArchive())) {
        mkdir($this->getDeviceFolderArchive());
      }
      $result = true;
    }
    return $result;
  }

  public function readNewMessages(
    $device_id = ""
  ) {
    return $this->readMessage($device_id, "*", "UNREAD");
  }

  public function readAllMessages(
    $device_id = ""
  ) {
    return $this->readMessage($device_id);
  }

  public function readMessage(
    $device_id = "",
    $message_id = "*",
    $message_filter = "*"
  ) {
    $result_array = array();
    if (empty($device_id)) {
      $device_id = $this->getDeviceId();
    }
    if ($this->updateDataStructure($device_id)) {
      $messages_new_array = glob($this->getDevicePathReceive() . "$message_id.$message_filter");
      if (count($messages_new_array) > 0) {
        foreach($messages_new_array as $message) {
          $from = "";
          $sms_sent = "";
          $sms_received = "";
          $content = "";
          $line_count = 0;
          $file = fopen($message, "r");
          while(! feof($file)) {
            $line_count++;
            $line_content = fgets($file);
            if (1 == $line_count) {
              $from = str_replace("from:", "", $line_content);
            } elseif (2 == $line_count) {
              $sms_sent = str_replace("sms_sent:", "", $line_content);
            } elseif (3 == $line_count) {
              $sms_received = str_replace("sms_received:", "", $line_content);
            } else {
              if ($line_count > 4) {
                $content.= "\n";
              }
              $content.= $line_content;
            }
          }
          fclose($file);
          array_push($result_array, ["device_id"    => $device_id,
                                     "message_id"   => pathinfo($message)['filename'],
                                     "from"         => $from,
                                     "sms_sent"     => $sms_sent,
                                     "sms_received" => $sms_received,
                                     "content"      => $content,
                                     "status"       => pathinfo($message)['extension']
                                    ]);
          $message_read = str_replace(".UNREAD", ".READ", $message);
          if ($message_read != $message) {
            rename($message, $message_read);
            touch($message_read);
          }
        }
      }
    }
    return $result_array;
  }

  public function readAllSentStatus(
    $device_id = ""
  ) {
    return $this->readSentStatus($device_id);
  }

  public function readSentStatus(
    $device_id = "",
    $message_id = "*",
    $message_filter = "*"
  ) {
    $result_array = array();
    if (empty($device_id)) {
      $device_id = $this->getDeviceId();
    }
    if ($this->updateDataStructure($device_id)) {
      $messages_new_array = glob($this->getDevicePathSend() . "$message_id.$message_filter");
      if (count($messages_new_array) > 0) {
        foreach($messages_new_array as $message) {
          $id = pathinfo($message)['filename'];
          $status = pathinfo($message)['extension'];
          array_push($result_array, ["device_id"  => $device_id,
                                     "message_id" => $id,
                                     "status"     => $status
                                    ]);
        }
      }
    }
    return $result_array;
  }

  public function sendMessage(
    $device_id,
    $to,
    $content
  ) {
    $message_id = "";
    if (empty($device_id)) {
      $device_id = $this->getDeviceId();
    }
    if ($this->updateDataStructure($device_id)) {
      $escape_content = addcslashes($content, "\\\"\n");
      $message_id = dechex(time())."-".str_replace(".","-", uniqid("", true));
      $message_content = "{\"id\": \"$message_id\", \"to\": \"$to\", \"content\": \"$escape_content\"}";
      file_put_contents($this->getDevicePathSend() . $message_id . ".NEW", $message_content);
    }
    return $message_id;
  }

  public function reactivatePushedMessages(
    $pushed_timeout = 0
  ) {
    $messages_count = 0;
    if ($pushed_timeout > 0) {
      $messages_pushed_array = glob($this->getDevicePathSend() . "*.PUSHED");
      foreach($messages_pushed_array as $message_pushed) {
        if (time() > (filemtime($message_pushed) + $pushed_timeout)) {
          $message_new = str_replace(".PUSHED", ".NEW", $message_pushed);
          rename($message_pushed, $message_new);
          touch($message_new);
          $messages_count++;
        }
      }
    }
    return $messages_count;
  }

  public function getMessagesToSend(
    $device_id = ""
  ) {
    $result = "{";
    if (empty($device_id)) {
      $device_id = $this->getDeviceId();
    }
    if ($this->updateDataStructure($device_id)) {
      $messages_new_array = glob($this->getDevicePathSend() . "*.NEW");
      if (count($messages_new_array) > 0) {
        $result.= "\"messages\": [";
        foreach($messages_new_array as $message_new) {

          $result.= file_get_contents($message_new) . ",";

          $message_pushed = str_replace(".NEW", ".PUSHED", $message_new);
          rename($message_new, $message_pushed);
          touch($message_pushed);
        }
        $result = substr($result, 0, (strlen($result) - 1)) . "]";
      }
    }
    $result.= "}";
    return $result;
  }

  public function getTimeoutDevices()
  {
    $result_array = array();
    if ($this->getDeviceTimeout() > 0) {
      $devices_array = glob($this->getDataPath() . "*");
      foreach($devices_array as $device) {
        if (is_dir($device) && ("." != $device) && (".." != $device)) {
          $last_update = filemtime($device);
          if (time() > ($last_update + $this->getDeviceTimeout())) {
            array_push($result_array, ["device_id"   => pathinfo($device)['basename'],
                                       "last_update" => $last_update
                                      ]);
          }
        }
      }
    }
    return $result_array;
  }

  /**
   * Archive successful messages, which are
   *  DELIVERED sent messages and READ received messages
   *
   * @param string $device_id The Android device id (which is in the URL)
   *
   * @return int The number of messages archived
   */
  public function archiveSuccessMessages(
    $device_id = ""
  ) {
    $archived_messages = 0;
    if ($this->getSuccessArchiveTime() > 0) {
      if (empty($device_id)) {
        $device_id = $this->getDeviceId();
      }
      if ($this->updateDataStructure($device_id)) {
        $messages_array = glob($this->getDevicePathSend() . "*.DELIVERED");
        foreach($messages_array as $message) {
          if (time() > (filemtime($message) + $this->getSuccessArchiveTime())) {
            $message_archive = str_replace($this->getDevicePathSend(), $this->getDevicePathArchive(), $message);
            rename($message, $message_archive);
            $archived_messages++;
          }
        }
        $messages_array = glob($this->getDevicePathReceive() . "*.READ");
        foreach($messages_array as $message) {
          if (time() > (filemtime($message) + $this->getSuccessArchiveTime())) {
            $message_archive = str_replace($this->getDevicePathReceive(), $this->getDevicePathArchive(), $message);
            rename($message, $message_archive);
            $archived_messages++;
          }
        }
      }
    }
    return $archived_messages;
  }

  /**
   * Purge archived messages
   *
   * @param string $device_id The Android device id (which is in the URL)
   *
   * @return int The number of messages purged
   */
  public function purgeArchiveMessages(
    $device_id = ""
  ) {
    $purged_messages = 0;
    if ($this->getPurgeArchiveTime() > 0) {
      if (empty($device_id)) {
        $device_id = $this->getDeviceId();
      }
      if ($this->updateDataStructure($device_id)) {
        $messages_array = glob($this->getDevicePathArchive() . "*.*");
        foreach($messages_array as $message) {
          if (is_file($message) && (time() > (filemtime($message) + $this->getPurgeArchiveTime()))) {
            unlink($message);
            $purged_messages++;
          }
        }
      }
    }
    return $purged_messages;
  }

  /**
   * Purge log files
   *
   * @param string $device_id The Android device id (which is in the URL)
   *
   * @return int The number of messages purged
   */
  public function purgeLogFiles(
    $device_id = ""
  ) {
    $purged_files = 0;
    if ($this->getPurgeLogTime() > 0) {
      if (empty($device_id)) {
        $device_id = $this->getDeviceId();
      }
      if ($this->updateDataStructure($device_id)) {
        $log_array = glob($this->getDevicePathLogs() . "*.log");
        foreach($log_array as $log_file) {
          if (is_file($log_file) && (time() > (filemtime($log_file) + $this->getPurgeLogTime()))) {
            unlink($log_file);
            $purged_files++;
          }
        }
      }
    }
    return $purged_files;
  }

  public function writeLog(
    $log_message
  ) {
    $result = false;
    if (("" != $this->getDeviceFolder()) && file_exists($this->getDeviceFolderLogs())) {
      file_put_contents($this->getDevicePathLogs() . date("Y-m-d").".log",
                        date("Y-m-d H:i:s") . " " . $log_message."\n",
                        FILE_APPEND
                       );
      $result = true;
    }
    return $result;
  }

  public function calculateHash(
    $hash_secret,
    $device_id
  ) {
    return substr(strtolower(md5($hash_secret . "#salt@" . $device_id)), 0, 6);
  }

  /**
   * Main API server, which displays directly the necessary information
   *
   * @param string $hash_secret The hash secret (calculated with calculateHash)
   * @param string $new_message_callback Callback action for new message 
   * @param string $update_callback Callback action for updated status
   * @param string $timeout_callback Callback action for timeout detection
   * @param string $post_raw Forced raw post data (mainly for debugging and tests)
   */
  public function apiServer(
    $hash_secret = "",
    $new_message_callback = NULL,
    $update_callback = NULL,
    $timeout_callback = NULL,
    $post_raw = ""
  ) {
    $response_code = 200;
    $response = "";
    $device_h =  isset($_GET["h"])  ? $_GET["h"]  : '';
    $device_id = isset($_GET["id"]) ? $_GET["id"] : '';
    
    if ("" == $device_id) {
      $response_code = 404;
    } elseif (("" != $hash_secret) && ($device_h != $this->calculateHash($hash_secret, $device_id))) {
      $response_code = 401;
      $device_id = "";
    }

    if ($this->updateDataStructure($device_id)) {
      $response = "{\"medic-gateway\": true}";

      if (!empty($post_raw)) {
        $post_data = $post_raw;
      } else {
        $post_data = file_get_contents("php://input");
      }

      if (!empty($post_data)) {
        $this->writeLog($post_data);

        $new_messages_array = $this->handleMessages($post_data);
        $updates_array = $this->handleUpdates($post_data);
        $this->reactivatePushedMessages($this->getDeviceTimeout());

        $response = $this->getMessagesToSend();
        
        if (is_callable($new_message_callback)) {
          if (count($new_messages_array) > 0) {
            $new_message_callback($new_messages_array);
            foreach($new_messages_array as $message) {
              $this->readMessage("", $message["message_id"]);
            }
          }
        }

        if (is_callable($update_callback)) {
          if (count($updates_array) > 0) {
            $update_callback($updates_array);
          }
        }

      }
      // Ordering and cleaning for the current device id
      $this->archiveSuccessMessages();
      $this->purgeArchiveMessages();
      $this->purgeLogFiles();
    }

    if (is_callable($timeout_callback)) {
      if ($this->getDeviceTimeout() > 0) {
        $timeout_callback($this->getTimeoutDevices());
      }
    }

    http_response_code($response_code);
    echo $response;
  }

}