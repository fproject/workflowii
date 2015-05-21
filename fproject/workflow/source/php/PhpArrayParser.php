<?php 

namespace fproject\workflow\source\php;

use Yii;
use yii\base\Object;
use yii\helpers\ArrayHelper;
use fproject\workflow\core\WorkflowValidationException;
use yii\helpers\VarDumper;

class PhpArrayParser extends Object implements IArrayParser
{
	
	/**
	 * @var boolean when TRUE, the parse method will also perform some validations
	 */
	public $validate = true;

	/**
	 * Parse a workflow defined as a PHP Array.
	 *
	 * The workflow definition passed as argument is turned into an array that can be
	 * used by the WorkflowPhpSource components. 
	 * 
	 * @param string $wId
	 * @param array $definition
	 * @param WorkflowPhpSource $source
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
		$initialStatusId = $workflowId . WorkflowPhpSource::SEPARATOR_STATUS_NAME .$statusId;
		if($workflowId != $wId)
        {
			throw new WorkflowValidationException('Initial status must belong to workflow : '.$initialStatusId);
		}
	
		if (!isset($definition[WorkflowPhpSource::KEY_NODES]))
        {
			throw new WorkflowValidationException("No status definition found");
		}
        
		$result['initialStatusId'] = $initialStatusId;
	
		if (!is_array($definition[WorkflowPhpSource::KEY_NODES]))
        {
			throw new WorkflowValidationException('Invalid Status definition : array expected');
		}
        
		$startStatusIdIndex = [];
		$endStatusIdIndex = [];
	
		foreach($definition[WorkflowPhpSource::KEY_NODES] as $key => $value)
        {
            $parsedIdDef = $this->parseStatusIdAndDef($key, $value);
	
			list($workflowId, $statusId) = $source->parseStatusId($parsedIdDef['id'],$wId);
			$startStatusId = $startStatusIdIndex[] = $workflowId . WorkflowPhpSource::SEPARATOR_STATUS_NAME . $statusId;
			if($workflowId != $wId) {
				throw new WorkflowValidationException('Status must belong to workflow : '.$startStatusId);
			}

            $startStatusDef = $parsedIdDef['def'];

			if (is_array($startStatusDef))
            {
				if(count($startStatusDef) == 0)
                {
					/**
					 * empty status config array
					 *
					 * 'A' => []
					 */
					$result[WorkflowPhpSource::KEY_NODES][$startStatusId] = null;
				}
                else
                {
					foreach ($startStatusDef as $startStatusKey => $startStatusValue)
                    {
						if ($startStatusKey === WorkflowPhpSource::KEY_METADATA )
						{
							/**
							 * validate metadata
							 *
							 * 'A' => [
							 * 		'metadata' => [ 'key' => 'value']
							 * ]
							 */
								
							if (is_array($startStatusDef[WorkflowPhpSource::KEY_METADATA]))
                            {
								if (!ArrayHelper::isAssociative($startStatusDef[WorkflowPhpSource::KEY_METADATA]))
                                {
									throw new WorkflowValidationException("Invalid metadata definition for status $startStatusId : associative array expected");
								}
							}
                            else
                            {
								throw new WorkflowValidationException("Invalid metadata definition for status $startStatusId : array expected");
							}
							$result[WorkflowPhpSource::KEY_NODES][$startStatusId][WorkflowPhpSource::KEY_METADATA] = $startStatusDef[WorkflowPhpSource::KEY_METADATA];
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
									$canEndStId = implode(WorkflowPhpSource::SEPARATOR_STATUS_NAME, $pieces);
									$endStatusIdIndex[] = $canEndStId;
									$result[WorkflowPhpSource::KEY_NODES][$startStatusId]['transition'][$canEndStId] = [];
								}
							}
                            elseif (is_array($transitionDefinition))
                            {
								/**
								 *  'transition' => [ ...]
								 */
								foreach($transitionDefinition as $tkey => $tvalue)
                                {
									if (is_string($tkey)) {
										/**
										 * 'transition' => [ 'A' => [] ]
										 */
										$endStatusId = $tkey;
										if (!is_array($tvalue)) {
											throw new WorkflowValidationException("Wrong definition for between $startStatusId and $endStatusId : array expected");
										}
										$transDef = $tvalue;
									} elseif (is_string($tvalue)){
										/**
										 * 'transition' =>  'A' 
										 */
										$endStatusId = $tvalue;
										$transDef = null;
									} else {
										throw new WorkflowValidationException("Wrong transition definition for status $startStatusId : key = "
												. VarDumper::dumpAsString($tkey). " value = ". VarDumper::dumpAsString($tvalue));
									}
										
									$pieces = $source->parseStatusId($endStatusId,$wId);
									$canEndStId = implode(WorkflowPhpSource::SEPARATOR_STATUS_NAME, $pieces);
									$endStatusIdIndex[] = $canEndStId;
										
									if ($transDef != null) {
										$result[WorkflowPhpSource::KEY_NODES][$startStatusId]['transition'][$canEndStId] = $transDef;
									}else {
										$result[WorkflowPhpSource::KEY_NODES][$startStatusId]['transition'][$canEndStId] = [];
									}
								}
							} else {
								throw new WorkflowValidationException("Invalid transition definition format for status $startStatusId : string or array expected");
							}
						}
						elseif (is_string($startStatusKey))
                        {
							$result[WorkflowPhpSource::KEY_NODES][$startStatusId][$startStatusKey] = $startStatusValue;
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
				$result[WorkflowPhpSource::KEY_NODES][$startStatusId] = null;
			}
		}
	
		// copy remaining workflow properties
		foreach($definition as $propName => $propValue) {
			if(is_string($propName)) {
				if($propName != 'initialStatusId' && $propName != WorkflowPhpSource::KEY_NODES) {
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
					list($thisWid, $thisSid) = $source->parseStatusId($id,$wId);
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

        return ['id'=>$statusId,'def'=>$statusDef];
    }
}