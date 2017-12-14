<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/14/2017
   * Time: 4:44 PM
   */
  namespace AlumniEmail;

  class Link
  {
    public $id;
    public $msgId;
    public $name;
    public $url;


    public function __construct( $data = null)
    {
      if (isset( $data->id)) {
        $this->id = (isset($data->id)) ? $data->id : NULL;
        $this->msgId = (isset($data->msgId)) ? $data->msgId : NULL;
        $this->name = (isset($data->name)) ? $data->name : NULL;
        $this->url = (isset($data->url)) ? $data->url : NULL;
      }
    }

    //*******************************************
    public function setCount( $type) {

    }

  }