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
 * @subpackage Util
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Lookup global information from the database, allowing for project specific overrides
 *
 * @package    Gems
 * @subpackage Util
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class Gems_Util_DbLookup extends Gems_Registry_TargetAbstract
{
    /**
     *
     * @var Zend_Acl
     */
    protected $acl;

    /**
     *
     * @var Zend_Cache_Core
     */
    protected $cache;

    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var Zend_Translate
     */
    protected $translate;

    /**
     *
     * @var Gems_Util
     */
    protected $util;

    /**
     * Retrieve a list of orgid/name pairs
     *
     * @staticvar array $organizations
     * @return array
     */
    public function getActiveOrganizations()
    {
        static $organizations;

        if (! $organizations) {
            $orgId = GemsEscort::getInstance()->getCurrentOrganization();
            $organizations = $this->db->fetchPairs('
                SELECT gor_id_organization, gor_name
                    FROM gems__organizations
                    WHERE (gor_active=1 AND
                            gor_id_organization IN (SELECT gr2o_id_organization FROM gems__respondent2org)) OR
                        gor_id_organization = ?
                    ORDER BY gor_name', $orgId);
        }

        return $organizations;
    }

    /**
     * Return key/value pairs of all active staff members
     *
     * @staticvar array $data
     * @return array
     */
    public function getActiveStaff()
    {
        static $data;

        if (! $data) {
            $data = $this->db->fetchPairs("SELECT gsf_id_user, CONCAT(COALESCE(gsf_last_name, '-'), ', ', COALESCE(gsf_first_name, ''), COALESCE(CONCAT(' ', gsf_surname_prefix), ''))
                    FROM gems__staff WHERE gsf_active = 1 ORDER BY gsf_last_name, gsf_first_name, gsf_surname_prefix");
        }

        return $data;
    }

    public function getActiveStaffGroups()
    {
        static $groups;

        if (! $groups) {
            $groups = $this->db->fetchPairs('SELECT ggp_id_group, ggp_name FROM gems__groups WHERE ggp_group_active=1 AND ggp_staff_members=1 ORDER BY ggp_name');
        }

        return $groups;
    }

    /**
     * Retrieve an array of groups the user is allowed to assign: his own group and all groups
     * he inherits rights from
     *
     * @return array
     */
    public function getAllowedRespondentGroups()
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;
        $results = $this->cache->load($cacheId);
        if (! $results) {
            $select = 'SELECT ggp_id_group, ggp_name '
                    . 'FROM gems__groups WHERE ggp_group_active=1 AND ggp_respondent_members=1 ORDER BY ggp_name';

            $results = $this->db->fetchPairs($select);

            $this->cache->save($results, $cacheId, array('roles'));
        }
        return $this->util->getTranslated()->getEmptyDropdownArray() + $results;
    }

    /**
     * Retrieve an array of groups the user is allowed to assign: his own group and all groups
     * he inherits rights from
     *
     * @return array
     */
    public function getAllowedStaffGroups()
    {
        $groups = $this->getActiveStaffGroups();
        $user = GemsEscort::getInstance()->getLoader()->getCurrentUser();
        if ($user->getRole() === 'master') {
            return $groups;

        } else {
            $rolesAllowed = $user->getRoles();
            $roles        = $this->db->fetchPairs('SELECT ggp_id_group, ggp_role FROM gems__groups WHERE ggp_group_active=1 AND ggp_staff_members=1 ORDER BY ggp_name');
            $result       = array();

            foreach ($roles as $id => $role) {
                if ((in_array($role, $rolesAllowed)) && isset($groups[$id])) {
                    $result[$id] = $groups[$id];
                }
            }

            return $result;
        }
    }

    /**
     * Return the available Comm templates.
     *
     * @staticvar array $data
     * @return array The tempalteId => subject list
     */
    public function getCommTemplates($mailTarget=false)
    {
        static $data;

        if (! $data) {
            $sql = 'SELECT gct_id_template, gct_name FROM gems__comm_templates';
            if ($mailTarget) {
                $sql .= ' WHERE gct_target = ?';
            }
            $sql .= ' ORDER BY gct_name';

            if ($mailTarget) {
                $data = $this->db->fetchPairs($sql, $mailTarget);
            } else {
                $data = $this->db->fetchPairs($sql);
            }
        }

        return $data;
    }

    public function getDefaultGroup()
    {
        $groups  = $this->getActiveStaffGroups();
        $roles   = $this->db->fetchPairs('SELECT ggp_role, ggp_id_group FROM gems__groups WHERE ggp_group_active=1 AND ggp_staff_members=1 ORDER BY ggp_name');
        $current = null;

        foreach (array_reverse($this->acl->getRoles()) as $roleId) {
            if (isset($roles[$roleId], $groups[$roles[$roleId]])) {
                if ($current) {
                    if ($this->acl->inheritsRole($current, $roleId)) {
                        $current = $roleId;
                    }
                } else {
                    $current = $roleId;
                }
            }
        }

        if (isset($roles[$current])) {
            return $roles[$current];
        }
    }

    /**
     * Get the filter to use on the tokenmodel when working with a mailjob.
     *
     * @param array $job
     * @return array
     */
    public function getFilterForMailJob($job)
    {
        // Set up filter
        $filter = array(
        	'can_email'           => 1,
            'gtr_active'          => 1,
            'gsu_active'          => 1,
            'grc_success'         => 1,
        	'gto_completion_time' => NULL,
        	'gto_valid_from <= CURRENT_DATE',
            '(gto_valid_until IS NULL OR gto_valid_until >= CURRENT_TIMESTAMP)'
        );

        if ($job['gcj_filter_mode'] == 'R') {
            $filter[] = 'gto_mail_sent_date <= DATE_SUB(CURRENT_DATE, INTERVAL ' . $job['gcj_filter_days_between'] . ' DAY)';
            $filter[] = 'gto_mail_sent_num <= ' . $job['gcj_filter_max_reminders'];
        } else {
            $filter['gto_mail_sent_date'] = NULL;
        }
        if ($job['gcj_id_organization']) {
            $filter['gto_id_organization'] = $job['gcj_id_organization'];
        }
        if ($job['gcj_id_track']) {
            $filter['gto_id_track'] = $job['gcj_id_track'];
        }
        if ($job['gcj_round_description']) {
            if ($job['gcj_id_track']) {
                $roundIds = $this->db->fetchCol('
                    SELECT gro_id_round FROM gems__rounds WHERE gro_active = 1 AND gro_id_track = ? AND gro_round_description = ?', array(
                    $job['gcj_id_track'],
                    $job['gcj_round_description'])
                );
            } else {
                $roundIds = $this->db->fetchCol('
                    SELECT gro_id_round FROM gems__rounds WHERE gro_active = 1 AND gro_round_description = ?', array(
                    $job['gcj_round_description'])
                );
            }
            $filter['gto_id_round'] = $roundIds;
        }
        if ($job['gcj_id_survey']) {
            $filter['gto_id_survey'] = $job['gcj_id_survey'];
        }

        return $filter;
    }

    /**
     * The active groups
     *
     * @staticvar array $groups
     * @return array
     */
    public function getGroups()
    {
        static $groups;

        if (! $groups) {
            $groups = $this->util->getTranslated()->getEmptyDropdownArray() +
                $this->db->fetchPairs('SELECT ggp_id_group, ggp_name FROM gems__groups WHERE ggp_group_active=1 ORDER BY ggp_name');
        }

        return $groups;
    }

    /**
     * Return the available mail templates.
     *
     * @staticvar array $data
     * @return array The tempalteId => subject list
     */
    public function getMailTemplates()
    {
        static $data;

        if (! $data) {
            $data = $this->db->fetchPairs("SELECT gmt_id_message, gmt_subject FROM gems__mail_templates ORDER BY gmt_subject");
        }

        return $data;
    }

    /**
     *
     * @staticvar array $organizations
     * @return array List of the active organizations
     */
    public function getOrganizations()
    {
        static $organizations;

        if (! $organizations) {
            $organizations = $this->db->fetchPairs('SELECT gor_id_organization, gor_name FROM gems__organizations WHERE gor_active=1 ORDER BY gor_name');
            natsort($organizations);
        }

        return $organizations;
    }

    /**
     * Get all organizations that share a given code
     *
     * On empty this will return all organizations
     *
     * @staticvar array $organizations
     * @param string $code
     * @return array key = gor_id_organization, value = gor_name
     */
    public function getOrganizationsByCode($code = null)
    {
        static $organizations = array();

        if (is_null($code)) {
            return $this->getOrganizations();
        }

        if (!isset($organizations[$code])) {
            $organizations[$code] = $this->db->fetchPairs('SELECT gor_id_organization, gor_name FROM gems__organizations WHERE gor_active=1 and gor_code=? ORDER BY gor_name', array($code));
        }

        return $organizations[$code];
    }

    /**
     * Returns a list of the organizations where users can login.
     *
     * @staticvar array $organizations
     * @return array List of the active organizations
     */
    public function getOrganizationsForLogin()
    {
        static $organizations;

        if (! $organizations) {
            try {
                $organizations = $this->db->fetchPairs('SELECT gor_id_organization, gor_name FROM gems__organizations WHERE gor_active=1 AND gor_has_login=1 ORDER BY gor_name');
            } catch (Exception $e) {
                try {
                    // 1.4 fallback
                    $organizations = $this->db->fetchPairs('SELECT gor_id_organization, gor_name FROM gems__organizations WHERE gor_active=1 ORDER BY gor_name');
                } catch (Exception $e) {
                    $organizations = array();
                }
            }
            natsort($organizations);
        }

        return $organizations;
    }

    /**
     * Returns a list of the organizations that have respondents.
     *
     * @staticvar array $organizations
     * @return array List of the active organizations
     */
    public function getOrganizationsWithRespondents()
    {
        static $organizations;

        if (! $organizations) {
            $organizations = $this->db->fetchPairs(
                    'SELECT gor_id_organization, gor_name
                        FROM gems__organizations
                        WHERE gor_active = 1 AND (gor_has_respondents = 1 OR gor_add_respondents = 1)
                        ORDER BY gor_name'
                    );
            natsort($organizations);
        }

        return $organizations;
    }

    /**
     * Find the patient nr corresponding to this respondentId / Orgid combo
     *
     * @param int $respondentId
     * @param int $organizationId
     * @return string A patient nr or null
     * @throws Gems_Exception When the patient does not exist
     */
    public function getPatientNr($respondentId, $organizationId)
    {
        $result = $this->db->fetchOne(
                "SELECT gr2o_patient_nr FROM gems__respondent2org WHERE gr2o_id_user = ? AND gr2o_id_organization = ?",
                array($respondentId, $organizationId)
                );

        if ($result !== false) {
            return $result;
        }

        throw new Gems_Exception(
                sprintf($this->translate->_('Respondent id %s not found.'), $respondentId),
                200,
                null,
                sprintf($this->translate->_('In the organization nr %d.'), $organizationId)
                );
    }

    /**
     * Find the respondent id corresponding to this patientNr / Orgid combo
     *
     * @param string $patientId
     * @param int $organizationId
     * @return int A respondent id or null
     * @throws Gems_Exception When the respondent does not exist
     */
    public function getRespondentId($patientId, $organizationId)
    {
        $result = $this->db->fetchOne(
                "SELECT gr2o_id_user FROM gems__respondent2org WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?",
                array($patientId, $organizationId)
                );

        if ($result !== false) {
            return $result;
        }

        throw new Gems_Exception(
                sprintf($this->translate->_('Patient number %s not found.'), $patientId),
                200,
                null,
                sprintf($this->translate->_('In the organization nr %d.'), $organizationId)
                );
    }

    /**
     * Find the respondent id name corresponding to this patientNr / Orgid combo
     *
     * @param string $patientId
     * @param int $organizationId
     * @return array ['id', 'name']
     * @throws Gems_Exception When the respondent does not exist
     */
    public function getRespondentIdAndName($patientId, $organizationId)
    {
        $output = $this->db->fetchRow(
                "SELECT gr2o_id_user as id,
                    TRIM(CONCAT(
                        COALESCE(CONCAT(grs_last_name, ', '), '-, '),
                        COALESCE(CONCAT(grs_first_name, ' '), ''),
                        COALESCE(grs_surname_prefix, ''))) as name
                    FROM gems__respondent2org INNER JOIN
                        gems__respondents ON gr2o_id_user = grs_id_user
                    WHERE gr2o_patient_nr = ? AND gr2o_id_organization = ?",
                array($patientId, $organizationId)
                );

        if ($output !== false) {
            return $output;
        }

        throw new Gems_Exception(
                sprintf($this->translate->_('Patient number %s not found.'), $patientId),
                200,
                null,
                sprintf($this->translate->_('In the organization nr %d.'), $organizationId)
                );
    }

    /**
     * Returns the roles in the acl
     *
     * @return array roleId => ucfirst(roleId)
     */
    public function getRoles()
    {
        $roles = array();

        if ($this->acl) {
            foreach ($this->acl->getRoles() as $role) {
                //Do not translate, only make first one uppercase
                $roles[$role] = ucfirst($role);
            }
        }

        return $roles;
    }

    /**
     * Get all round descriptions for exported
     *
     * @param int $trackId Optional track id
     * @param int $surveyId Optional survey id
     * @return array
     */
    public function getRoundsForExport($trackId = null, $surveyId = null)
    {
        // Read some data from tables, initialize defaults...
        $select = $this->db->select();

        // Fetch all round descriptions
        $select->from('gems__tokens', array('gto_round_description', 'gto_round_description'))
            ->distinct()
            ->where('gto_round_description IS NOT NULL AND gto_round_description != ""')
            ->order(array('gto_round_description'));

        if (!empty($trackId)) {
            $select->where('gto_id_track = ?', (int) $trackId);
        }

        if (!empty($surveyId)) {
            $select->where('gto_id_survey = ?', (int) $surveyId);
        }

        $result = $this->db->fetchPairs($select);

        return $result;
    }

    /**
     * Return key/value pairs of all staff members, currently active or not
     *
     * @return array
     */
    public function getStaff()
    {
        $cacheId = __CLASS__ . '_' . __FUNCTION__;

        if ($results = $this->cache->load($cacheId)) {
            return $results;
        }

        $select = "SELECT gsf_id_user,
                        CONCAT(
                            COALESCE(gsf_last_name, '-'),
                            ', ',
                            COALESCE(gsf_first_name, ''),
                            COALESCE(CONCAT(' ', gsf_surname_prefix), '')
                            )
                    FROM gems__staff
                    ORDER BY gsf_last_name, gsf_first_name, gsf_surname_prefix";

        $results = $this->db->fetchPairs($select);

        $results = $results + array(
            Gems_User_UserLoader::SYSTEM_USER_ID => $this->translate->_('&laquo;system&raquo;'),
        );

        $this->cache->save($results, $cacheId, array('staff'));

        return $results;
    }

    public function getStaffGroups()
    {
        static $groups;

        if (! $groups) {
            $groups = $this->db->fetchPairs('SELECT ggp_id_group, ggp_name FROM gems__groups WHERE ggp_staff_members=1 ORDER BY ggp_name');
        }

        return $groups;
    }

    /**
     * Get all surveys that can be exported
     *
     * For export not only active surveys should be returned, but all surveys that can be exported.
     * As this depends on the kind of source used it is in this method so projects can change to
     * adapt to their own sources.
     *
     * @param int $trackId Optional track id
     * @return array
     */
    public function getSurveysForExport($trackId = null)
    {
        // Read some data from tables, initialize defaults...
        $select = $this->db->select();

        // Fetch all surveys
        $select->from('gems__surveys')
            ->join('gems__sources', 'gsu_id_source = gso_id_source')
            ->where('gso_active = 1')
            //->where('gsu_surveyor_active = 1')    // Leave inactive surveys, we toss out the inactive ones for limesurvey as it is no problem for OpenRosa to have them in
            ->order(array('gsu_active DESC', 'gsu_survey_name'));

        if ($trackId) {
            $select->where('gsu_id_survey IN (SELECT gto_id_survey FROM gems__tokens WHERE gto_id_track = ?)', $trackId);
        }

        $result = $this->db->fetchAll($select);

        if ($result) {
            // And transform to have inactive surveys in gems and source in a
            // different group at the bottom
            $surveys = array();
            $Inactive = $this->translate->_('inactive');
            $sourceInactive = $this->translate->_('source inactive');
            foreach ($result as $survey) {
                $id   = $survey['gsu_id_survey'];
                $name = $survey['gsu_survey_name'];
                if ($survey['gsu_surveyor_active'] == 0) {
                    // Inactive in the source, for LimeSurvey this is a problem!
                    if (!strpos($survey['gso_ls_class'], 'LimeSurvey')) {
                        $surveys[$sourceInactive][$id] = $name;
                    }
                } elseif ($survey['gsu_active'] == 0) {
                    // Inactive in GemsTracker
                    $surveys[$Inactive][$id] = $name;
                } else {
                    $surveys[$id] = $name;
                }
            }
        } else {
            $surveys = array();
        }

        return $surveys;
    }

    public function getUserConsents()
    {
        static $consents;

        if (! $consents) {
            $consents = $this->db->fetchPairs('SELECT gco_description, gco_description FROM gems__consents ORDER BY gco_order');

            foreach ($consents as &$name) {
                $name = $this->translate->_($name);
            }
        }

        return $consents;
    }
}
