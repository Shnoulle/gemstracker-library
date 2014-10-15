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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage AppointmentFilterModelAbstract
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id: AppointmentFilterModelAbstract.php $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage AppointmentFilterModelAbstract
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 15-okt-2014 13:07:11
 */
abstract class Gems_Agenda_AppointmentFilterModelAbstract extends Gems_Model_JoinModel
{
    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     *
     * @param string $name
     */
    public function __construct($name)
    {
        parent::__construct($name, 'gems__appointment_filters', 'gaf');
    }

    /**
     * Set those settings needed for the browse display
     *
     * @return \Gems_Agenda_AppointmentFilterModelAbstract
     */
    public function applyBrowseSettings()
    {
        $this->addColumn('COALESCE(gaf_manual_name, gaf_calc_name)', 'gaf_name');
        $this->set('gaf_name', 'label', $this->_('Name'));
        $this->set('gaf_id_order', 'label', $this->_('Order'),
                'description', $this->_('Display order')
                );
        $this->set('gaf_active', 'label', $this->_('Active'),
                'multiOptions', $this->util->getTranslated()->getYesNo()
                );
        
        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems_Agenda_AppointmentFilterModelAbstract
     */
    public function applyDetailSettings()
    {
        $this->set('gaf_manual_name', 'label', $this->_('Manual name'),
                'description', $this->_('A name for this filter. The calculated name is used otherwise.'));
        $this->set('gaf_calc_name', 'label', $this->_('Calculated name'));
        $this->set('gaf_id_order', 'label', $this->_('Order'),
                'description', $this->_('Display order')
                );
        $this->set('gaf_active', 'label', $this->_('Active'),
                'multiOptions', $this->util->getTranslated()->getYesNo()
                );

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @return \Gems_Agenda_AppointmentFilterModelAbstract
     */
    public function applyEditSettings()
    {
        $this->applyDetailSettings();

        $this->set('gaf_calc_name', 'elementClass', 'Exhibitor');
        $this->setOnSave('gaf_calc_name', array($this, 'calcultateName'));
        $this->set('gaf_active', 'elementClass', 'Checkbox');

        return $this;
    }

    /**
     * A ModelAbstract->setOnSave() function that returns the input
     * date as a valid date.
     *
     * @see MUtil_Model_ModelAbstract
     *
     * @param mixed $value The value being saved
     * @param boolean $isNew True when a new item is being saved
     * @param string $name The name of the current field
     * @param array $context Optional, the other values being saved
     * @return Zend_Date
     */
    abstract public function calcultateName($value, $isNew = false, $name = null, array $context = array());
}