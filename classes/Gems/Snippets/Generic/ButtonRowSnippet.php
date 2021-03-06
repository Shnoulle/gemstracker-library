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
 * @subpackage Snippets\Generic
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id: CurrentSiblingsButtonRowSnippet.php 203 2011-07-07 12:51:32Z matijs $
 */

namespace Gems\Snippets\Generic;

/**
 * Displays the parent menu item (if existing) plus any current
 * level buttons that are visible
 *
 * @package    Gems
 * @subpackage Snippets\Generic
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2
 */
class ButtonRowSnippet extends \MUtil_Snippets_SnippetAbstract
{
    /**
     * Add the children of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentChildren = false;

    /**
     * Add the parent of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentParent = false;

    /**
     * Add the siblings of the current menu item
     *
     * @var boolean
     */
    protected $addCurrentSiblings = false;

    /**
     * Add siblings of the current menu item with any paramters.
     *
     * Add only those with the same when false.
     *
     * @var boolean
     */
    protected $anyParameterSiblings = false;

    /**
     * Required
     *
     * @var \Gems_Menu
     */
    protected $menu;

    /**
     * Required
     *
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     * Set the menu items (allows for overruling in subclasses)
     *
     * @param \Gems_Menu_MenuList $menuList
     */
    protected function addButtons(\Gems_Menu_MenuList $menuList)
    {
        if ($this->addCurrentParent) {
            $menuList->addCurrentParent($this->_('Cancel'));
        }
        if ($this->addCurrentSiblings) {
            $menuList->addCurrentSiblings($this->anyParameterSiblings);
        }
        if ($this->addCurrentChildren) {
            $menuList->addCurrentChildren();
        }
        // \MUtil_Echo::track($this->addCurrentParent, $this->addCurrentSiblings, $this->addCurrentChildren);
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
        $menuList = $this->menu->getMenuList();

        $menuList->addParameterSources($this->request, $this->menu->getParameterSource());

        // \MUtil_Echo::track($this->request->getParams(), $this->menu->getParameterSource()->getArrayCopy());

        $this->addButtons($menuList);

        if ($menuList->render($view)) {
            return \MUtil_Html::create('div', array('class' => 'buttons', 'renderClosingTag' => true), $menuList);
        }
    }
}
