<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/14/2017
   * Time: 4:44 PM
   */

	namespace AlumniEmail;

	require_once '/Log/Log.php';

	class MessageCounts
  {
    protected $id;
    protected $messageId;
    protected $subMethod;
	  public $queryParams = array(  'startAt' => 0,
	                                'maxResults' => 1);   // defaults
		public $Log;

		//**************  C O N S T R U C T  *******************************
    public function __construct( $id = null, $type = '')
    {
      if (isset( $id))
        $this->messageId = $id;
      else return FALSE;
      $this->subMethod = $type;
	    $this->Log = new Log( __FILE__);
    }

	  //***********  R E T R I E V E   C O U N T  ***************
    //  This calls iMods API to get count and updates the count in the message record in d/b
    public function retrieveCount() {
      $urlItems = ['method'=>'messages', 'messageId'=>$this->messageId, 'subMethod'=>$this->subMethod];

      // Instantiate iModules API Obj passing URL params
      $myAPICall = new \AlumniEmail\APICall( $urlItems);

      // Call the API passing in query parameters (we don't want rows, just the count)
      $results = $myAPICall->doAPI( $this->queryParams);

      // Check for ERROR
      if ($results instanceof \Exception) {
	      $this->Log->writeToLog( '', "<MessageCounts::retrieveCount> MsgID: ". $this->messageId . " submethod = ". $this->subMethod . ", Error: " . $results->getMessage());
        return;  // Bail
      }
      // Check if TIMEOUT
	    if (isset($results->message)) {
		    $this->Log->writeToLog( '', "<MessageCounts::retrieveCount> MsgID: ". $this->messageId . " submethod = ". $this->subMethod . ", Error: " . $results->message);
	    }
      // Process count
      if (isset( $results->data)) {
        $messageRepository = new \AlumniEmail\MessageRepository();
        $rc = $messageRepository->updateCount( $this->subMethod, $this->messageId, $results->total);
	      return $rc;
      }

    }

    //*******************************************
    public function setCount( $type) {

    }

  }