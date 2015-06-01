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

class Workflow extends AbstractWorkflowItem
{
	const PARAM_INITIAL_STATUS_ID = 'initialStatusId';

	private $_id;
	private $_initialStatusId;

	public function __construct($config = [])
	{
		if (!empty($config['id'])) {
			$this->_id = $config['id'];
			unset($config['id']);
		} else {
			throw new InvalidConfigException('missing workflow id ');
		}

		if (!empty($config[self::PARAM_INITIAL_STATUS_ID])) {
			$this->_initialStatusId = $config[self::PARAM_INITIAL_STATUS_ID];
			unset($config[self::PARAM_INITIAL_STATUS_ID]);
		} else {
			throw new InvalidConfigException('missing initial status id');
		}
		parent::__construct($config);
	}

    /** @inheritdoc */
	public function getId()
	{
		return $this->_id;
	}

    /**
     * Gets the initial status's ID
     */
    public function getInitialStatusId()
	{
		return $this->_initialStatusId;
	}
}
