<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

namespace Gems\Util;

/**
 * Utility function for the user of consents.
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class ConsentCode extends \Gems_Registry_CachedArrayTargetAbstract
{
    /**
     * Variable to add tags to the cache for cleanup.
     *
     * @var array
     */
    protected $_cacheTags = array('consent');

    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Return false on checkRegistryRequestsAnswers when the anser is not an array
     *
     * @var boolean
     */
    protected $requireArray = false;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     * Compatibility mode, for use with logical operators returns this->getCode()
     *
     * @return string
     */
    public function __toString()
    {
        return $this->getCode();
    }

    /**
     * Can you use the data with this code
     *
     * @return boolean
     */
    public function canBeUsed()
    {
        return $this->_get('gco_code') !== $this->util->getConsentRejected();
    }

    /**
     * Returns the complete record.
     *
     * @return array
     */
    public function getAllData()
    {
        return $this->_data;
    }

    /**
     * The reception code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_get('gco_code');
    }

    /**
     *
     * @return boolean
     */
    public function getDescription()
    {
        return $this->_get('gco_description');
    }

    /**
     *
     * @return boolean
     */
    public function hasDescription()
    {
        return (boolean) $this->_get('gco_description');
    }

    /**
     * Load the data when the cache is empty.
     *
     * @param mixed $id
     * @return array The array of data values
     */
    protected function loadData($id)
    {
        $sql = "SELECT * FROM gems__consents WHERE gco_description = ? LIMIT 1";
        return $this->db->fetchRow($sql, $id);
    }
}
