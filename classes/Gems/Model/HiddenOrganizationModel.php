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
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Extension of JoinModel for models where the organization id is
 * part of the key, but left out of the request.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Model_HiddenOrganizationModel extends \Gems_Model_JoinModel
{
    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     * Stores the fields that can be used for sorting or filtering in the
     * sort / filter objects attached to this model.
     *
     * @param array $parameters
     * @param boolean $includeNumericFilters When true numeric filter keys (0, 1, 2...) are added to the filter as well
     * @return array The $parameters minus the sort & textsearch keys
     */
    public function applyParameters(array $parameters, $includeNumericFilters = false)
    {
        if ($parameters) {
            // Allow use when passed only an ID value
            if (isset($parameters[\MUtil_Model::REQUEST_ID]) && (! isset($parameters[\MUtil_Model::REQUEST_ID1], $parameters[\MUtil_Model::REQUEST_ID2]))) {

                $id    = $parameters[\MUtil_Model::REQUEST_ID];
                $keys  = $this->getKeys();
                $field = array_shift($keys);

                $parameters[$field] = $id;

                if ($field2 = array_shift($keys)) {
                    $parameters[$field2] = $this->getCurrentOrganization();
                    \MUtil_Echo::r('Still using old HiddenModel parameters.', 'DEPRECIATION WARNING');
                    \MUtil_Echo::r($parameters);
                }

                unset($parameters[\MUtil_Model::REQUEST_ID]);
            }

            if (isset($parameters[\MUtil_Model::REQUEST_ID2]) &&
                (! array_key_exists($parameters[\MUtil_Model::REQUEST_ID2], $this->currentUser->getAllowedOrganizations()))) {

                $this->initTranslateable();

                throw new \Gems_Exception(
                        $this->_('Inaccessible or unknown organization'),
                        403, null,
                        sprintf($this->_('Access to this page is not allowed for current role: %s.'), $this->currentUser->getRole()));
            }

            return parent::applyParameters($parameters, $includeNumericFilters);
        }

        return array();
    }

    /**
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return ($this->currentUser instanceof \Gems_User_User);
    }

    /**
     * The current organization id of the current user
     *
     * @return int
     */
    public function getCurrentOrganization()
    {
        return $this->currentUser->getCurrentOrganizationId();
    }

    /**
     * Return an identifier the item specified by $forData
     *
     * basically transforms the fieldnames ointo oan IDn => value array
     *
     * @param mixed $forData Array value to vilter on
     * @param array $href Or \ArrayObject
     * @return array That can by used as href
     */
    public function getKeyRef($forData, $href = array(), $organizationInKey = null)
    {
        $keys = $this->getKeys();

        if (! $organizationInKey) {
            if ($forData instanceof \MUtil_Lazy_RepeatableInterface) {
                // Here I kind of assume that the data always contains the organization key.
                $organizationInKey = true;
            } else {
                $ordId = $this->getCurrentOrganization();
                $organizationInKey = self::_getValueFrom('gr2o_id_organization', $forData) == $ordId;
            }
        }

        if ($organizationInKey) {
            $href[\MUtil_Model::REQUEST_ID]  = self::_getValueFrom(reset($keys), $forData);
        } else {
            $href[\MUtil_Model::REQUEST_ID1] = self::_getValueFrom(reset($keys), $forData);
            next($keys);
            $href[\MUtil_Model::REQUEST_ID2] = self::_getValueFrom(key($keys), $forData);
        }

        return $href;
    }

    /**
     * Returns a translate adaptor
     *
     * @return \Zend_Translate_Adapter
     */
    protected function getTranslateAdapter()
    {
        if ($this->translate instanceof \Zend_Translate)
        {
            return $this->translate->getAdapter();
        }

        if (! $this->translate instanceof \Zend_Translate_Adapter) {
            $this->translate = new \MUtil_Translate_Adapter_Potemkin();
        }

        return $this->translate;
    }
}
