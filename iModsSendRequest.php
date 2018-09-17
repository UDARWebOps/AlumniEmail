<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/16/2017
   * Time: 12:28 PM
   */

  namespace AlumniEmail;


  class iModsSendRequest {
    public $sendMethod;
    public $url;
    public $queryParams = array();
    public $header;

    public function __construct( $data = null)
    {
//    	echo ("<br>In iModsSendRequeset");
      if (is_array( $data)) {
          $this->sendMethod = $data['sendMethod'];
          $this->url = $data['url'];
          $this->queryParams = $data['queryParams'];
          $this->header = $data['header'];
      }
    }

    //---------------------------------------------------------
  	//
    public function send_request() {
//	    echo("<br>In send_request");
      $parms = NULL;

  		if (isset( $this->queryParams)) {
  			$parms = self::stringify( $this->queryParams);
  		}
//	    echo("<br>before curl_init");
  		$curl = curl_init();
//	    echo("<br>after curl_init");
  		if ($this->sendMethod === "POST") {
  			curl_setopt_array ( $curl, array(
  					CURLOPT_URL => $this->url,
  					CURLOPT_RETURNTRANSFER => TRUE,
  					CURLOPT_POSTFIELDS => $parms,
  					CURLOPT_POST => 1
  				)
  			);
  		}
  		else {   // $method == GET
  			$this->url .= '?' . $parms;
  			curl_setopt_array($curl, array(
  				CURLOPT_URL => $this->url,
  				CURLOPT_RETURNTRANSFER => TRUE,
  				CURLOPT_ENCODING => "",
  				CURLOPT_MAXREDIRS => 10,
  				CURLOPT_TIMEOUT => 30,
  				CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  				CURLOPT_CUSTOMREQUEST => "GET",
  				CURLOPT_HTTPHEADER => $this->header
  			));
  		}
//	    echo("<br>about to exec curl");

  		$curl_response = curl_exec( $curl);
//	    echo("<br>just executed curl");
			if (curl_errno( $curl)) {
//				curl_close( $curl);
				throw new \Exception( curl_error( $curl));
			}
			if ((empty( $curl_response)) || (FALSE === $curl_response)) {
				curl_close( $curl);
				throw new \Exception( "iModules returned FALSE or there was no response body");
			}

  		curl_close( $curl);

  		$decoded = json_decode( $curl_response);
  		if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
  		  die('error occurred: ' . $decoded->response->errormessage);
  		}

  		return $decoded;
    }

    //---------------------------------------------------------
    private function stringify( $data) {
      // put fields into url-encoded key-value pairs
      $fields_string = '';
        $len = sizeof( $data);
        $i = 1;
      foreach ($data as $key => $value) {
        $fields_string .= $key . '=' . $value;
        if ($i < $len) {$fields_string .= '&';}
        $i++;

      }
      return $fields_string;
    }
  }