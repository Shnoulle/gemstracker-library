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
 * Extension of MUtil model with auto update changed and create fields.
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Model_JoinModel extends \MUtil_Model_JoinModel
{
    /**
     *
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var \Zend_Translate_Adapter
     */
    protected $translateAdapter;

    /**
     * Create a model that joins two or more tables
     *
     * @param string $name        A name for the model
     * @param string $startTable  The base table for the model
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     */
    public function __construct($name, $startTable, $fieldPrefix = null, $saveable = null)
    {
        parent::__construct($name, $startTable, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems_Model::setChangeFieldsByPrefix($this, $fieldPrefix);
        }
    }

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string
     * returns the translation
     *
     * @param  string             $text   Translation string
     * @param  string|\Zend_Locale $locale (optional) Locale/Language to use, identical with locale
     *                                    identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function _($text, $locale = null)
    {
        if (! $this->translateAdapter) {
            $this->initTranslateable();
        }
        return $this->translateAdapter->_($text, $locale);
    }

    /**
     *
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @return mixed The saveable setting to use
     */
    protected function _checkSaveable($saveable, $fieldPrefix)
    {
        if (null === $saveable) {
            return $fieldPrefix ? parent::SAVE_MODE_ALL : null;
        }

        return $saveable;
    }

    /**
     * Add a table to the model with a left join
     *
     * @param mixed  $table       The name of the table to join or a table object or an array(corr_name => tablename) or array(int => tablename, corr_name)
     * @param array  $joinFields  Array of source->dest primary keys for this join
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     *
     * @return \Gems_Model_JoinModel
     */
    public function addLeftTable($table, array $joinFields, $fieldPrefix = null, $saveable = null)
    {
        parent::addLeftTable($table, $joinFields, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems_Model::setChangeFieldsByPrefix($this, $fieldPrefix);
        }

        return $this;
    }

    /**
     * Add a table to the model with a right join
     *
     * @param mixed  $table       The name of the table to join or a table object or an array(corr_name => tablename) or array(int => tablename, corr_name)
     * @param array  $joinFields  Array of source->dest primary keys for this join
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     *
     * @return \Gems_Model_JoinModel
     */
    public function addRightTable($table, array $joinFields, $fieldPrefix = null, $saveable = null)
    {
        parent::addRightTable($table, $joinFields, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems_Model::setChangeFieldsByPrefix($this, $fieldPrefix);
        }

        return $this;
    }

    /**
     * Add a table to the model with an inner join
     *
     * @param mixed  $table       The name of the table to join or a table object or an array(corr_name => tablename) or array(int => tablename, corr_name)
     * @param array  $joinFields  Array of source->dest primary keys for this join
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     *
     * @return \Gems_Model_JoinModel
     */
    public function addTable($table, array $joinFields, $fieldPrefix = null, $saveable = null)
    {
        parent::addTable($table, $joinFields, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems_Model::setChangeFieldsByPrefix($this, $fieldPrefix);
        }
        return $this;
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * This function is no needed if the classes are setup correctly
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->initTranslateable();
    }

    /**
     * Function that checks the setup of this class/traight
     *
     * This function is not needed if the variables have been defined correctly in the
     * source for this object and theose variables have been applied.
     *
     * return @void
     */
    protected function initTranslateable()
    {
        if ($this->translateAdapter instanceof \Zend_Translate_Adapter) {
            // OK
            return;
        }

        if ($this->translate instanceof \Zend_Translate) {
            // Just one step
            $this->translateAdapter = $this->translate->getAdapter();
            return;
        }

        if ($this->translate instanceof \Zend_Translate_Adapter) {
            // It does happen and if it is all we have
            $this->translateAdapter = $this->translate;
            return;
        }

        // Make sure there always is an adapter, even if it is fake.
        $this->translateAdapter = new \MUtil_Translate_Adapter_Potemkin();
    }

    /**
     * Copy from \Zend_Translate_Adapter
     *
     * Translates the given string using plural notations
     * Returns the translated string
     *
     * @see \Zend_Locale
     * @param  string             $singular Singular translation string
     * @param  string             $plural   Plural translation string
     * @param  integer            $number   Number for detecting the correct plural
     * @param  string|\Zend_Locale $locale   (Optional) Locale/Language to use, identical with
     *                                      locale identifier, @see \Zend_Locale for more information
     * @return string
     */
    public function plural($singular, $plural, $number, $locale = null)
    {
        if (! $this->translateAdapter) {
            $this->initTranslateable();
        }
        $args = func_get_args();
        return call_user_func_array(array($this->translateAdapter, 'plural'), $args);
    }

    /**
     *
     * @param string $table_name  Does not test for existence
     * @param string $fieldPrefix Prefix to use for change fields (date/userid), if $saveable empty sets it to true
     * @param mixed  $saveable    Will changes to this table be saved, true or a combination of SAVE_MODE constants
     * @return \Gems_Model_JoinModel
     */
    public function setTableSaveable($table_name, $fieldPrefix = null, $saveable = null)
    {
        parent::setTableSaveable($table_name, $this->_checkSaveable($saveable, $fieldPrefix));

        if ($fieldPrefix) {
            \Gems_Model::setChangeFieldsByPrefix($this, $fieldPrefix);
        }

        return $this;
    }
}