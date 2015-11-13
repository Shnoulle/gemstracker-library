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
 * @subpackage Snippets
 * @author     Jasper van Gestel <jvangestel@gmail.com>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Adds the ability to add a variable value to a select form element based on another form elements value
 *
 * Add the option 'variableSelect' in the model definition of that field to activate.
 * It takes the following options in an array:
 * source: 			REQUIRED 	string 		The fieldname of the element on which the base the new values
 * baseQuery: 		REQUIRED 	string 		The query to calculate the new values.
 *
 * disabledEmpty: 				boolean		if the value of the source is empty, the target form element is disabled
 * ajax: 						Array 		The controller and action of an ajax option which does the new calculation directly on change
 * firstValue 					multi 		Set one or multiple first values to prepend the new option values
 * 											true: an empty value is added
 * 											false: nothing will be added
 * 											array: key=>value = optionValue=>label
 *
 *
 * TODO:
 * - Make it work for different target elements (besides a select)
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.3
 */
class Gems_Snippets_ModelFormVariableFieldSnippet extends \Gems_Snippets_ModelFormSnippetGeneric
{
	protected $ajaxEvents;

	protected $db;

	protected $util;

    protected function addFormElements(\MUtil_Model_Bridge_FormBridgeInterface $bridge, \MUtil_Model_ModelAbstract $model)
    {
        parent::addFormElements($bridge, $model);

		foreach ($model->getItemsOrdered() as $name) {
			$modelOptions = $model->get($name);
			if (isset($modelOptions['variableSelect'])) {
				$selectOptions = $modelOptions['variableSelect'];
				if (isset($selectOptions['source']) && isset($selectOptions['baseQuery'])) {
					$sourceName = $selectOptions['source'];
					$sourceValue = $this->formData[$sourceName];
					if ($sourceValue) {
						$targetOptions = array();
						if ($selectOptions['firstValue'] === true) {
							$targetOptions[''] = '';
						} elseif(is_array($selectOptions['firstValue'])) {
							$targetOptions = $selectOptions['firstValue'];
						}
	        			$targetOptions += $this->db->fetchPairs($selectOptions['baseQuery'], $sourceValue);

						$bridge->addSelect($name, 'multiOptions', $targetOptions);
					} else {
						if (isset($selectOptions['disabledEmpty']) && $selectOptions['disabledEmpty']) {
							$bridge->addSelect($name, 'disabled', true);
						}
					}

					$this->addAjaxEvent($name, $selectOptions);
				}
			}
		}
    }

    protected function getAjaxEventScript() {
    	if (is_array($this->ajaxEvents)) {
    		$script = '
    		(function($) {'
    		;

    		foreach($this->ajaxEvents as $event) {
    			$script .= $event;
    		}
    		$script .= '}(jQuery));';
    		return $script;
    	}

    	return false;
    }


    protected function addAjaxEvent($target, $selectOptions)
    {
    	if (isset($selectOptions['ajax'])) {
    		$queryUrl = \Zend_Controller_Front::getInstance()->getBaseUrl() . '/' . $selectOptions['ajax']['controller'] . '/' . $selectOptions['ajax']['action'];
    		$source = $selectOptions['source'];
			$script = '
			$("#'.$target.'").getSelectOptions(
                {
                 queryUrl: "'. $queryUrl .'",
                 source: "' . $source . '",';

            if (isset($selectOptions['disabledEmpty']) && $selectOptions['disabledEmpty']) {
                $script .= '
                enableAfter: true,';
            }
            if (isset($selectOptions['firstValue']) && $selectOptions['firstValue'] !== false) {
                $script .= '
                 firstValue: ' . json_encode($this->getKeyValueArray($selectOptions['firstValue'])) . ',';
            }
            if (isset($selectOptions['defaultValues']) && $selectOptions['defaultValues'] !== false) {
                $script .= '
                 defaultValues: ' . json_encode($this->getKeyValueArray($selectOptions['defaultValues'])) . ',';
            }
            $script .= '
                }
            );';

    		$this->ajaxEvents[] = $script;
    	}
    }

    protected function getKeyValueArray($array) {
        if (is_array($array)) {
            $newArray = array();
            foreach($array as $key=>$value) {
                $newArray[] = array($key, $value);
            }
            return $newArray;
        }
        return $array;
    }

	/**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil_Html_HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {

    	if ($script = $this->getAjaxEventScript()) {
            \MUtil_JQuery::enableView($view);
	    	$view->headScript()->appendFile(\Zend_Controller_Front::getInstance()->getBaseUrl()  .  '/gems/js/jquery.getSelectOptions.js');
	        $view->inlineScript()->appendScript($script);
	    }

        return parent::getHtmlOutput($view);
    }
}
