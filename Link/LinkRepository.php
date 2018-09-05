<?php
  /**
   * Created by PhpStorm.
   * Link: mhugos
   * Date: 8/14/2017
   * Time: 4:46 PM
   */
  namespace AlumniEmail;

  use \PDO;
  require_once 'Link.php';
  require_once 'Message/MessageRepository.php';

  class LinkRepository
  {
      private $connection;
      private $config;

      public function __construct( \PDO $connection = null)
      {
          $this->connection = $connection;
          if ($this->connection === null) {
              $config = parse_ini_file( '/../config-email.ini');
              $this->connection = new \PDO(
	              'mysql:host=' . $config['host'] . ';dbname=' . $config['dbname'],
                      $config['username'],
                      $config['password']
                  );
              $this->connection->setAttribute(
                  PDO::ATTR_ERRMODE,
                  PDO::ERRMODE_EXCEPTION
              );
          }
      }

	  //*******************************************
	  public function saveLink( Link $Link)
	  {
		  $dbLink = self::findLink( $Link->id);
		  if ($dbLink instanceof Link) {              // Link was found in d/b
			  if (self::testDiff( $dbLink, $Link)) {   // If some values changed, UPDATE it . . .
				  $rc = self::updateLink($Link);
				  return $rc;
			  }
			  else return -1;                            // There were no changes, so no update was done
		  }
		  else {        // Otherwise, INSERT it . . .
			  if (!empty($Link->name)) {        // Sometimes a Link is empty, so check and return FALSE if empty
				  $rc = self::insertLink($Link);
				  return $rc;
			  }
			  else {
				  return 0;
			  }
		  }
	  }

      //*******************************************
      private function updateLink( Link $Link)
      {
          $preparedStmt = '
            UPDATE links
            SET';
          $comma = ' ';
          foreach ($Link as $key => $value) {
              if ($key !== 'id') {
                  $preparedStmt .= $comma . $key . " = :" . $key;
                  $comma = ', ';
              }
          }
          $preparedStmt .= ' WHERE id = :id';

          $rc = self::executeSQL( 'update', $preparedStmt, $Link);
          return $rc;
      }

      //*******************************************
      private function insertLink( Link $Link) {
          $preparedStmt = '
            INSERT INTO links (';
          $comma = ' ';
          foreach ($Link as $key => $value) {
              $preparedStmt .= $comma . $key;
              $comma = ', ';
          }
          $preparedStmt .= ') VALUES (';
          $comma = ' ';
          foreach ($Link as $key => $value) {
              $preparedStmt .= $comma . ":" . $key;
              $comma = ', ';
          }
          $preparedStmt .= ')';

          return self::executeSQL( 'insert', $preparedStmt, $Link);
      }

      //*******************************************
      public function updateCount( $countType, $messageId, $count) {
	      if (isset($count)) {
		      $messageRepository = new MessageRepository();
		      $rc                = $messageRepository->updateCount( $countType, $messageId, $count);
	      }

	      return $rc;
      }


      //*******************************************
      private function executeSQL( $type, $preparedStmt, Link $Link) {
          $stmt = $this->connection->prepare( $preparedStmt);

          $stmt->bindParam(':id', $Link->id, PDO::PARAM_INT);
          $stmt->bindParam(':msgId', $Link->msgId, PDO::PARAM_INT);
          $stmt->bindParam(':name', $Link->name, PDO::PARAM_STR);
          $stmt->bindParam(':url', $Link->url, PDO::PARAM_STR);
          try {
              return $stmt->execute();
          }
          catch (\Exception $e) {
              return $e;
          }
      }

      //*******************************************
      public function findLink( $id)
      {
        $stmt = $this->connection->prepare('
            SELECT Links.*
             FROM Links
             WHERE id = :id
        ');

        $linkObj = new Link();
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_INTO, $linkObj);
        $link =  $stmt->fetch();
        return $link;           // this returns a Link obj
      }

      //*******************************************
      public function findAll( $msgId)   // Find all links for a message
      {
        $stmt = $this->connection->prepare('
            SELECT *
             FROM Links
             WHERE msgId = :msgId
        ');
        $linkObj = new Link();
        $stmt->bindParam(':msgId', $msgId);
        $stmt->execute();
        $stmt->setFetchMode(PDO::FETCH_CLASS, 'Link');
        $result = $stmt->fetchAll();
        return $result;
      }

      public function getConnection() {
          return $this->connection;
      }

	  //*******************************************
	  private function testDiff( $dbLink, $retrievedLink) {
		  if (  $dbLink->name != $retrievedLink->name  ||
			  $dbLink->url != $retrievedLink->url) {
			  return TRUE;
		  }
		  else {
			  return FALSE;
		  }
	  }

  }