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
  require_once 'Log/Log.php';

  class OpensClicks
  {
    protected $messageId;
    protected $subMethod;
	  public    $queryParams = array(  'startAt' => 0,
	                                   'maxResults' => 1000);   // defaults
    public $Log;
    protected $results;

	  //**************  C O N S T R U C T  *******************************
    public function __construct( $id = NULL, $type = '', $params = NULL)
    {
      if (isset( $id))
        $this->messageId = $id;
      else return FALSE;
      $this->subMethod = $type;

	    // This will override the defaults for startAt and maxResults if passed in
	    if (isset($params)) {
		    foreach ($params as $param_name => $param_value) {
			    $this->queryParams[$param_name] = $param_value;
		    };
	    }
      $this->Log = new Log( __FILE__);
    }

    //***********  R E T R I E V E  O P E N S  &  C L I C K S   ***************
    public function retrieveOpensClicks() {
      $urlItems = ['method'=>'messages', 'messageId'=>$this->messageId, 'subMethod'=>$this->subMethod];

      // Instantiate iModules API Obj passing URL params
      $myAPICall = new APICall( $urlItems);

	    $totalRecs = -1;

	    // Loop for getting all opens/clicks
	    // Call API passing in query parameters
	    while ($totalRecs == -1 || ($this->queryParams['startAt'] < $totalRecs)) {
		    $this->results = $myAPICall->doAPI( $this->queryParams);
        $msg = '<OpensClicks::retrieveOpensClicks> MsgId = ' . $this->messageId;

		    // Check for ERROR
		    if ($this->results instanceof \Exception) {
          $this->Log->writeToLog( '', $msg  . $this->results->getMessage());
			    return;  // Bail
		    }
		    // Check if TIMEOUT
		    if (isset($this->results->message)) {
			    $this->Log->writeToLog( '', "<MessageCounts::retrieveCount> MsgID: ". $this->messageId . " submethod = ". $this->subMethod . ", Error: " . $this->results->message);
			    return;
		    }

		    $totalRecs = $this->results->total;

		    // Process the opens/clicks
		    if (isset($this->results)) {
			    $recipientRepository = new \AlumniEmail\RecipientRepository($this->messageId);
			    foreach ($this->results->data as $rec) {
				    try {
					    self::saveFlag($recipientRepository, $rec);
				    } catch (Exception $e) {
              $this->Log->writeToLog( '', $msg . $e->getMessage());
				    }
			    }
		    }
        $this->queryParams['startAt'] += $this->queryParams['maxResults'];
	    }
    }

		//*****************  S A V E   F L A G   **********************************************
		public function saveFlag( $recipientRepository, $rec) {
      $rc = $recipientRepository->updateRecipientFlag( $this->subMethod, $rec);
      $msg = '<OpensClicks::saveFlag> RecipientID for Msg #' . $this->messageId . ' = ' . $rec->id;
      if ($rc instanceof \Exception) {
        $this->Log->writeToLog( '', $msg . ' -- D/B Error! ' . $rc->getMessage());
        return 0;
      }
      elseif (0 === $rc ) {
        $this->Log->writeToLog( '', $msg . ' Recipient is empty, not saved');
      }
      elseif (-1 === $rc) {
        $this->Log->writeToLog( '', $msg . ' Recipient info not changed, did not update');
      }
      else return 1;
    }

  }