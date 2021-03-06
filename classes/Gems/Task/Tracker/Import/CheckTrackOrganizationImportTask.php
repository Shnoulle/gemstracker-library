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
 * @subpackage Task\Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CheckTrackOrganizationImportTask.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

namespace Gems\Task\Tracker\Import;

/**
 *
 *
 * @package    Gems
 * @subpackage Task\Tracker
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Jan 19, 2016 6:26:42 PM
 */
class CheckTrackOrganizationImportTask extends \MUtil_Task_TaskAbstract
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($lineNr = null, $organizationData = null)
    {
        $batch  = $this->getBatch();
        $import = $batch->getVariable('import');

        if (isset($organizationData['gor_id_organization']) && $organizationData['gor_id_organization']) {

            $oldId = $organizationData['gor_id_organization'];

        } else {
            $oldId = false;
            $batch->addToCounter('import_errors');
            $batch->addMessage(sprintf(
                    $this->_('No gor_id_organization not specified for organization at line %d.'),
                    $lineNr
                    ));
        }

        if (isset($organizationData['gor_name']) && $organizationData['gor_name']) {
            if ($oldId) {
                $orgId = $this->db->fetchOne(
                        "SELECT gor_id_organization FROM gems__organizations WHERE gor_name = ?",
                        $organizationData['gor_name']
                        );
                if ($orgId) {
                    $import['organizationIds'][$oldId] = $orgId;
                    $import['formDefaults']['gtr_organizations'][] = $orgId;
                } else {
                    $import['organizationIds'][$oldId] = false;
                }
            }
        } else {
            $orgId = false;
            $batch->addToCounter('import_errors');
            $batch->addMessage(sprintf(
                    $this->_('No gor_name not specified for organization at line %d.'),
                    $lineNr
                    ));
        }
    }
}
