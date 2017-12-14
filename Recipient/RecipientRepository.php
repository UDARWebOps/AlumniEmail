<?php
  /**
   * Created by PhpStorm.
   * Recipient: mhugos
   * Date: 8/14/2017
   * Time: 4:46 PM
   */
  namespace AlumniEmail;

  use \PDO;
  require_once 'Recipient.php';

  class RecipientRepository
  {
      private $connection;
      private $config;
      private $table;
      private $totalRecs = 0;
      private $numberProcessed = 0;

      public function __construct( $msgID = NULL, \PDO $connection = NULL)
      {
        $this->connection = $connection;
        if ($this->connection === null) {
            $this->config = parse_ini_file( '/../config-email.ini');
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
        if (isset($msgID)) {
          $this->table = 'recipients' . $msgID;
          if (!self::tableExists()) {
            // todo -- try / catch
            self::createTable($this->table);
          }
        }
      }

      //*******************************************
      public function saveRecipient( $Recipient)
      {
          $dbRecipient = self::findRecipient( $Recipient->id);
          if ($dbRecipient instanceof Recipient) {            // Recipient already in d/b
	          if (self::testDiff( $dbRecipient, $Recipient)) {  // If some values changed, update
		          $rc = self::updateRecipient($Recipient);
		          return $rc;
	          }
	          else return -1;                            // There were no changes, so no update was done
          }
          else {        // Otherwise, INSERT it . . .
	          if (!empty($Recipient->emailAddress)) {        // Sometimes a message is empty, so check and return FALSE if empty
		          $rc = self::insertRecipient($Recipient);    // New recipient

		          return $rc;
	          }
	          else {
		          return 0;
	          }
          }
      }

      //*******************************************
      private function updateRecipient( $Recipient)
      {
      	// todo: this will update the clicks and opens flds, too.  Place logic that willon't
          $preparedStmt = '
            UPDATE ' . $this->table . '
            SET';
          $comma = ' ';
          foreach ($Recipient as $key => $value) {
              if ($key !== 'id') {
                  $preparedStmt .= $comma . $key . " = :" . $key;
                  $comma = ', ';
              }
          }
          $preparedStmt .= ' WHERE id = :id';

          $rc = self::executeSQL( 'update', $preparedStmt, $Recipient);
          return $rc;
      }

      //*******************************************
      private function insertRecipient( $Recipient) {
          $preparedStmt = '
            INSERT INTO ' . $this->table . ' (';
          $comma = ' ';
          foreach ($Recipient as $key => $value) {
              $preparedStmt .= $comma . $key;
              $comma = ', ';
          }
          $preparedStmt .= ') VALUES (';
          $comma = ' ';
          foreach ($Recipient as $key => $value) {
              $preparedStmt .= $comma . ":" . $key;
              $comma = ', ';
          }
          $preparedStmt .= ')';

          return self::executeSQL( 'insert', $preparedStmt, $Recipient);
      }

		  //*******************************************
	    // Opens or Clicks
		  public function updateRecipientFlag( $flagType, $openRec)
		  {
			  $fieldName = $flagType . 'Count';
			  $Recipient = new Recipient( (object)[
                        'id'      => $openRec->recipientId,
                        $fieldName=> 1
			  ]);
			  $preparedStmt = '
	            UPDATE ' . $this->table . ' 
	            SET ';
			  $preparedStmt .= $fieldName . ' = ?';
			  $preparedStmt .= ' WHERE id = ?';

			  $stmt = $this->connection->prepare( $preparedStmt);
			  $stmt->bindParam( 1, $Recipient->$fieldName, PDO::PARAM_INT);
			  $stmt->bindParam( 2, $Recipient->id, PDO::PARAM_INT);

			  try {
				  return $stmt->execute();
			  }
			  catch (\Exception $e) {
				  return $e;
			  }
		  }

      //*******************************************
      private function executeSQL( $type, $preparedStmt, $Recipient) {
          $stmt = $this->connection->prepare( $preparedStmt);

          $stmt->bindParam(':id', $Recipient->id, PDO::PARAM_INT);
          $stmt->bindParam(':emailAddress', $Recipient->emailAddress, PDO::PARAM_STR);
          $stmt->bindParam(':firstName', $Recipient->firstName, PDO::PARAM_STR);
          $stmt->bindParam(':lastName', $Recipient->lastName, PDO::PARAM_STR);
          $stmt->bindParam(':classYear', $Recipient->classYear, PDO::PARAM_INT);
          $stmt->bindParam(':memberId', $Recipient->memberId, PDO::PARAM_INT);
          $stmt->bindParam(':constituentId', $Recipient->constituentId, PDO::PARAM_STR);
          $stmt->bindParam(':dateAdded', $Recipient->dateAdded, PDO::PARAM_INT);
          $stmt->bindParam(':lastUpdated', $Recipient->lastUpdated, PDO::PARAM_INT);
          $stmt->bindParam(':opensCount', $Recipient->opensCount, PDO::PARAM_INT);
          $stmt->bindParam(':clicksCount', $Recipient->clicksCount, PDO::PARAM_INT);
          try {
              return $stmt->execute();
          }
          catch (\Exception $e) {
              return $e;
          }
      }

      //*******************************************
      public function tableExists()
      {
	      // Try a select statement against the table
	      // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
	      try {
		      $result = $this->connection->query("SELECT 1 FROM " . $this->table . " LIMIT 1");
	      } catch (\Exception $e) {
		      // We got an exception == table not found
		      return FALSE;
	      }

	      // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
	      return $result !== FALSE;
      }

			//*******************************************
			public function createTable( $tableName) {
			  $sql = "CREATE TABLE IF NOT EXISTS " . $tableName . " (
  								  `id` int(10) NOT NULL,
								    `emailAddress` varchar(256) NOT NULL,
								    `firstName` varchar(50) DEFAULT NULL,
								    `lastName` varchar(100) DEFAULT NULL,
								    `classYear` int(4) DEFAULT NULL,
								    `memberId` int(11) DEFAULT NULL,
								    `constituentId` varchar(10) DEFAULT NULL,
								    `dateAdded` bigint(20) DEFAULT NULL,
								    `lastUpdated` bigint(20) DEFAULT NULL,
								    `opensCount` int(11) NOT NULL DEFAULT '0',
								    `clicksCount` int(11) NOT NULL DEFAULT '0',
								    PRIMARY KEY (`id`)
								  ) ENGINE=MyISAM DEFAULT CHARSET=latin1;";
				try {
				  // use exec() because no results are returned
				  $result = $this->connection->exec( $sql);
				  echo "<br/>Table " . $tableName . " created successfully";
				  return true;
			  } catch (\Exception $e) {
				  return $e;
			  }
			}

      //*******************************************
      public function findRecipient( $id)
      {
      	$select = $this->table . '.*';
        $stmt = $this->connection->prepare('
            SELECT ' . $this->table . '.*
             FROM ' . $this->table . ' 
             WHERE id = :id
        ');
	      $recipientObj = new Recipient();
	      $stmt->bindParam(':id', $id);
	      $stmt->execute();
	      // Set the fetchmode to populate an instance of 'Recipient' class
	      $stmt->setFetchMode( PDO::FETCH_INTO, $recipientObj);
	      $recipient =  $stmt->fetch();
	      return $recipient;           // this returns a Recipient obj
      }

	  //*******************************************
      public function getCount() {
	      try {
		      $stmt = $this->connection->prepare("SELECT COUNT(*) FROM " . $this->table);
		      $stmt->setFetchMode( PDO::FETCH_ASSOC);
		      $stmt->execute();
		      $result = $stmt->fetch();
	      } catch (\Exception $e) {
		      // We got an exception
		      return FALSE;
	      }
	      return $result['COUNT(*)'];
      }

      //*******************************************
	    public function getProcessedCount() {
      	return $this->numberProcessed;
	    }
      //*******************************************
      public function getRecipients()
      {
//        if (isset( $msgID)) {
          $recipientObj = new Recipient();
//          $this->table = 'recipients' . $msgID;
	        $totalRecs = $this->getCount();
          $stmt = $this->connection->prepare('
              SELECT * FROM ' . $this->table . ' LIMIT ' . $this->numberProcessed . ',10000 
          ');
	        $stmt->setFetchMode( PDO::FETCH_ASSOC);
          $stmt->execute();
		      $this->numberProcessed += $stmt->rowCount();
		      return $stmt->fetchAll();
	        // fetchAll() will create an array

//        }
      }

	  //*******************************************
      public function getConnection() {
          return $this->connection;
      }


		  //*******************************************
		  private function testDiff( $dbRecipient, $retrievedRecipient) {
			  if (  $dbRecipient->emailAddress != $retrievedRecipient->emailAddress  ||
				  $dbRecipient->firstName != $retrievedRecipient->firstName  ||
				  $dbRecipient->lastName != $retrievedRecipient->lastName  ||
				  $dbRecipient->classYear != $retrievedRecipient->classYear  ||
				  $dbRecipient->memberId != $retrievedRecipient->memberId  ||
				  $dbRecipient->constituentId != $retrievedRecipient->constituentId ||
				  $dbRecipient->dateAdded != $retrievedRecipient->dateAdded ||
				  $dbRecipient->lastUpdated != $retrievedRecipient->lastUpdated)
			  {
				  return TRUE;
			  }
			  else {
				  return FALSE;
			  }
		  }
	  }