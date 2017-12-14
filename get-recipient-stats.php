<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/16/2017
   * Time: 2:20 PM
   */
	// ************************
	//  Create the required email statistics per recipient and save to d/b in the format of 'recipient_stat_<year>_<month>'
	//
	//  This program is called with query params:
	//   1) month=: '01' thru '12'
  //   2) year=: yyyy
	//



  namespace AlumniEmail;

  require_once 'Log/Log.php';
  require_once 'Message/MessageRepository.php';
  require_once 'Message/MessageTrackRepository.php';
	require_once 'Recipient/RecipientRepository.php';
	require_once 'Recipient/RecipientStatRepository.php';

	define( 'STATS_PROCESSED', 1);

	$from = '';
	$to = '';

  ini_set('max_execution_time',2400);  // 40 minutes

  $Log = new Log( __FILE__, 'stat');
  $Log->writeToLog( 'initiate');

	if ((!isset( $_REQUEST['month'])) || !isset( $_REQUEST['year']) || !isset( $_REQUEST['fromDay'])  || !isset( $_REQUEST['toDay'])) {
		// todo: check for numeric or valid day for month?
    $Log->writeToLog( '', 'missing Month or Year, or valid from/to days');
 	  exit( 'Missing year and/or month');
  }
	else {
    // Get from and to timestamps in epoch format
    $TZ = new \DateTimeZone("America/Chicago");
//    $fromTimestamp = new \DateTime( $_REQUEST['year'] . '/' . $_REQUEST['month'] . '/01T00:00:00', $TZ);
		// FROM
    $fromTimestamp = new \DateTime( $_REQUEST['year'] . '/' . $_REQUEST['month'] . '/' . $_REQUEST['fromDay'] . 'T00:00:01', $TZ);
    $from = $fromTimestamp->format('U') . '000';
    // TO
		$interval = $_REQUEST['toDay'] - $_REQUEST['fromDay'] + 1;
		$interval = 'P' . $interval . 'D';
    $fromTimestamp->add( new \DateInterval($interval));   // Add interval
//    $fromTimestamp->add( new \DateInterval('P1M'));   // Add 1 month
    $fromTimestamp->sub( new \DateInterval( 'PT1S')); // Minus 1 second
    $to = $fromTimestamp->format('U') . '000';

    // Do the d/b query to get message IDs within the timeframe specified in query params
    $Messages = new MessageRepository();
    $messageIDs = $Messages->findMessages( $from, $to);
    // Check for errors
    if ($messageIDs instanceof \Exception) {
     $Log->writeToLog( '', 'D/B Errors! ' . $messageIDs->getMessage());
      return 0;
    }
    if (empty($messageIDs)) {
	    $Log->writeToLog( '', 'No messages found for search criteria: year=' . $_REQUEST['year'] . ', month=' . $_REQUEST['month']);
	    return 0;
    }
		// Create the monthly messages d/b table and populate with a row for each messageID using default '0' values for stats and CSV processing flags
		// If the method detects a row already created,
		// These tables are used to keep track of what was processed / not processed
		$MessageTrackRepository = new MessageTrackRepository(  $_REQUEST['year'], $_REQUEST['month']);
    foreach ($messageIDs as $messageID) {
    	$MessageTrackRepository->saveMessageTrack( $messageID['id']);
    }


    //Loop thru the message IDs returned:
    //  1) Get all recipient records for the message
    //  2) for each recipient, create/update the email stats record
    $RecipientStatRepository = new RecipientStatRepository( $_REQUEST['year'], $_REQUEST['month']);
    foreach ($messageIDs as $message) {
	    if (!$MessageTrackRepository->isProcessed( $message['id'])) {
		    $RecipientRepository = new RecipientRepository($message['id']);
		    $Recipients = array();
		    while ($Recipients = $RecipientRepository->getRecipients()) {
			    $success             = 1;
			    foreach ($Recipients as $Recipient) {
				    $Recipient = (object) $Recipient;
					    $rc  = $RecipientStatRepository->saveRecipientStat($Recipient, $message['id']);         // This will update the appropriate arrays of msg ids for the recipient record
					    $msg = '<get-recipient-stats.php... return from $RecipientStatRepository::saveRecipientStat> MsgId = ' . $message['id'] . ', RecipientID = ' . $Recipient->id;
					    if ($rc instanceof \Exception) {
						    $Log->writeToLog('', $msg . ' -- D/B Error! ' . $rc->getMessage());

						    return 0;
					    }
					    elseif (0 === $rc) {
						    $Log->writeToLog('', $msg . ' -- Some other error');
						    $success = 0;

						    return 0;
						    if (!TRUE === $rc) {   // save / update not successful
							    $success = 0;
						    }
					    }
			    }
          $Log->writeToLog( '', $msg . ' -- Processed Count is: ' . $RecipientRepository->getProcessedCount() );
		    }
		    ($success) ? $MessageTrackRepository->saveMessageTrack( $message['id'], STATS_PROCESSED) : $Log->writeToLog('', 'Not all recipient stats were successfully saved. Please rerun for message ' . $message['id']);
	    }
    }
	}

  $text = '';
  foreach ($_REQUEST as $key => $value) {
	  $text .= ' ' . $key . ' = ' . $value;
  }
  $text .= ' (FROM: ' . $from . ' TO: ' . $to . ')';
  $Log->writeToLog( '', '-------------- Finished '. $text);

  echo 'Table recipient_stat_' . $_REQUEST['year'] . '_' . $_REQUEST['month'] . ' created successfully';
  echo '<br/>Done!';