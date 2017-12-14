<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/16/2017
   * Time: 2:20 PM
   */

  namespace AlumniEmail;

  require_once 'Messages.php';
  require_once 'Recipients.php';
  require_once 'Log/Log.php';

  $queryParams = array();

  $Log = new Log( __FILE__);
  $Log->writeToLog( 'initiate');

  if (isset( $_REQUEST['messageIDs'])) {
    $messageIDs = explode( ',', $_REQUEST['messageIDs']);
  }
  if (isset( $_REQUEST['startAt'])) {
    $queryParams['startAt'] = $_REQUEST['startAt'];
  }
  if (isset( $_REQUEST['maxResults'])) {
    $queryParams['maxResults'] = $_REQUEST['maxResults'];
  }

  //  If no msg IDs were specified in the query string, get all message ids.
  //  Recipients will be retrieved for ALL messages.
  if (!isset( $messageIDs)) {
    // get all message IDs
    $messages = new Messages();
    $messageObjs = $messages->getAllMessageIDs();
    foreach ($messageObjs as $message) {
      $messageIDs[] = $message->id;   // create array of msg ids
    }
  }
  // Now get recipient records per message
  foreach ($messageIDs as $messageId) {
    $Recipients = new Recipients( $messageId, $queryParams);
    $Recipients->retrieveRecipients();
  }

  echo '<br/>Done getting recipients!';