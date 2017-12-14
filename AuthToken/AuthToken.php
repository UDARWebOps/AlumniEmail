<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/14/2017
   * Time: 4:44 PM
   */


  class AuthToken
   {
       public $id;
       public $token;
       public $expires;

       public function __construct( $data = null)
       {
           if (is_array( $data)) {
               if (isset( $data['id'])) $this->id = $data['id'];
               $this->token = $data['token'];
               $this->expires = $data['expires'];
           }
       }

      public function getToken() {
          return $this->token;
      }

   }