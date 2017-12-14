<?php
/**
 * Created by PhpStorm.
 * User: mhugos
 * Date: 9/23/2017
 * Time: 2:27 PM
 */
  require_once 'Links.php';
  require_once 'Messages.php';

  if (isset( $_REQUEST['messageIDs'])) {
    $messageIDs = explode( ',', $_REQUEST['messageIDs']);
    foreach ($messageIDs as $messageID) {
      // Retrieve message info
      $message = new Messages();
      $this_message = $message->getMessage( $messageID);

      // Retrieve links from d/b
      $links = new Links( $messageID);
      $msgLinks = $links->getLinks();


      echo '<h2>Message #' . $messageID . '</h2>';
      echo '<ul>';
      echo '<li>Msg Name: ' . $this_message->emailName . '</li>';
      echo '<li>Msg Sent: ' . $this_message->actualSendTimestamp . '</li>';
      echo '<li>Sent: ' . $this_message->sentCount . '</li>';
      echo '<li>Recipients: ' . $this_message->recipientsCount . '</li>';
      echo '</ul>';

      echo '<p>There are a total of ' . count($msgLinks) . ' links for this message.';
      foreach ($msgLinks as $link) {
        echo '<ul class="link">';
        echo '<li>Link #' . $link->id;
        echo '<ul>';
        echo '<li> Name: ' . $link->name . '</li>';
        echo '<li> URL : ' . $link->url . '</li>';
        echo '</ul>';
        echo '</li>';
        echo '</ul>';
      }
    }
  }
  else echo 'you must provide at least one messageID (?messageIDs=nnnnn,nnnnn,nnnnn, ...)';


