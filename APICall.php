<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/16/2017
   * Time: 3:56 PM
   */

  namespace AlumniEmail;

  require_once 'AuthToken/AuthTokenRepository.php';
  require_once 'iModsSendRequest.php';


  class APICall {
    public $mainMethod;
    public $messageId;
    public $subMethod;
    public $parameters;
    public $AuthTokenRepository;
    private $header;
    private $SendRequest;

    public function __construct( $data = null)
    {
        if (is_array( $data)) {
            $this->mainMethod = $data['method'];
            $this->messageId = ($data['messageId']) ? $data['messageId'] . '/' : $data['messageId'];  // if not null, add slash
            $this->subMethod = $data['subMethod'];
            $this->AuthTokenRepository = new AuthTokenRepository();
        }
      return $this;
    }

    //*******************************************
    public function doAPI( array $params = NULL) {
      $this->header = array(
        'Authorization: Bearer '. $this->AuthTokenRepository->getToken(),
        'cache-control: no-cache'
      );

      $ar = array(
        'sendMethod'=> "GET",
        'url'       => 'https://emapi.imodules.com/v2/' . $this->mainMethod . '/' . $this->messageId . $this->subMethod,
        'queryParams'    => $params,
        'header'    => $this->header
        );

      $this->SendRequest = new iModsSendRequest( $ar);
      try {
        return ($this->SendRequest->send_request());
      }
      catch(\Exception $e) {
        return $e;
      }
    }

    //*******************************************
    public function getMessages() {
      try {
        return ($this->SendRequest->send_request());
      }
      catch(\Exception $e) {
        return $e;
      }
    }

    //*******************************************
    public function getMessage() {
      try {
        return ($this->SendRequest->send_request( ));
      }
      catch(\Exception $e) {
        return $e;
      }
    }

    //*******************************************
    public function getBounces() {
      try {
        return ($this->SendRequest->send_request());
      }
      catch(\Exception $e) {
        return $e;
      }
    }

    //*******************************************
    public function getClicks() {
      try {
        return ($this->SendRequest->send_request());
      }
      catch(\Exception $e) {
        return $e;
      }
    }

    //*******************************************
    public function getDelivers() {
      try {
        return ($this->SendRequest->send_request());
      }
      catch(\Exception $e) {
        return $e;
      }
    }

    //*******************************************
    public function getRecipients() {
      try {
        return ($this->SendRequest->send_request());
      }
      catch(\Exception $e) {
        return $e;
      }
    }

    //*******************************************
    public function getOpens() {
      try {
        return ($this->SendRequest->send_request());
      }
      catch(\Exception $e) {
        return $e;
      }
    }

    //*******************************************
    public function getLinks() {
      try {
        return ($this->SendRequest->send_request());
      }
      catch(\Exception $e) {
        return $e;
      }
    }

  }