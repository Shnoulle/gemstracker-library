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
 * @subpackage Form
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Table.php 86 2011-10-11 11:35:46Z matijsdejong $
 */

/**
 * Display a form in a table decorator.
 *
 * @package    Gems
 * @subpackage Form
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Form_Decorator_Tabs extends Zend_Form_Decorator_ViewHelper
{
    protected $_cellDecorators;
    protected $_options;
    protected $_subform;

    /**
     * Constructor
     *
     * Accept options during initialization.
     *
     * @param  array|Zend_Config $options
     * @return void
     */
    public function __construct($options = null)
    {
        if ($options instanceof Zend_Config) {
            $this->setConfig($options);
        } elseif (is_array($options)) {
            $this->setOptions($options);
        } else {
            $this->setOptions(array());
        }
    }

    private function applyDecorators(Zend_Form_Element $element, array $decorators)
    {
        $element->clearDecorators();
        foreach ($decorators as $decorator) {
            call_user_func_array(array($element, 'addDecorator'), $decorator);
        }

        return $this;
    }

    public function getCellDecorators()
    {
        if (! $this->_cellDecorators) {
            $this->loadDefaultCellDecorators();
        }

        return $this->_cellDecorators;
    }

    /**
     * Retrieve current element
     *
     * @return mixed
     */
    public function getElement()
    {
        return $this->_subform;
    }

    /**
     * Retrieve a single option
     *
     * @param  string $key
     * @return mixed
     */
    public function getOption($key)
    {
        if (isset($this->_options[$key])) {
            return $this->_options[$key];
        }
    }

    /**
     * Retrieve decorator options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->_options;
    }

    public function loadDefaultCellDecorators()
    {
        if (! $this->_cellDecorators) {
            /* $this->_cellDecorators = array(
                array('ViewHelper'),
                array('Errors'),
                array('Description', array('tag' => 'p', 'class' => 'description'))
                ); */
            $this->_cellDecorators = array('ViewHelper', 'Errors');
        }
        return $this->_cellDecorators;
    }

    /**
     * Delete a single option
     *
     * @param  string $key
     * @return bool
     */
    public function removeOption($key)
    {
        unset($this->_options[$key]);
    }

    /**
     * Render the element
     *
     * @param  string $content Content to decorate
     * @return string
     */
    public function render($content)
    {
        if ((null === ($element = $this->getElement())) ||
            (null === ($view = $element->getView()))) {
            return $content;
        }

        $cellDecorators = $this->getCellDecorators();

        $containerDiv = MUtil_Html::create()->div(array('id' => 'tabElement'));


        if ($element instanceof MUtil_Form_Element_Table) {
            $containerDiv->appendAttrib('class', $element->getAttrib('class'));
            $subforms = $element->getSubForms();
        } elseif ($element instanceof Zend_Form)  {
            $cellDecorators = null;
            $subforms = array($element);
        }
        
        if ($subforms) {
            $activeTabs = false;
            if (count($subforms) > 1) {
                $activeTabs = true;

                $jquery = $view->jQuery();

                $js = sprintf('%1$s("#tabElement").tabs();', ZendX_JQuery_View_Helper_JQuery::getJQueryHandler());
                $jquery->addOnLoad($js);

                $list = $containerDiv->ul();
            }
            $tabNumber = 0;

            $active = $this->getOption('active');
            foreach($subforms as $subform) {
                if ($activeTabs) {
                    if ($tabcolumn = $this->getOption('tabcolumn')) {
                        $tabName = $subform->getElement($tabcolumn)->getValue();
                    } else {
                        $elements = reset($subform->getElements());
                        $tabName = $element->getValue();
                    }
                    
                    if ($active == $tabName) {
                        $js = sprintf('%1$s("#tabElement").tabs({ selected: %2$d});', ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(), $tabNumber);
                        $jquery->addOnLoad($js);
                    }
                    $tabNumber++;

                    $tabId = $tabName.'-tab';
                    $list->li()->a('#'.$tabId, $tabName);
                    $subtable = $containerDiv->div(array('id' => $tabId))->table(array('class' => 'formTable'));
                } else {
                    $subtable = $containerDiv->table(array('class' => 'formTable'));
                }
                foreach ($subform->getElements() as $subelement) {

                    if ($subelement instanceof Zend_Form_Element_Hidden) {
                        $this->applyDecorators($subelement, array(array('ViewHelper')));
                        $subtable[] = $subelement;
                    } else {
                        $row = $subtable->tr();
                        $label = $row->td()->label(array('for' => $subelement->getId()));

                        $label[] = $subelement->getLabel();
                        
                        //MUtil_Echo::track($subelement);

                        $column = $row->td();
                        
                        $column[] = $subelement;
                    }
                }
            }
        }


        $containerDiv->view = $view;
        $html = $containerDiv;

        return $html;
    }

    /**
     * Set decorator options from a config object
     *
     * @param  Zend_Config $config
     * @return Zend_Form_Decorator_Interface
     */
    public function setConfig(Zend_Config $config)
    {
        $this->setOptions($config->toArray());

        return $this;
    }

    /**
     * Set an element to decorate
     *
     * While the name is "setElement", a form decorator could decorate either
     * an element or a form object.
     *
     * @param  mixed $element
     * @return Zend_Form_Decorator_Interface
     */
    public function setElement($element)
    {
        $this->_subform = $element;

        return $this;
    }


    /**
     * Set a single option
     *
     * @param  string $key
     * @param  mixed $value
     * @return Zend_Form_Decorator_Interface
     */
    public function setOption($key, $value)
    {
        switch ($key) {
            case 'cellDecorator':
                $value = $this->getCellDecorators() + array($value);

            case 'cellDecorators':
                $this->_cellDecorators = $value;
                break;

            default:
                $this->_options[$key] = $value;
                break;
        }

        return $this;
    }

    /**
     * Set decorator options from an array
     *
     * @param  array $options
     * @return Zend_Form_Decorator_Interface
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            $this->setOption($key,  $value);
        }

        return $this;
    }
}