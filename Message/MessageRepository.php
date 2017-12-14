<?php
  /**
   * Created by PhpStorm.
   * Message: mhugos
   * Date: 8/14/2017
   * Time: 4:46 PM
   */
  namespace AlumniEmail;

  use \PDO;
  require_once 'Message.php';
  require_once 'Log/Log.php';

  class MessageRepository
  {
      private $connection;
      private $config;

      public function __construct( \PDO $connection = null)
      {
          $this->connection = $connection;
          if ($this->connection === null) {
              $this->config = parse_ini_file( '/../config-email.ini');
              $this->connection = new \PDO(
	              'mysql:host=' . $this->config['host'] . ';dbname=' . $this->config['dbname'],
                      $this->config['username'],
                      $this->config['password']
                  );
              $this->connection->setAttribute(\PDO::ATTR_ERRMODE,\PDO::ERRMODE_EXCEPTION );
//              $this->connection->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, 'SET NAMES utf8mb4');
//              $this->connection->setAttribute(\PDO::MYSQL_ATTR_INIT_COMMAND, 'SET CHARACTER SET utf8mb4');
          }
      }

      //*******************************************
      public function saveMessage( $Message)
      {
          $dbMessage = self::findMessage( $Message->id);
          if ($dbMessage instanceof Message) {              // Message was found in d/b
              if (self::testDiff( $dbMessage, $Message)) {   // If some values changed, UPDATE it . . .
                  $rc = self::updateMessage($Message);
                  return $rc;
              }
              else return -1;                            // There were no changes, so no update was done
          }
          else {        // Otherwise, INSERT it . . .
              if (!empty($Message->emailName)) {        // Sometimes a message is empty, so check and return FALSE if empty
                  $rc = self::insertMessage($Message);
                  return $rc;
              }
              else {
                  return 0;
              }
          }
      }

      //*******************************************
      private function updateMessage( Message $Message)
      {
          $preparedStmt = '
            UPDATE messages
            SET';
          $comma = ' ';
          foreach ($Message as $key => $value) {
              if ($key !== 'id') {
                  $preparedStmt .= $comma . $key . " = :" . $key;
                  $comma = ', ';
              }
          }
          $preparedStmt .= ' WHERE id = :id';

          $rc = self::executeSQL( 'update', $preparedStmt, $Message);
          return $rc;
      }

      //*******************************************
      private function insertMessage( Message $Message) {
          $preparedStmt = '
            INSERT INTO messages (';
          $comma = ' ';
          foreach ($Message as $key => $value) {
              $preparedStmt .= $comma . $key;
              $comma = ', ';
          }
          $preparedStmt .= ') VALUES (';
          $comma = ' ';
          foreach ($Message as $key => $value) {
              $preparedStmt .= $comma . ":" . $key;
              $comma = ', ';
          }
          $preparedStmt .= ')';

          return self::executeSQL( 'insert', $preparedStmt, $Message);
      }

      //*******************************************
      public function updateCount( $countType, $messageId, $count)
      {
          $fieldName = $countType . 'Count';
          $Message = new Message( (object)[
            'id'      =>$messageId,
            $fieldName=>$count
          ]);
          $preparedStmt = '
            UPDATE messages
            SET ';
          $preparedStmt .= $fieldName . ' = ?';
          $preparedStmt .= ' WHERE id = ?';

          $stmt = $this->connection->prepare( $preparedStmt);
          $stmt->bindParam( 1, $Message->$fieldName, PDO::PARAM_NULL);
          $stmt->bindParam( 2, $Message->id, PDO::PARAM_INT);

          try {
              return $stmt->execute();
          }
          catch (\Exception $e) {
              return $e;
          }
      }

      //*******************************************
      private function executeSQL( $type, $preparedStmt, Message $Message) {
          $stmt = $this->connection->prepare( $preparedStmt);

          $stmt->bindParam(':id', $Message->id, PDO::PARAM_INT);
          $stmt->bindParam(':subCommunityId', $Message->subCommunityId, PDO::PARAM_INT);
          $stmt->bindParam(':emailName', $Message->emailName, PDO::PARAM_STR);
          $stmt->bindParam(':fromName', $Message->fromName, PDO::PARAM_STR);
          $stmt->bindParam(':fromAddress', $Message->fromAddress, PDO::PARAM_STR);
          $stmt->bindParam(':subjectLine', $Message->subjectLine, PDO::PARAM_STR);
          $stmt->bindParam(':preHeader', $Message->preHeader, PDO::PARAM_STR);
          $stmt->bindParam(':categoryName', $Message->categoryName, PDO::PARAM_STR);
          $stmt->bindParam(':scheduledDateTimestamp', $Message->scheduledDateTimestamp, PDO::PARAM_INT);
          $stmt->bindParam(':actualSendTimestamp', $Message->actualSendTimestamp, PDO::PARAM_INT);
          $stmt->bindParam(':dateAdded', $Message->dateAdded, PDO::PARAM_INT);
          $stmt->bindParam(':sentCount', $Message->sentCount, PDO::PARAM_INT);
          $stmt->bindParam(':deliversCount', $Message->deliversCount, PDO::PARAM_NULL);
          $stmt->bindParam(':bouncesCount', $Message->bouncesCount, PDO::PARAM_NULL);
          $stmt->bindParam(':recipientsCount', $Message->recipientsCount, PDO::PARAM_NULL);
          $stmt->bindParam(':opensCount', $Message->opensCount, PDO::PARAM_NULL);
          $stmt->bindParam(':clicksCount', $Message->clicksCount, PDO::PARAM_NULL);
          $stmt->bindParam(':linksCount', $Message->linksCount, PDO::PARAM_NULL);
	        $stmt->bindParam(':recipProcessed', $Message->recipProcessed, PDO::PARAM_INT);
//	        $stmt->bindParam(':recipLastStartAt', $Message->recipLastStartAt, PDO::PARAM_INT);
          try {
              return $stmt->execute();
          }
          catch (\Exception $e) {
              return $e;
          }
      }

      //*******************************************
      public function findMessage( $id)
      {
        $stmt = $this->connection->prepare('
            SELECT Messages.*
             FROM Messages
             WHERE id = :id
        ');

        $messageObj = new Message();
        $stmt->bindParam( ':id', $id);
        $stmt->execute();
        $stmt->setFetchMode( PDO::FETCH_INTO, $messageObj);
        $message =  $stmt->fetch();
        return $message;           // this returns a Message obj
      }

      //*******************************************
      public function findMessages( $from, $to)   // DEFAULT Mode=1: return just message ids
      {
        $stmt = $this->connection->prepare('
          SELECT id, emailName FROM Messages WHERE (actualSendTimestamp >= ' . $from . ') AND (actualSendTimestamp <= ' . $to . ') 
        ');
        $messageObj = new Message();
        try {
          $stmt->setFetchMode( PDO::FETCH_CLASS, 'Message');
          $stmt->execute();
          $messages = $stmt->fetchAll();
          return $messages;
        }
        catch (\Exception $e) {
            return $e;
        }
      }

		  //*******************************************
		  public function getMessageTitle( $id, $Log)
		  {
			  $preparedStmt = 'SELECT Messages.* FROM Messages WHERE id = :id';
			  $stmt = $this->connection->prepare( $preparedStmt);
			  $stmt->bindParam( ':id', $id);
			  $Log->writeToLog( '', 'Stmt = ' . $preparedStmt);
			  $messageObj = new Message();
			  $stmt->execute();
			  $stmt->setFetchMode( PDO::FETCH_INTO, $messageObj);
			  $message =  $stmt->fetch();
			  $Log->writeToLog( '', "Message Title is: " . $message->emailName);
			  return $message->emailName;           // this returns a Message obj
		  }

		  //*******************************************
		  public function getMessageTitles( array $ids, $Log)
		  {
			  $inQuery = implode(',', array_fill(0, count($ids), '?'));
			  $preparedStmt = 'SELECT Messages.* FROM Messages WHERE id IN (' . $inQuery . ')';
			  $stmt = $this->connection->prepare( $preparedStmt);
			  foreach ($ids as $k => $id)
				  $stmt->bindValue(($k+1), $id);
			  $Log->writeToLog( '', 'Stmt = ' . $preparedStmt);
			  $messageObj = new Message();
			  $stmt->execute();
			  $stmt->setFetchMode(PDO::FETCH_ASSOC);
			  $messages = $stmt->fetchAll();
			  $titles = array();
			  foreach ($messages as $message) {
			    $titles[$message['id']] = $message['emailName'];
			  };
			  return $titles;
		  }

	    //*******************************************
      public function getConnection() {
          return $this->connection;
      }

		  //*******************************************
		  public function isRecipProcessed( $id)
		  {
			  $message = self::findMessage( $id);
			  return $message->recipProcessed;
		  }

		  //*******************************************
		  public function getRecipTotal( $id)
		  {
			  $message = self::findMessage( $id);
			  return $message->recipientsCount;
		  }

      //*******************************************
      private function testDiff( $dbMessage, $retrievedMessage) {
          if (  $dbMessage->sentCount != $retrievedMessage->sentCount  ||
                $dbMessage->dateAdded != $retrievedMessage->dateAdded  ||
                $dbMessage->actualSendTimestamp != $retrievedMessage->actualSendTimestamp  ||
                $dbMessage->scheduledDateTimestamp != $retrievedMessage->scheduledDateTimestamp) {
              return TRUE;
          }
          else {
              return FALSE;
          }
      }
  }