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
 * The organization model
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: RespondentRelationModel.php 2763 2015-10-30 18:33:48Z matijsdejong $
 */

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class Gems_Model_RespondentRelationModel extends \Gems_Model_JoinModel {

    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    public function __construct($name = null, $startTable = null, $fieldPrefix = null, $saveable = null) {
        parent::__construct('respondent_relation', 'gems__respondent_relations', 'grr');

        $this->addTable('gems__respondent2org', array('gr2o_id_user' => 'grr_id_respondent'), null, false);

        $keys = $this->_getKeysFor('gems__respondent2org');
        $keys['rid'] = 'grr_id';
        $this->setKeys($keys);

        // Do not really delete but make inactive so we can always display old relations
        $this->setDeleteValues('grr_active', 0);
    }

    public function _($text, $locale = null)
    {
        return $this->translate->getAdapter()->_($text, $locale);
    }

    public function applyBrowseSettings()
    {
        $this->addFilter(array('grr_active'=>1));
        $this->set('grr_type',
                'label', $this->_('Relation type'), 'description', $this->_('Father, mother, etc.'));
        $this->set('grr_gender', 'label', $this->_('Gender'), 'multiOptions', $this->loader->getUtil()->getTranslated()->getGenderHello());
        $this->set('grr_first_name', 'label', $this->_('First name'));
        $this->set('grr_last_name', 'label', $this->_('Last name'));
        $this->set('grr_birthdate', 'label', $this->_('Birthday'), 'dateFormat', \Zend_Date::DATE_MEDIUM, 'elementClass', 'Date');
        $this->set('grr_email', 'label', $this->_('E-Mail'));
    }

    public function applyDetailSettings()
    {
        $this->applyBrowseSettings();
        $this->set('grr_comments', 'label', $this->_('Comments'), 'elementClass', 'TextArea');
        $this->set('grr_birthdate', 'jQueryParams', array('defaultDate' => '-30y', 'maxDate' => 0, 'yearRange' => 'c-130:c0'));
    }

    /**
     * Return an object for a row of this model
     *
     * @param int $respondentId
     * @param int $relationId
     * @return \Gems_Model_RespondentRelationInstance
     */
    public function getRelation($respondentId, $relationId)
    {
        $filter = array(
            'grr_id_respondent' => $respondentId,        // Just a safeguard to make sure we get only relations for this patient
            'grr_id'            => $relationId
        );

        $data = $this->loadFirst($filter);

        if (!$data) {
            $data = array();
        }

        $relationObject = $this->loader->getInstance('Model_RespondentRelationInstance', $this, $data);

        return $relationObject;
    }

    /**
     * Get the relations for a given respondentId or patientNr + organizationId combination
     *
     * @param type $respondentId
     * @param type $patientNr
     * @param type $organizationId
     * @return array
     */
    public function getRelationsFor($respondentId, $patientNr = null, $organizationId = null, $onlyActive = true)
    {
        static $relationsCache = array();

        if (is_null($respondentId)) {
            $respondentId = $this->loader->getUtil()->getDbLookup()->getRespondentId($patientNr, $organizationId);
        }

        if (!array_key_exists($respondentId, $relationsCache)) {
            $relations = array();
            $filter = array('grr_id_respondent'=>$respondentId);
            if ($onlyActive) {
                $filter['grr_active'] = 1;
            }
            $rawRelations = $this->load($filter);
            foreach ($rawRelations as $relation)
            {
                $relations[$relation['grr_id']] = join(' ', array($relation['grr_type'], $relation['grr_first_name'], $relation['grr_last_name']));
            }
            $relationsCache[$respondentId] = $relations;
        }

        return $relationsCache[$respondentId];
    }
}