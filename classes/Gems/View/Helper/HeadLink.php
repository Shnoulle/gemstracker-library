<?php
/**
 * Copyright (c) 2014, Erasmus MC
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
 * @subpackage View\Helper
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */



/**
 * Make sure each .less css script is compiled to .css
 *
 * @package    Gems
 * @subpackage View\Helper
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.5
 */
class Gems_View_Helper_HeadLink extends \MUtil_Less_View_Helper_HeadLink
{
    public function __construct()
    {
        parent::__construct();
        $this->_webroot = GEMS_WEB_DIR;
    }

    /**
     * Create HTML link element from data item
     *
     * @param  \stdClass $item
     * @return string
     */
    public function itemToString(\stdClass $item)
    {

    	$attributes = (array) $item;

        if (isset($attributes['type']) &&
                (($attributes['type'] == 'text/css') || ($attributes['type'] == 'text/less'))) {

            // This is a stylesheet, consider extension and compile .less to .css
            if (($attributes['type'] == 'text/less') || \MUtil_String::endsWith($attributes['href'], '.less', true)) {
                $this->compile($this->view, $attributes['href'], false);

                // Modify object, not the derived array
                $item->type = 'text/css';
                $item->href = substr($attributes['href'], 0, -4) . 'css';
            }
        }

    	$version = $this->view->currentVersion;

    	if (property_exists($item, 'href')) {
        	$item->href = $item->href . '?' . $version;
        }

        return \Zend_View_Helper_HeadLink::itemToString($item);
    }

}