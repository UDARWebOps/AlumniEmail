<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/16/2017
   * Time: 2:20 PM
   */
  namespace AlumniEmail;
  require_once 'Log/Log.php';
	require_once 'Messages.php';
	require_once 'OpensClicks.php';

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
  //  Opens will be retrieved for ALL messages.
  if (!isset( $messageIDs)) {
    // get all message IDs
    $messages = new Messages();
    $messageObjs = $messages->getAllMessageIDs();
    foreach ($messageObjs as $message) {
      $messageIDs[] = $message->id;   // create array of msg ids
    }
  }
  $log_msg = 'Number of messages to be processed: '. count( $messageIDs);
  $Log->writeToLog( '', $log_msg);

  // Now loop through the message id array and get the opens and clicks
  $countTypes = array( 'opens', 'clicks');
  foreach ($messageIDs as $messageId) {
    $log_msg = 'Processing MsgID '. $messageId;
    $Log->writeToLog( '', $log_msg);
    foreach ($countTypes as $countType) {
      $log_msg = 'Getting '. $countType;
      $Log->writeToLog( '', $log_msg);
	    $OpensClicks = new OpensClicks( $messageId, $countType, $queryParams);
      // This calls the API to get the specified count
      $OpensClicks->retrieveOpensClicks();
    }
  }
  $Log->writeToLog( '', '-------------- Finished '. $text);
  echo '<br/>Done getting opens/clicks!';