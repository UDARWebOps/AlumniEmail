<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/14/2017
   * Time: 4:44 PM
   */

	namespace AlumniEmail;


	class Recipient
  {
    public $id;
    public $emailAddress;
    public $firstName;
    public $lastName;
    public $classYear;
    public $memberId;
    public $constituentId;
    public $dateAdded;
    public $lastUpdated;
    public $opensCount;
    public $clicksCount;


    public function __construct( $data = null)
    {
        if (isset( $data->id))
          $this->id = $data->id;
	      $this->emailAddress = (isset($data->emailAddress)) ? $data->emailAddress : '';
	      $this->firstName = (isset($data->firstName)) ? $data->firstName : NULL;
	      $this->lastName = (isset($data->lastName)) ? $data->lastName : NULL;
	      $this->classYear = (isset($data->classYear)) ? $data->classYear : NULL;
	      $this->memberId = (isset($data->memberId)) ? $data->memberId : NULL;
	      $this->constituentId = (isset($data->constituentId)) ? $data->constituentId : NULL;
	      $this->dateAdded = (isset($data->dateAdded)) ? $data->dateAdded : NULL;
	      $this->lastUpdated = (isset($data->lastUpdated)) ? $data->lastUpdated : NULL;
	      $this->opensCount = (isset($data->opensCount)) ? $data->opensCount : 0;
	      $this->clicksCount = (isset($data->clicksCount)) ? $data->clicksCount : 0;
    }

    //*******************************************
    public function setCount( $type) {

    }

  }