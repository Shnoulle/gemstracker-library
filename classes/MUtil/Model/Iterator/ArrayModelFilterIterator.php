<?php

/**
 * Copyright (c) 201e, Erasmus MC
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
 * @package    MUtil
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 201e Erasmus MC
 * @license    New BSD License
 * @version    $id: ArrayModelFilterIterator.php 203 2012-01-01t 12:51:32Z matijs $
 */

/**
 *
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class MUtil_Model_Iterator_ArrayModelFilterIterator implements OuterIterator, Serializable
{
    /**
     * The filter to apply
     *
     * @var array
     */
    protected $_filter;

    /**
     *
     * @var Iterator
     */
    protected $_iterator;

    /**
     *
     * @var MUtil_Model_ArrayModelAbstract
     */
    protected $_model;

    /**
     *
     * @param Iterator $iterator
     * @param MUtil_Model_ArrayModelAbstract $model
     * @param array $filter
     */
    public function __construct(Iterator $iterator, $model, array $filter)
    {
        $this->_iterator = $iterator;
        $this->_model    = $model;
        $this->_filter   = $filter;
    }

    /*
    public function __wakeup()
    {
        echo get_class($this->_iterator), get_class($this->_model) . '<br/>';;
    } // */

    /**
     *
     * @return boolean
     */
    public function accept()
    {
        // return $this->_model->applyFiltersToRow($this->current(), $this->_filter);
        return call_user_func($this->_model, $this->current(), $this->_filter);
    }

    /**
     * Return the current element
     *
     * @return array
     */
    public function current()
    {
        return $this->_iterator->current();
    }

    /**
     * Get the inner iterator.
     *
     * @return Iterator
     */
    public function getInnerIterator()
    {
        return $this->_iterator;
    }

    /**
     * Return the key of the current element
     *
     * @return int
     */
    public function key()
    {
        return $this->_iterator->key();
    }

    /**
     * Move forward to next element
     */
    public function next()
    {
        do {
            $this->_iterator->next();
        } while (! $this->accept());
    }

    /**
     *  Rewind the Iterator to the first element
     */
    public function rewind()
    {
        $this->_iterator->rewind();

        while (! $this->accept()) {
            $this->_iterator->next();
        }
    }

    /**
     * Return the string representation of the object.
     *
     * @return string
     */
    public function serialize()
    {
        $serializer = Zend_Serializer::getDefaultAdapter();

        $data = array(
            'filter' => $this->_filter,
            'model'  => $this->_model,
            'iter'   => $serializer->serialize($this->_iterator),
        );

        return Zend_Serializer::getDefaultAdapter()->serialize($data);
    }

    /**
     * Called during unserialization of the object.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $serializer = Zend_Serializer::getDefaultAdapter();

        echo $serialized . '<br/><br/><br/>';
        // MUtil_Echo::track('check');

        $data = $serializer->unserialize($serialized);

        // echo $data['iter'] . '<br/>';
        $this->_filter   = $data['filter'];
        $this->_model    = $data['model'];
        $this->_iterator = $serializer->unserialize($data['iter']);
        // echo get_class($this->_iterator) . ' ' . $this->_iterator->_mapFunction[0] . '<br/>';
        // $this->_model = $this->_iterator->_mapFunction[0];
    }

    /**
     * True if not end of inner iterator
     *
     * @return boolean
     */
    public function valid()
    {
        return $this->_iterator->valid();
    }
}
