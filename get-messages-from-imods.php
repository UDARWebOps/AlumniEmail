<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/16/2017
   * Time: 2:20 PM
   */
	// This program is called with some query params:
	//   1) with epoch dates:  get-messages-from-imods.php?method=messages&fromTimestamp=1503321145000&toTimestamp=2147483647000&startAt=0&maxResults=100
	//   2) with formatted dates:  get-messages-from-imods.php?method=messages&fromTimestamp=9/1/2017&toTimestamp=9/30/2017&startAt=0&maxResults=100
  namespace AlumniEmail;

  require_once 'Log/Log.php';
  require_once 'Messages.php';
	require_once 'MessageCounts/MessageCounts.php';
	require_once 'Links.php';
	require_once 'Recipients.php';
	require_once 'OpensClicks.php';

  ini_set('max_execution_time',0);  // No timeout limit

  $Log = new Log( __FILE__);
  $Log->writeToLog( 'initiate');
  $messageIDs = array();
  // Check for messageIDs passed in
	if (isset( $_REQUEST['messageIDs'])) {
		$messageIDs = explode( ',', $_REQUEST['messageIDs']);
	}

	//---------------  GET MESSAGES  ---------------
	$newMessages = array();
	if (!empty( $messageIDs)) {     // message IDs were passed via the query string...
		foreach ($messageIDs as $messageId) {
			$messages = new Messages( array( 'method' => $_REQUEST['method'], 'messageIDs' => $messageId));
			// Get the messages from iMods and save them to database
			$msgID = $messages->retrieveMessages();
			if (isset($msgID[0])) {
				$newMessages[] = $msgID[0];
			}
		}
	}
	else {    // otherwise, get messages based on timestamps
		// Create new Messages object by passing in the parameters from the query string to this program (from, to, max results, etc.)
		$messages = new Messages( $_REQUEST);
		// Get the messages from iMods, save them to database, and get any new (non-processed) message IDs
		$newMessages = $messages->retrieveMessages();
	}

  $log_msg = 'Number of messages to be processed: '. count( $newMessages);
  $Log->writeToLog( '', $log_msg);

	// Loop through $newMessages and do the rest of the processing for each msg
  $messageCountTypes = array('delivers', 'bounces', 'recipients', 'opens', 'clicks');  // todo: add 'links' later. For now, done below
  foreach ($newMessages as $messageId) {
  	    $log_msg = 'Processing MsgID '. $messageId;
			  $Log->writeToLog( '', $log_msg);

			  //---------------  GET COUNTS  ---------------
			  // Loop thru the count types and retrieve / save counts
			  foreach ($messageCountTypes as $countType) {
					$MessageCount = new MessageCounts( $messageId, $countType);
					// This calls the API to get the specified count (we are not interested in the actual rows)
					$MessageCount->retrieveCount();
				}

				//---------------  GET LINKS  ---------------
				$links = new Links( $messageId);
				$links->retrieveLinks();

				//---------------  GET RECIPIENTS  ---------------
				$Recipients = new Recipients( $messageId);
				// This calls the API to get recipients, then saves them to the recipients<msgid> table
				$Recipients->retrieveRecipients();

				//---------------  GET OPENS, GET CLICKS  ---------------
				$recipientCountTypes = array( 'opens', 'clicks');
				foreach ($recipientCountTypes as $countType) {
					$OpensClicks = new OpensClicks( $messageId, $countType);
					// This calls the API to get the specified count + recipients who opened/clicked
					$OpensClicks->retrieveOpensClicks();
		}
	}
	$text = '';
	foreach ($_REQUEST as $key => $value) {
  	$text .= ', ' . $key . ' = ' . $value;
	}
  $Log->writeToLog( '', '-------------- Finished '. $text);
	echo '<br/>Done!';