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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
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
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: StaffLogAction.php 2493 2015-04-15 16:29:48Z matijsdejong $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 16-apr-2015 17:36:20
 */
class Gems_Default_StaffLogAction extends \Gems_Default_LogAction
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
    protected $autofilterParameters = array('extraFilter' => 'getStaffFilter');

    /**
     *
     * @var \Gems_User_User
     */
    public $currentUser;

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStartSnippets = array('Generic\\ContentTitleSnippet', 'Log\\StaffLogSearchSnippet');

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
    protected function createModel($detailed, $action)
    {
        // Make sure the user is loaded
        $user = $this->getSelectedUser();

        if ($user) {
            if (! ($this->currentUser->hasPrivilege('pr.staff.see.all') ||
                    $this->currentUser->isAllowedOrganization($user->getBaseOrganizationId()))) {
                throw new \Gems_Exception($this->_('No access to page'), 403, null, sprintf(
                        $this->_('You have no right to access users from the organization %s.'),
                        $user->getBaseOrganization()->getName()
                        ));
            }
        }

        return parent::createModel($detailed, $action);
    }

    /**
     * Function to allow the creation of search defaults in code
     *
     * @see getSearchFilter()
     *
     * @return array
     */
    public function getSearchDefaults()
    {
        $data = parent::getSearchDefaults();

        if (! isset($data[\MUtil_Model::REQUEST_ID])) {
            $data[\MUtil_Model::REQUEST_ID] = intval($this->_getIdParam());
        }

        return $data;
    }

    /**
     * Load the user selected by the request - if any
     *
     * @staticvar \Gems_User_User $user
     * @return \Gems_User_User or false when not available
     */
    public function getSelectedUser()
    {
        static $user = null;

        if ($user !== null) {
            return $user;
        }

        $staffId = $this->_getIdParam();
        if ($staffId) {
            $user   = $this->loader->getUserLoader()->getUserByStaffId($staffId);
            $source = $this->menu->getParameterSource();
            $user->applyToMenuSource($source);
        } else {
            $user = false;
        }

        return $user;
    }

    /**
     * Get filter for current respondent
     *
     * @return array
     */
    public function getStaffFilter()
    {
        return array('gla_by' => intval($this->_getIdParam()));
    }
}
