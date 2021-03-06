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
 * @package    Gems
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 * Format and araay of data according to a provided model
 *
 * @package    Gems
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class Gems_FormattedData extends \IteratorIterator
{
    /**
     * @var \MUtil_Model_ModelAbstract
     */
    private $model;

    /**
     * @var \ArrayObject
     */
    private $data;

    private $formatted;

    /**
     * A cache for the model options needed to format the data
     *
     * @var array
     */
    protected $_options = array();

    /**
     *
     * @param array $data
     * @param \MUtil_Model_ModelAbstract $model
     * @param boolean $formatted
     * @return \Gems_FormattedData
     */
    public function __construct($data, \MUtil_Model_ModelAbstract $model, $formatted = true)
    {
        $this->data  = parent::__construct(new \ArrayObject($data));
        $this->model = $model;
        $this->setFormatted($formatted);
        return $this;
    }

    public function current() {
        //Here we get the actual record to transform!
        $row = parent::current();
        if ($this->formatted) {
            $row = $this->format($row, $this->model);
        }
        return $row;
    }

    /**
     * Formats a row of data using the given model
     *
     * Static method only available for single rows, for a convenient way of using on a
     * rowset, use the class and iterate
     *
     * @param array $row
     * @param \MUtil_Model_ModelAbstract $model
     * @return array The formatted array
     */
    public function format($row, $model) {
        foreach ($row as $fieldname=>$value) {
                $row[$fieldname] = $this->_format($fieldname, $row[$fieldname], $model);
        }
        return $row;
    }

     /**
     * This is the actual format function, copied from the Exhibitor for field
     *
     * @param type $name
     * @param type $result
      *@param \MUtil_Model_ModelAbstract $model
     * @return type
     */
    private function _format($name, $result, $model)
    {
        static $view = null;

        if (!isset($this->_options[$name])) {
            $this->_options[$name] = $model->get($name, array('default', 'multiOptions', 'formatFunction', 'dateFormat', 'storageFormat', 'itemDisplay'));
        }

        $options = $this->_options[$name];

        foreach($options as $key => $value) {
            switch ($key) {
                case 'default':
                    if (is_null($result)) {
                        $result = $value;
                    }
                    break;

                case 'multiOptions':
                    $multiOptions = $value;
                    if (is_array($multiOptions)) {
                        /*
                         *  Sometimes a field is an array and will be formatted later on using the
                         *  formatFunction -> handle each element in the array.
                         */
                        if (is_array($result)) {
                            foreach($result as $key => $value) {
                                if (array_key_exists($value, $multiOptions)) {
                                    $result[$key] = $multiOptions[$value];
                                }
                            }
                        } else {
                            if (array_key_exists($result, $multiOptions)) {
                                $result = $multiOptions[$result];
                            }
                        }
                    }
                    break;

                case 'formatFunction':
                    $callback = $value;
                    $result = call_user_func($callback, $result);
                    break;

                case 'dateFormat':
                    if (array_key_exists('formatFunction', $options)) {
                        // if there is a formatFunction skip the date formatting
                        continue;
                    }

                    $dateFormat = $value;
                    $storageFormat = $model->get($name, 'storageFormat');
                    $result = \MUtil_Date::format($result, $dateFormat, $storageFormat);
                    break;

                case 'itemDisplay':
                    $function = $value;
                    if (is_callable($function)) {
                        $result = call_user_func($function, $result);
                    } elseif (is_object($function)) {
                        if (($function instanceof \MUtil_Html_ElementInterface)
                            || method_exists($function, 'append')) {
                            $object = clone $function;
                            $result = $object->append($result);
                        }
                    } elseif (is_string($function)) {
                        // Assume it is a html tag when a string
                        $result = \MUtil_Html::create($function, $result);
                    }

                default:
                    break;
            }
        }

        if (is_object($result)) {
            // If it is Lazy, execute it
            if ($result instanceof \MUtil_Lazy_LazyInterface) {
                $result = \MUtil_Lazy::rise($result);
            }

            // If it is Html, render it
            if ($result instanceof \MUtil_Html_HtmlInterface) {

                if (is_null($view)) {
                    $viewRenderer = \Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
                    if (null === $viewRenderer->view) {
                        $viewRenderer->initView();
                    }
                    $view = $viewRenderer->view;
                }

                $result = $result->render($view);
            }
        }
        return $result;
     }

     public function getFormatted() {
         return $this->formatted;
     }

     public function setFormatted($bool) {
         $this->formatted = (bool) $bool;
     }
}