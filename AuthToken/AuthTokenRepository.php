<?php
  /**
   * Created by PhpStorm.
       * AuthToken: mhugos
   * Date: 8/14/2017
   * Time: 4:46 PM
   */
  namespace AlumniEmail;

  use \PDO;
  require_once 'AuthToken.php';
  require_once 'iModsSendRequest.php';

  class AuthTokenRepository
  {
      private $connection;
      private $config;
      private $AuthToken;

      public function __construct( \PDO $connection = null)
      {
          $this->connection = $connection;
          if ($this->connection === null) {
	          $temp = dirname(__FILE__);
	          $config_path = dirname($temp, 1);
	          $this->config = parse_ini_file( $config_path . '\config-email.ini');
              $this->connection = new \PDO(
                      'mysql:host=' . $this->config['host'] . ';dbname=' . $this->config['dbname'],
                      $this->config['username'],
                      $this->config['password']
                  );
              $this->connection->setAttribute(
                  PDO::ATTR_ERRMODE,
                  PDO::ERRMODE_EXCEPTION
              );
          }
          $this->AuthToken = self::getAuthToken();
      }

      public function getAuthToken()
      {
          $stmt = $this->connection->prepare('
              SELECT authtoken.*
               FROM authtoken
               WHERE 1
          ');
          $stmt->execute();

          // Set the fetchmode to populate an instance of 'AuthToken'
          $stmt->setFetchMode(PDO::FETCH_CLASS, 'AuthToken');
          $AuthToken =  $stmt->fetch();
          $AuthToken = ($AuthToken == FALSE) ? new \AuthToken(array('id'=>0,'token'=>null,'expires'=>null)) : $AuthToken;
          $now = time();
          if (strtotime($AuthToken->expires) < $now) {                  // Token in d/b has expired
              $newToken = $this->getNewToken();                         // Get a new one from iMods
              $AuthToken->token = $newToken->access_token;              // Extract the token value from iMods return values
              $AuthToken->expires = date("Y-m-d H:i:s", $now + 86300);  // Adding a few seconds less than a 24 hour period
              $this->saveToken( $AuthToken);                            // Save the new one to the d/b
          }
          return $AuthToken;
      }

      private function getNewToken() {
          $requestData = array(
            'sendMethod' => 'POST',
            'url' => 'http://nyu.imodules.com/apiservices/authenticate',
            'queryParams' => array(
              'client_id' => 1068,
              'client_secret' => '23547CCF-DCB1-4E3D-A4AA-F392DD092F66',
              'grant_type' => 'email_api_auth_key'
            ),
            'header' => NULL
          );

          $request = new \AlumniEmail\iModsSendRequest( $requestData);
          $response = $request->send_request();
          return $response;
      }

      private function saveToken( \AuthToken $AuthToken) {
          // Check for existence of record in d/b
          $stmt = $this->connection->prepare('
              SELECT authtoken.*
              FROM authtoken
              WHERE id = ' . $AuthToken->id
          );
          $stmt->bindParam(':id', $AuthToken->id);
          $rc = $stmt->execute();
          if ($stmt->fetch()) {  // record exists, so UPDATE
              $stmt = $this->connection->prepare('
                  UPDATE authtoken
                  SET token = :token,
                      expires = :expires
                  WHERE id = :id
              ');
              $stmt->bindParam(':id', $AuthToken->id);
              $stmt->bindParam(':token', $AuthToken->token);
              $stmt->bindParam(':expires', $AuthToken->expires);

              return $stmt->execute();
          }
          else {  // record does not exist, yet, so INSERT
              $stmt = $this->connection->prepare('
                  INSERT INTO authtoken (id, token, expires)
                  VALUES (:id, :token, :expires)
              ');
              $stmt->bindParam(':id', $AuthToken->id);
              $stmt->bindParam(':token', $AuthToken->token);
              $stmt->bindParam(':expires', $AuthToken->expires);

              return $stmt->execute();
          }
      }

      public function getToken() {
          return $this->AuthToken->token;
      }
  }