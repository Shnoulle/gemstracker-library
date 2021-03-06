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
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Controller for maintaining reception codes.
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_ReceptionAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = array(
        'extraSort' => array(
            'grc_id_reception_code' => SORT_ASC,
            ),
        );

    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public $cacheTags = array('receptionCode', 'receptionCodes');

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $rcLib = $this->util->getReceptionCodeLibrary();
        $yesNo  = $this->util->getTranslated()->getYesNo();

        $model  = new \MUtil_Model_TableModel('gems__reception_codes');
        $model->copyKeys(); // The user can edit the keys.

        $model->set('grc_id_reception_code', 'label', $this->_('Code'), 'size', '10');
        $model->set('grc_description',       'label', $this->_('Description'), 'size', '30');

        $model->set('grc_success',           'label', $this->_('Is success code'),
            'multiOptions', $yesNo ,
            'elementClass', 'CheckBox',
            'description', $this->_('This reception code is a success code.'));
        $model->set('grc_active',            'label', $this->_('Active'),
            'multiOptions', $yesNo,
            'elementClass', 'CheckBox',
            'description', $this->_('Only active codes can be selected.'));
        if ($detailed) {
            $model->set('desc1', 'elementClass', 'Html',
                    'label', ' ',
                    'value', \MUtil_Html::create('h4', $this->_('Can be assigned to'))
                    );
        }
        $model->set('grc_for_respondents',   'label', $this->_('Respondents'),
            'multiOptions', $yesNo,
            'elementClass', 'CheckBox',
            'description', $this->_('This reception code can be assigned to a respondent.'));
        $model->set('grc_for_tracks',        'label', $this->_('Tracks'),
            'multiOptions', $yesNo,
            'elementClass', 'CheckBox',
            'description', $this->_('This reception code can be assigned to a track.'));
        $model->set('grc_for_surveys',       'label', $this->_('Tokens'),
            'multiOptions', $rcLib->getSurveyApplicationValues(),
            'description', $this->_('This reception code can be assigned to a token.'));
        if ($detailed) {
            $model->set('desc2', 'elementClass', 'Html',
                    'label', ' ',
                     'value', \MUtil_Html::create('h4', $this->_('Additional actions'))
                    );
        }
        $model->set('grc_redo_survey',       'label', $this->_('Redo survey'),
            'multiOptions', $rcLib->getRedoValues(),
            'description', $this->_('Redo a survey on this reception code.'));
        $model->set('grc_overwrite_answers', 'label', $this->_('Overwrite existing consents'),
            'multiOptions', $yesNo,
            'elementClass', 'CheckBox',
            'description', $this->_('Remove the consent from already answered surveys.'));

        if ($detailed) {
            $model->set('grc_id_reception_code', 'validator', $model->createUniqueValidator('grc_id_reception_code'));
            $model->set('grc_description',       'validator', $model->createUniqueValidator('grc_description'));
        }

        if ($this->project->multiLocale) {
            $model->set('grc_description',       'description', 'ENGLISH please! Use translation file to translate.');
        }

        \Gems_Model::setChangeFieldsByPrefix($model, 'grc');

        return $model;
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return $string
     */
    public function getIndexTitle()
    {
        return $this->_('Reception codes');
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('reception code', 'reception codes', $count);
    }
}
