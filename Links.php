<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/14/2017
   * Time: 4:44 PM
   */

  namespace AlumniEmail;

  require_once 'APICall.php';
  require_once 'Link/LinkRepository.php';
  require_once 'Link/Link.php';


  class Links
  {
    protected $messageId;
    protected $subMethod;
    public $Log;

    public function __construct( $id = null)
    {
      if (isset( $id))
        $this->messageId = $id;
      else return FALSE;
      $this->subMethod = "links";
      $this->Log = new Log( __FILE__);
    }

    //*******************************************
    public function retrieveLinks( ) {
      $urlItems = ['method'=>'messages', 'messageId'=>$this->messageId, 'subMethod'=>$this->subMethod];
      // Instantiate iModules API Obj passing URL params
      $myAPICall = new \AlumniEmail\APICall( $urlItems);
      // Call the API
      $links = $myAPICall->doAPI();
      // Check for ERROR
      $msg = '<Links:: retrieveLinks> MsgId = ' . $this->messageId;
      if ($links instanceof \Exception) {
        $this->Log->writeToLog( '', $msg  . $links->getMessage());
        return;  // Bail
      }
      if (isset( $links)) {
        $linkRepository = new \AlumniEmail\LinkRepository();
        $linkCount = 0;
        foreach ($links as $link) {
          try {
            $link = new Link( $link);
            $link->msgId = $this->messageId;
            self::saveLink( $linkRepository, $link);
            $linkCount += 1;
          }
          catch (Exception $e) {
            $this->Log->writeToLog( '', $msg . 'linkID = ' . $link->id . $e->getMessage());
          }
        }
	      if (!$rc = $linkRepository->updateCount( "links", $this->messageId, $linkCount)) {
          $this->Log->writeToLog( '', $msg  . 'LinkCount for MsgID ' . $this->messageId . ': Error saving link count to message row.');
	      };

      }
    }

		//*****************  S A V E   L I N K  **********************************************
		public function saveLink( $linkRepository, $Link) {
      $rc = $linkRepository->saveLink( $Link);
			$msg = '<Links::saveLink> LinkID for Msg #' . $Link->msgId . ' = ' . $Link->id ;
      if ($rc instanceof \Exception) {
        $this->Log->writeToLog( '', $msg . ' -- D/B Errors! ' . $rc->getMessage());
        return 0;
      }
      elseif (0 === $rc ) {
        $this->Log->writeToLog( '', $msg . ' Link is empty, not saved');
      }
      elseif (-1 === $rc) {
        $this->Log->writeToLog( '', $msg . ' Link info not changed, did not update');
      }
      else return 1;
    }

		//*****************  G E T   L I N K S  **********************************************
		public function getLinks() {
      $linkRepository = new \AlumniEmail\LinkRepository();
      $links = $linkRepository->findAll( $this->messageId);
      return $links;
    }


    //*******************************************
    public function setCount( $type) {

    }

  }