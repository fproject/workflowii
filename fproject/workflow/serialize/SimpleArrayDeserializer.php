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

namespace fproject\workflow\serialize;

use fproject\workflow\core\ActiveWorkflowBehavior;
use fproject\workflow\core\ArrayWorkflowItemFactory;
use fproject\workflow\core\IWorkflowItemFactory;
use Yii;
use yii\base\Component;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use fproject\workflow\core\WorkflowException;
use yii\helpers\VarDumper;

/**
 * Parse a workflow definition provided as a PHP associate array with minimal information.
 * 
 * Following rules apply :
 * - the array must be associative, each key being a status Id, and each value is an array of target status id.
 * - no 'initialStatusId' is required : the first status defined is considered as the initial status
 * - no additional attribute is supported : label, metadata, transition 
 *
 * For example : 
 * [
 *	'draft'     => ['ready', 'delivered'],
 *	'ready'     => ['draft', 'delivered'],
 *	'delivered' => ['payed', 'archived'],
 * 	'payed'     => ['archived'],
 *	'archived'  => []
 * ]
 * 
 * You can also use a comma separated list of status for the end status list instead of an array.
 * For example : 
 * [
 *	'draft'     => 'ready, delivered',
 *	'ready'     => 'draft, delivered',
 *	'delivered' => 'payed, archived',
 * 	'payed'     => 'archived',
 *	'archived'  => []
 * ]
 */
class SimpleArrayDeserializer extends Object implements IArrayDeserializer {
	
	/**
	 * @var boolean when TRUE, the parse method will also perform some validations
	 */
	public $validate = true;

    /**
     * @inheritdoc
     */
	public function deserialize($wId, $definition, $factory, $model) {
		if (empty($wId)) {
			throw new WorkflowException("Missing argument : workflow Id");
		}
		if (!is_array($definition)) {
			throw new WorkflowException("Workflow definition must be provided as an array");
		}
		
		if (!ArrayHelper::isAssociative($definition)) {
			throw new WorkflowException("Workflow definition must be provided as associative array");
		}
		
		$normalized 		= [];
		$startStatusIdIndex = [];
		$endStatusIdIndex   = [];
		
		foreach($definition as $id => $targetStatusList) {
			list($workflowId, $statusId,) = $factory->parseWorkflowStatus($id, $wId, $model);
			$absoluteStatusId = $workflowId . ArrayWorkflowItemFactory::SEPARATOR_STATUS_NAME .$statusId;
			if ( $workflowId != $wId) {
				throw new WorkflowException('Status must belong to workflow : ' . $absoluteStatusId);
			}
			if (count($normalized) == 0) {
				$initialStatusId = $absoluteStatusId;
				$normalized['initialStatusId'] = $initialStatusId;
				$normalized[ArrayWorkflowItemFactory::KEY_NODES] = [];
			}
			$startStatusIdIndex[] = $absoluteStatusId;

			if (is_string($targetStatusList)) {
				$ids = array_map('trim', explode(',', $targetStatusList));
				$endStatusIds = $this->normalizeStatusIds($ids, $wId, $factory, $model);
			}elseif (is_array($targetStatusList)) {
				if( ArrayHelper::isAssociative($targetStatusList,false) ){
					throw new WorkflowException("Associative array not supported (status : $absoluteStatusId)");
				}
				$endStatusIds = $this->normalizeStatusIds($targetStatusList, $wId, $factory, $model);
			}elseif ( $targetStatusList === null ) {
				$endStatusIds = [];
			}else {
				throw new WorkflowException('End status list must be an array for status  : ' . $absoluteStatusId);
			}
			
			if ( count($endStatusIds)) {
				$normalized[ArrayWorkflowItemFactory::KEY_NODES][$absoluteStatusId] = ['transition' => array_fill_keys($endStatusIds,[])];
				$endStatusIdIndex = array_merge($endStatusIdIndex, $endStatusIds);
			} else {
				$normalized[ArrayWorkflowItemFactory::KEY_NODES][$absoluteStatusId] = null;
			}
		}

		if ( $this->validate === true) {
			if (isset($initialStatusId) && !in_array($initialStatusId, $startStatusIdIndex)) {
				throw new WorkflowException("Initial status not defined : $initialStatusId");
			}
		
			// detect not defined statuses
		
			$missingStatusIdSuspects = array_diff($endStatusIdIndex, $startStatusIdIndex);
			if ( count($missingStatusIdSuspects) != 0) {
				$missingStatusId = [];
				foreach ($missingStatusIdSuspects as $id) {
					list($thisWid,,) = $factory->parseWorkflowStatus($id, $wId, $model);
					if ($thisWid == $wId) {
						$missingStatusId[] = $id; // refering to the same workflow, this Id is not defined
					}
				}
				if ( count($missingStatusId) != 0) {
					throw new WorkflowException("One or more end status are not defined : ".VarDumper::dumpAsString($missingStatusId));
				}
			}
		}
		return $normalized;
	}

    /**
     *
     * @param array $ids
     * @param string $workflowId
     * @param IWorkflowItemFactory $factory
     * @param Component|ActiveWorkflowBehavior $model
     * @return array
     */
	private function normalizeStatusIds($ids, $workflowId, $factory, $model)
	{
		$normalizedIds = [];
		foreach ($ids as $id) {
			$pieces = $factory->parseWorkflowStatus($id, $workflowId, $model);
			$normalizedIds[] = $pieces[0]. ArrayWorkflowItemFactory::SEPARATOR_STATUS_NAME . $pieces[1];
		}
		return $normalizedIds;		
	}
} 