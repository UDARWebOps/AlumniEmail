<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/14/2017
   * Time: 4:44 PM
   */
  namespace AlumniEmail;


  class MessageTrack
   {
      public $id;
      public $statsProcessed;
      public $csvProcessed;

      public function __construct( $data = null)
       {
               if (isset( $data->id))
                   $this->id = $data->id;
               $this->statsProcessed = (isset($data->statsProcessed)) ? $data->statsProcessed : NULL;
               $this->csvProcessed = (isset($data->csvProcessed)) ? $data->csvProcessed : NULL;
           }
   }