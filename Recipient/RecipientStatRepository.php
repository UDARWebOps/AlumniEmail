<?php
  /**
   * Created by PhpStorm.
   * Recipient: mhugos
   * Date: 8/14/2017
   * Time: 4:46 PM
   */
  namespace AlumniEmail;

  use \PDO;
  require_once 'RecipientStat.php';
  require_once 'Log/Log.php';


  class RecipientStatRepository
  {
      private $connection;
      private $config;
      private $table;
      private $messageIDs = array();
      private $year;
      private $month;
      private $csvFile;

      public function __construct( $year, $month, \PDO $connection = null)
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
	      $this->table = 'recipient_stat_' . $year . '_' . $month;
				if (!self::tableExists()){
					// todo -- try / catch
					self::createTable( $this->table);
				}
      }

      //*******************************************
      public function saveRecipientStat( $Recipient, $msgID = NULL) {
        $vars = array( 'Receives', 'Opens', 'Clicks');
        $dbRecipientStat = self::findRecipientStat( $Recipient->id);

        if ($dbRecipientStat instanceof RecipientStat) {
          if (isset( $dbRecipientStat->id)) {            // Recipients Stat record is already in d/b

	          // loop thru the vars and update the received, opened, or clicked fields
	          // $dbRecipientStat is passed in by reference -- updates to it are direct
	          foreach ($vars as $var) {
              self::checkMessageID( $Recipient, $dbRecipientStat, $msgID, $var);
            }
            // iMods admins can receive an inordinate amount of emails, so cut them off if json string is longer than 1000
            if (strlen( $dbRecipientStat->emailReceives) <= 1000 ) {
	            $rc = self::updateRecipientStat($dbRecipientStat);
	            return $rc;
            }
          }
          else {        // Otherwise, INSERT it . . .
            $dbRecipientStat->id = $Recipient->id;
            $dbRecipientStat->firstName = $Recipient->firstName;
            $dbRecipientStat->lastName = $Recipient->lastName;
            $dbRecipientStat->emailAddress = $Recipient->emailAddress;
            $dbRecipientStat->memberId = $Recipient->memberId;
            $dbRecipientStat->constituentId = $Recipient->constituentId;
            $dbRecipientStat->emailReceives = serialize( array($msgID));
            if ($Recipient->opensCount > 0) {
              $dbRecipientStat->emailOpens = serialize( array($msgID));
            }
            if ($Recipient->clicksCount > 0) {
              $dbRecipientStat->emailClicks = serialize( array($msgID));
            }
            $rc = self::insertRecipientStat( $dbRecipientStat);    // New recipient
            return $rc;
          }
        }
      }

      //*******************************************
      private function updateRecipientStat( $RecipientStat)
      {
          $preparedStmt = '
            UPDATE ' . $this->table . '
            SET';
          $comma = ' ';
          foreach ($RecipientStat as $key => $value) {
              if ($key !== 'id') {
                  $preparedStmt .= $comma . $key . " = :" . $key;
                  $comma = ', ';
              }
          }
          $preparedStmt .= ' WHERE id = :id';

          $rc = self::executeSQL( 'update', $preparedStmt, $RecipientStat);
          return $rc;
      }

      //*******************************************
      private function insertRecipientStat( $RecipientStat) {
          $preparedStmt = '
            INSERT INTO ' . $this->table . ' (';
          $comma = ' ';
          foreach ($RecipientStat as $key => $value) {
              $preparedStmt .= $comma . $key;
              $comma = ', ';
          }
          $preparedStmt .= ') VALUES (';
          $comma = ' ';
          foreach ($RecipientStat as $key => $value) {
              $preparedStmt .= $comma . ":" . $key;
              $comma = ', ';
          }
          $preparedStmt .= ')';

          return self::executeSQL( 'insert', $preparedStmt, $RecipientStat);
      }

      //*******************************************
      private function executeSQL( $type, $preparedStmt, $RecipientStat) {
          $stmt = $this->connection->prepare( $preparedStmt);

          $stmt->bindParam(':id', $RecipientStat->id, PDO::PARAM_INT);
          $stmt->bindParam(':emailAddress', $RecipientStat->emailAddress, PDO::PARAM_STR);
          $stmt->bindParam(':firstName', $RecipientStat->firstName, PDO::PARAM_STR);
          $stmt->bindParam(':lastName', $RecipientStat->lastName, PDO::PARAM_STR);
          $stmt->bindParam(':memberId', $RecipientStat->memberId, PDO::PARAM_INT);
          $stmt->bindParam(':constituentId', $RecipientStat->constituentId, PDO::PARAM_STR);
          $stmt->bindParam(':emailReceives', $RecipientStat->emailReceives, PDO::PARAM_STR);
          $stmt->bindParam(':emailOpens', $RecipientStat->emailOpens, PDO::PARAM_STR);
          $stmt->bindParam(':emailClicks', $RecipientStat->emailClicks, PDO::PARAM_STR);
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
			  $sql = "CREATE TABLE IF NOT EXISTS `" . $this->table . "` (
							  `id` int(10) NOT NULL,
							  `emailAddress` varchar(256) NOT NULL,
							  `firstName` varchar(50) DEFAULT NULL,
							  `lastName` varchar(100) DEFAULT NULL,
							  `memberId` int(11) DEFAULT NULL,
							  `constituentId` int(10) DEFAULT NULL,
							  `emailReceives` varchar(500) DEFAULT NULL,
							  `emailOpens` varchar(500) DEFAULT NULL,
							  `emailClicks` varchar(500) DEFAULT NULL,
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
      public function findRecipientStat( $id)
      {
        $stmt = $this->connection->prepare('
            SELECT ' . $this->table . '.*
             FROM ' . $this->table . ' 
             WHERE id = :id
        ');
	      $recipientStatObj = new RecipientStat();
	      $stmt->bindParam(':id', $id);
	      $stmt->execute();
	      // Set the fetchmode to populate an instance of 'RecipientStatObj' class
	      $stmt->setFetchMode( PDO::FETCH_INTO, $recipientStatObj);
	      $recipientStat =  $stmt->fetch();
	      return $recipientStatObj;           // this returns a RecipientStat object
      }

		  //*******************************************
		  public function findAll( $Log)   // Get all recipient records in the recipient stat table
		  {
			  $Log->writeToLog( '', 'In findAll.  Table = ' . $this->table);
//			  $recipientStatObj = new RecipientStat();
			  $preparedStmt = 'SELECT * FROM ' . $this->table;
			  $Log->writeToLog( '', 'MySQL stmt = "' . $preparedStmt);
			  $stmt = $this->connection->prepare($preparedStmt);
//			  $stmt = $this->connection->prepare('SELECT * FROM ' . $this->table . '');
//			  $Log = new Log( __FILE__, 'stat');
			  $Log->writeToLog( '', 'After PREPARE stmt.');
			  $stmt->setFetchMode( PDO::FETCH_ASSOC);
			  $stmt->execute();

			  // fetchAll() will create an array
			  return $stmt->fetchAll();
		  }

		  public function getConnection() {
	          return $this->connection;
	      }

		  //*******************************************
	    // $dbRecipientStat is passed in by reference -- updates to it are direct
		  private function checkMessageID( $Recipient, &$dbRecipientStat, $msgID, $var) {
        // create the column name e.g. "emailReceives" "emailClicks" "emailOpens"
      	$varName = 'email' . $var;
        $messageIDs = unserialize( $dbRecipientStat->$varName);
        if (!in_array( $msgID, $messageIDs)) {
        	$flag = 0;
          if (($var == 'Opens' || $var == 'Clicks')) {
            $countVar = strtolower($var) . 'Count';
            if ($Recipient->$countVar > 0) {
              $flag = 1;
            }
          } else {
            $flag = 1;
          }
          if (1 === $flag) {
            $messageIDs[] = $msgID;  // add msg ID to array
            $dbRecipientStat->$varName = serialize( $messageIDs);   // update the field
          }
        }
		  }

	  }