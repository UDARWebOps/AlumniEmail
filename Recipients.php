<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/14/2017
   * Time: 4:44 PM
   */

	namespace AlumniEmail;

	require_once 'APICall.php';
  require_once 'Recipient/RecipientRepository.php';
  require_once 'Message/MessageRepository.php';
	require_once 'Log/Log.php';


	class Recipients
  {
    protected $messageId;
    protected $subMethod;
	  public $queryParams = array(  'startAt' => 0,
	                                'maxResults' => 1000);   // defaults
	  public $Log;

	  //**************  C O N S T R U C T  *******************************
	  // This object is created on a per message basis
//    public function __construct( $id = NULL, array $params =  NULL)
    public function __construct( $id = NULL)
    {
	    if (isset( $id))
		    $this->messageId = $id;
	    else return FALSE;
	    $this->subMethod = "recipients";

	    // This will override the defaults for startAt and maxResults if passed in
//	    if (isset($params)) {
//		    foreach ($params as $param_name => $param_value) {
//			    $this->queryParams[$param_name] = $param_value;
//		    };
//	    }
	    $this->Log = new Log( __FILE__);
    }

	  //***********  R E T R I E V E   R E C I P I E N T S  ***************
    public function retrieveRecipients() {
      $urlItems = ['method'=>'messages', 'messageId'=>$this->messageId, 'subMethod'=>$this->subMethod];

      // Instantiate iModules API Obj passing URL params
      $myAPICall = new APICall( $urlItems);

      $MessageRepository = new MessageRepository();
      $RecipientRepository = new RecipientRepository( $this->messageId);

	    // Get the total number of recipients
      $totalRecs = $MessageRepository->getRecipTotal( $this->messageId);
      if (!isset( $totalRecs)) {    // Can be NULL if request timed out or other tech difficulties encountered at iMods
	      $totalRecs = -1;
      }
      // Set starting point.  If restarting after a previous abort, want to pick up where it left off
	    $this->queryParams['startAt'] = $RecipientRepository->getCount();  // This counts records in d/b.  Existing number of recipients will be starting point

	    // Loop for getting all recipients
	    // Call API passing in query parameters
	    while ($totalRecs == -1 || ($this->queryParams['startAt'] < $totalRecs)) {
			  $results = $myAPICall->doAPI( $this->queryParams);
		    $msg = '<Recipients::retrieveRecipients> MsgId = ' . $this->messageId;

		    // todo: create a class for error checking.  Doing this in OpensClicks.php & MessageCounts.php also
			  // Check for ERROR and bail if Error
			  if ($results instanceof \Exception) {
				  $this->Log->writeToLog( '', $msg  . $results->getMessage());
			    return;
		    }
		    // Check if TIMEOUT
		    if (isset($results->message)) {
			    $this->Log->writeToLog( '', "<MessageCounts::retrieveCount> MsgID: ". $this->messageId . " submethod = ". $this->subMethod . ", Error: " . $results->message);
			    return;
		    }

		    $totalRecs = $results->total;  // Total is sent with every API response. Updating it just in case it's differnt

		    // Process list of recipients
		    if (isset($results)) {
			    $recipientRepository = new \AlumniEmail\RecipientRepository( $this->messageId);
			    foreach ($results->data as $recipient) {
				    try {
					    $recipient->opensCount  = 0;
					    $recipient->clicksCount = 0;
					    $recipient              = new Recipient( $recipient);

					    self::saveRecipient($recipientRepository, $recipient);
				    } catch (Exception $e) {
					    $this->Log->writeToLog( '', $msg . $e->getMessage());
				    }
			    }
		    }
		    $this->queryParams['startAt'] += $this->queryParams['maxResults'];
		    }
	    }

    //*****************  S A V E   R E C I P I E N T  **********************************************
		public function saveRecipient( $recipientRepository, $recipient) {
      $rc = $recipientRepository->saveRecipient( $recipient);
      $msg = '<Recipients::saveRecipient> MsgId = ' . $this->messageId . ', RecipientID = ' . $recipient->id;
      if ($rc instanceof \Exception) {
      	$this->Log->writeToLog( '', $msg . ' -- D/B Error! ' . $rc->getMessage());
        return 0;
      }
      elseif (0 === $rc ) {
	      $this->Log->writeToLog( '', $msg . ' --  Recipient is empty, not saved ');
      }
      elseif (-1 === $rc) {
	      $this->Log->writeToLog( '', $msg . ' -- Recipient info not changed, did not update ');
      }
      else return 1;
    }

		//NOT USED*****************  G E T   R E C I P I E N T S  **********************************************
//		public function getRecipients() {
//      $recipientRepository = new \AlumniEmail\RecipientRepository();
//      $results = $recipientRepository->getAll( $this->messageId);
//      return $results;
//    }


    //*******************************************
    public function setCount( $type) {

    }

  }