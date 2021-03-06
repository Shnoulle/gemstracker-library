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
 * @subpackage Upgrades
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Short description for Upgrades
 *
 * Long description for class Upgrades (if any)...
 *
 * @package    Gems
 * @subpackage Upgrades
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_Upgrades extends \Gems_UpgradesAbstract
{
    public function __construct()
    {
        //Important, ALWAYS run the contruct of our parent object
        parent::__construct();

        //Now set the context
        $this->setContext('gems');
        //And add our patches
        $this->register('Upgrade143to150', 'Upgrade from 1.4.3 to 1.5.0');
        $this->register('Upgrade150to151', 'Upgrade from 1.5.0 to 1.5.1');
        $this->register('Upgrade151to152', 'Upgrade from 1.5.1 to 1.5.2');
        $this->register('Upgrade152to153', 'Upgrade from 1.5.2 to 1.5.3');
        $this->register('Upgrade153to154', 'Upgrade from 1.5.3 to 1.5.4');
        $this->register('Upgrade154to155', 'Upgrade from 1.5.4 to 1.5.5');
        $this->register('Upgrade155to156', 'Upgrade from 1.5.5 to 1.5.6');
        $this->register('Upgrade156to157', 'Upgrade from 1.5.6 to 1.5.7');
        $this->register('Upgrade157to16',  'Upgrade from 1.5.7 to 1.6');
        $this->register('Upgrade16to161',  'Upgrade from 1.6.0 to 1.6.1');
        $this->register('Upgrade161to162', 'Upgrade from 1.6.1 to 1.6.2');
        $this->register('Upgrade162to163', 'Upgrade from 1.6.2 to 1.6.3');
        $this->register('Upgrade163to164', 'Upgrade from 1.6.3 to 1.6.4');
        $this->register('Upgrade164to170', 'Upgrade from 1.6.4 to 1.7.0');
        $this->register('Upgrade170to171', 'Upgrade from 1.7.0 to 1.7.1');
        $this->register('Upgrade171to172', 'Upgrade from 1.7.1 to 1.7.2');
        /**
         * To have the new_project updated to the highest level, update
         *
         * /new_project/var/settings/upgrades.ini
         *
         * to have the right index for this context, normally when no index set on register
         * it will start counting at 1.
         */
    }


    /**
     * To upgrade from 143 to 15 we need to do some work:
     * 1. execute db patches 42 and 43
     * 2. create new tables
     */
    public function Upgrade143to150()
    {
        $this->_batch->addTask('Db_AddPatches', 42);
        $this->_batch->addTask('Db_AddPatches', 43);

        $this->_batch->addTask('Db_CreateNewTables');

        $this->_batch->addTask('Echo', $this->_('Syncing surveys for all sources'));

        //Now sync the db sources to allow limesurvey source to add a field to the tokentable
        $model = new \MUtil_Model_TableModel('gems__sources');
        $data  = $model->load(false);

        foreach ($data as $row) {
            $this->_batch->addTask('Tracker_SourceSyncSurveys', $row['gso_id_source']);
        }

        return true;
    }

    /**
     * To upgrade to 1.5.1 just execute patchlevel 44
     */
    public function Upgrade150to151()
    {
        $this->_batch->addTask('Db_AddPatches', 44);

        return true;
    }

    /**
     * To upgrade to 1.5.2 just execute patchlevel 45
     */
    public function Upgrade151to152()
    {
        $this->_batch->addTask('Db_AddPatches', 45);

        return true;
    }

    /**
     * To upgrade to 1.5.2 just execute patchlevel 46
     */
    public function Upgrade152to153()
    {
        $this->_batch->addTask('Db_AddPatches', 46);

        return true;
    }

    /**
     * To upgrade to 1.5.4 just execute patchlevel 47
     */
    public function Upgrade153to154()
    {
        $this->_batch->addTask('Db_AddPatches', 47);

        return true;
    }

    /**
     * To upgrade to 1.5.5 just execute patchlevel 48
     */
    public function Upgrade154to155()
    {
        $this->_batch->addTask('Db_AddPatches', 48);

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));

        return true;
    }

    /**
     * To upgrade to 1.5.6 just execute patchlevel 49
     */
    public function Upgrade155to156()
    {
        $this->_batch->addTask('Db_AddPatches', 49);

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));

        return true;
    }

    /**
     * To upgrade to 1.5.7 just execute patchlevel 50
     */
    public function Upgrade156to157()
    {
        $this->_batch->addTask('Db_AddPatches', 50);

        return true;
    }

    /**
     * To upgrade to 1.6 just execute patchlevel 51
     */
    public function Upgrade157to16()
    {
        $this->_batch->addTask('Db_AddPatches', 51);

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));

        return true;
    }

    /**
     * To upgrade to 1.6.1 just execute patchlevel 52
     */
    public function Upgrade16to161()
    {
        $this->_batch->addTask('Db_AddPatches', 52);

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));

        return true;
    }

    /**
     * To upgrade to 1.6.2 just execute patchlevel 53
     */
    public function Upgrade161to162()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 53);

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));

        return true;
    }

    /**
     * To upgrade to 1.6.3 just execute patchlevel 54
     */
    public function Upgrade162to163()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 54);
        $this->_batch->addTask('Updates_UpdateRoleIds');

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));

        return true;
    }

    /**
     * To upgrade to 1.6.4 just execute patchlevel 55
     */
    public function Upgrade163to164()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 55);
        $this->_batch->addTask('Updates_CompileTemplates');

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));

        return true;
    }

    /**
     * To upgrade to 1.7.0
     */
     public function Upgrade164to170()
   {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 56);

        // Use AddTask task to execute after patches
        $this->_batch->addTask('AddTask', 'Updates_EncryptPasswords', 'gems__sources', 'gso_id_source', 'gso_ls_password', 'gso_encryption');
        $this->_batch->addTask('AddTask', 'Updates_EncryptPasswords', 'gems__mail_servers', 'gms_from', 'gms_password', 'gms_encryption');
        $this->_batch->addTask('AddTask', 'Updates_EncryptPasswords', 'gems__radius_config', 'grcfg_id', 'grcfg_secret', 'grcfg_encryption');

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));

        return true;
    }

    /**
     * To upgrade to 1.7.1
     */
     public function Upgrade170to171()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 57);

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }

    /**
     * To upgrade to 1.7.2
     */
     public function Upgrade171to172()
    {
        $this->_batch->addTask('Db_CreateNewTables');
        $this->_batch->addTask('Db_AddPatches', 58);

        $this->_batch->addTask('Echo', $this->_('Make sure to read the changelog as it contains important instructions'));
        $this->_batch->addTask('Echo', $this->_('Check the Code compatibility report for any issues with project specific code!'));

        return true;
    }
}