<?php

/* Copyright Philippe Logel not MIT */
// Routes
use EcclesiaCRM\FamilyQuery;
use EcclesiaCRM\PersonQuery;
use EcclesiaCRM\GroupQuery;
use EcclesiaCRM\Service\MailChimpService;
use EcclesiaCRM\Person2group2roleP2g2rQuery;

$app->group('/mailchimp', function () {
    $this->get('/listmembers/{listID}',function($request,$response,$args) {
      $mailchimp = new MailChimpService();
      
      return $response->withJSON(['MailChimpMembers' => $mailchimp->getListMembersFromListId($args['listID'])]);
    });
    
    $this->post('/createlist', function ($request, $response, $args) {
    
      $input = (object)$request->getParsedBody();
    
      if ( isset ($input->ListTitle) && isset ($input->Subject) && isset ($input->PermissionReminder) && isset ($input->ArchiveBars) && isset ($input->Status) ){
        $mailchimp = new MailChimpService();
      
        if ( !is_null ($mailchimp) && $mailchimp->isActive() ){
           $res = $mailchimp->createList($input->ListTitle, $input->Subject, $input->PermissionReminder, $input->ArchiveBars, $input->Status);
         
           if ( !array_key_exists ('title',$res) ) {
             return $response->withJson(['success' => true, "result" => $res]);
           } else {
             return $response->withJson(['success' => false, "error" => $res]);
           }
        }
      }
      
      return $response->withJson(['success' => false,"res" => $res]);
    });
    
    $this->post('/deleteallsubscribers', function ($request, $response, $args) {
      $input = (object)$request->getParsedBody();
    
      if ( isset ($input->list_id) ){
         $mailchimp = new MailChimpService();
      
         if ( !is_null ($mailchimp) && $mailchimp->isActive() ){
           $res = $mailchimp->deleteAllSubscribers($input->list_id);
           
           if ( !array_key_exists ('title',$res) ) {
             return $response->withJson(['success' => true, "result" => $res]);
           } else {
             return $response->withJson(['success' => false, "error" => $res]);
           }
         }
      }
      
      return $response->withJson(['success' => false]);
    });
    

    $this->post('/deletelist', function ($request, $response, $args) {
      $input = (object)$request->getParsedBody();
    
      if ( isset ($input->list_id) ){
         $mailchimp = new MailChimpService();
      
         if ( !is_null ($mailchimp) && $mailchimp->isActive() ){
           $res = $mailchimp->deleteList($input->list_id);
           
           if ( !array_key_exists ('title',$res) ) {
             return $response->withJson(['success' => true, "result" => $res]);
           } else {
             return $response->withJson(['success' => false, "error" => $res]);
           }
         }
      }
      
      return $response->withJson(['success' => false]);
    });
    
    $this->post('/createcampaign', function ($request, $response, $args) {
      $input = (object)$request->getParsedBody();
    
      if ( isset ($input->list_id) && isset ($input->subject) && isset ($input->title) && isset ($input->htmlBody) ){
         $mailchimp = new MailChimpService();
      
         if ( !is_null ($mailchimp) && $mailchimp->isActive() ){
           $res = $mailchimp->createCampaign($input->list_id, $input->subject, $input->title, $input->htmlBody);
           
           if ( !array_key_exists ('title',$res) ) {
             return $response->withJson(['success' => true, "result" => $res]);
           } else {
             return $response->withJson(['success' => false, "error" => $res]);
           }
         }
      }
      
      return $response->withJson(['success' => false]);
    });
    
    
    $this->post('/status', function ($request, $response, $args) {
      $input = (object)$request->getParsedBody();
    
      if ( isset ($input->status) && isset ($input->list_id) && isset ($input->email) ){
      
        // we get the MailChimp Service
        $mailchimp = new MailChimpService();
        
        $res = $mailchimp->updateMember($input->list_id,"","",$input->email,$input->status);
        
        if ( !array_key_exists ('title',$res) ) {
          return $response->withJson(['success' => true, "result" => $res]);
        } else {
          return $response->withJson(['success' => false, "error" => $res]);
        }
      }
      
      return $response->withJson(['success' => false]);
    });
    
    $this->post('/suppress', function ($request, $response, $args) {
      $input = (object)$request->getParsedBody();
    
      if ( isset ($input->list_id) && isset ($input->email) ){
      
        // we get the MailChimp Service
        $mailchimp = new MailChimpService();
        
        $res = $mailchimp->deleteUser($input->list_id,$input->email);
        
        if ( !array_key_exists ('title',$res) ) {
          return $response->withJson(['success' => true, "result" => $res]);
        } else {
          return $response->withJson(['success' => false, "error" => $res]);
        }
      }
      
      return $response->withJson(['success' => false]);
    });

    $this->post('/addperson', function ($request, $response, $args) {
      $input = (object)$request->getParsedBody();
    
      if ( isset ($input->personID) && isset ($input->list_id) ){
      
        // we get the MailChimp Service
        $mailchimp = new MailChimpService();
        $person = PersonQuery::create()->findPk($input->personID);
        
        if ( !is_null ($mailchimp) && $mailchimp->isActive() /*&& !is_null($person) && $mailchimp->isEmailInMailChimp($person->getEmail()) == ''*/ ) {
          $res = $mailchimp->postMember($input->list_id,32,$person->getFirstName(),$person->getLastName(),$person->getEmail(),'subscribed');
          
          if ( !array_key_exists ('title',$res) ) {
            return $response->withJson(['success' => true, "result" => $res]);
          } else {
             return $response->withJson(['success' => false, "error" => $res]);
          }
        }
      }
      
      return $response->withJson(['success' => false]);
    });

    $this->post('/addfamily', function ($request, $response, $args) {
      $input = (object)$request->getParsedBody();
    
      if ( isset ($input->familyID) && isset ($input->list_id) ){
      
        // we get the MailChimp Service
        $mailchimp = new MailChimpService();
        
        if ( !is_null ($mailchimp) && $mailchimp->isActive() ) {
          $family = FamilyQuery::create()->findPk($input->familyID);
          $persons = $family->getPeople();
         
          // all person from the family should be deactivated too
          $res = [];
          foreach ($persons as $person) {
            $res[] = $mailchimp->postMember($input->list_id,32,$person->getFirstName(),$person->getLastName(),$person->getEmail(),'subscribed');
          }
          
          return $response->withJson(['success' => true, "result" => $res]);
        }
      }
      
      return $response->withJson(['success' => false]);
    });

    $this->post('/addgroup', function ($request, $response, $args) {
      $input = (object)$request->getParsedBody();
    
      if ( isset ($input->groupID) && isset ($input->list_id) ){
      
        // we get the MailChimp Service
        $mailchimp = new MailChimpService();
        
        if ( !is_null ($mailchimp) && $mailchimp->isActive() ) {
          $members = EcclesiaCRM\Person2group2roleP2g2rQuery::create()
              ->joinWithPerson()
              ->usePersonQuery()
                ->filterByDateDeactivated(null)// RGPD, when a person is completely deactivated
              ->endUse()
              ->findByGroupId($input->groupID);
        
            
          // all person from the family should be deactivated too
          $res = [];
          foreach ($members as $member) {
            $res[] = $mailchimp->postMember($input->list_id,32,$member->getPerson()->getFirstName(),$member->getPerson()->getLastName(),$member->getPerson()->getEmail(),'subscribed');
          }
          
          return $response->withJson(['success' => true, "result" => $res]);
        }
      }
      
      return $response->withJson(['success' => false]);
    });
});
