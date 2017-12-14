<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/16/2017
   * Time: 2:20 PM
   */

  namespace AlumniEmail;
  require_once 'Messages.php';
  require_once 'MessageCounts/MessageCounts.php';
  require_once 'Links.php';

  //
  if (isset( $_REQUEST['messageIDs'])) {
    $messageIDs = explode( ',', $_REQUEST['messageIDs']);
  }

	// If no message IDs passed in, get all message IDs from d/b
  if (!isset( $messageIDs)) {
    $messages = new Messages();
    $messageObjs = $messages->getAllMessageIDs();
    foreach ($messageObjs as $message) {
      $messageIDs[] = $message->id;
    }
  }

  // Now loop through the message id array and get the counts
  $countTypes = array('delivers', 'bounces', 'recipients', 'OpensClicks', 'clicks');
  foreach ($messageIDs as $messageId) {
    foreach ($countTypes as $countType) {
      $MessageCount = new MessageCounts( $messageId, $countType);
      // This calls the API to get the specified count
      $MessageCount->retrieveCount();
    }
    // Retrieve links from iMods
    $links = new Links( $messageId);
    $links->retrieveLinks();
  }

//  echo '<br/>Done!';