<?php
  /**
   * Created by PhpStorm.
   * Recipient: mhugos
   * Date: 8/14/2017
   * Time: 4:46 PM
   */
  namespace AlumniEmail;

  use \PDO;
  require_once 'MessageTrack.php';

  class MessageTrackRepository {
	  private $connection;
	  private $config;
	  private $table;
	  private $messageIDs = array();

	  public function __construct($year, $month, \PDO $connection = NULL) {
		  $this->connection = $connection;
		  if ($this->connection === NULL) {
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
		  $this->table = 'message_track_' . $year . '_' . $month;
		  if (!self::tableExists()) {
			  // todo -- try / catch
			  self::createTable($this->table);
		  }
	  }

	  //*******************************************
	  // $mode:  null: create new row
	  //          '1': message processed for recipient stats
	  //          '2': message processed in csv file
	  public function saveMessageTrack($msgID = NULL, $mode = NULL) {
		  $dbMessageTrack = self::findMessageTrack($msgID);

		  if ($dbMessageTrack instanceof MessageTrack) {
			  if (isset($dbMessageTrack->id)) {            // Message Track record is already in d/b
				  if (1 === $mode) {
					  $dbMessageTrack->statsProcessed = 1;
				  }
				  elseif (2 === $mode) {
					  $dbMessageTrack->csvProcessed = 1;
				  }
				  $rc = self::updateMessageTrack($dbMessageTrack);

				  return $rc;
			  }
			  else {        // Otherwise, INSERT it . . .
				  $dbMessageTrack->id             = $msgID;
				  $dbMessageTrack->statsProcessed = 0;
				  $dbMessageTrack->csvProcessed   = 0;
				  $rc                             = self::insertMessageTrack($dbMessageTrack);    // New message track record

				  return $rc;
			  }
		  }
	  }

	  //*******************************************
	  private function updateMessageTrack(&$dbMessageTrack) {
		  $preparedStmt = '
            UPDATE ' . $this->table . '
            SET';
		  $comma        = ' ';
		  foreach ($dbMessageTrack as $key => $value) {
			  if ($key !== 'id') {
				  $preparedStmt .= $comma . $key . " = :" . $key;
				  $comma        = ', ';
			  }
		  }
		  $preparedStmt .= ' WHERE id = :id';

		  $rc = self::executeSQL('update', $preparedStmt, $dbMessageTrack);

		  return $rc;
	  }

	  //*******************************************
	  private function insertMessageTrack($MessageTrack) {
		  $preparedStmt = '
            INSERT INTO ' . $this->table . ' (';
		  $comma        = ' ';
		  foreach ($MessageTrack as $key => $value) {
			  $preparedStmt .= $comma . $key;
			  $comma        = ', ';
		  }
		  $preparedStmt .= ') VALUES (';
		  $comma        = ' ';
		  foreach ($MessageTrack as $key => $value) {
			  $preparedStmt .= $comma . ":" . $key;
			  $comma        = ', ';
		  }
		  $preparedStmt .= ')';

		  return self::executeSQL('insert', $preparedStmt, $MessageTrack);
	  }

	  //*******************************************
	  private function executeSQL($type, $preparedStmt, $MessageTrack) {
		  $stmt = $this->connection->prepare($preparedStmt);

		  $stmt->bindParam(':id', $MessageTrack->id, PDO::PARAM_INT);
		  $stmt->bindParam(':statsProcessed', $MessageTrack->statsProcessed, PDO::PARAM_INT);
		  $stmt->bindParam(':csvProcessed', $MessageTrack->csvProcessed, PDO::PARAM_INT);
		  try {
			  return $stmt->execute();
		  } catch (\Exception $e) {
			  return $e;
		  }
	  }

	  //*******************************************
	  public function tableExists() {
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
	  public function createTable($tableName) {
		  $sql = "CREATE TABLE IF NOT EXISTS `" . $this->table . "` (
								  `id` int(10) NOT NULL,
								  `statsProcessed` int(1) NOT NULL,
								  `csvProcessed` int(1) NOT NULL
								) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
		  try {
			  // use exec() because no results are returned
			  $result = $this->connection->exec($sql);
			  echo "<br/>Table " . $tableName . " created successfully";

			  return TRUE;
		  } catch (\Exception $e) {
			  return $e;
		  }
	  }

	  public function isProcessed( $id) {
	  	$MessageTrack = self::findMessageTrack( $id);
	  	return $MessageTrack->statsProcessed;
	  }

	  //*******************************************
	  public function findMessageTrack( $id) {
		  $stmt = $this->connection->prepare('
            SELECT ' . $this->table . '.*
             FROM ' . $this->table . ' 
             WHERE id = :id
        ');
		  $MessageTrackObj = new MessageTrack();
		  $stmt->bindParam(':id', $id);
		  $stmt->execute();
		  // Set the fetchmode to populate an instance of 'MessageTrackObj' class
		  $stmt->setFetchMode(PDO::FETCH_INTO, $MessageTrackObj);
		  $MessageTrack = $stmt->fetch();

		  return $MessageTrackObj;           // this returns a MessageTrack object
	  }

	  //*******************************************
	  public function findAll()   // Get all records from message tracking table
	  {
		  $MessageTrackObj = new MessageTrack();
		  $stmt            = $this->connection->prepare('
            SELECT * FROM ' . $this->table . ' 
        ');
		  $stmt->setFetchMode(PDO::FETCH_ASSOC);
		  $stmt->execute();

		  // fetchAll() will create an array
		  return $stmt->fetchAll();
	  }

	  public function getConnection() {
		  return $this->connection;
	  }

	  //*******************************************
	  private function checkMessageID($Recipient, &$dbMessageTrack, $msgID, $var) {
		  $varName    = 'email' . $var;
		  $messageIDs = unserialize($dbMessageTrack->$varName);
		  if (!in_array($msgID, $messageIDs)) {
			  $flag = 0;
			  if (($var == 'Opens' || $var == 'Clicks')) {
				  $countVar = strtolower($var) . 'Count';
				  if ($Recipient->$countVar > 0) {
					  $flag = 1;
				  }
			  }
			  else {
				  $flag = 1;
			  }
			  if (1 === $flag) {
				  $messageIDs[]             = $msgID;  // add msg ID to array
				  $dbMessageTrack->$varName = serialize($messageIDs);   // update the field
			  }
		  }
	  }

	  //*******************************************
	  public function isMonthProcessed() {
		  $messages = $this::findAll();
		  $IDs = array();
		  foreach ($messages as $message) {
			  if (!$message['statsProcessed']) {
				  return FALSE;
			  }
			  $IDs[] = $message['id'];
		  }

		  return $IDs;
	  }

	  //*******************************************
	  public function isMessageStatsProcessed() {
		  $messages = $this::findAll();
		  $IDs = array();
		  foreach ($messages as $message) {
			  if (!$message['statsProcessed']) {
				  return FALSE;
			  }
			  $IDs[] = $message['id'];
		  }

		  return $IDs;
	  }
  }

