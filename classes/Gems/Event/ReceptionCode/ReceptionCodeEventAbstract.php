<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ReceptionCodeEventAbstract
 *
 * @author 175780
 */
class Gems_Event_ReceptionCode_ReceptionCodeEventAbstract implements Gems_Event_ReceptionCodeEventInterface {
    
    public function setReceptionCode($object, $receptionCode, $comment, $userId)
    {
        // Perform your own logic here
    }
    
    protected function cascade($object, $receptionCode, $comment, $userId) {
        if ($object instanceof Gems_Tracker_Token) {
            $this->cascadeToken($object, $receptionCode, $comment, $userId);
        }
    }
    
    /**
     * Handles cascading FROM a respondent object down
     * 
     * @param type $object
     * @param type $receptionCode
     * @param type $comment
     * @param type $userId
     */
    protected function cascadeRespondent($object, $receptionCode, $comment, $userId) {
        
    }
    
    protected function cascadeToken($object, $receptionCode, $comment, $userId) {
        
    }
    
    protected function cascadeTrack($object, $receptionCode, $comment, $userId) {
        
    }
}