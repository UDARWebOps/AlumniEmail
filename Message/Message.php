<?php
  /**
   * Created by PhpStorm.
   * User: mhugos
   * Date: 8/14/2017
   * Time: 4:44 PM
   */
  namespace AlumniEmail;


  class Message
   {
      public $id;
      public $subCommunityId;
      public $emailName;
      public $fromName;
      public $fromAddress;
      public $subjectLine;
      public $preHeader;
      public $categoryName;
      public $scheduledDateTimestamp;
      public $actualSendTimestamp;
      public $dateAdded;
      public $sentCount;
      public $deliversCount;
      public $bouncesCount;
      public $recipientsCount;
      public $opensCount;
      public $clicksCount;
      public $linksCount;
      public $recipProcessed;
//      public $recipLastStartAt;

      public function __construct( $data = null)
       {
//           if (is_object( $data)) {
               if (isset( $data->id))
                   $this->id = $data->id;
               $this->subCommunityId = (isset($data->subCommunityId)) ? $data->subCommunityId : NULL;
               $this->emailName = (isset($data->emailName)) ? $data->emailName : NULL;
               $this->fromName = (isset($data->fromName)) ? $data->fromName : NULL;
               $this->fromAddress = (isset($data->fromAddress)) ? $data->fromAddress : NULL;
               $this->subjectLine = (isset($data->subjectLine)) ? $data->subjectLine : NULL;
               $this->preHeader = (isset($data->preHeader)) ? $data->preHeader : NULL;
               $this->categoryName = (isset($data->categoryName)) ? $data->categoryName : NULL;
               $this->scheduledDateTimestamp = (isset($data->scheduledDateTimestamp)) ? $data->scheduledDateTimestamp : NULL;
               $this->actualSendTimestamp = (isset($data->actualSendTimestamp)) ? $data->actualSendTimestamp : NULL;
               $this->dateAdded = (isset($data->dateAdded)) ? $data->dateAdded : NULL;
               $this->sentCount = (isset($data->sentCount)) ? $data->sentCount : NULL;
               $this->deliversCount = (isset($data->deliversCount)) ? $data->deliversCount : NULL;
               $this->bouncesCount = (isset($data->bouncesCount)) ? $data->bouncesCount : NULL;
               $this->recipientsCount = (isset($data->recipientsCount)) ? $data->recipientsCount : NULL;
               $this->opensCount = (isset($data->opensCount)) ? $data->opensCount : NULL;
               $this->clicksCount = (isset($data->clicksCount)) ? $data->clicksCount : NULL;
               $this->linksCount = (isset($data->linksCount)) ? $data->linksCount : NULL;
               $this->recipProcessed = (isset($data->recipProcessed)) ? $data->recipProcessed : 0;
//               $this->recipLastStartAt = (isset($data->recipLastStartAt)) ? $data->recipLastStartAt : 0;
           }

       public function getMessageID()
       {
           return $this->id;
       }
       public function getSubCommunity()
       {
           return $this->subCommunityId;
       }
       public function getRecipientsProcessed()
       {
           return $this->recipProcessed;
       }
       public function getLastStartAt()
       {
           return $this->recipLastStartAt;
       }
   }