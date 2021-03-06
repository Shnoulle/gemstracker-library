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
 */

/**
 * A special displaygroup, to be displayed in a jQuery tab. Main difference is in the decorators.
 *
 * @version $Id$
 * @author 175780
 * @filesource
 * @package Gems
 * @subpackage Form
 */
class Gems_Form_TabGroup extends \Zend_Form_DisplayGroup {

    private $_alternate = null;

    public function  __construct($name, \Zend_Loader_PluginLoader $loader, $options = null) {
        $this->_alternate = new \MUtil_Lazy_Alternate(array('odd','even'));
        parent::__construct($name, $loader, $options);
    }

    /**
     * Add element to stack
     *
     * @param  \Zend_Form_Element $element
     * @return \Zend_Form_DisplayGroup
     */
    public function addElement(\Zend_Form_Element $element)
    {
        $decorators = $element->getDecorators();
        $decorator = array_shift($decorators);
        $element->setDecorators(array($decorator,
            array('Description', array('class'=>'description')),
                            'Errors',
                            array(array('data' => 'HtmlTag'), array('tag' => 'td', 'class' => 'element')),
                            array('Label'),
                            array(array('labelCell' => 'HtmlTag'), array('tag' => 'td', 'class'=>'label')),
                            array(array('row' => 'HtmlTag'), array('tag' => 'tr', 'class' => $this->_alternate))
            ));
        
        return parent::addElement($element);
    }

    /**
     * Load default decorators
     *
     * @return void
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('FormElements')
                 ->addDecorator(array('table' => 'HtmlTag'), array('tag' => 'table', 'class'=>'formTable'))
                 ->addDecorator(array('tab' => 'HtmlTag'), array('tag' => 'div', 'class' => 'displayGroup'))
                 ->addDecorator('TabPane', array('jQueryParams' => array('containerId' => 'mainForm',
                                                                         'title' => $this->getAttrib('title'))));
        }
        return $this;
    }
}