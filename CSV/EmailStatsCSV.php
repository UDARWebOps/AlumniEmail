<?php
	/**
	 * Created by PhpStorm.
	 * User: mg5139
	 * Date: 11/16/2017
	 * Time: 4:32 PM
	 */

	namespace AlumniEmail;

	require_once 'Message/MessageRepository.php';
	require_once 'Log/Log.php';


	class EmailStatsCSV {
		private $csvFile;
		private $year;
		private $month;
		private $Log;
		private $line = '';
		private $IDs;
		private $titles;
		private $MessageRepository;
		private $justTitlesLine = array( '', '', '', '', '', '', '');


		//**************  C O N S T R U C T  *******************************
		public function __construct( $year = NULL, $month = NULL, $Log, array $IDs) {
			$this->year    = (isset($year)) ? $year : '';
			$this->month   = (isset($month)) ? $month : '';
			$this->Log     = $Log;
			$this->csvFile = 'emailStats_' . $year . '_' . $month . '.csv';
			$this->IDs     = $IDs;
			$this->MessageRepository = new MessageRepository();
			$this->titles = $this->MessageRepository->getMessageTitles( $this->IDs, $this->Log);
		}

		//**************  W R I T E   C S V   *******************************
		public function writeCSV() {
			$RecipientStatRepository = new RecipientStatRepository( $this->year, $this->month);

			$this->handle = fopen( $this->csvFile, 'a') or die('Cannot open file:  ' . $this->csvFile); // this implicitly creates file
			// write column headers
			fputcsv( $this->handle, array(
				'Email Address',
				'Advance ID',
				'First Name',
				'Last Name',
				'Number of Emails Sent',
				'Number of Emails Opened',
				'Number of Emails Clicked',
				'Emails Opened - Name',
				'Emails Clicked - Name'
			));

			$RecipientsStats   = $RecipientStatRepository->findAll( $this->Log);
			foreach ($RecipientsStats as $recipientStat) {
				$receives = (isset($recipientStat['emailReceives'])) ? unserialize( $recipientStat['emailReceives']) : array();
				$opens    = (isset($recipientStat['emailOpens'])) ? unserialize( $recipientStat['emailOpens']) : array();
				$clicks   = (isset($recipientStat['emailClicks'])) ? unserialize( $recipientStat['emailClicks']) : array();
				$this->line     = array(
					$recipientStat['emailAddress'],
					$recipientStat['constituentId'],
					$recipientStat['firstName'],
					$recipientStat['lastName'],
					count($receives),
					count($opens),
					count($clicks)
				);
				self::writeRecipientLines( $opens, $clicks);
			}
			// Close the file
			fclose($this->handle);
		}

		//***********************************************************
		private function writeRecipientLines( array $openedMessageIDs, array $clickedMessageIDs) {
			$messageTitlesOpened  = self::getMessageTitles( $openedMessageIDs);
			$messageTitlesClicked = self::getMessageTitles( $clickedMessageIDs);
			$this->line[] .= (isset( $messageTitlesOpened[0])) ? $messageTitlesOpened[0] : '';
			$this->line[] .= (isset( $messageTitlesClicked[0])) ? $messageTitlesClicked[0] : '';
			// this writes the first line of recipient info
			fputcsv( $this->handle, $this->line);

			// now see if there are additional email titles to print
			if ((count($messageTitlesOpened) > 1) || (count($messageTitlesClicked) > 1) ) {
				$i = 1;
				while ((count($messageTitlesOpened) > $i) || (count($messageTitlesClicked) > $i)) {
					$this->line = $this->justTitlesLine;
					$this->line[] .= (isset($messageTitlesOpened[$i])) ? $messageTitlesOpened[$i] : '';
					$this->line[] .= (isset($messageTitlesClicked[$i])) ? $messageTitlesClicked[$i] : '';
					fputcsv( $this->handle, $this->line);
					$i+= 1;
				}
			}
		}

		//***********************************************************
		private function getMessageTitles( array $MessageIDs) {
			$titles = array();
			foreach ($MessageIDs as $msgID) {
				$titles[] = $this->titles[$msgID];
			}
			return $titles;
		}
	}
