<?php
///////////////////////////////////////////////////////////////////////////////
//
// © Copyright f-project.net 2010-present. All Rights Reserved.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//
///////////////////////////////////////////////////////////////////////////////

namespace fproject\workflow\core;

use Yii;
use yii\base\InvalidConfigException;

/**
 * A Status object is a component of a workflow.
 *
 * @author Bui Sy Nguyen
 */
class Status extends AbstractWorkflowItem implements IStatus
{
	/**
	 * @var string the status Id
	 */
	private $_id;
	/**
	 * @var string the status label
	 */
	private $_label = '';
	/**
	 * @var string the workflow Id
	 */
	private $_workflow_id;
	/**
	 * @var Transition[] list of all out-going transitions for this status
	 */
	private $_transitions = [];


	/**
	 * Status constructor.
	 *
	 * To create a Status you must provide following values
	 * in the $config array passed as argument:
	 *
	 * - **id** : the id for this status.
	 * - **workflowId ** : the id of the workflow this status belongs to.
	 *
	 * Following values are optional :
	 *
	 * - **label** : human readable name for this status.
	 *
	 * @param array $config
	 * @throws InvalidConfigException
	 */
	public function __construct($config = [])
	{
		if ( ! empty($config['workflowId'])) {
			$this->_workflow_id = $config['workflowId'];
			unset($config['workflowId']);
		} else {
			throw new InvalidConfigException('missing workflow id');
		}

		if ( ! empty($config['id'])) {
			$this->_id = $config['id'];
			unset($config['id']);
		} else {
			throw new InvalidConfigException('missing status id');
		}

		if ( ! empty($config['label'])) {
			$this->_label = $config['label'];
			unset($config['label']);
		}
		parent::__construct($config);
	}

    /**
     * Add an out-going transition to this status.
     *
     * @param Transition $transition
     * @throws WorkflowException
     */
	public function addTransition($transition)
	{
		if ( empty($transition) || ! $transition instanceof Transition) {
			throw new WorkflowException('"transition" must be an instance of Transition');
		}
		$this->_transitions[$transition->getEndStatus()->getId()] = $transition;
	}

	/**
	 * @inheritdoc
	 */
	public function getId()
	{
		return $this->_id;
	}

    /**
     * @inheritdoc
     */
	public function getLabel()
	{
		return $this->_label;
	}

    /**
     * @inheritdoc
     */
	public function getWorkflowId()
	{
		return $this->_workflow_id;
	}

    /**
     * @inheritdoc
     */
	public function getTransitions()
	{
		return $this->_transitions;
	}
}
