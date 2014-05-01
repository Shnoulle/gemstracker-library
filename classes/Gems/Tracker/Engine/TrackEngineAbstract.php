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
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Utility class containing functions used by most track engines.
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class Gems_Tracker_Engine_TrackEngineAbstract extends MUtil_Translate_TranslateableAbstract implements Gems_Tracker_Engine_TrackEngineInterface
{
    /**
     * Field key separator
     */
    const FIELD_KEY_SEPARATOR = '__';

    /**
     * Option seperator for fields
     */
    const FIELD_SEP = '|';

    /**
     * Stores the models for each action
     *
     * @var array
     */
    protected $_fieldModels = array();

    /**
     * Cache for appointment fields check
     *
     * @var boolean
     */
    private $_hasAppointmentFields = null;

    /**
     *
     * @var array of rounds data
     */
    protected $_rounds;

    /**
     *
     * @var array
     */
    protected $_trackData;

    /**
     * Can be an empty array.
     *
     * @var array The gems__track_fields + gems__track_appointments data
     */
    protected $_trackFields = false;

    /**
     *
     * @var int
     */
    protected $_trackId;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Gems_Events
     */
    protected $events;

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var Gems_Tracker
     */
    protected $tracker;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     *
     * @var Zend_View
     */
    protected $view;

    /**
     *
     * @param array $trackData array containing track record
     */
    public function __construct($trackData)
    {
        $this->_trackData = $trackData;
        $this->_trackId   = $trackData['gtr_id_track'];
    }

    /**
     * Loads the rounds data for this type of track engine.
     *
     * Can be overruled by sub classes.
     */
    protected function _ensureRounds()
    {
        if (! is_array($this->_rounds)) {
            $roundSelect = $this->db->select();
            $roundSelect->from('gems__rounds')
                ->where('gro_id_track = ?', $this->_trackId)
                ->order('gro_id_order');

            // MUtil_Echo::r((string) $roundSelect);

            $this->_rounds  = array();
            foreach ($roundSelect->query()->fetchAll() as $round) {
                $this->_rounds[$round['gro_id_round']] = $round;
            }
        }
    }

    /**
     * Loads the $this->_trackFields array, if not already there
     */
    protected function _ensureTrackFields()
    {
        if (! is_array($this->_trackFields)) {

            $for    = array('gtf_id_track' => $this->_trackId);
            $model  = $this->getFieldsMaintenanceModel(false, 'index', $for);
            $fields = $model->load($for);

            $this->_trackFields = array();
            if (is_array($fields)) {
                foreach ($fields as $field) {
                    $this->_trackFields[$field['sub'] . self::FIELD_KEY_SEPARATOR . $field['gtf_id_field']] = $field;
                }
            }
        }
    }

    /**
     * Returns a list of available icons under 'htdocs/pulse/icons'
     * @return string[]
     */
    protected function _getAvailableIcons()
    {
        $icons = array();
        $iterator = new DirectoryIterator(realpath(GEMS_WEB_DIR . '/gems/icons'));

        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isFile()) {
                // $icons[$fileinfo->getFilename()] = $fileinfo->getFilename();
                $filename = $fileinfo->getFilename();
                $url = $this->view->baseUrl() . MUtil_Html_ImgElement::getImageDir($filename);
                $icons[$fileinfo->getFilename()] = MUtil_Html::create('span', $filename, array('style' => 'background: transparent url(' . $url . $filename . ') center right no-repeat; padding-right: 20px;'));
            }
        }

        return $icons;
    }

    /**
     * Update the track, both in the database and in memory.
     *
     * @param array $values The values that this token should be set to
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    private function _update(array $values, $userId)
    {
        if ($this->tracker->filterChangesOnly($this->_trackData, $values)) {

            if (Gems_Tracker::$verbose) {
                $echo = '';
                foreach ($values as $key => $val) {
                    $echo .= $key . ': ' . $this->_trackData[$key] . ' => ' . $val . "\n";
                }
                MUtil_Echo::r($echo, 'Updated values for ' . $this->_trackId);
            }

            if (! isset($values['gto_changed'])) {
                $values['gtr_changed'] = new MUtil_Db_Expr_CurrentTimestamp();
            }
            if (! isset($values['gtr_changed_by'])) {
                $values['gtr_changed_by'] = $userId;
            }

            // Update values in this object
            $this->_trackData = $values + $this->_trackData;

            // return 1;
            return $this->db->update('gems__tracks', $values, array('gtr_id_track = ?' => $this->_trackId));

        } else {
            return 0;
        }
    }

    /**
     * Integrate field loading en showing and editing
     *
     * @param \MUtil_Model_ModelAbstract $model
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return \Gems_Tracker_Engine_TrackEngineAbstract
     */
    public function addFieldsToModel(\MUtil_Model_ModelAbstract $model, $respondentId, $organizationId, $patientNr = null, $edit = true)
    {
        $this->_ensureTrackFields();

        if ($this->_trackFields) {
            // Add the data to the load / save
            $transformer = new Gems_Tracker_Model_RespondentTrackModelTransformer(
                    $this,
                    $respondentId,
                    $organizationId,
                    $patientNr,
                    $edit
                    );
            $model->addTransformer($transformer);
        }

        return $this;
    }

    /**
     * Creates all tokens that should exist, but do not exist
     *
     * NOTE: When overruling this function you should not create tokens because they
     * were deleted by the user
     *
     * @param Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens created by this code
     */
    protected function addNewTokens(Gems_Tracker_RespondentTrack $respTrack, $userId)
    {
        $orgId       = $respTrack->getOrganizationId();
        $respId      = $respTrack->getRespondentId();
        $respTrackId = $respTrack->getRespondentTrackId();

        // $this->t

        $sql = "SELECT gro_id_round, gro_id_survey, gro_id_order, gro_round_description
            FROM gems__rounds
            WHERE gro_id_track = ? AND
                gro_active = 1 AND
                gro_id_round NOT IN (SELECT gto_id_round FROM gems__tokens WHERE gto_id_respondent_track = ?)
            ORDER BY gro_id_order";

        $newRounds = $this->db->fetchAll($sql, array($this->_trackId, $respTrackId));

        foreach ($newRounds as $round) {

            $values = array();

            // From the respondent track
            $values['gto_id_respondent_track'] = $respTrackId;
            $values['gto_id_respondent']       = $respId;
            $values['gto_id_organization']     = $orgId;
            $values['gto_id_track']            = $this->_trackId;

            // From the rounds
            $values['gto_id_round']          = $round['gro_id_round'];
            $values['gto_id_survey']         = $round['gro_id_survey'];
            $values['gto_round_order']       = $round['gro_id_order'];
            $values['gto_round_description'] = $round['gro_round_description'];

            // All other values are not changed by this query and get the default DB value on insertion

            $this->tracker->createToken($values, $userId);
        }

        return count($newRounds);
    }

    /**
     * Set menu parameters from this track engine
     *
     * @param Gems_Menu_ParameterSource $source
     * @return Gems_Tracker_Engine_TrackEngineInterface (continuation pattern)
     */
    public function applyToMenuSource(Gems_Menu_ParameterSource $source)
    {
        $source->setTrackId($this->_trackId);
        $source->setTrackType($this->getTrackType());
        return $this;
    }

    /**
     * Calculate the track info from the fields
     *
     * @param int $respTrackId Gems respondent track id or null when new
     * @param array $data The values to save
     * @return string The description to save as track_info
     */
    public function calculateFieldsInfo($respTrackId, array $data)
    {
        $this->_ensureTrackFields();

        if (! $this->_trackFields) {
            return null;
        }

        $results = array();
        foreach ($this->_trackFields as $key => $field) {
            if (isset($data[$key]) && (is_array($data[$key]) || strlen($data[$key]))) {
                if ("appointment" !== $field['gtf_field_type']) {
                    if (is_array($data[$key])) {
                        $results = array_merge($results, $data[$key]);
                    } else {
                        $results[] = $data[$key];
                    }
                }
            }
        }

        return trim(implode(' ', $results));
    }

    /**
     * Calculate the number of active rounds in this track from the database.
     *
     * @return int The number of rounds in this track.
     */
    public function calculateRoundCount()
    {
        return $this->db->fetchOne("SELECT COUNT(*) FROM gems__rounds WHERE gro_active = 1 AND gro_id_track = ?", $this->_trackId);
    }

    /**
     * Checks all existing tokens and updates any changes to the original rounds (when necessary)
     *
     * @param Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens changed by this code
     */
    protected function checkExistingRoundsFor(Gems_Tracker_RespondentTrack $respTrack, $userId)
    {
        // Quote here, I like to keep bound parameters limited to the WHERE
        // Besides, these statements are not order dependent while parameters are and do not repeat
        $qOrgId   = $this->db->quote($respTrack->getOrganizationId());
        $qRespId  = $this->db->quote($respTrack->getRespondentId());
        $qTrackId = $this->db->quote($this->_trackId);
        $qUserId  = $this->db->quote($userId);

        $respTrackId = $respTrack->getRespondentTrackId();

        $sql = "UPDATE gems__tokens, gems__rounds, gems__reception_codes
            SET gto_id_respondent = $qRespId,
                gto_id_organization = $qOrgId,
                gto_id_track = $qTrackId,
                gto_id_survey = CASE WHEN gto_start_time IS NULL AND grc_success = 1 THEN gro_id_survey ELSE gto_id_survey END,
                gto_round_order = gro_id_order,
                gto_round_description = gro_round_description,
                gto_changed = CURRENT_TIMESTAMP,
                gto_changed_by = $qUserId
            WHERE gto_id_round = gro_id_round AND
                gto_reception_code = grc_id_reception_code AND
                gro_active = 1 AND
                (
                    gto_id_respondent != $qRespId OR
                    gto_id_organization != $qOrgId OR
                    gto_id_track != $qTrackId OR
                    gto_id_survey != CASE WHEN gto_start_time IS NULL AND grc_success = 1 THEN gro_id_survey ELSE gto_id_survey END OR
                    gto_round_order != gro_id_order OR
                    (gto_round_order IS NULL AND gro_id_order IS NOT NULL) OR
                    (gto_round_order IS NOT NULL AND gro_id_order IS NULL) OR
                    gto_round_description != gro_round_description OR
                    (gto_round_description IS NULL AND gro_round_description IS NOT NULL) OR
                    (gto_round_description IS NOT NULL AND gro_round_description IS NULL)
                ) AND
                gto_id_respondent_track = ?";

        $stmt = $this->db->query($sql, $respTrackId);

        return $stmt->rowCount();
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        if ($this->db) {
            $this->_ensureRounds();
        }

        return (boolean) $this->db;
    }

    /**
     * Check for the existence of all tokens and create them otherwise
     *
     * @param Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @param Gems_Task_TaskRunnerBatch $changes batch for counters
     */
    public function checkRoundsFor(Gems_Tracker_RespondentTrack $respTrack, $userId, Gems_Task_TaskRunnerBatch $batch = null)
    {
        if (null === $batch) {
            $batch = new Gems_Task_TaskRunnerBatch('tmp-tack-' . $respTrack->getRespondentTrackId());
        }
        // Step one: update existing tokens
        $i = $batch->addToCounter('roundChangeUpdates', $this->checkExistingRoundsFor($respTrack, $userId));
        $batch->setMessage('roundChangeUpdates', sprintf($this->_('Round changes propagated to %d tokens.'), $i));

        // Step two: deactivate inactive rounds
        $i = $batch->addToCounter('deletedTokens', $this->removeInactiveRounds($respTrack, $userId));
        $batch->setMessage('deletedTokens', sprintf($this->_('%d tokens deleted by round changes.'), $i));

        // Step three: create lacking tokens
        $i = $batch->addToCounter('createdTokens', $this->addNewTokens($respTrack, $userId));
        $batch->setMessage('createdTokens', sprintf($this->_('%d tokens created to by round changes.'), $i));

        // Step four: set the dates and times
        //$changed = $this->checkTokensFromStart($respTrack, $userId);
        $changed = $respTrack->checkTrackTokens($userId);
        $ica = $batch->addToCounter('tokenDateCauses', $changed ? 1 : 0);
        $ich = $batch->addToCounter('tokenDateChanges', $changed);
        $batch->setMessage('tokenDateChanges', sprintf($this->_('%2$d token date changes in %1$d tracks.'), $ica, $ich));

        $i = $batch->addToCounter('checkedRespondentTracks');
        $batch->setMessage('checkedRespondentTracks', sprintf($this->_('Checked %d tracks.'), $i));
    }

    /**
     * Convert a TrackEngine instance to a TrackEngine of another type.
     *
     * @see getConversionTargets()
     *
     * @param type $conversionTargetClass
     */
    public function convertTo($conversionTargetClass)
    {
        throw new Gems_Exception_Coding(sprintf($this->_('%s track engines cannot be converted to %s track engines.'), $this->getName(), $conversionTargetClass));
    }

    /**
     * Copy a track and all it's related data (rounds/fields etc)
     *
     * @param inte $oldTrackId  The id of the track to copy
     * @return int              The id of the copied track
     */
    public function copyTrack($oldTrackId)
    {
        $trackModel = $this->tracker->getTrackModel();

        $roundModel = $this->getRoundModel(true, 'rounds');
        $fieldModel = $this->getFieldsMaintenanceModel(false, 'fields', array());

        // First load the track
        $trackModel->applyParameters(array('id' => $oldTrackId));
        $track = $trackModel->loadFirst();

        // Create an empty track
        $newTrack = $trackModel->loadNew();
        unset($track['gtr_id_track'], $track['gtr_changed'], $track['gtr_changed_by'], $track['gtr_created'], $track['gtr_created_by']);
        $track['gtr_track_name'] .= $this->_(' - Copy');
        $newTrack = $track + $newTrack;
        // Now save (not done yet)
        $savedValues = $trackModel->save($newTrack);
        $newTrackId = $savedValues['gtr_id_track'];

        // Now copy the rounds
        $roundModel->applyParameters(array('id' => $oldTrackId));
        $rounds = $roundModel->load();

        if ($rounds) {
            $numRounds = count($rounds);
            $newRounds = $roundModel->loadNew($numRounds);
            foreach ($newRounds as $idx => $newRound) {
                $round = $rounds[$idx];
                unset($round['gro_id_round'], $round['gro_changed'], $round['gro_changed_by'], $round['gro_created'], $round['gro_created_by']);
                $round['gro_id_track'] = $newTrackId;
                $newRounds[$idx] = $round + $newRounds[$idx];
            }
            // Now save (not done yet)
            $savedValues = $roundModel->saveAll($newRounds);
        } else {
            $numRounds = 0;
        }

        // Now copy the fields
        $fieldModel->applyParameters(array('id' => $oldTrackId));
        $fields = $fieldModel->load();

        if ($fields) {
            $numFields = count($fields);
            $newFields = $fieldModel->loadNew($numFields);
            foreach ($newFields as $idx => $newField) {
                $field = $fields[$idx];
                unset($field['gtf_id_field'], $field['gtf_changed'], $field['gtf_changed_by'], $field['gtf_created'], $field['gtf_created_by']);
                $field['gtf_id_track'] = $newTrackId;
                $newFields[$idx] = $field + $newFields[$idx];
            }
            // Now save (not done yet)
            $savedValues = $fieldModel->saveAll($newFields);
        } else {
            $numFields = 0;
        }

        //MUtil_Echo::track($track, $copy);
        //MUtil_Echo::track($rounds, $newRounds);
        //MUtil_Echo::track($fields, $newFields);
        Zend_Controller_Action_HelperBroker::getStaticHelper('FlashMessenger')->addMessage(sprintf($this->_('Copied track, including %s round(s) and %s field(s).'), $numRounds, $numFields));

        return $newTrackId;
    }

    /**
     * Create model for rounds. Allowes overriding by sub classes.
     *
     * @return Gems_Model_JoinModel
     */
    protected function createRoundModel()
    {
        return new Gems_Model_JoinModel('rounds', 'gems__rounds', 'gro');
    }

    /**
     * Displays the content spaced
     *
     * @param string $value
     * @return string
     */
    public function formatMultiField($value)
    {
        // MUtil_Echo::track($value);
        if (is_array($value)) {
            return implode(' ', $value);
        } else {
            return $value;
        }
    }

    /**
     * Returns a list of classnames this track engine can be converted into.
     *
     * Should always contain at least the class itself.
     *
     * @see convertTo()
     *
     * @param array $options The track engine class options available in as a "track engine class names" => "descriptions" array
     * @return array Filter or adaptation of $options
     */
    public function getConversionTargets(array $options)
    {
        $classParts = explode('_', get_class($this));
        $className  = end($classParts);

        return array($className => $options[$className]);
    }

    /**
     * Returns an array of the fields in this track
     * key / value are id / field name
     *
     * @return array fieldid => fieldcode
     */
    public function getFieldNames()
    {
        $this->_ensureTrackFields();

        $fields = array();

        foreach ($this->_trackFields as $key => $field) {
            $fields[$key] = $field['gtf_field_name'];
        }

        return $fields;
    }

    /**
     * Returns an array of the fields in this track
     * key / value are id / code
     *
     * @return array fieldid => fieldcode
     */
    public function getFields()
    {
        $this->_ensureTrackFields();

        $fields = array();

        foreach ($this->_trackFields as $key => $field) {
            $fields[$key] = $field['gtf_field_code'];
        }

        return $fields;
    }

    /**
     * Returns the field data for the respondent track id.
     *
     * @param int $respTrackId Gems respondent track id or null when new
     * @return array of the existing field values for this respondent track
     */
    public function getFieldsData($respTrackId)
    {
        $this->_ensureTrackFields();

        $defaults = array_fill_keys(array_keys($this->_trackFields), null);

        if (! $respTrackId) {
            // Return empty array as we do not store default values for fields
            return $defaults;
        }

        $sql     = "
            SELECT CONCAT(?, ?, gr2t2a_id_app_field) AS gr2t2f_id_field, gr2t2a_id_appointment AS gr2t2f_value
                FROM gems__respondent2track2appointment
                WHERE gr2t2a_id_respondent_track = ?
            UNION ALL
            SELECT CONCAT(?, ?, gr2t2f_id_field) AS gr2t2f_id_field, gr2t2f_value
                FROM gems__respondent2track2field
                WHERE gr2t2f_id_respondent_track = ?";

        $results = $this->db->fetchPairs($sql, array(
            Gems_Tracker_Model_FieldMaintenanceModel::APPOINTMENTS_NAME,
            self::FIELD_KEY_SEPARATOR,
            $respTrackId,
            Gems_Tracker_Model_FieldMaintenanceModel::FIELDS_NAME,
            self::FIELD_KEY_SEPARATOR,
            $respTrackId,
            ));

        // MUtil_Echo::track($respTrackId, $sql, $results);

        if ($results) {
            $this->_ensureTrackFields();

            foreach ($results as $field => $result) {
                if (isset($this->_trackFields[$field])) {
                    switch ($this->_trackFields[$field]['gtf_field_type']) {
                        case 'multiselect':
                            $results[$field] = explode(self::FIELD_SEP, $result);
                            break;

                        case 'date':
                            if (empty($result)) {
                                $results[$field] = null;
                            } else {
                                $results[$field] = new MUtil_Date($result, Zend_Date::ISO_8601);
                            }

                        default:
                            break;
                    }
                }
            }
        }

        return $results + $defaults;
    }

    /**
     * Returns the fields required for editing a track of this type.
     *
     * @return array of Zend_Form_Element
     * /
    public function getFieldsElements()
    {
        $this->_ensureTrackFields();

        $elements = array();

        if ($this->_trackFields) {
            $empty = $this->util->getTranslated()->getEmptyDropdownArray();

            foreach ($this->_trackFields as $name => $field) {

                // Get the field
                $multi = explode(self::FIELD_SEP, $field['gtf_field_values']);
                $multi = array_combine($multi, $multi);

                if ($field['gtf_readonly']) {
                    $element = new MUtil_Form_Element_Exhibitor($name);

                } else {
                    switch ($field['gtf_field_type']) {
                        case "multiselect":
                            $element = new Zend_Form_Element_MultiCheckbox($name);
                            $element->setMultiOptions($multi);
                            break;

                        case "select":
                            $element = new Zend_Form_Element_Select($name);
                            $element->setMultiOptions($empty + $multi);
                            break;

                        case "date":
                            $options = array();
                            MUtil_Model_FormBridge::applyFixedOptions('date', $options);

                            $element = new Gems_JQuery_Form_Element_DatePicker($name, $options);
                            $element->setStorageFormat('yyyy-MM-dd');
                            break;

                        case "appointment":
                            /*
                            $multi   = $this->
                            $element = new Zend_Form_Element_Select($name);
                            $element->setMultiOptions($empty + $multi);
                            break;
                            // * /

                        default:
                            $element = new Zend_Form_Element_Text($name);
                            $element->setAttrib('size', 40);
                            break;
                    }
                }
                $element->setLabel($field['gtf_field_name'])
                        ->setRequired($field['gtf_required'])
                        ->setDescription($field['gtf_field_description']);

                $elements[$name] = $element;
            }
        }

        return $elements;
    }

    /**
     * Returns a model that can be used to retrieve or save the field definitions for the track editor.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @param array $data the current request data
     * @return Gems_Tracker_Model_FieldMaintenanceModel
     */
    public function getFieldsMaintenanceModel($detailed, $action, array $data)
    {
        if (isset($this->_fieldModels[$action])) {
            return $this->_fieldModels[$action];
        }

        $model = $this->tracker->createTrackClass('Model_FieldMaintenanceModel');

        if ($detailed) {
            if (('edit' === $action) || ('create' === $action)) {
                $model->applyEditSettings($this->_trackId, $data);

                if ('create' === $action) {
                    $model->set('gtf_id_track', 'default', $this->_trackId);

                    // Set the default round order

                    // Load last row
                    $row = $model->loadFirst(
                            array('gtf_id_track' => $this->_trackId),
                            array('gtf_id_order' => SORT_DESC)
                            );

                    if ($row && isset($row['gtf_id_order'])) {
                        $new_order = $row['gtf_id_order'] + 10;
                        $model->set('gtf_id_order', 'default', $new_order);
                    }
                }
            } else {
                $model->applyDetailSettings($this->_trackId, $data);
            }

        } else {
            $model->applyBrowseSettings();
        }

        $this->_fieldModels[$action] = $model;

        return $model;
    }

    /**
     * Get a big array with model settings for fields in a track
     *
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return array fieldname => array(settings)
     */
    public function getFieldsModelSettings($respondentId, $organizationId, $patientNr = null, $edit = true)
    {
        $this->_ensureTrackFields();

        if (! $this->_trackFields) {
            return array();
        }

        $fieldSettings = array();
        $appointments  = null;
        $empty         = $this->util->getTranslated()->getEmptyDropdownArray();

        foreach ($this->_trackFields as $name => $field) {

            $fieldSettings[$name] = array(
                'label'       => $field['gtf_field_name'],
                'required'    => $field['gtf_required'],
                'description' => $field['gtf_field_description'],
                );

            if ($field['gtf_readonly']) {
                $fieldSettings[$name]['elementClass'] = 'Exhibitor';

            } else {
                switch ($field['gtf_field_type']) {
                    case "multiselect":
                        $multi = explode(self::FIELD_SEP, $field['gtf_field_values']);
                        $multi = array_combine($multi, $multi);

                        $fieldSettings[$name]['elementClass']   = 'MultiCheckbox';
                        $fieldSettings[$name]['multiOptions']   = $multi;
                        $fieldSettings[$name]['formatFunction'] = array($this, 'formatMultiField');

                        break;

                    case "select":
                        $multi = explode(self::FIELD_SEP, $field['gtf_field_values']);
                        $multi = array_combine($multi, $multi);

                        $fieldSettings[$name]['elementClass'] = 'Select';
                        $fieldSettings[$name]['multiOptions'] = $empty + $multi;
                        break;

                    case "date":
                        $fieldSettings[$name]['elementClass']  = 'Date';
                        $fieldSettings[$name]['storageFormat'] = 'yyyy-MM-dd';
                        break;

                    case "appointment":
                        if (! $appointments) {
                            $agenda       = $this->loader->getAgenda();
                            $appointments = $agenda->getActiveAppointments($respondentId, $organizationId, $patientNr);
                            // MUtil_Echo::track($appointments);
                        }
                        $fieldSettings[$name]['elementClass'] = 'Select';
                        $fieldSettings[$name]['multiOptions'] = $empty + $appointments;
                        break;

                    default:
                        $fieldSettings[$name]['elementClass'] = 'Text';
                        $fieldSettings[$name]['size']         = 40;
                        break;
                }
            }
        }

        return $fieldSettings;
    }

    /**
     * Returns an array name => code of all the fields of the type specified
     *
     * @param string $fieldType
     * @return array name => code
     */
    public function getFieldsOfType($fieldType)
    {
        $this->_ensureTrackFields();

        if (! $this->_trackFields) {
            return array();
        }

        $output = array();

        foreach ($this->_trackFields as $key => $field) {
            if ($fieldType == $field['gtf_field_type']) {
                $output[$key] = $field['gtf_field_code'];
            }
        }

        return $output;
    }

    /**
     * Get the round id of the first round
     *
     * @return int Gems id of first round
     */
    public function getFirstRoundId()
    {
        $this->_ensureRounds();

        reset($this->_rounds);

        return key($this->_rounds);
    }

    /**
     * Look up the round id for the next round
     *
     * @param int $roundId  Gems round id
     * @return int Gems round id
     */
    public function getNextRoundId($roundId)
    {
       $this->_ensureRounds();

       if ($this->_rounds && $roundId) {
           $next = false;
           foreach ($this->_rounds as $currentRoundId => $round) {
               if ($next) {
                   return $currentRoundId;
               }
               if ($currentRoundId == $roundId) {
                   $next = true;
               }
           }

           return null;

       } elseif ($this->_rounds) {
           end($this->_rounds);
           return key($this->_rounds);
       }
    }

    /**
     * Look up the round id for the previous round
     *
     * @param int $roundId  Gems round id
     * @param int $roundOrder Optional extra round order, for when the current round may have changed.
     * @return int Gems round id
     */
    public function getPreviousRoundId($roundId, $roundOrder = null)
    {
       $this->_ensureRounds();

       if ($this->_rounds && $roundId) {
           $returnId = null;
           foreach ($this->_rounds as $currentRoundId => $round) {
               if (($currentRoundId == $roundId) || ($roundOrder && ($round['gro_id_order'] >= $roundOrder))) {
                   // Null is returned when querying this function with the first round id.
                   return $returnId;
               }
               $returnId = $currentRoundId;
           }


           throw new Gems_Exception_Coding("Requested non existing previous round id for round $roundId.");

       } elseif ($this->_rounds) {
           end($this->_rounds);
           return key($this->_rounds);
       }
    }

    /**
     * Returns a snippet name that can be used to display the answers to the token or nothing.
     *
     * @param Gems_Tracker_Token $token
     * @return array Of snippet names
     */
    public function getRoundAnswerSnippets(Gems_Tracker_Token $token)
    {
        $this->_ensureRounds();
        $roundId = $token->getRoundId();

        if (isset($this->_rounds[$roundId]['gro_display_event']) && $this->_rounds[$roundId]['gro_display_event']) {
            $event = $this->events->loadSurveyDisplayEvent($this->_rounds[$roundId]['gro_display_event']);

            return $event->getAnswerDisplaySnippets($token);
        }
    }

    /**
     * Return the Round Changed event name for this round
     *
     * @param int $roundId
     * @return Gems_Event_RoundChangedEventInterface event instance or null
     */
    public function getRoundChangedEvent($roundId)
    {
        $this->_ensureRounds();

        if (isset($this->_rounds[$roundId]['gro_changed_event']) && $this->_rounds[$roundId]['gro_changed_event']) {
            return $this->events->loadRoundChangedEvent($this->_rounds[$roundId]['gro_changed_event']);
        }
    }

    /**
     * Get the defaults for a new round
     *
     * @return array Of fieldname => default
     */
    public function getRoundDefaults()
    {
        $this->_ensureRounds();

        if ($this->_rounds) {
            $defaults = end($this->_rounds);
            unset($defaults['gro_id_round'], $defaults['gro_id_survey']);

            $defaults['gro_id_order'] = $defaults['gro_id_order'] + 10;
        } else {
            // Rest of defaults come form model
            $defaults = array('gro_id_track' => $this->_trackId);
        }

        return $defaults;
    }

    /**
     * A generic helper function for generating a round description.
     *
     * @param array $roundData Contents of the round
     * @return string
     */
    protected function getRoundDescription(array $roundData)
    {
        $surveys = $this->util->getTrackData()->getAllSurveys();

        $hasOrder  = $roundData['gro_id_order'];
        $hasDescr  = strlen(trim($roundData['gro_round_description']));
        $hasSurvey = isset($surveys[$roundData['gro_id_survey']]);

        if ($hasOrder && $hasDescr && $hasSurvey) {
            return sprintf($this->_('%d: %s - %s'),
                $roundData['gro_id_order'], $roundData['gro_round_description'], $surveys[$roundData['gro_id_survey']]);
        } elseif ($hasOrder && $hasDescr) {
            return sprintf($this->_('%d: %s'),
                $roundData['gro_id_order'], $roundData['gro_round_description']);
        } elseif ($hasOrder && $hasSurvey) {
            return sprintf($this->_('%d: %s'),
                $roundData['gro_id_order'], $surveys[$roundData['gro_id_survey']]);
        } elseif ($hasDescr && $hasSurvey) {
            return sprintf($this->_('%s - %s'),
                $roundData['gro_round_description'], $surveys[$roundData['gro_id_survey']]);
        } elseif ($hasDescr) {
            return $roundData['gro_round_description'];
        } elseif ($hasSurvey) {
            return $surveys[$roundData['gro_id_survey']];
        } else {
            return '';
        }
    }

    /**
     * An array of snippet names for editing a round.
     *
     * @return array of string snippet names
     */
    public function getRoundEditSnippetNames()
    {
        return array('EditRoundSnippet');
    }

    /**
     * Returns a model that can be used to retrieve or save the data.
     *
     * @param boolean $detailed Create a model for the display of detailed item data or just a browse table
     * @param string $action The current action
     * @return MUtil_Model_ModelAbstract
     */
    public function getRoundModel($detailed, $action)
    {
        $model = $this->createRoundModel();

        // Set the keys to the parameters in use.
        $model->setKeys(array(MUtil_Model::REQUEST_ID => 'gro_id_track', Gems_Model::ROUND_ID => 'gro_id_round'));

        if ($detailed) {
            $model->set('gro_id_track',  'label', $this->_('Track'), 'elementClass', 'exhibitor', 'multiOptions', MUtil_Lazy::call($this->util->getTrackData()->getAllTracks));
        }

        $model->set('gro_id_survey',         'label', $this->_('Survey'),         'multiOptions', $this->util->getTrackData()->getAllSurveysAndDescriptions());
        $model->set('gro_icon_file',         'label', $this->_('Icon'));
        $model->set('gro_id_order',          'label', $this->_('Order'),          'default', 10, 'validators[]', $model->createUniqueValidator(array('gro_id_order', 'gro_id_track')));
        $model->set('gro_round_description', 'label', $this->_('Description'),    'size', '30'); //, 'minlength', 4, 'required', true);

        $list = $this->events->listRoundChangedEvents();
        if (count($list) > 1) {
            $model->set('gro_changed_event',     'label', $this->_('After change'),   'multiOptions', $list);
        }
        $list = $this->events->listSurveyDisplayEvents();
        if (count($list) > 1) {
            $model->set('gro_display_event',     'label', $this->_('Answer display'), 'multiOptions', $list);
        }
        $model->set('gro_active',            'label', $this->_('Active'),         'multiOptions', $this->util->getTranslated()->getYesNo(), 'elementClass', 'checkbox');
        $model->setIfExists('gro_code',          'label', $this->_('Code name'), 'size', 10, 'description', $this->_('Only for programmers.'));

        $model->addColumn(
            "CASE WHEN gro_active = 1 THEN '' ELSE 'deleted' END",
            'row_class');

        switch ($action) {
            case 'create':
                $this->_ensureRounds();

                if ($this->_rounds && ($round = end($this->_rounds))) {
                    $model->set('gro_id_order', 'default', $round['gro_id_order'] + 10);
                }
                // Intentional fall through
                // break;
            case 'edit':
            	$model->set('gro_icon_file', 'multiOptions', $this->util->getTranslated()->getEmptyDropdownArray() + $this->_getAvailableIcons());
                break;

            default:
                $model->set('gro_icon_file', 'formatFunction', array('MUtil_Html_ImgElement', 'imgFile'));
                break;

        }

        return $model;
    }

    /**
     * An array of snippet names for editing a round.
     *
     * @return array of string snippet names
     */
    public function getRoundShowSnippetNames()
    {
        return array('ShowRoundSnippet');
    }

    /**
     * Returns a model that can be used to save, edit, etc. the token
     *
     * @return Gems_Tracker_Model_StandardTokenModel
     */
    public function getTokenModel()
    {
        return $this->tracker->getTokenModel();
    }

    /**
     * Get the TrackCompletedEvent for the given trackId
     *
     * @return Gems_Event_TrackCalculationEventInterface | null
     */
    public function getTrackCalculationEvent()
    {
        if (isset($this->_trackData['gtr_calculation_event']) && $this->_trackData['gtr_calculation_event']) {
            return $this->events->loadTrackCalculationEvent($this->_trackData['gtr_calculation_event']);
        }
    }

    /**
     * Get the TrackCompletedEvent for the given trackId
     *
     * @return Gems_Event_TrackCompletedEventInterface|null
     */
    public function getTrackCompletionEvent()
    {
        if (isset($this->_trackData['gtr_completed_event']) && $this->_trackData['gtr_completed_event']) {
            return $this->events->loadTrackCompletionEvent($this->_trackData['gtr_completed_event']);
        }
    }

    /**
     *
     * @return int The track id
     */
    public function getTrackId()
    {
        return $this->_trackId;
    }

    /**
     *
     * @return string The gems track name
     */
    public function getTrackName()
    {
        return $this->_trackData['gtr_track_name'];
    }

    /**
     * True when this track contains appointment fields
     *
     * @return boolean
     */
    protected function hasAppointmentFields()
    {
        if (null === $this->_hasAppointmentFields) {
            $this->_ensureTrackFields();
            $this->_hasAppointmentFields = false;

            foreach ($this->_trackFields as $field) {
                if ('appointment' == $field['gtf_field_type']) {
                    $this->_hasAppointmentFields = true;
                    break;
                }
            }
        }

        return $this->_hasAppointmentFields;
    }

    /**
     * Remove the unanswered tokens for inactive rounds.
     *
     * @param Gems_Tracker_RespondentTrack $respTrack The respondent track to check
     * @param int $userId Id of the user who takes the action (for logging)
     * @return int The number of tokens changed by this code
     */
    protected function removeInactiveRounds(Gems_Tracker_RespondentTrack $respTrack, $userId)
    {
        $qTrackId     = $this->db->quote($this->_trackId);
        $qRespTrackId = $this->db->quote($respTrack->getRespondentTrackId());

        $where = "gto_start_time IS NULL AND
            gto_id_respondent_track = $qRespTrackId AND
            gto_id_round IN (SELECT gro_id_round
                    FROM gems__rounds
                    WHERE gro_active = 0 AND
                        gro_id_track = $qTrackId)";

        return $this->db->delete('gems__tokens', $where);
    }

    /**
     * Saves the field data for the respondent track id.
     *
     * @param int $respTrackId Gems respondent track id
     * @param array $data The values to save
     * @return int The number of changed fields
     */
    public function setFieldsData($respTrackId, array $data)
    {
        $element         = null;
        $newAppointments = array();
        $newFields       = array();

        $this->_ensureTrackFields();

        // Clean up any keys not in fields
        $data = array_intersect_key($data, $this->_trackFields);
        // MUtil_Echo::track($data);

        foreach ($data as $key => $value) {

            list($sub, $id) = explode(self::FIELD_KEY_SEPARATOR, $key);

            if (is_array($value)) {
                $value = implode(self::FIELD_SEP, $value);
            }

            // Do the hard work for storing dates
            if (isset($this->_trackFields[$key]['gtf_field_type']) &&
                    ('date' == $this->_trackFields[$key]['gtf_field_type'])) {
                if (! empty($value)) {
                    $value = MUtil_Date::format(
                            $value,
                            'yyyy-MM-dd',
                            MUtil_Model_FormBridge::getFixedOption('date', 'dateFormat')
                            );
                } else {
                    $value = null;
                }
            }

            if (Gems_Tracker_Model_FieldMaintenanceModel::APPOINTMENTS_NAME === $sub) {
                $newAppointments[] = array(
                    'gr2t2a_id_respondent_track' => $respTrackId,
                    'gr2t2a_id_app_field'        => $id,
                    'gr2t2a_id_appointment'      => $value,
                );
            } elseif (Gems_Tracker_Model_FieldMaintenanceModel::FIELDS_NAME === $sub) {
                $newFields[]= array(
                    'gr2t2f_id_respondent_track' => $respTrackId,
                    'gr2t2f_id_field'            => $id,
                    'gr2t2f_value'               => $value,
                );
            }
        }

        $changed = 0;
        if ($newAppointments) {
            $model = new MUtil_Model_TableModel('gems__respondent2track2appointment');

            Gems_Model::setChangeFieldsByPrefix($model, 'gr2t2a');

            $model->saveAll($newAppointments);

            $changed = $changed + $model->getChanged();
        }
        if ($newFields) {
            $model = new MUtil_Model_TableModel('gems__respondent2track2field');

            Gems_Model::setChangeFieldsByPrefix($model, 'gr2t2f');

            $model->saveAll($newFields);

            $changed = $changed + $model->getChanged();
        }

        return $changed;
    }

    /**
     * Updates the number of rounds in this track.
     *
     * @param int $userId The current user
     * @return int 1 if data changed, 0 otherwise
     */
    public function updateRoundCount($userId)
    {
        $values['gtr_survey_rounds'] = $this->calculateRoundCount();

        return $this->_update($values, $userId);
    }
}