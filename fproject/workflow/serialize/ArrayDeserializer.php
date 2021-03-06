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

use fproject\workflow\core\ArrayWorkflowItemFactory;
use Yii;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use fproject\workflow\core\WorkflowException;
use yii\helpers\VarDumper;

/**
 * @inheritdoc
 */
class ArrayDeserializer extends Object implements IArrayDeserializer
{

	/**
	 * @var boolean when TRUE, the parse method will also perform some validations
	 */
	public $validate = true;

	/**
	 * @inheritdoc
	 */
	public function deserialize($wId, $definition, $factory, $model)
    {
		$result = [];
		if (!isset($definition['initialStatusId']))
        {
			throw new WorkflowException('Missing "initialStatusId"');
		}

		list($workflowId, $statusId) = $factory->parseWorkflowStatus($definition['initialStatusId'], $wId, $model);

		$initialStatusId = $workflowId . ArrayWorkflowItemFactory::SEPARATOR_STATUS_NAME .$statusId;
        if(!isset($wId))
        {
            $wId = $workflowId;
        }
		elseif($workflowId != $wId)
        {
			throw new WorkflowException('Initial status must belong to workflow : '.$initialStatusId);
		}
	
		if (!isset($definition[ArrayWorkflowItemFactory::KEY_NODES]))
        {
			throw new WorkflowException("No status definition found");
		}
        
		$result['initialStatusId'] = $initialStatusId;
	
		if (!is_array($definition[ArrayWorkflowItemFactory::KEY_NODES]))
        {
			throw new WorkflowException('Invalid Status definition : array expected');
		}

		$startStatusIdIndex = [];
		$endStatusIdIndex = [];

        /** @var array $stsDefinitions */
        $stsDefinitions = $definition[ArrayWorkflowItemFactory::KEY_NODES];
	
		foreach($stsDefinitions as $key => $value)
        {
            list($parsedId, $startStatusDef) = $this->parseStatusIdAndDef($key, $value);
	
			list($workflowId, $statusId) = $factory->parseWorkflowStatus($parsedId, $wId, null);
			$startStatusId = $startStatusIdIndex[] = $workflowId . ArrayWorkflowItemFactory::SEPARATOR_STATUS_NAME . $statusId;
			if($workflowId != $wId) {
				throw new WorkflowException('Status must belong to workflow : '.$startStatusId);
			}

			if (is_array($startStatusDef))
            {
				if(count($startStatusDef) == 0)
                {
					/**
					 * empty status config array
					 *
					 * 'A' => []
					 */
					$result[ArrayWorkflowItemFactory::KEY_NODES][$startStatusId] = null;
				}
                else
                {
					foreach ($startStatusDef as $startStatusKey => $startStatusValue)
                    {
						if ($startStatusKey === ArrayWorkflowItemFactory::KEY_METADATA )
						{
							/**
							 * validate metadata
							 *
							 * 'A' => [
							 * 		'metadata' => [ 'key' => 'value']
							 * ]
							 */
								
							if (is_array($startStatusDef[ArrayWorkflowItemFactory::KEY_METADATA]))
                            {
								if (!ArrayHelper::isAssociative($startStatusDef[ArrayWorkflowItemFactory::KEY_METADATA]))
                                {
									throw new WorkflowException("Invalid metadata definition for status $startStatusId : associative array expected");
								}
							}
                            else
                            {
								throw new WorkflowException("Invalid metadata definition for status $startStatusId : array expected");
							}
							$result[ArrayWorkflowItemFactory::KEY_NODES][$startStatusId][ArrayWorkflowItemFactory::KEY_METADATA] = $startStatusDef[ArrayWorkflowItemFactory::KEY_METADATA];
						}
						elseif ($startStatusKey === ArrayWorkflowItemFactory::KEY_EDGES)
						{
							$transitionDefinition = $startStatusDef[ArrayWorkflowItemFactory::KEY_EDGES];
							if (is_string($transitionDefinition))
                            {
								/**
								 *  'A' => [
								 *   	'transition' => 'A, B, WID/C'
								 *   ]
								 */
								$ids = array_map('trim', explode(',', $transitionDefinition));
								foreach ($ids as $id) {
									$pieces = $factory->parseWorkflowStatus($id, $wId, $model);
									$canEndStId = implode(ArrayWorkflowItemFactory::SEPARATOR_STATUS_NAME, $pieces);
									$endStatusIdIndex[] = $canEndStId;
									$result[ArrayWorkflowItemFactory::KEY_NODES][$startStatusId][ArrayWorkflowItemFactory::KEY_EDGES][$canEndStId] = [];
								}
							}
                            elseif (is_array($transitionDefinition))
                            {
								/**
								 *  'transition' => [ ...]
								 */
								foreach($transitionDefinition as $tKey => $tValue)
                                {
									if (is_string($tKey)) {
										/**
										 * 'transition' => [ 'A' => [] ]
										 */
										$endStatusId = $tKey;
										if (!is_array($tValue)) {
											throw new WorkflowException("Wrong definition for between $startStatusId and $endStatusId : array expected");
										}
										$transDef = $tValue;
									} elseif (is_string($tValue)){
										/**
										 * 'transition' =>  'A' 
										 */
										$endStatusId = $tValue;
										$transDef = null;
									} else {
										throw new WorkflowException("Wrong transition definition for status $startStatusId : key = "
												. VarDumper::dumpAsString($tKey). " value = ". VarDumper::dumpAsString($tValue));
									}
										
									$pieces = $factory->parseWorkflowStatus($endStatusId, $wId, $model);
									$canEndStId = implode(ArrayWorkflowItemFactory::SEPARATOR_STATUS_NAME, $pieces);
									$endStatusIdIndex[] = $canEndStId;
										
									if ($transDef != null) {
										$result[ArrayWorkflowItemFactory::KEY_NODES][$startStatusId][ArrayWorkflowItemFactory::KEY_EDGES][$canEndStId] = $transDef;
									}else {
										$result[ArrayWorkflowItemFactory::KEY_NODES][$startStatusId][ArrayWorkflowItemFactory::KEY_EDGES][$canEndStId] = [];
									}
								}
							} else {
								throw new WorkflowException("Invalid transition definition format for status $startStatusId : string or array expected");
							}
						}
						elseif (is_string($startStatusKey))
                        {
							$result[ArrayWorkflowItemFactory::KEY_NODES][$startStatusId][$startStatusKey] = $startStatusValue;
						}
					}
				}
			}
            else
            { //$startStatusDef is not array
				/**
				 * Node IDS must be canonical and array keys
				 * 'status' => [
				 * 		'A'
				 * ]
				 *  turned into
				 *
				 * 'status' => [
				 * 		'WID/A' => null
				 * ]
	
				 */
				$result[ArrayWorkflowItemFactory::KEY_NODES][$startStatusId] = null;
			}
		}
	
		// copy remaining workflow properties
		foreach($definition as $propName => $propValue) {
            if($propName !== 'initialStatusId' && $propName !== ArrayWorkflowItemFactory::KEY_NODES) {
                $result[$propName] = $propValue;
            }
		}
		
		if ($this->validate === true) {
			if (!in_array($initialStatusId, $startStatusIdIndex)) {
				throw new WorkflowException("Initial status not defined : $initialStatusId");
			}
		
			// detect not defined statuses
		
			$missingStatusIdSuspects = array_diff($endStatusIdIndex, $startStatusIdIndex);
			if (count($missingStatusIdSuspects) != 0) {
				$missingStatusId = [];
				foreach ($missingStatusIdSuspects as $id) {
					list($thisWid, ) = $factory->parseWorkflowStatus($id, $wId, $model);
					if ($thisWid == $wId) {
						$missingStatusId[] = $id; // refering to the same workflow, this Id is not defined
					}
				}
				if (count($missingStatusId) != 0) {
					throw new WorkflowException("One or more end status are not defined : ".VarDumper::dumpAsString($missingStatusId));
				}
			}
		}
		return $result;
	}

    /**
     * Parse status's ID and definition
     *
     * @param $key
     * @param $value
     * @return array The parse status array definition
     * @throws WorkflowException
     */
    private function parseStatusIdAndDef($key, $value)
    {
        if (is_string($key) )
        {
            /**
             * 'status' => ['A' => ???]
             */
            $statusId = $key;
            if($value == null)
            {
                $statusDef = $statusId;	// 'status' => ['A' => null]
            }
            elseif (is_array($value))
            {
                $statusDef = $value;			// 'status' => ['A' => [ ...] ]
            }
            else
            {
                throw new WorkflowException("Wrong definition for status $statusId : array expected");
            }
        }
        elseif (is_string($value))
        {
            /**
             * 0 => 'A'
             */
            $statusId = $value;
            $statusDef = $statusId;
        }
        else
        {
            throw new WorkflowException("Wrong status definition : key = " . VarDumper::dumpAsString($key). " value = ". VarDumper::dumpAsString($value));
        }

        return [$statusId, $statusDef];
    }
}