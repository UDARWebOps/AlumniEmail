<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/14/2017
   * Time: 4:44 PM
   */

	namespace AlumniEmail;


	class RecipientStat
  {
    public $id;
    public $emailAddress;
    public $firstName;
    public $lastName;
    public $memberId;
    public $constituentId;
    public $emailReceives;
    public $emailOpens;
    public $emailClicks;


    public function __construct( $data = null)
    {
        if (isset( $data->id))
          $this->id = $data->id;
	      $this->emailAddress = (isset($data->emailAddress)) ? $data->emailAddress : '';
	      $this->firstName = (isset($data->firstName)) ? $data->firstName : NULL;
	      $this->lastName = (isset($data->lastName)) ? $data->lastName : NULL;
	      $this->memberId = (isset($data->memberId)) ? $data->memberId : NULL;
	      $this->constituentId = (isset($data->constituentId)) ? $data->constituentId : NULL;
	      $this->emailReceives = (isset($data->emailReceives)) ? $data->emailReceives : NULL;
	      $this->emailOpens = (isset($data->emailOpens)) ? $data->emailOpens : NULL;
	      $this->emailClicks = (isset($data->emailClicks)) ? $data->emailClicks : NULL;
    }

    //*******************************************
    public function setCount( $type) {

    }

  }