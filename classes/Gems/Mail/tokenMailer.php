<?php

class Gems_Mail_tokenMailer extends Gems_Mail_RespondentMailer
{
    /**
     *
     * @var Zend_Db_Adapter_Abstract
     */
	protected $db;	
	/**
	 * 
	 * @var Gems_Loader;
	 */
	protected $loader;

	protected $token;
	protected $tokenId;

    /**
     *
     * @var Zend_Translate $translate
     */
	protected $translate;

    /**
     * 
     * @var Gems_User;
     */
    protected $user;


	public function __construct($tokenId)
	{
		$this->tokenId = $tokenId;
	}

    protected function afterMail()
    {
        $tokenData['gto_mail_sent_num'] = new Zend_Db_Expr('gto_mail_sent_num + 1');
        $tokenData['gto_mail_sent_date'] = MUtil_Date::format(new Zend_Date(), 'yyyy-MM-dd');

        $this->db->update('gems__tokens', $tokenData, $this->db->quoteInto('gto_id_token = ?', $this->tokenId));


            $currentUserId                = $this->loader->getCurrentUser()->getUserId();
            $changeDate                   = new MUtil_Db_Expr_CurrentTimestamp();

            $logData['grco_id_to']        = $this->respondent->getId();
            $logData['grco_id_by']        = $currentUserId;
            $logData['grco_organization'] = $this->organizationId;
            $logData['grco_id_token']     = $this->tokenId;

            $logData['grco_method']       = 'email';
            $logData['grco_topic']        = substr($this->subject, 0, 120);
            $logData['grco_address']      = substr($this->to, 0, 120);
            $logData['grco_sender']       = substr($this->from, 0, 120);

            $logData['grco_id_message']   = $this->templateId ? $this->templateId : null;

            $logData['grco_changed']      = $changeDate;
            $logData['grco_changed_by']   = $currentUserId;
            $logData['grco_created']      = $changeDate;
            $logData['grco_created_by']   = $currentUserId;

            $this->db->insert('gems__log_respondent_communications', $logData);

    }


	public function afterRegistry()
	{
		$this->token = $this->loader->getTracker()->getToken($this->tokenId);
        if ($this->token->exists) {
            $this->patientId = $this->token->getPatientNumber();
            $this->organizationId = $this->token->getOrganizationId();
        }
		

		parent::afterRegistry();
	}

    public function getDataLoaded()
    {
        if ($this->token->exists) {
            if (parent::getDataLoaded()) {
                return true;
            } else {
                return false;
            }
        } else {
            $this->addMessage($this->translate->_('Token') . $this->translate->_(' not found'));
            return false;
        }
    }

    /**
     * Get specific data set in the mailer
     * @return Array 
     */
    public function getPresetTargetData()
    {
        $targetData = parent::getPresetTargetData();
        if ($this->token->exists) {
            $targetData['track']        = $this->token->getTrackName();
            $targetData['round']        = $this->token->getRoundDescription();
            $targetData['survey']       = $this->token->getSurvey()->getName();
            $targetData['last_contact'] = $this->token->getMailSentDate();
        }
        return $targetData;
    }

	/**
     * Get the respondent mailfields and add them
     */
    protected function loadMailFields()
    {
        parent::loadMailFields();
        if ($this->token->exists) {
            $this->addMailFields($this->tokenMailFields());
        }
    }
	
	/**
     * Returns an array of {field_names} => values for this token for
     * use in an e-mail tamplate.
     *
     * @param array $tokenData
     * @return array
     */
    public function tokenMailFields()
    {
        $locale = $this->respondent->getLanguage();
        $survey = $this->token->getSurvey();
        // Count todo
        $tSelect = $this->loader->getTracker()->getTokenSelect(array(
            'all'   => 'COUNT(*)',
            'track' => $this->db->quoteInto(
                    'SUM(CASE WHEN gto_id_respondent_track = ? THEN 1 ELSE 0 END)',
                    $this->token->getRespondentTrackId())
            ));
        $tSelect->andSurveys(array())
            ->forRespondent($this->token->getRespondentId(), $this->organizationId)
            ->forGroupId($survey->getGroupId())
            ->onlyValid();
        $todo = $tSelect->fetchRow();

        // Set the basic fields
        
        $result['round']                   = $this->token->getRoundDescription();

        $organizationLoginUrl = $this->organization->getLoginUrl();
        
        $result['site_ask_url']            = $organizationLoginUrl . '/ask/';
        // Url's
        $url      = $organizationLoginUrl . '/ask/forward/' . MUtil_Model::REQUEST_ID . '/';
        $url      .= $this->tokenId;
        $urlInput = $result['site_ask_url'] . 'index/' . MUtil_Model::REQUEST_ID . '/' . $this->tokenId;

        $result['survey']           = $survey->getName();

        $result['todo_all']         = sprintf($this->translate->plural('%d survey', '%d surveys', $todo['all'], $locale), $todo['all']);
        $result['todo_all_count']   = $todo['all'];
        $result['todo_track']       = sprintf($this->translate->plural('%d survey', '%d surveys', $todo['track'], $locale), $todo['track']);
        $result['todo_track_count'] = $todo['track'];

        $result['token']            = strtoupper($this->tokenId);
        $result['token_from']       = MUtil_Date::format($this->token->getValidFrom(), Zend_Date::DATE_LONG, 'yyyy-MM-dd', $locale);
        
        $result['token_link']       = '[url=' . $url . ']' . $survey->getName() . '[/url]';

        $result['token_until']      = MUtil_Date::format($this->token->getValidUntil(), Zend_Date::DATE_LONG, 'yyyy-MM-dd', $locale);
        $result['token_url']        = $url;
        $result['token_url_input']  = $urlInput;

        $result['track']            = $this->token->getTrackName();

        // Add the code fields
        $join = $this->db->quoteInto('gtf_id_field = gr2t2f_id_field AND gr2t2f_id_respondent_track = ?', $this->token->getRespondentTrackId());
        $select = $this->db->select();
        $select->from('gems__track_fields', array(new Zend_Db_Expr("CONCAT('track.', gtf_field_code)")))
                ->joinLeft('gems__respondent2track2field', $join, array('gr2t2f_value'))
                ->distinct()
                ->where('gtf_field_code IS NOT NULL')
                ->order('gtf_field_code');
        $codes = $this->db->fetchPairs($select);

        $result = $result + $codes;

        return $result;
    }
}