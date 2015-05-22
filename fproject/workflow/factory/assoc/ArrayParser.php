<?php 

namespace fproject\workflow\factory\assoc;

use Yii;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use fproject\workflow\core\WorkflowValidationException;
use yii\helpers\VarDumper;

class ArrayParser extends Object implements IArrayParser
{
	
	/**
	 * @var boolean when TRUE, the parse method will also perform some validations
	 */
	public $validate = true;

	/**
	 * Parse a workflow defined as a PHP Array.
	 *
	 * The workflow definition passed as argument is turned into an array that can be
	 * used by the WorkflowArrayFactory components.
	 * 
	 * @param string $wId
	 * @param array $definition
	 * @param WorkflowArrayFactory $source
	 * @return array The parse workflow array definition
	 * @throws WorkflowValidationException
	 */
	public function parse($wId, $definition, $source)
    {
	
		$result = [];
		if (!isset($definition['initialStatusId']))
        {
			throw new WorkflowValidationException('Missing "initialStatusId"');
		}
	
		list($workflowId, $statusId) = $source->parseStatusId($definition['initialStatusId'],$wId);
		$initialStatusId = $workflowId . WorkflowArrayFactory::SEPARATOR_STATUS_NAME .$statusId;
		if($workflowId != $wId)
        {
			throw new WorkflowValidationException('Initial status must belong to workflow : '.$initialStatusId);
		}
	
		if (!isset($definition[WorkflowArrayFactory::KEY_NODES]))
        {
			throw new WorkflowValidationException("No status definition found");
		}
        
		$result['initialStatusId'] = $initialStatusId;
	
		if (!is_array($definition[WorkflowArrayFactory::KEY_NODES]))
        {
			throw new WorkflowValidationException('Invalid Status definition : array expected');
		}
        
		$startStatusIdIndex = [];
		$endStatusIdIndex = [];
	
		foreach($definition[WorkflowArrayFactory::KEY_NODES] as $key => $value)
        {
            list($parsedId, $startStatusDef) = $this->parseStatusIdAndDef($key, $value);
	
			list($workflowId, $statusId) = $source->parseStatusId($parsedId, $wId);
			$startStatusId = $startStatusIdIndex[] = $workflowId . WorkflowArrayFactory::SEPARATOR_STATUS_NAME . $statusId;
			if($workflowId != $wId) {
				throw new WorkflowValidationException('Status must belong to workflow : '.$startStatusId);
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
					$result[WorkflowArrayFactory::KEY_NODES][$startStatusId] = null;
				}
                else
                {
					foreach ($startStatusDef as $startStatusKey => $startStatusValue)
                    {
						if ($startStatusKey === WorkflowArrayFactory::KEY_METADATA )
						{
							/**
							 * validate metadata
							 *
							 * 'A' => [
							 * 		'metadata' => [ 'key' => 'value']
							 * ]
							 */
								
							if (is_array($startStatusDef[WorkflowArrayFactory::KEY_METADATA]))
                            {
								if (!ArrayHelper::isAssociative($startStatusDef[WorkflowArrayFactory::KEY_METADATA]))
                                {
									throw new WorkflowValidationException("Invalid metadata definition for status $startStatusId : associative array expected");
								}
							}
                            else
                            {
								throw new WorkflowValidationException("Invalid metadata definition for status $startStatusId : array expected");
							}
							$result[WorkflowArrayFactory::KEY_NODES][$startStatusId][WorkflowArrayFactory::KEY_METADATA] = $startStatusDef[WorkflowArrayFactory::KEY_METADATA];
						}
						elseif ($startStatusKey === 'transition')
						{
							$transitionDefinition = $startStatusDef['transition'];
							if (is_string($transitionDefinition))
                            {
								/**
								 *  'A' => [
								 *   	'transition' => 'A, B, WID/C'
								 *   ]
								 */
								$ids = array_map('trim', explode(',', $transitionDefinition));
								foreach ($ids as $id) {
									$pieces = $source->parseStatusId($id,$wId);
									$canEndStId = implode(WorkflowArrayFactory::SEPARATOR_STATUS_NAME, $pieces);
									$endStatusIdIndex[] = $canEndStId;
									$result[WorkflowArrayFactory::KEY_NODES][$startStatusId]['transition'][$canEndStId] = [];
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
											throw new WorkflowValidationException("Wrong definition for between $startStatusId and $endStatusId : array expected");
										}
										$transDef = $tValue;
									} elseif (is_string($tValue)){
										/**
										 * 'transition' =>  'A' 
										 */
										$endStatusId = $tValue;
										$transDef = null;
									} else {
										throw new WorkflowValidationException("Wrong transition definition for status $startStatusId : key = "
												. VarDumper::dumpAsString($tKey). " value = ". VarDumper::dumpAsString($tValue));
									}
										
									$pieces = $source->parseStatusId($endStatusId,$wId);
									$canEndStId = implode(WorkflowArrayFactory::SEPARATOR_STATUS_NAME, $pieces);
									$endStatusIdIndex[] = $canEndStId;
										
									if ($transDef != null) {
										$result[WorkflowArrayFactory::KEY_NODES][$startStatusId]['transition'][$canEndStId] = $transDef;
									}else {
										$result[WorkflowArrayFactory::KEY_NODES][$startStatusId]['transition'][$canEndStId] = [];
									}
								}
							} else {
								throw new WorkflowValidationException("Invalid transition definition format for status $startStatusId : string or array expected");
							}
						}
						elseif (is_string($startStatusKey))
                        {
							$result[WorkflowArrayFactory::KEY_NODES][$startStatusId][$startStatusKey] = $startStatusValue;
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
				$result[WorkflowArrayFactory::KEY_NODES][$startStatusId] = null;
			}
		}
	
		// copy remaining workflow properties
		foreach($definition as $propName => $propValue) {
			if(is_string($propName)) {
				if($propName != 'initialStatusId' && $propName != WorkflowArrayFactory::KEY_NODES) {
					$result[$propName] = $propValue;
				}
			}
		}
		
		if ($this->validate === true) {
			if (!in_array($initialStatusId, $startStatusIdIndex)) {
				throw new WorkflowValidationException("Initial status not defined : $initialStatusId");
			}
		
			// detect not defined statuses
		
			$missingStatusIdSuspects = array_diff($endStatusIdIndex, $startStatusIdIndex);
			if (count($missingStatusIdSuspects) != 0) {
				$missingStatusId = [];
				foreach ($missingStatusIdSuspects as $id) {
					list($thisWid, ) = $source->parseStatusId($id,$wId);
					if ($thisWid == $wId) {
						$missingStatusId[] = $id; // refering to the same workflow, this Id is not defined
					}
				}
				if (count($missingStatusId) != 0) {
					throw new WorkflowValidationException("One or more end status are not defined : ".VarDumper::dumpAsString($missingStatusId));
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
     * @throws WorkflowValidationException
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
                throw new WorkflowValidationException("Wrong definition for status $statusId : array expected");
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
            throw new WorkflowValidationException("Wrong status definition : key = " . VarDumper::dumpAsString($key). " value = ". VarDumper::dumpAsString($value));
        }

        return [$statusId, $statusDef];
    }
}