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
 *    * Neither the name of the Erasmus MC nor the
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
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class DeleteSingleSurveyInSourceTokenSnippet extends Gems_Tracker_Snippets_EditSingleSurveyTokenSnippetAbstract
{
    /**
     * Replacement token after a redo delete
     *
     * @var string
     */
    protected $_replacementTokenId;

    /**
     *
     * @var Zend_Session_Namespace
     */
    protected $session;

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
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_FormBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     */
    protected function addFormElements(MUtil_Model_FormBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        $bridge->addHidden('gr2o_id_organization');
        $bridge->addHidden('gr2t_id_respondent_track');
        $bridge->addHidden('gr2t_id_user');
        $bridge->addHidden('gr2t_id_organization');
        $bridge->addHidden('gr2t_id_track');
        $bridge->addHidden('gr2t_active');
        $bridge->addHidden('gr2t_count');
        $bridge->addHidden('gr2t_reception_code');
        $bridge->addHidden('gto_id_respondent_track');
        $bridge->addHidden('gto_id_round');
        $bridge->addHidden('gto_id_respondent');
        $bridge->addHidden('gto_id_organization');
        $bridge->addHidden('gto_id_track');
        $bridge->addHidden('gto_id_survey');
        $bridge->addHidden('gtr_id_track');
        $bridge->addHidden('gtr_track_type');

        $bridge->addExhibitor('gto_id_token');

        // Patient
        $bridge->addExhibitor('gr2o_patient_nr');
        $bridge->addExhibitor('respondent_name');

        // Survey
        $bridge->addExhibitor('gsu_survey_name');
        $bridge->addExhibitor('ggp_name');
        $bridge->addExhibitor('gr2t_track_info');

        // Token
        $bridge->addExhibitor('gto_valid_from');
        $bridge->addExhibitor('gto_valid_until');
        $bridge->addTextarea('gto_comment', 'rows', 3, 'cols', 50);
        $bridge->addExhibitor('gto_completion_time');
        $bridge->addList('gto_reception_code');

        // Change the button
        $this->saveLabel = $this->getTitle();
    }

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        // Communicate to user
        if ($changed) {
            $this->addMessage(sprintf($this->_('%2$u %1$s deleted'), $this->getTopic($changed), $changed));
        } else {
            parent::afterSave($changed);
        }
    }

    /**
     * Creates the model
     *
     * @return MUtil_Model_ModelAbstract
     */
    protected function createModel()
    {
        $model = parent::createModel();

        $options = $this->util->getReceptionCodeLibrary()->getTrackDeletionCodes();

        $model->set('gto_reception_code',
            'label',        $model->get('grc_description', 'label'),
            'multiOptions', $options,
            'required',     true,
            'size',         max(7, min(3, count($options) + 1)));

        return $model;
    }

    /**
     *
     * @return Gems_Menu_MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $controller = $this->request->getControllerName();

        $links->addByController($controller, 'show', $this->_('Show token'))
                ->addByController($controller, 'edit', $this->_('Edit token'))
                ->addByController($controller, 'index', $this->_('Show surveys'))
                ->addByController('respondent', 'show', $this->_('Show respondent'));

        return $links;
    }

    /**
     *
     * @return string The header title to display
     */
    protected function getTitle()
    {
        return sprintf($this->_('Delete %s!'), $this->getTopic());
    }

    /**
     * Step by step form processing
     *
     * Returns false when $this->afterSaveRouteUrl is set during the
     * processing, which happens by default when the data is saved.
     *
     * @return boolean True when the form should be displayed
     */
    protected function processForm()
    {
        if (parent::processForm()) {
            // Warn the world, can do only now
            $this->addMessage(sprintf($this->_('Watch out! You cannot undo a %s deletion!'), $this->getTopic()));

            return true;
        }

        return false;
    }

    /**
     * Hook containing the actual save code.
     *
     * Call's afterSave() for user interaction.
     *
     * @see afterSave()
     */
    protected function saveData()
    {
        $code = $this->loader->getTracker()->getReceptionCode($this->formData['gto_reception_code']);

        $changed = $this->token->setReceptionCode($code, $this->formData['gto_comment'], $this->session->user_id);

        // NOTE:
        // Currently this code is never used, as the user
        // does not get an option to select these codes.
        if ($code->hasRedoCode()) {
            $newComment = sprintf($this->_('Redo of token %s.'), $this->tokenId);
            if ($this->formData['gto_comment']) {
                $newComment .= "\n\n";
                $newComment .= $this->_('Old comment:');
                $newComment .= "\n";
                $newComment .= $this->formData['gto_comment'];
            }

            $this->_replacementTokenId = $this->token->createReplacement($newComment, $this->session->user_id);

            // Create a link for the old token
            if ($menuItem = $this->menu->find(array('controller' => $this->request->getControllerName(), 'action' => 'show', 'allowed' => true))) {
                $paramSource['gto_id_token']   = $this->tokenId;
                $paramSource['gtr_track_type'] = $this->token->getTrackEngine()->getTrackType();

                $link = $menuItem->toActionLink($paramSource, strtoupper($this->tokenId), true);
                $link->class = '';

                $oldTokenUrl = $link->render($this->view);
            } else {
                $oldTokenUrl = strtoupper($this->tokenId);
            }

            // Tell what the user what happened
            $this->addMessage(sprintf($this->_('Created replacement token %2$s for token %1$s.'), $oldTokenUrl, strtoupper($this->_replacementTokenId)));

            // Lookup token
            $newToken = $this->loader->getTracker()->getToken($this->_replacementTokenId);

            // Make sure the Next token is set right
            $this->token->setNextToken($newToken);

            // Copy answers when requested.
            if ($code->hasRedoCopyCode()) {
                $newToken->setRawAnswers($this->token->getRawAnswers());
            }
        } else {
            $respTrack = $this->token->getRespondentTrack();

            // Use the repesondent track function as that cascades the consent code
            $changed = $respTrack->setReceptionCode($this->formData['gto_reception_code'], $this->formData['gto_comment'], $this->session->user_id);
        }

        // Tell the user what happened
        $this->afterSave($changed);
    }
}
