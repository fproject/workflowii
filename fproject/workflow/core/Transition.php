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
 * Transition object is an oriented link between a start and an end status.
 */
class Transition extends AbstractWorkflowItem implements ITransition
{
	private $_id = null;

	/**
	 * @var IStatus the status this transition is starting from
	 */
	private $_startStatus;
	/**
	 * @var IStatus the status this transition is ending to.
	 */
	private $_endStatus;

	private $_label;

    /**
     * Creates a Transition object.
     *
     * To create a new Transition, you should provide following mandatory values in the
     * configuration array $config :
     *
     * - **start** : the start Status instance
     * - **end** : the end Status instance
     *
     * @see Status
     * @param array $config
     * @throws InvalidConfigException
     * @throws WorkflowException
     */
	public function __construct($config = [])
	{
		if (!empty($config['start'])) {
			$this->_startStatus = $config['start'];
			unset($config['start']);
			if (!$this->_startStatus instanceof Status) {
				throw new WorkflowException('Start status must be an instance of Status');
			}
		} else {
			throw new InvalidConfigException('missing start status');
		}

		if (!empty($config['end'])) {
			$this->_endStatus = $config['end'];
			unset($config['end']);
			if (!$this->_endStatus instanceof Status) {
				throw new WorkflowException('End status must be an instance of Status');
			}

		} else {
			throw new InvalidConfigException('missing end status');
		}

		if (!empty($config['label'])) {
			$this->_label = $config['label'];
			unset($config['label']);
		}

		parent::__construct($config);
		$this->_id = $this->_startStatus->getId().'-'.$this->_endStatus->getId();
	}
	/**
	 * Returns the id of this transition.
	 *
	 * The id is built by concatenating the start and the end status Ids, separated with character '-'. For instance, a transition
	 * between status A and B has an idea equals to "A-B".
	 *
	 * @return string the transition Id
	 * @see \fproject\workflow\core\WorkflowItem::getId()
	 */
	public function getId()
	{
		return $this->_id;
	}

	/**
	 * @inheritdoc
	 */
	public function getEndStatus()
	{
		return $this->_endStatus;
	}

    /**
     * @inheritdoc
     */
	public function getStartStatus()
	{
		return $this->_startStatus;
	}

	/**
	 * @inheritdoc
	 */
	public function getLabel()
	{
		return $this->_label;
	}
}
