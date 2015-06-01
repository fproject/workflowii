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

namespace fproject\workflow\validators;

use fproject\workflow\helpers\WorkflowScenario;
use Yii;
use yii\base\Model;
use yii\validators\Validator;
use fproject\workflow\core\ActiveWorkflowBehavior;
use fproject\workflow\core\WorkflowException;

/**
 * WorkflowValidator run validation for the current workflow event.
 *
 * @author Bui Sy Nguyen
 *
 */
class WorkflowValidator extends Validator
{
	/**
	 * Overloads the default initialization value because by default, we want to run the validation
	 * even if the status attribute is null (which is considered as a 'leaveWorkflow' event).
	 *
	 * @var boolean see yii\validators§\Validator
	 */
	public $skipOnEmpty = false;

	public function init()
	{
		parent::init();
		if ($this->message === null) {
			$this->message = Yii::t('app', 'Error on {attribute}.');
		}
	}

    /**
     * Apply active validators for the current workflow event sequence.
     *
     * If a workflow event sequence is about to occur, this method scan all validators defined in the
     * owner model, and applies the ones which are valid for the upcomming events.
     * @param Model|ActiveWorkflowBehavior $object
     * @param string $attribute
     * @throws WorkflowException
     *
     * @see Validator::validateAttribute()
     * @see IEventSequence
     */
	public function validateAttribute($object, $attribute)
	{
		if (!ActiveWorkflowBehavior::isAttachedTo($object) ) {
			throw new WorkflowException('Validation error : the model does not have the ActiveWorkflowBehavior');
		}

		try {
			$scenarioList= $object->getScenarioSequence($object->$attribute);
		} catch (WorkflowException $e) {
			$object->addError($attribute, 'Workflow validation failed : '.$e->getMessage());
			$scenarioList = [];
		}

		if (count($scenarioList) != 0 ) {
			foreach ($object->getValidators() as $validator) {
				foreach ($scenarioList as $scenario) {
					if ($this->_isActiveValidator($validator, $scenario)) {
						$validator->validateAttributes($object);
					}
				}
			}
		}
	}

    /**
     * Checks if a validator is active for the workflow event passed as argument.
     *
     * @param Validator $validator The validator instance to test
     * @param $currentScenario
     * @return bool
     */
	private function _isActiveValidator($validator, $currentScenario)
	{
		foreach ($validator->on as $scenario) {
			if (WorkflowScenario::match($scenario, $currentScenario)) {
				return true;
			}
		}
		return false;
	}
}
