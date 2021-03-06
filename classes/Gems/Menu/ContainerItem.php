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
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * A container item is one that gathers multiple sub menu
 * items, but does not have it's own controller/action pair
 * but selects the first sub item instead.
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.5
 */
class Gems_Menu_ContainerItem extends \Gems_Menu_SubMenuItem
{
    /**
     *
     * @var \Zend_Session_Namespace
     */
    private static $_sessionStore;

    /**
     *
     * @return \Zend_Session_Namespace
     */
    private static function _getSessionStore($label)
    {
        if (! self::$_sessionStore instanceof \Zend_Session_Namespace) {
            self::$_sessionStore = new \Zend_Session_Namespace('MenuContainerItems');
        }
        if (! isset(self::$_sessionStore->$label)) {
            self::$_sessionStore->$label = new \ArrayObject();
        }

        return self::$_sessionStore->$label;
    }

    /**
     * Returns a \Zend_Navigation creation array for this menu item, with
     * sub menu items in 'pages'
     *
     * @param \Gems_Menu_ParameterCollector $source
     * @return array
     */
    protected function _toNavigationArray(\Gems_Menu_ParameterCollector $source)
    {
        $result = parent::_toNavigationArray($source);

        $store  = self::_getSessionStore($this->get('label'));
        if (isset($store->controller)) {
            foreach ($result['pages'] as $page) {
                if ($page['controller'] === $store->controller) {
                    if (isset($store->action) && $page['action'] === $store->action) {
                        $result['action'] = $store->action;
                        $this->set('action', $store->action);
                    }
                    $result['controller'] = $store->controller;
                    $this->set('controller', $store->controller);
                }
            }
        }

        // Get any missing MVC keys from children, even when invisible
        if ($requiredIndices = $this->notSet('controller', 'action')) {

            if (isset($result['pages'])) {
                $firstChild = null;
                $order = 0;
                foreach ($result['pages'] as $page) {
                    if ($page['allowed']) {
                        if ($page['order'] < $order || $order == 0) {
                            $firstChild = $page;
                            $order = $page['order'];
                        }
                    }
                }

                if (null === $firstChild) {
                    // No children are visible and required mvc properties
                    // are missing: ergo this page is not visible.
                    $result['visible'] = false;

                    // Use first (invisible) child as firstChild
                    $firstChild = reset($result['pages']);
                }
            } else {
                // Use '/' slash as default to ensure any not visible
                // menu items points to another existing item that is
                // active.
                $firstChild = array_fill_keys($requiredIndices, '/');
            }

            foreach ($requiredIndices as $key) {
                $result[$key] = $firstChild[$key];
            }
        }

        return $result;
    }

    /**
     * Set the visibility of the menu item and any sub items in accordance
     * with the specified user role.
     *
     * @param \Zend_Acl $acl
     * @param string $userRole
     * @return \Gems_Menu_MenuAbstract (continuation pattern)
     */
    protected function applyAcl(\MUtil_Acl $acl, $userRole)
    {
        parent::applyAcl($acl, $userRole);

        if ($this->isVisible()) {
            $this->set('allowed', false);
            $this->set('visible', false);

            if ($this->_subItems) {
                foreach ($this->_subItems as $item) {

                    if ($item->get('visible', true)) {
                        $this->set('allowed', true);
                        $this->set('visible', true);

                        return $this;
                    }
                }
            }
        }
        return $this;
    }

    /**
     * Make sure only the active branch is visible
     *
     * @param array $activeBranch Of \Gems_Menu_Menu Abstract items
     * @return \Gems_Menu_MenuAbstract (continuation pattern)
     */
    protected function setBranchVisible(array $activeBranch)
    {
        parent::setBranchVisible($activeBranch);

        $child  = end($activeBranch);
        $store  = self::_getSessionStore($this->get('label'));
        $contr  = $child->get('controller');
        $action = $child->get('action');

        $store->controller = $contr;
        $store->action = $action;
        $this->set('controller', $contr);
        $this->set('action', $action);

        return $this;
    }
}
