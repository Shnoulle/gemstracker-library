<?php

/**
 * Copyright (c) 2012, Erasmus MC
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
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $id: RoundTokenSnippet.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class Gems_Snippets_Respondent_RoundTokenSnippet extends Gems_Snippets_RespondentTokenSnippet
{
    /**
     * Set a fixed model sort.
     *
     * Leading _ means not overwritten by sources.
     *
     * @var array
     */
    protected $_fixedSort = array(
            'gto_round_order' => SORT_ASC,
            'gto_created'     => SORT_ASC,
        );

    /**
     * Sets pagination on or off.
     *
     * @var boolean
     */
    public $browse = false;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    protected $project;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param MUtil_Model_TableBridge $bridge
     * @param MUtil_Model_ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(MUtil_Model_TableBridge $bridge, MUtil_Model_ModelAbstract $model)
    {
        // MUtil_Model::$verbose = true;
        //
        // Initiate data retrieval for stuff needed by links
        $bridge->gr2o_patient_nr;
        $bridge->gr2o_id_organization;
        $bridge->gr2t_id_respondent_track;
        $bridge->gtr_track_type;

        $HTML = MUtil_Html::create();

        $roundIcon[] = MUtil_Lazy::iif($bridge->gro_icon_file, MUtil_Html::create('img', array('src' => $bridge->gro_icon_file, 'class' => 'icon')));

        if ($menuItem = $this->findMenuItem('track', 'show-track')) {
            $href = $menuItem->toHRefAttribute($this->request, $bridge);
        } else {
        }

        $bridge->addMultiSort('gsu_survey_name', $roundIcon);
        $bridge->addSortable('ggp_name');
        $bridge->addSortable('calc_used_date', null, $HTML->if($bridge->is_completed, 'disabled date', 'enabled date'));
        $bridge->addSortable('gto_changed');
        $bridge->addSortable('assigned_by', $this->_('Assigned by'));


        $project = GemsEscort::getInstance()->project;

        // If we are allowed to see the result of the survey, show them
        $user = $this->loader->getCurrentUser();
        if ($user->hasPrivilege('pr.respondent.result')) {
            $bridge->addSortable('gto_result', $this->_('Score'), 'date');
        }

        $bridge->useRowHref = false;

        $actionLinks[] = $this->createMenuLink($bridge, 'track',  'answer');
        $actionLinks[] = $this->createMenuLink($bridge, 'survey', 'answer');
        $actionLinks[] = array(
            $bridge->ggp_staff_members->if($this->createMenuLink($bridge, 'ask', 'take'), $bridge->calc_id_token->strtoupper()),
            'class' => $bridge->ggp_staff_members->if(null, $bridge->calc_id_token->if('token')));
        // calc_id_token is empty when the survey has been completed

        // Remove nulls
        $actionLinks = array_filter($actionLinks);
        if ($actionLinks) {
            $bridge->addItemLink($actionLinks);
        }

        $this->addTokenLinks($bridge);
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see MUtil_Registry_TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput()
    {
        if ($this->menu) {
            if ($default = $this->project->getDefaultTrackId()) {

                $track = $this->loader->getTracker()->getTrackEngine($default);
                
                if ($track->isUserCreatable()) {
                    $list = $this->menu->getMenuList()
                            ->addByController('track', 'create',
                                    sprintf($this->_('Add %s track to this respondent'), $track->getTrackName())
                                    )
                            ->addParameterSources(
                                    array(
                                        Gems_Model::TRACK_ID   => $default,
                                        'gtr_id_track'         => $default,
                                        'gtr_track_type'       => $track->getTrackType(),
                                        'track_can_be_created' => 1,
                                        ),
                                    $this->request
                                    );
                    $this->onEmpty = $list->getActionLink('track', 'create');
                }
            }
            if (! $this->onEmpty) {
                $list = $this->menu->getMenuList()
                        ->addByController('track', 'show-track', $this->_('Add a track to this respondent'))
                        ->addParameterSources($this->request);
                $this->onEmpty = $list->getActionLink('track', 'show-track');
            }
        }

        return $this->respondentData && $this->request && parent::hasHtmlOutput();
    }
}