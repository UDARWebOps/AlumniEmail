<?php
	/**
	 * Created by PhpStorm.
	 * User: mg5139
	 * Date: 11/15/2017
	 * Time: 2:52 PM
	 */
	// ************************
	//  Create the required email report and save to CSV file in the format of 'emailStats_<year>_<month>'
	//
	// This program is called with query params:
	//   1) month=: '01' thru '12'
  //   2) year=: yyyy

  namespace AlumniEmail;

  require_once 'Log/Log.php';
  require_once 'Log/EmailStats.php';
  require_once 'CSV/EmailStatsCSV.php';
  require_once 'Message/MessageTrackRepository.php';
  require_once 'Recipient/RecipientStatRepository.php';


  function writeReport( $year = NULL, $month = NULL) {
	  $Log = new Log( __FILE__, 'stat');
	  $Log->writeToLog( 'initiate');
	  ini_set('max_execution_time', 600);  // 10 minutes

	  if ((!isset($month)) || !isset($year)) {
		  $Log->writeToLog( '', 'missing Month or Year');
		  exit( 'Missing year and/or month');
	  }

	  $MessageTrackRepository = new MessageTrackRepository( $year, $month);
	  $IDs = $MessageTrackRepository->isMonthProcessed();   // Check if the month has been processed for stats
	  if ($IDs != FALSE) {
		  $CSV = new EmailStatsCSV( $year, $month, $Log, $IDs);
		  try {
			  $CSV->writeCSV();
		  }
		  catch (\Exception $e) {
			  $Log->writeToLog( '', $e->getMessage());
		  }
	  }
	  else {
		  $Log->writeToLog( '', 'Stats have NOT been processed for all messages in the month requested: ' . $year . ', ' . $month);
	  }
	  $Log->writeToLog( '', 'Done writing email report for ' . $_REQUEST['year'] . '-' . $_REQUEST['month'] . '!');
  }

  writeReport( $_REQUEST['year'], $_REQUEST['month']);
  echo 'Done writing email report for ' . $_REQUEST['year'] . '-' . $_REQUEST['month'] . '!';
