<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
 * @package    MUtil
 * @subpackage Task
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: TaskRunnerBatch.php$
 */

/**
 * The TaskBatch is an implementation of MUtil_Batch_BatchAbstract that simplifies
 * batch creation by allowing each job step to be created in a seperate class.
 *
 * These tasks can automatically load global objects when they implement
 * MUtil_Registry_TargetInterface. Otherwise you can pass only scalar values during
 * execution.
 *
 * Task are loaded through a plugin architecture, but you can also specify them using
 * their full class name.
 *
 * @see MUtil_Batch_BatchAbstract
 * @see MUtil_Registry_TargetInterface
 *
 * @package    MUtil
 * @subpackage Task
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class MUtil_Task_TaskBatch extends MUtil_Batch_BatchAbstract
{
    /**
     *
     * @var MUtil_Registry_SourceInterface
     */
    protected $source;

    /**
     *
     * @var MUtil_Loader_PluginLoader
     */
    protected $taskLoader;

    /**
     *
     * @var array containing the classPrefix => classPath for task laoder
     */
    protected $taskLoaderDirs = array('MUtil_Task' => 'MUtil/Task');

    /**
     * Add a task to the stack, optionally adding as much parameters as needed
     *
     * @param string $task Name of Task class
     * @param mixed $param1 Optional scalar or array with scalars, as many parameters as needed allowed
     * @param mixed $param2 ...
     * @return \MUtil_Task_TaskBatch (continuation pattern)
     */
    public function addTask($task, $param1 = null)
    {
        $taskClass = $this->getTaskLoader()->load($task);

        if (! $taskClass instanceof MUtil_Task_TaskInterface) {
            throw new MUtil_Batch_BatchException(sprintf('Cannot load task class %s.', $task));
        }

        // Repeatable task cannot
        if ($this->isPull() && ($taskClass instanceof MUtil_Task_RepeatableTaskInterface)) {
            // Cannot use push with repeatable tasks
            $this->setMethodPush();
        }

        $params = array_slice(func_get_args(), 1);
        $this->addStep('runTask', $task, $params);

        return $this;
    }

    /**
     * Helper function for adding an iterator task to the stack, optionally adding as much parameters as needed.
     *
     * @param string $task Name of Task class, the execute method is called repeatedly with the output of the each
     *                     iteration used as the input for the execute method of this task.
     * @param callable $callForIterator A method to call for getting the iterator. You cannot pass the iterator itself.
     * @param mixed $param1 Optional scalar or array with scalars, as many parameters as needed call the iterator
     * @param mixed $param2 ...
     * @return \MUtil_Task_TaskBatch (continuation pattern)
     */
    public function addTaskIterator($task, $callForIterator, $param1 = null)
    {
        $taskClass = $this->getTaskLoader()->load($task);

        if (! $taskClass instanceof MUtil_Task_TaskInterface) {
            throw new MUtil_Batch_BatchException(sprintf('Cannot load task class %s.', $task));
        }

        if (!is_callable($callForIterator)) {
            throw new MUtil_Batch_BatchException(sprintf(
                    'Second task iterator argument for task class %s is not callable.',
                    $task
                    ));
        }

        // Add the iterator taks.
        $args = func_het_args();
        array_unshift($args, 'IteratorTask');

        call_user_func_array(array($this, 'addTask'), $args);

        return $this;
    }

    /**
     * Return the source used to set variables in tasks.
     *
     * @return MUtil_Registry_SourceInterface
     */
    public function getSource()
    {
        if (! $this->source) {
            $this->setSource(new MUtil_Registry_Source());
        }
        return $this->source;
    }

    /**
     * Get the plugin loader to load the tasks
     *
     * @return  MUtil_Loader_PluginLoader
     */
    public function getTaskLoader()
    {
        if (! $this->taskLoader) {
            $this->setTaskLoader(new MUtil_Loader_PluginLoader($this->getTaskLoaderPrefixDirectories()));
        }

        return $this->taskLoader;
    }

    /**
     * Returns an array containing the classPrefix => classPath values
     * to be used by this instance
     *
     * @return array
     */
    public function getTaskLoaderPrefixDirectories()
    {
        return $this->taskLoaderDirs;
    }

    /**
     *
     * @param string $task Class name of task
     * @param array $params Parameters used in the call to execute
     * @throws Gems_Exception
     */
    public function runTask($task, array $params = array())
    {
        $taskObject = $this->getTaskLoader()->createClass($task);
        if ($taskObject instanceof MUtil_Registry_TargetInterface) {
            $this->getSource()->applySource($taskObject);
        }

        if ($taskObject instanceof MUtil_Task_TaskInterface) {
            $taskObject->setBatch($this);

            call_user_func_array(array($taskObject, 'execute'), $params);

            if ($taskObject instanceof MUtil_Task_RepeatableTaskInterface) {
                // Loop here. No exit is reason repeatable task cannot
                // use the PULL method.
                while (! $taskObject->isFinished()) {

                    $this->_extraRun();

                    if ($this->_checkReport()) {
                        // Communicate progress
                        $this->_updateBar();
                    }
                    
                    call_user_func_array(array($taskObject, 'execute'), $params);
                }
            } else {
            }
        } else {
            throw new Gems_Exception(sprintf('ERROR: Task by name %s not found', $task));
        }
    }

    /**
     * Set the variable source for tasks.
     *
     * @param MUtil_Registry_SourceInterface $source
     * @return \MUtil_Task_TaskBatch (continuation pattern)
     */
    public function setSource(MUtil_Registry_SourceInterface $source)
    {
        $this->source = $source;
        return $this;
    }

    /**
     * Add an execution step to the command stack.
     *
     * @param string $task Name of Task class
     * @param mixed $id A unique id to prevent double adding of something to do
     * @param mixed $param1 Scalar or array with scalars, as many parameters as needed allowed
     * @return \MUtil_Task_TaskBatch (continuation pattern)
     */
    public function setTask($task, $id, $param1 = null)
    {
        $params = array_slice(func_get_args(), 2);
        $this->setStep('runTask', $id, $task, $params);

        return $this;
    }

    /**
     * Set the plugin loader to load the tasks
     *
     * @param MUtil_Loader_PluginLoader $taskLoader
     * @return \MUtil_Task_TaskBatch (continuation pattern)
     */
    public function setTaskLoader(MUtil_Loader_PluginLoader $taskLoader)
    {
        $this->taskLoader = $taskLoader;
        return $this;
    }

    /**
     * Set the directories to be used by this instance
     *
     * @param array $dirs An array containing the classPrefix => classPath values
     * @return \MUtil_Task_TaskBatch (continuation pattern)
     */
    public function setTaskLoaderPrefixDirectories(array $dirs)
    {
        $this->taskLoaderDirs = $dirs;
        return $this;
    }
}
