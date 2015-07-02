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
 * @subpackage Export
 * @author     Jasper van Gestel <jvangestel@gmail.com>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AnswerExportModelSource.php 2451 2015-03-09 18:03:25Z matijsdejong $
 */

/**
 *
 * @package    Gems
 * @subpackage Export
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class Gems_Export_ModelSource_AnswerExportModelSource extends \Gems_Export_ModelSource_ExportModelSourceAbstract
{

	/**
     * Defines the value used for 'no round description'
     *
     * It this value collides with a used round description, change it to something else
     */
    const NoRound = '-1';

    /**
     * @var \Zend_Db_Adapter_Abstract
     */
    public $db;

    /**
     * @var \Gems_Loader
     */
	public $loader;

    /**
     * @var \Zend_Locale
     */
	public $locale;

	/**
     *
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * @var \Gems_Util
     */
	public $util;


    /**
     * Translate the set form options to a valid filter for the model for the Response Database
     * @param  array data existing options set in the form
     * @param  array Filter for the model already set in getFilters()
     * @return array Filter for the model
     */
	protected function getResponseDatabaseFilter($data, &$filter)
    {
        if (isset($data['filter_answer']) &&
                (!empty($data['filter_answer'])) &&
                isset($data['filter_value']) &&
                $data['filter_value'] !== '') {

            $select = $this->db->select()
                    ->from('gemsdata__responses', array(''))
                    ->join('gems__tokens', 'gto_id_token = gdr_id_token', array(''))
                    ->where('gdr_answer_id = ?', $data['filter_answer']);

            if (is_array($data['filter_value'])) {
                $select->where('gdr_response IN (?)', $data['filter_value']);
            } else {
                $select->where('gdr_response = ?', $data['filter_value']);
            }

            $select->distinct()
                   ->columns('gto_id_respondent', 'gems__tokens');

            $result = $select->query()->fetchAll(\Zend_Db::FETCH_COLUMN);

            if (!empty($result)) {
                $filter['respondentid'] = $result;
            } else {
                $filter['respondentid'] = -1;
            }
        }
    }

    /**
     * Translate the set form options to a valid filter for the model
     * @param  array data existing options set in the form
     * @return array Filter for the model
     */
	public function getFilters($data)
	{
		$filters = array();

        $dateOutFormat = 'yyyy-MM-dd';
        $dateInFormat  = \MUtil_Model_Bridge_FormBridge::getFixedOption('date', 'dateFormat');

		if (isset($data['sid']) && is_array($data['sid'])) {
			foreach($data['sid'] as $surveyId) {
				if ($surveyId) {
					$filter = array();
			        if (isset($data['ids'])) {
			            $idStrings = $data['ids'];

			            $idArray = preg_split('/[\s,;]+/', $idStrings, -1, PREG_SPLIT_NO_EMPTY);

			            if ($idArray) {
			                // Make sure output is OK
			                // $idArray = array_map(array($this->db, 'quote'), $idArray);

			                $filter['gto_id_respondent'] = $idArray;
			            }
			        }

			        if ($this->project->hasResponseDatabase()) {
            			$this->getResponseDatabaseFilter($data, $filter);
        			}

			        /*if ($this->project->hasResponseDatabase()) {
			            $this->_getResponseDatabaseFilter($data, $filter);
			        }*/

			        if (isset($data['tid']) && !empty($data['tid'])) {
			            $filter['gto_id_track'] = $data['tid'];
			        }

			        if (isset($data['sid'])) {
			        	$filter['gto_id_survey'] = $surveyId;
			        }

			        if (isset($data['rounds']) && !empty($data['rounds'])) {
			        	$filter['gto_round_description'] = $data['rounds'];
			        }

			        if (isset($data['oid'])) {
			            $filter['gto_id_organization'] = $data['oid'];
			        } else {
			            //Invalid id so when nothing selected... we get nothing
			            // $filter['organizationid'] = '-1';
			        }

                    if (isset($data['valid_from']) && !empty($data['valid_from'])) {
                        $filter[] = "gto_valid_from >= ".$this->db->quote(\MUtil_Date::format($data['valid_from'],  $dateOutFormat, $dateInFormat))."";
                    }
                    if (isset($data['valid_until']) && !empty($data['valid_until'])) {
                        $filter[] = "gto_valid_until >= ".$this->db->quote(\MUtil_Date::format($data['valid_until'],  $dateOutFormat, $dateInFormat))."";
                    }

                    /*if (isset($data['valid_until'])) {
                        $filter[] = 'gto_valid_until >= ' . $data['valid_until'];
                    }*/

			        $filter['grc_success'] = 1;
                    $filter[] = "gto_completion_time IS NOT NULL";
			        // Consent codes
			        /*$filter['consentcode'] = array_diff(
			                (array) $this->util->getConsentTypes(),
			                (array) $this->util->getConsentRejected()
			                );
					*/
			        $filters[] = $filter;
			    }
		    }
		}

        // \Gems_Tracker::$verbose = true;
        return $filters;
	}

	/**
     * Get form elements for the specific Export
     * @param  \Gems_Form $form existing form type
     * @param  array data existing options set in the form
     * @return array of form elements
     */
	public function getFormElements(\Gems_Form $form, &$data)
	{

        $form->activateJQuery();
		$dbLookup      = $this->util->getDbLookup();
		$translated    = $this->util->getTranslated();
		$noRound       = array(self::NoRound => $this->_('No round description'));
        $empty         = $translated->getEmptyDropdownArray();

        $dateOptions = array();
        \MUtil_Model_Bridge_FormBridge::applyFixedOptions('date', $dateOptions);

        $organizations = $this->loader->getCurrentUser()->getRespondentOrganizations();

        $tracks        = $empty + $this->util->getTrackData()->getAllTracks();
    	$rounds        = $empty + $noRound + $dbLookup->getRoundsForExport(
                isset($data['tid']) ? $data['tid'] : null
            );

        $surveys       = $dbLookup->getSurveysForExport(isset($data['tid']) ? $data['tid'] : null, isset($data['rounds']) ? $data['rounds'] : null);

        $yesNo         = $translated->getYesNo();
		$elements = array();

		$element = $form->createElement('textarea', 'ids');
        $element->setLabel($this->_('Respondent id\'s'))
                ->setAttrib('cols', 60)
                ->setAttrib('rows', 4)
                ->setDescription($this->_('Not respondent nr, but respondent id as exported here.'));
        $elements[] = $element;

        $element = $form->createElement('select', 'tid');
        $element->setLabel($this->_('Tracks'))
            ->setMultiOptions($tracks);
        $elements[] = $element;

        if (isset($data['tid']) && $data['tid']) {
            $element = $form->createElement('radio', 'tid_fields');
            $element->setLabel($this->_('Export fields'))
                ->setMultiOptions($yesNo);
            $elements[] = $element;

            if (!array_key_exists('tid_fields', $data)) {
                $data['tid_fields'] = 1;
            }
        }

        $element = $form->createElement('select', 'rounds');
        $element->setLabel($this->_('Round description'))
            ->setMultiOptions($rounds);
        $elements[] = $element;

        $element = $form->createElement('multiselect', 'sid');
        $element->setLabel($this->_('Survey'))
            ->setMultiOptions($surveys)
            ->setDescription($this->_('Use CTRL or Shift to select more'));
        $elements[] = $element;

        $element = $form->createElement('multiCheckbox', 'oid');
        $element->setLabel($this->_('Organization'))
                ->setMultiOptions($organizations);
        $elements[] = $element;

        if (\MUtil_Bootstrap::enabled()) {
            $element = new \MUtil_Bootstrap_Form_Element_ToggleCheckboxes('toggleOrg', array('selector'=>'input[name^=oid]'));
        } else {
            $element = new \Gems_JQuery_Form_Element_ToggleCheckboxes('toggleOrg', array('selector'=>'input[name^=oid]'));
        }

        $element->setLabel($this->_('Toggle'));
        $elements[] = $element;

        $element = $form->createElement('datePicker', 'valid_from', $dateOptions);
        $element->setLabel($this->_('Valid from'));

        $elements[] = $element;

        $element = $form->createElement('datePicker', 'valid_until', $dateOptions);
        $element->setLabel($this->_('Valid until'));
        $elements[] = $element;

        if (\MUtil_Bootstrap::enabled()) {
            $element = new \MUtil_Bootstrap_Form_Element_ToggleCheckboxes('toggleOrg', array('selector'=>'input[name^=oid]'));
        } else {
            $element = new \Gems_JQuery_Form_Element_ToggleCheckboxes('toggleOrg', array('selector'=>'input[name^=oid]'));
        }

        $element = $form->createElement('checkbox', 'column_identifiers');
        $element->setLabel($this->_('Column Identifiers'));
        $element->setDescription($this->_('Prefix the column labels with an identifier. (A) Answers, (TF) Trackfields, (D) Description'));
        $elements[] = $element;


        //unset($data['records']);
        if (!empty($data['sid'])) {
        	$filters   = $this->getFilters($data);
        	foreach($filters as $key => $filter) {
        		unset($data['records_'.$key]);
        		$model = $this->getModel($filter, $data);
	            $survey   = $this->loader->getTracker()->getSurvey(intval($filter['gto_id_survey']));

	           	$recordCount = $model->loadPaginator($filter)->getTotalItemCount();
	            $element = $form->createElement('exhibitor', 'records_'.$key);
	            $element->setValue($survey->getName() . ': ' . sprintf($this->_('%s records found.'), $recordCount));
	            //$element->setValue($survey->getName());
	            $elements[] = $element;
	        }
        }

        if ($this->project->hasResponseDatabase()) {
            $this->addResponseDatabaseForm($form, $data, $elements);
        }

		return $elements;
	}

    /**
     * Add form elements when a responseDatabase is present
     * @param  \Gems_Form $form existing form type
     * @param  array data existing options set in the form
     * @return array of form elements
     */
    protected function addResponseDatabaseForm($form, &$data, &$elements)
    {
        if (isset($data['tid']) && (!empty($data['tid']))) {
            // If we have a responsedatabase and a track id, try something cool ;-)
            $responseDb = $this->project->getResponseDatabase();
            if ($this->db === $responseDb) {
                // We are in the same database, now put that to use by allowing to filter respondents based on an answer in any survey
                $empty      = $this->util->getTranslated()->getEmptyDropdownArray();
                $allSurveys = $empty + $this->util->getDbLookup()->getSurveysForExport();

                $element = new \Zend_Form_Element_Select('filter_sid');
                $element->setLabel($this->_('Survey'))
                        ->setMultiOptions($allSurveys);

                $groupElements = array($element);

                if (isset($data['filter_sid']) && !empty($data['filter_sid'])) {
                    $filterSurvey    = $this->loader->getTracker()->getSurvey($data['filter_sid']);
                    $filterQuestions = $empty + $filterSurvey->getQuestionList($this->locale->getLanguage());

                    $element = new \Zend_Form_Element_Select('filter_answer');
                    $element->setLabel($this->_('Question'))
                            ->setMultiOptions($filterQuestions);
                    $groupElements[] = $element;
                }

                if (isset($filterSurvey) && isset($data['filter_answer']) && !empty($data['filter_answer'])) {
                    $questionInfo = $filterSurvey->getQuestionInformation($this->locale->getLanguage());

                    if (array_key_exists($data['filter_answer'], $questionInfo)) {
                        $questionInfo = $questionInfo[$data['filter_answer']];
                    } else {
                        $questionInfo = array();
                    }

                    if (array_key_exists('answers', $questionInfo) && is_array($questionInfo['answers']) && count($questionInfo['answers']) > 1) {
                        $element = new \Zend_Form_Element_Multiselect('filter_value');
                        $element->setMultiOptions($empty + $questionInfo['answers']);
                        $element->setAttrib('size', count($questionInfo['answers']) + 1);
                    } else {
                        $element = new \Zend_Form_Element_Text('filter_value');
                    }
                    $element->setLabel($this->_('Value'));
                    $groupElements[] = $element;
                }

                $form->addDisplayGroup($groupElements, 'filter', array('showLabels'  => true, 'Description' => $this->_('Filter')));
                array_shift($elements);
            }
        }
    }

    /**
     * Get the model to export
     * @param  array  $filter Filter for the model
     * @param  array  $data   Data from the form options
     * @return \MUtil_Model_ModelAbstract
     */
	public function getModel($filter = array(), $data = array())
	{
		$surveyId = $filter['gto_id_survey'];
        $language    = $this->locale->getLanguage();

        $survey      = $this->loader->getTracker()->getSurvey($surveyId);
        $model = $survey->getAnswerModel($language);

        $questions = $survey->getQuestionList($language);
        foreach($questions as $questionName => $label ) {
            if ($parent = $model->get($questionName, 'parent_question')) {
                if ($model->get($parent, 'type') === \MUtil_Model::TYPE_NOVALUE) {
                    $model->remove($parent, 'label');
                    $model->set($questionName, 'label', $label);
                }
            }
        }

        $prefixes = array();

        $prefixes['A'] = array_keys($questions);

        $source = $survey->getSource();
        $attributes = $source->getAttributes();

        foreach($attributes as $attribute) {
            $model->set($attribute, 'label', $attribute);
        }

        $model->addTable('gems__respondent2track', array('gr2t_id_respondent_track' => 'gto_id_respondent_track'), 'gr2t');
        $model->addTable('gems__tracks', array('gtr_id_track' => 'gto_id_track'), 'gtr');

        $model->set('respondentid',        'label', $this->_('Respondent ID'));
        $model->set('organizationid',      'label', $this->_('Organization'),
                                                'multiOptions', $this->loader->getCurrentUser()->getAllowedOrganizations()
        );
        // Add Consent
        $model->set('consentcode',              'label', $this->_('Consent'));
        $model->set('resptrackid',              'label', $this->_('Respondent track ID'));
        $model->set('gto_round_description',    'label', $this->_('Round description'));
        $model->set('gtr_track_name',           'label', $this->_('Track name'));
        $model->set('gr2t_track_info',          'label', $this->_('Track description'));

        $model->set('submitdate',               'label', $this->_('Submit date'));
        $model->set('startdate',                'label', $this->_('Start date'));
        $model->set('datestamp',                'label', $this->_('Datestamp'));
        $model->set('gto_valid_from',           'label', $this->_('Valid from'));
        $model->set('gto_valid_until',          'label', $this->_('Valid until'));
        $model->set('startlanguage',            'label', $this->_('Start language'));
        $model->set('lastpage',                 'label', $this->_('Last page'));

        $model->set('gto_id_token',                       'label', $this->_('Token'));

        $prefixes['D'] = array_diff($model->getItemNames(), $prefixes['A']);

        if (isset($data['tid_fields']) && $data['tid_fields'] == 1) {
        	$trackId = $filter['gto_id_track'];
        	$engine = $this->loader->getTracker()->getTrackEngine($trackId);
        	$engine->addFieldsToModel($model, false, 'gto_id_respondent_track');

            $prefixes['TF'] = array_diff($model->getItemNames(), $prefixes['A'], $prefixes['D']);

        }

        if (isset($data['column_identifiers']) && $data['column_identifiers'] == 1) {

            foreach ($prefixes as $prefix => $prefixCategory) {
                foreach($prefixCategory as $columnName) {
                    if ($label = $model->get($columnName, 'label')) {
                        $model->set($columnName, 'label', '(' . $prefix . ') ' . $label);
                    }
                }
            }
        }

		return $model;
	}

    /**
     * Get the proposed filename for the export of a model with specific filter options
     * @param  array  $filter Filter for the model
     * @return string   proposed filename
     */
	public function getFileName($filter)
	{
		$surveyId = $filter['gto_id_survey'];
		$survey      = $this->loader->getTracker()->getSurvey($surveyId);

		return $survey->getName();
	}
}