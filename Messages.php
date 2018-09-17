<?php
	/**
	 * Created by PhpStorm.
	 * User: mg5139
	 * Date: 9/1/2017
	 * Time: 11:32 AM
	 */

	namespace AlumniEmail;

	require_once 'APICall.php';
	require_once 'Message/MessageRepository.php';
	require_once 'Recipient/RecipientRepository.php';
	require_once 'Log/Log.php';


	class Messages {
    public $urlItems = [];
		public $queryParams = array();
		private $storedMessages = array();
		public $Log;

		//**************  C O N S T R U C T  *******************************
    public function __construct( array $params = NULL) {

	    $this->Log = new Log( __FILE__);
	    $this->urlItems = [
        'method' => $params['method'],
        'messageId' => (isset($params['messageIDs'])) ? $params['messageIDs'] : NULL,
        'subMethod' => (isset($params['subMethod'])) ? $params['subMethod'] : NULL
      ];

      // Set defaults for paging params only if we're not asking for a specific message
	    if (!$this->urlItems['messageId']) {
		    $this->queryParams['startAt'] = 0;
		    $this->queryParams['maxResults'] = 100;
	    }
      // Put passed in values into query param array
	    // Check for non-numeric dates and transform into epoch dates
	    // Timestamp query params are "fromTimestamp" and "toTimestamp"
      foreach ($params as $param_name => $param_value) {
        if ((strpos( $param_name, "Timestamp") !== FALSE) &&
          (!is_numeric( $param_value))                              // date is NOT in epoch format
        ) {
          $date = new \DateTime( $param_value, new \DateTimeZone("America/Chicago"));
          $param_value = $date->format('U') * 1000;
        }
        $this->queryParams[$param_name] = $param_value;
      };

      //*** Remove the URL Query parameters that were used to define the endpoint.
	    // They are not iMods API query parameters but rather are part of the URI
      unset($this->queryParams['method']);
      if (array_key_exists( 'messageIDs', $this->queryParams)) {
        unset($this->queryParams['messageIDs']);
      }
      if (array_key_exists( 'subMethod', $this->queryParams)) {
        unset($this->queryParams['subMethod']);
      }
    }

    //***********  R E T R I E V E   M E S S A G E S  ***************
    //  This serves to retrieve one or multiple message from iMods
    //  A single message is denoted by the presence of a message id in the paramaters array when instantiated ($this->parameters['messageId']) */
    //*********************************************
    public function retrieveMessages() {
      // Instantiate API Obj passing URL params
//	    echo("<br>In retrieveMessages");
      $myAPICall = new APICall( $this->urlItems);
//	    echo("<br>In retrieveMessages 2");
	    $messages = $myAPICall->doAPI($this->queryParams);  // get a preview of data to determine multiple or single msg

	    // ---------  M U L T I P L E   M E S S A G E S
	    if (isset( $messages->data)) {
		    $totalRecs = -1;
		    // Paging loop for getting all records
		    while ($totalRecs == -1 || ($this->queryParams['startAt'] < $totalRecs)) {
			    // Call API using paging parameters in query string
			    $messages = $myAPICall->doAPI($this->queryParams);
			    // Check for ERROR
			    if ($messages instanceof \Exception) {
            $this->Log->writeToLog( '', '(Multiple Messages) '  . $messages->getMessage());
				    return;  // Bail
			    }
			    // Loop through each message
			    foreach ($messages->data as $message) {
			    	$Message = new Message ($message);
				    self::processMessage( $Message);
			    }
			    $totalRecs = $messages->total;
			    $this->queryParams['startAt'] += $this->queryParams['maxResults'];    // Adjust counters for paging
		    }
	    }
      //---------   S I N G L E   M E S S A G E
      else {
	      self::processMessage( new Message( $messages));
      }

      return $this->storedMessages;
    }

    //*****************  P R O C E S S   M E S S A G E  **********************************************
		public function processMessage( &$objMessage){
			try {
				$messageRepository = new MessageRepository();
				$logMsg = '<Messages::retrieveMessages> MsgId = ' . $objMessage->id;
				$objMessage->subjectLine = self::removeEmoji( $objMessage->subjectLine);
				if (self::saveMessage( $messageRepository, $objMessage)) {  // Save msg.  If successful...
					$this->storedMessages[] = $objMessage->id;               // ... put id in array to be used for subsequent calls for Counts
				}
			} catch (Exception $e) {
				$this->Log->writeToLog( '', $logMsg  . $e->getMessage());
			}
		}

    //*****************  S A V E   M E S S A G E  **********************************************
    public function saveMessage( $messageRepository, $Message) {
      if ($Message->getSubCommunity() == 1) {   // Only save emails from main community
        $rc = $messageRepository->saveMessage( $Message);
        $msg = '<Messages::saveMessage> MsgID = ' . $Message->id;
        if ($rc instanceof \Exception) {
	        $this->Log->writeToLog( '', $msg . ' -- D/B Errors! ' . $rc->getMessage());
	        $rc = 0;
        }
        elseif (0 === $rc) {
	        $this->Log->writeToLog( '', $msg . ' -- Message is empty, not saved');
        }
        elseif (-1 === $rc) {
	        $this->Log->writeToLog( '', $msg . ' -- Message info not changed, did not update ');
	        // If recipient file has not been processed, we want to continue with this msg
	        if (!$messageRepository->isRecipProcessed( $Message->id)) {
		        $rc = 1;
	        } else {
	        	$rc = 0;
	        }
        }
        else {
          return 1;
        }
        return $rc;
      }
    }

		//*****************  R E M O V E   E M O J I   **********************************************
    private function removeEmoji( $text){
          return preg_replace('/([0-9|#][\x{20E3}])|[\x{00ae}|\x{00a9}|\x{203C}|\x{2047}|\x{2048}|\x{2049}|\x{3030}|\x{303D}|\x{2139}|\x{2122}|\x{3297}|\x{3299}][\x{FE00}-\x{FEFF}]?|[\x{2190}-\x{21FF}][\x{FE00}-\x{FEFF}]?|[\x{2300}-\x{23FF}][\x{FE00}-\x{FEFF}]?|[\x{2460}-\x{24FF}][\x{FE00}-\x{FEFF}]?|[\x{25A0}-\x{25FF}][\x{FE00}-\x{FEFF}]?|[\x{2600}-\x{27BF}][\x{FE00}-\x{FEFF}]?|[\x{2900}-\x{297F}][\x{FE00}-\x{FEFF}]?|[\x{2B00}-\x{2BF0}][\x{FE00}-\x{FEFF}]?|[\x{1F000}-\x{1F6FF}][\x{FE00}-\x{FEFF}]?/u', '', $text);
    }


    //*****************  G E T   M E S S A G E   **********************************************
    public function getMessage( $msgId = NULL) {
      $messageRepository = new MessageRepository();
      $message = $messageRepository->findMessage( $msgId);
      return $message;
    }

     //*****************  G E T   A L L   M E S S A G E  I D S   **********************************************
    public function getAllMessageIDs() {
      $messageRepository = new MessageRepository();
      $message = $messageRepository->findAllMessages( 1);  // mode=1 means just get message ids
      return $message;
    }
  }

