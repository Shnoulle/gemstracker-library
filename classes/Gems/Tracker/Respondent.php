<?php
/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentTrack.php 1033 2012-11-22 12:13:08Z mennodekker $
 */

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class Gems_Tracker_Respondent extends Gems_Registry_TargetAbstract
{
    /**
     *
     * @var array The gems__respondents data
     */
    protected $_respondentData;

    /**
     *
     * @var int The gems__respondents id
     */
    protected $_respondentId;
    
    /**
     *
     * @var int The gems__respondent2org id
     */
    protected $_respondentOrgId;

    /**
     *
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;
    
    /**
     *
     * @var Gems_Model_RespondentModel
     */
    protected $model;

    /**
     * @param mixed $respondentData   RespondentId or array containing respondent & respondent2org record
     * @param int   $respondentOrgId  Organization this respondent belongs to
     */
    public function __construct($respondentData, $respondentOrgId = null)
    {
        if (is_array($respondentData)) {
            $this->_respondentData = $respondentData;
            $this->init();
        } else {
            $this->_respondentId = $respondentData;
            $this->_respondentOrgId = $respondentOrgId;
        }
    }


    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if (! $this->_respondentData) {
            $this->refresh();
        }

        return (boolean) $this->_respondentData;
    }

    /**
     *
     * @return string The respondents patient number
     */
    public function getPatientNumber()
    {
        if (! isset($this->_respTrackData['gr2o_patient_nr'])) {
            $this->refresh();
        }

        return $this->_respTrackData['gr2o_patient_nr'];
    }

    /**
     *
     * @return int The organization id
     */
    public function getOrganizationId()
    {
        return $this->_respondentOrgId;
    }

    public function getReceptionCode()
    {
        return $this->tracker->getReceptionCode($this->_respondentData['gr2o_reception_code']);
    }

    /**
     *
     * @return int The respondent id
     */
    public function getRespondentId()
    {
        return $this->_respondentId;
    }
    
    /**
     * Get the respondentmodel for this respondent
     * 
     * Used internally for retrieving and storing respondents
     * 
     * @return Gems_Model_RespondentModel
     */
    public function getRespondentModel()
    {
        if (! $this->_model instanceof Gems_Model_RespondentModel) {
            $this->_model = $this->loader->getModels()->getRespondentModel(true);
        }
        
        return $this->_model;
    }
    
    /**
     * Handles setting or resetting respondentId and RespondentOrgId
     */
    public function init() {
        $this->_respondentId   = isset($this->_respondentData['grs_id_user']) ? $this->_respondentData['grs_id_user'] : null;
        $this->_respondentOrgId  = isset($this->_respondentData['gr2o_id_organization']) ? $this->_respondentData['gr2o_id_organization'] : null;
    }


    /**
     *
     * @param array $respondentData Optional, the data refresh with, otherwise refresh from database.
     * @return Gems_Tracker_RespondentTrack (continuation pattern)
     */
    public function refresh(array $respondentData = null)
    {
        if (is_array($respondentData)) {
            $this->_respondentData = $respondentData + $this->_respondentData;
            $this->init();
            
        } elseif (!is_null($this->_respondentId)) {
            $respondentData = $this->getRespondentModel()->loadFirst(array(
                'grs_id_user'          => $this->_respondentId,
                'gr2o_id_organization' => $this->_respondentOrgId
                ));
            if ($respondentData) {
                $this->_respondentData = $respondentData;
                $this->init();
            } else {
                $this->_respondentData = array();
                $this->init();
            }
                
        }

        return $this;
    }

    /**
     * Set the reception code for this respondent track and make sure the
     * necessary cascade to the tokens and thus the source takes place.
     *
     * @param string $code The new (non-success) reception code or a Gems_Tracker_ReceptionCode object
     * @param string $comment Comment for tokens. False values leave value unchanged
     * @param int $userId The current user
     * @return int 1 if the token has changed, 0 otherwise
     */
    public function setReceptionCode($code, $comment, $userId)
    {
        // Make sure it is a Gems_Tracker_ReceptionCode object
        if (! $code instanceof Gems_Tracker_ReceptionCode) {
            $code = $this->tracker->getReceptionCode($code);
        }
        $changed = 0;

        $values = array(
            'grs_id_user'=>$this->_respondentId,
            'gr2o_id_organization'=>$this->_respondentId,
        );
        // We can not handle saving a comment for this code, so just enter the code
        // $values['gr2t_comment'] = $comment;
        $values['gr2o_reception_code'] = $code->getCode();
        
        $model = $this->getRespondentModel();
        $newValues = $model->save($values);
        $changed = (bool) $model->getChanged();
        if ($changed) {
            $this->refresh();
        }
        
        $eventChanged = $code->handleEvent($this, $code, $comment, $userId);
        $changed = ($changed || $eventChanged);
        
        return $changed;
    }
}