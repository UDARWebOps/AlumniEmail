<?php
	/**
	 * Created by PhpStorm.
	 * User: mg5139
	 * Date: 11/1/2017
	 * Time: 8:10 PM
	 */

	namespace AlumniEmail;

	use \DateTime;

	class Log {
    const RUN_LOG = 'usage-log.txt';
    const STAT_LOG = 'stat-log.txt';

    public $log_file;
		public $handle;
		public $timestamp;
		public $from_filename;

    //************  C O N S T R U C T   *******************************
    //  Default log file is the usage-log.txt file
    //  Log type: 'usage', 'stat'
		public function __construct( $from_filename, $log='usage') {
      $this->from_filename = $from_filename;    // Name of file that is opening the log
      $this->log_file = ('usage' === $log) ? self::RUN_LOG : self::STAT_LOG;
      $this->handle = fopen( $this->log_file, 'a') or die('Cannot open file:  '. $this->log_file); // this implicitly creates file
		}

    //************  W R I T E   T O   L O G   *******************************
		public function writeToLog( $mode = '', $message = NULL) {

			switch ($mode) {
				case 'initiate':
					$query_items = '';
					// get current timestamp
          $TZ = new \DateTimeZone("America/New_York");
          $this->timestamp = new \DateTime('now', $TZ);
					// get query params from request
					foreach ($_REQUEST as $item => $value) {
						$query_items .= $item . " = " . $value . ", ";
					}
					// write initiator line stating timestamp and query items
					$this->write_text = "\n". $this->timestamp->format('Y-m-d h:i:s A') . ", INITIATOR: " . $this->from_filename . ': ' . $query_items;
					break;

				case '';
          // get current timestamp
					$TZ = new \DateTimeZone("America/New_York");
					$this->timestamp = new \DateTime('now', $TZ);
          // write message line
					$this->write_text = "\n". $this->timestamp->format('Y-m-d h:i:s A') . ", " . $this->from_filename . ': ' . $message;
					break;
			}
			fwrite( $this->handle, $this->write_text);
//			fclose( $this->handle);
		}

		public function __destruct() {
			fclose( $this->handle);
		}
	}

//	$mylog = new Log();
//	echo 'hello';