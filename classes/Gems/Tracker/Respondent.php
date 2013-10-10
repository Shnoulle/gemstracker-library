<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @author     Jasper van Gestel <jappie@dse.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Tracker_Respondent extends Gems_Registry_TargetAbstract
{
	/**
     *
     * @var Gems_Loader
     */
    protected $loader;
    /**
     * 
     * @var Gems_Model
     */
	protected $model;

    /**
     * 
     * @var integer Organization Id
     */
    private $organizationId;

    /**
     * 
     * @var integer Patient Id
     */
    private $patientId;

    /**
     * @var array Respondent
     */
    protected $respondent;

    /**
     * 
     * @var string Respondent language
     */
    protected $respondentLanguage;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

	public function __construct($patientId=false, $organizationId=false)
    {
        $this->patientId = $patientId;
        $this->organizationId = $organizationId;
	}

    public function afterRegistry()
    {
        $this->model = $this->loader->getModels()->getRespondentModel(true);
        if ($this->patientId && $this->organizationId) {
            $this->respondent = $this->getRespondent($this->patientId, $this->organizationId);
        }
    }

    /**
     * Get the respondent with a patientId and organization Id combination
     * @param  integer $patientId      [description]
     * @param  integer $organizationId [description]
     */
	protected function getRespondent($patientId, $organizationId)
    {
		$select = $this->model->getSelect();

		$select->where('gr2o_patient_nr = ?', $patientId)
               ->where('gr2o_id_organization = ?', $organizationId);

		$result = $select->query()->fetchRow();

        return $result;
	}

    /**
     * Get Email adres of respondent
     * @return string
     */
    public function getEmailAddress()
    {
        return $this->respondent['grs_email'];
    }

    /**
     * Get First name of respondent
     * @return string
     */
    public function getFirstName()
    {
        return $this->respondent['grs_first_name'];
    }

    /**
     * Get the formal name of respondent
     * @return string
     */
    public function getFullName()
    {

        $genderGreetings = $this->util->getTranslated()->getGenderHello($this->getLanguage());
        
        $greeting = $genderGreetings[$this->respondent['grs_gender']];

        return $greeting . ' ' . $this->getName();
    }

    /**
     * Get the propper greeting of respondent
     * @return string
     */
    public function getGreeting()
    {   

        $genderGreetings = $this->util->getTranslated()->getGenderGreeting($this->getLanguage());
        
        $greeting = $genderGreetings[$this->respondent['grs_gender']];

        return $greeting . ' ' . $this->getName();
    }

    /**
     * Get Last name of respondent
     * @return string
     */
    public function getLastName()
    {
        return $this->respondent['grs_last_name'];
    }
    /**
     * Get the respondents prefered language
     * @return string
     */
    public function getLanguage() {
        if (!isset($this->respondentLanguage)) {
            $this->respondentLanguage = $this->respondent['grs_iso_lang'];
        }
        return $this->respondentLanguage;
    }

    /**
     * Get the respondent Mailfields
     * @return array
     */
    public function getMailFields() {
        if ($this->respondent) {
            $result = array();
            $result['email']        = $this->getEmail();
            $result['first_name']   = $this->getFirstName();
            $result['full_name']    = $this->getFullName();
            $result['greeting']     = $this->getGreeting();
            $result['last_name']    = $this->getLastName();
            $result['name']         = $this->getName();
        } else {
            $result = array(
                'email'     => '',
                'first_name'=> '',
                'full_name' => '',
                'greeting'  => '',
                'last_name' => '',
                'name'      => ''
            );
        }
        return $result;
    }

    /**
     * Get the full name (firstname, prefix and last name)
     * @return string
     */
    public function getName()
    {
        $fullName = $this->getFirstName();
        if (!empty($this->respondent['grs_surname_prefix'])) {
            $fullName .= ' ' . $this->respondent['grs_surname_prefix'];
        }
        $fullName .= $this->getLastName();

        return $fullName;
    }
    
    /**
     * Overwrite the respondents prefered language
     */
    public function setLocale($locale) {
        $this->respondentLanguage = $locale;
    }

    /**
     * 
     * @return integer Organization ID
     */
    public function getOrganizationId()
    {
        return $this->organizationId;
    }
}