<?php

/**
 * Copyright (c) 2014, Erasmus MC
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
 * @subpackage Agenda
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Appointment .php 1748 2014-02-19 18:09:41Z matijsdejong $
 */

/**
 *
 * @package    Gems
 * @subpackage Agenda
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Agenda_Appointment extends Gems_Registry_TargetAbstract
{
    /**
     *
     * @var int The id of the appointment
     */
    protected $_appointmentId;

    /**
     *
     * @var array The gems appointment data
     */
    protected $_gemsData = array();

    /**
     *
     * @var Gems_Agenda
     */
    protected $agenda;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * True when the token does exist.
     *
     * @var boolean
     */
    public $exists = true;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     * Creates the appointments object
     *
     * @param mixed $appointmentData Appointment Id or array containing appointment record
     */
    public function __construct($appointmentData)
    {
        if (is_array($appointmentData)) {
            $this->_gemsData       = $appointmentData;
            $this->_appointmentId  = $appointmentData['gap_id_appointment'];
        } else {
            $this->_appointmentId  = $appointmentData;
            // loading occurs in checkRegistryRequestAnswers
        }
    }

    /**
     * Makes sure the respondent data is part of the $this->_gemsData
     */
    protected function _ensureRespondentOrgData()
    {
        if (! isset($this->_gemsData['gr2o_id_user'], $this->_gemsData['gco_code'])) {
            $sql = "SELECT *
                FROM gems__respondents INNER JOIN
                    gems__respondent2org ON grs_id_user = gr2o_id_user INNER JOIN
                    gems__consents ON gr2o_consent = gco_description
                WHERE gr2o_id_user = ? AND gr2o_id_organization = ? LIMIT 1";

            $respId = $this->_gemsData['gap_id_user'];
            $orgId  = $this->_gemsData['gap_id_organization'];
            // MUtil_Echo::track($this->_gemsData);

            if ($row = $this->db->fetchRow($sql, array($respId, $orgId))) {
                $this->_gemsData = $this->_gemsData + $row;
            } else {
                $appId = $this->_appointmentId;
                throw new Gems_Exception("Respondent data missing for appointment id $appId.");
            }
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
        if ($this->db && (! $this->_gemsData)) {
            $this->refresh();
        }

        return $this->exists;
    }

    /**
     * Return the admission time
     *
     * @return MUtil_Date Admission time as a date or null
     */
    public function getAdmissionTime()
    {
        if (isset($this->_gemsData['gap_admission_time']) && $this->_gemsData['gap_admission_time']) {
            if (! $this->_gemsData['gap_admission_time'] instanceof MUtil_Date) {
                $this->_gemsData['gap_admission_time'] =
                        new MUtil_Date($this->_gemsData['gap_admission_time'], Gems_Tracker::DB_DATETIME_FORMAT);
            }
            return $this->_gemsData['gap_admission_time'];
        }
    }

    /**
     * Return the appointment id
     *
     * @return int
     */
    public function getId()
    {
        return $this->_appointmentId;
    }

    /**
     *
     * @return int
     */
    public function getOrganizationId()
    {
        return $this->_gemsData['gap_id_organization'];
    }

    /**
     *
     * @return string The respondents patient number
     */
    public function getPatientNumber()
    {
        if (! isset($this->_gemsData['gr2o_patient_nr'])) {
            $this->_ensureRespondentOrgData();
        }

        return $this->_gemsData['gr2o_patient_nr'];
    }

    /**
     * Return the user / respondent id
     *
     * @return int
     */
    public function getRespondentId()
    {
        return $this->_gemsData['gap_id_user'];
    }

    /**
     * Return true when the satus is active
     *
     * @return type
     */
    public function isActive()
    {
        return isset($this->_gemsData['gap_status']) && $this->agenda->isStatusActive($this->_gemsData['gap_status']);
    }

    /**
     *
     * @param array $gemsData Optional, the data refresh with, otherwise refresh from database.
     * @return \Gems_Agenda_Appointment (continuation pattern)
     */
    public function refresh(array $gemsData = null)
    {
        if (is_array($gemsData)) {
            $this->_gemsData = $gemsData + $this->_gemsData;
        } else {
            $select = $this->db->select();
            $select->from('gems__appointments')
                    ->where('gap_id_appointment = ?', $this->_appointmentId);

            $this->_gemsData = $this->db->fetchRow($select);
            if (false == $this->_gemsData) {
                // on failure, reset to empty array
                $this->_gemsData = array();
            }
        }
        $this->exists = isset($this->_gemsData['gap_id_appointment']);

        return $this;
    }

    /**
     * Recalculate all tracks that use this appointment
     *
     * @return int The number of tokens changed by this code
     */
    public function updateTracks()
    {
        $select = $this->db->select();
        $select->from('gems__respondent2track2appointment', 'gr2t2a_id_respondent_track')
                ->where('gr2t2a_id_appointment = ?', $this->_appointmentId)
                ->distinct();

        $tokenChanges = 0;
        $respTracks   = $this->db->fetchCol($select);

        // MUtil_Echo::track($respTracks);
        if ($respTracks) {
            $tracker = $this->loader->getTracker();
            $userId  = $this->loader->getCurrentUser()->getUserId();

            foreach ($respTracks as $respTrackId) {
                $respTrack = $tracker->getRespondentTrack($respTrackId);
                $tokenChanges += $respTrack->checkTrackTokens($userId);
            }
        }
        // MUtil_Echo::track($tokenChanges);

        return $tokenChanges;
    }
}