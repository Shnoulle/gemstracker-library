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
 * @subpackage Tracker_Field
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: FieldInterface.php $
 */

namespace Gems\Tracker\Field;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker_Field
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5 4-mrt-2015 11:11:42
 */
interface FieldInterface
{
    /**
     *
     * @param int $trackId gems__tracks id for this field
     * @param array $fieldDefinition Field definition array
     */
    public function __construct($trackId, array $fieldData);

    /**
     * Calculation the field info display for this type
     *
     * @param array $currentValue The current value
     * @param array $context The other values loaded so far
     * @return mixed the new value
     */
    public function calculateFieldInfo($currentValue, array $context);

    /**
     * On save calculation function
     *
     * @param array $currentValue The current value
     * @param array $values The values for the checked calculate from fields
     * @param array $context The other values being saved
     * @param int $respTrackId Optional gems respondent track id
     * @return mixed the new value
     * /
    public function calculateOnSave($currentValue, array $values, array $context, $respTrackId = null);

    /**
     *
     * @return The field code
     */
    public function getCode();

    /**
     *
     * @return The field label
     */
    public function getLabel();

    /**
     * Setting function for activity select
     *
     * @param string $values The content of the gtf_field_values field
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return array containing model settings
     * /
    public function getRespondentTrackSettings($values, $respondentId, $organizationId, $patientNr = null, $edit = true);

    /**
     * Setting function for activity select
     *
     * @param string $values The content of the gtf_field_values field
     * @param int $respondentId When null $patientNr is required
     * @param int $organizationId
     * @param string $patientNr Optional for when $respondentId is null
     * @param boolean $edit True when editing, false for display (detailed is assumed to be true)
     * @return array containing model settings
     * /
    public function getTrackMaintenanceSettings($values, $respondentId, $organizationId, $patientNr = null, $edit = true);
    // */

    /**
     * Should this field be added to the track info
     *
     * @return boolean
     */
    public function toTrackInfo();
}
