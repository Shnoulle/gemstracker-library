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
 * Short description of file
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: OpenRosaFormModel.php 215 2011-07-12 08:52:54Z michiel $
 */

/**
 *
 * @package    Gems
 * @subpackage OpenRosa
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6
 */
class OpenRosa_Model_OpenRosaFormModel extends \Gems_Model_JoinModel
{
    /**
     *
     * @var \Zend_Translate_Adapter
     */
    public $translate;

    public function __construct()
    {
        parent::__construct('orf', 'gems__openrosaforms', 'gof');
    }

    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->setIfExists('gof_form_id', 'label', $this->translate->_('FormID'));
        $this->setIfExists('gof_form_version', 'label', $this->translate->_('Version'));
        $this->setIfExists('gof_form_title', 'label', $this->translate->_('Name'));
        $this->setIfExists('gof_form_active', 'label', $this->translate->_('Active'), 'elementClass', 'checkbox');
    }

    /**
     * Get a select statement using a filter and sort
     *
     * Modified to add the information schema, only possible like this since
     * the table has no primary key and can not be added using normal joins
     *
     * @param array $filter
     * @param array $sort
     * @return \Zend_Db_Table_Select
     */
    public function _createSelect(array $filter, array $sort)
    {
        $select = parent::_createSelect($filter, $sort);

        $config = $select->getAdapter()->getConfig();
        if (isset($config['dbname'])) {
            $constraint = $select->getAdapter()->quoteInto(' AND TABLE_SCHEMA=?', $config['dbname']);
        } else {
            $constraint = '';
        }
        $select->joinLeft('INFORMATION_SCHEMA.TABLES', "table_name  = convert(concat_ws('_','gems__orf_', REPLACE(gof_form_id,'.','_'),gof_form_version) USING utf8)" . $constraint, array('TABLE_ROWS'));
        return $select;
    }
}