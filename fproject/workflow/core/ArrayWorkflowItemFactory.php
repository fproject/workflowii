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

use Codeception\Util\Debug;
use fproject\workflow\serialize\IArrayDeserializer;
use Yii;
use yii\base\Component;
use yii\base\Object;
use yii\base\InvalidConfigException;
use yii\helpers\Inflector;
use yii\helpers\VarDumper;

/**
 * This class provides workflow items (Workflow, Status, Transitions) from
 * a PHP associate array workflow definition.
 */
class ArrayWorkflowItemFactory extends Object implements IWorkflowItemFactory
{
	/**
	 *	The regular expression used to validate status and workflow Ids.
	 */
	const PATTERN_ID = '/^[a-zA-Z]+[[:alnum:]-]*$/';

	/**
	 * The separator used to create a status id by concatenating the workflow id and
	 * the status local id (e.g. post/draft).
	 */
	const SEPARATOR_STATUS_NAME = '/';

	/**
	 * Name of the array key for status list definition
	 */
	const KEY_NODES = 'status';

	/**
	 * Name of the key for transition list definition
	 */
	const KEY_EDGES = 'transition';

	/**
	 * Name of the key for metadata definition
	 */	
	const KEY_METADATA = 'metadata';

	/**
	 * Name of the deserializer class to use by default
	 */
	const DEFAULT_DESERIALIZER_CLASS = '\fproject\workflow\serialize\ArrayDeserializer';

	/**
	 * Name of the default deserializer component to use with the behavior. This value can be overwritten
	 * by the 'deserializer' configuration setting.
	 * Example : 
	 * 'workflowFactory' => [
	 * 		'class' => 'fproject\workflow\core\ArrayWorkflowItemFactory',
	 * 		'deserializer' => 'myDeserializer'
	 * ]
	 */	
	const DEFAULT_DESERIALIZER_NAME = 'arrayDeserializer';

    /**
     * Default value for `$workflowSourceNamespace` parameter of this component
     */
    const DEFAULT_WORKFLOW_SOURCE_NAMESPACE = 'app\models';

	/**
	 * @var string namespace where workflow source classes are located.
     *
     * You can config value of this field in Yii's application 'workflowFactory' component.
     *
     * If value of this parameter is not set, and the `$model` argument is passed to `getWorkflowSourceClassName()`
     * method, then the namespace of `$model` will be used to find workflow source classes.
     *
     * If both value of this parameter and the `$model` is null, the namespace defined in `DEFAULT_WORKFLOW_SOURCE_NAMESPACE`
     * will be used.
	 */
	public $workflowSourceNamespace = null;

    /**
     * @var string the suffix to determine workflow source class from a model class.
     *
     * You can config value of this field in Yii's application 'workflowFactory' component
     */
    public $workflowSourceSuffix = 'Source';

    /**
     * @var string the suffix to determine workflow source class from a model class.
     *
     * You can config value of this field in Yii's application 'workflowFactory' component
     */
    public $workflowSuffix = 'Workflow';

	/**
	 * @var Object reference to the deserializer to use with this ArrayWorkflowItemFactory
	 */
	private $_deserializer;

	/**
	 * @var array list of all workflow definition indexed by workflow id
	 */
	private $_workflowDef = [];

	/**
	 * @var Workflow[] list of workflow instances indexed by workflow id
	 */
	private $_w = [];

	/**
	 * @var Status[] list status instances indexed by their id
	 */
	private $_s = [];

	/**
	 * @var Transition[] list of out-going Transition instances indexed by the start status id
	 */
	private $_t = [];
	

	/**
	 * Built-in keys used for class map configuration.
	 */
	const CLASS_MAP_STATUS = 'status';
	const CLASS_MAP_TRANSITION = 'transition';
	const CLASS_MAP_WORKFLOW = 'workflow';

	/**
	 * The class map is used to allow the use of alternate classes to implement built-in types. This way
	 * you can provide your own implementation for status, transition or workflow.
	 * The class map can be configured when this component is created but can't be modified afterwards.
	 *
	 * @var array
	 */
	private $_classMap = [
		self::CLASS_MAP_WORKFLOW   => 'fproject\workflow\core\Workflow',
		self::CLASS_MAP_STATUS     => 'fproject\workflow\core\Status',
		self::CLASS_MAP_TRANSITION => 'fproject\workflow\core\Transition'
	];

    /**
     * Constructor.
     *
     * @param array $config
     * @throws InvalidConfigException
     */
	public function __construct($config = [])
	{
		if (array_key_exists('classMap', $config)) {
			if (is_array($config['classMap']) && count($config['classMap']) != 0) {
				$this->_classMap = array_merge($this->_classMap, $config['classMap']);
				unset($config['classMap']);

				// class-map validation

				foreach ([self::CLASS_MAP_STATUS, self::CLASS_MAP_TRANSITION, self::CLASS_MAP_WORKFLOW] as $type) {
					$className = $this->getClassMapByType($type);
					if (empty($className)) {
						throw new InvalidConfigException("Invalid class map value : missing class for type ".$type);
					}
				}
			} else {
				throw new InvalidConfigException("Invalid property type : 'classMap' must be a non-empty array");
			}
		}
		
		// create the deserializer component or get it by name from Yii::$app
		
		$deserializerName = isset($config['deserializer']) ? $config['deserializer'] : self::DEFAULT_DESERIALIZER_NAME;
		if ($deserializerName == null) {
			$this->_deserializer = null;
		} elseif (is_string($deserializerName)) {
			if (!Yii::$app->has($deserializerName)) {
				Yii::$app->set($deserializerName, ['class'=> self::DEFAULT_DESERIALIZER_CLASS]);
			}
			$this->_deserializer = Yii::$app->get($deserializerName);
			unset($config['deserializer']);
		} else {
			throw new InvalidConfigException("Invalid property type : 'deserializer' must be a a string or NULL");
		}		

		parent::__construct($config);
	}

    /**
     * @inheritdoc
     */
	public function getStatus($id, $wfId, $model)
	{
        $wDef = null;
		list($wId, $stId) = $this->parseIds($id, $wfId, $model, $wDef);

		$canonicalStId = $wId . self::SEPARATOR_STATUS_NAME . $stId;
		
		if (!array_key_exists($canonicalStId, $this->_s)) {
            if(!isset($wDef))
                $wDef = $this->getWorkflowDefinition($wId, $model);
			if ($wDef == null) {
				throw new WorkflowException('No workflow found with id ' . $wId);
			}
			if (!array_key_exists($canonicalStId, $wDef[self::KEY_NODES])) {
				throw new WorkflowException('No status found with id '. $canonicalStId);
			}
			$stDef = $wDef[self::KEY_NODES][$canonicalStId] != null ? $wDef[self::KEY_NODES][$canonicalStId] : [];
			unset($stDef[self::KEY_EDGES]);
			
			$stDef['class'] = $this->getClassMapByType(self::CLASS_MAP_STATUS);
			$stDef['workflowId'] = $wId;
			$stDef['id'] = $canonicalStId;
			$stDef['label'] = (isset($stDef['label']) ? $stDef['label'] : Inflector::camel2words($stId, true));
			
			$this->_s[$canonicalStId] = Yii::createObject($stDef);	
		}
		return $this->_s[$canonicalStId];
	}

    /**
     * @inheritdoc
     */
	public function getTransitions($statusId, $wfId, $model)
	{
        $wDef = null;
		list($wId, $lid) = $this->parseIds($statusId, $wfId, $model, $wDef);
		$statusId = $wId.self::SEPARATOR_STATUS_NAME.$lid;

		if (!array_key_exists($statusId, $this->_t)) {

			$start = $this->getStatus($statusId, null, null);
			if ($start == null) {
				throw new WorkflowException('start status not found : id = '. $statusId);
			}

            if(!isset($wDef))
                $wDef = $this->getWorkflowDefinition($wId, $model);

			$trDef = isset($wDef[self::KEY_NODES][$start->getId()][self::KEY_EDGES])
				? $wDef[self::KEY_NODES][$start->getId()][self::KEY_EDGES] : null;

			$transitions = [];
			if ($trDef != null) {
				
				foreach ($trDef as $endStId => $trCfg) {					
					$ids = $this->parseIds($endStId, $wId, null);
					$endId =  implode(self::SEPARATOR_STATUS_NAME, $ids);
					$end = $this->getStatus($endId, null, null);
					
					if ($end == null) {
						throw new WorkflowException('end status not found : start(id='.$statusId.') end(id='.$endStId.')');
					} else {
						$trCfg['class'] = $this->getClassMapByType(self::CLASS_MAP_TRANSITION);
						$trCfg['start'] = $start;
						$trCfg['end'  ] = $end;
						$transitions[] = Yii::createObject($trCfg);
					}					
				}
			}
			$this->_t[$statusId] = $transitions;
		}
		return $this->_t[$statusId];
	}

    /**
     * @inheritdoc
     */
	public function getTransition($startId, $endId, $wfId, $model)
	{
		$tr = $this->getTransitions($startId, $wfId, $model);
		if (count($tr) > 0) {
			foreach ($tr as $aTransition) {
				if ($aTransition->getEndStatus()->getId() == $endId) {
					return $aTransition;
				}
			}
		}
		return null;
	}

    /**
     * @inheritdoc
     *
     */
	public function getWorkflow($id, $model)
	{
		if (!array_key_exists($id, $this->_w)) {

			$workflow = null;
			$def =  $this->getWorkflowDefinition($id, $model);

			if ($def != null) {
				unset($def[self::KEY_NODES]);
				$def['id'] = $id;
				if (isset($def[Workflow::PARAM_INITIAL_STATUS_ID])) {
					$ids = $this->parseIds($def[Workflow::PARAM_INITIAL_STATUS_ID], $id, null);
					$def[Workflow::PARAM_INITIAL_STATUS_ID] = implode(self::SEPARATOR_STATUS_NAME, $ids);
				} else {
					throw new WorkflowException('failed to load Workflow '.$id.' : missing initial status id');
				}
				$def['class'] = $this->getClassMapByType(self::CLASS_MAP_WORKFLOW);
				$workflow = Yii::createObject($def);
			}
			$this->_w[$id] = $workflow;
		}
		return $this->_w[$id];
	}

    /**
     * Loads definition for the workflow whose id is passed as argument.
     *
     * The workflow Id passed as argument is used to create the class name of the object
     * that holds the workflow definition.
     *
     * @param string $wfId the ID of workflow to search
     * @param Component|ActiveWorkflowBehavior $model
     *
     * @return array the workflow definition array
     *
     * @throws InvalidConfigException
     * @throws WorkflowException
     *
     */
	public function getWorkflowDefinition($wfId, $model)
	{
        if(isset($model) && !isset($wfId))
        {
            //Do nothing
        }
		elseif (!$this->isValidWorkflowId($wfId)) {
			throw new WorkflowException('Invalid workflow Id : '.VarDumper::dumpAsString($wfId));
		}

		if (!isset($wfId) || !isset($this->_workflowDef[$wfId])) {
			$wfSrcClassName = $this->getWorkflowSourceClassName($wfId, $model);
			try {
                /** @var Object|IWorkflowSource $wfSrc */
				$wfSrc = Yii::createObject(['class' => $wfSrcClassName]);
			} catch (\ReflectionException $e) {
				throw new WorkflowException('Failed to load workflow definition : '.$e->getMessage());
			}
			if ($this->isWorkflowSource($wfSrc)) {
				$this->_workflowDef[$wfId] = $this->deserialize($wfId, $wfSrc->getDefinition($model));
			} else {
				throw new WorkflowException('Invalid workflow source class : '.$wfSrcClassName);
			}
		}
		return $this->_workflowDef[$wfId];
	}

    /**
     * @inheritdoc
     */
    public function getDefaultWorkflowId($model)
    {
        if(isset($model)) {
            $tokens = explode('\\', get_class($model));
            return end($tokens) . $this->workflowSuffix;
        }
        else
            return null;
    }

    /**
     * Returns the complete name for the IWorkflowSource class used to retrieve the definition of workflow $workflowId.
     * The class name is built by appending the workflow id to the `workflowSourceNamespace` parameter set for this factory component.
     *
     * @param string $workflowId a workflow id
     * @param Component|ActiveWorkflowBehavior $model the model that owns the workflow.
     *
     * @return string the full qualified class name implements IWorkflowSource used to provide definition for the workflow
     * @throws WorkflowException
     */
	public function getWorkflowSourceClassName($workflowId, $model)
	{
		if (isset($workflowId) && !$this->isValidWorkflowId($workflowId)) {
			throw new WorkflowException('Not a valid workflow Id : '.$workflowId);
		}

        if(!isset($workflowId)) {
            $workflowId = $this->getDefaultWorkflowId($model);
        }

        $ns = $this->workflowSourceNamespace;

        if(!isset($ns))
        {
            if(isset($model))
            {
                $ro = new \ReflectionObject($model);
                $ns = $ro->getNamespaceName();
            }
            if(!isset($ns))
                $ns = self::DEFAULT_WORKFLOW_SOURCE_NAMESPACE;
        }
		return $ns . '\\' . $workflowId . $this->workflowSourceSuffix;
	}

    /**
     * Returns the class map array for this Workflow factory instance.
     * @return string[]
     */
	public function getClassMap()
	{
		return $this->_classMap;
	}

	/**
	 * Returns the class name that implement the type passed as argument.
	 * There are 3 built-in types that must have a class name :
	 *
	 * - self::TYPE_WORKFLOW
	 * - self::TYPE_STATUS
	 * - self::TYPE_TRANSITION
	 *
	 * The constructor ensure that if a class map is provided, it include class names for these 3 types. Failure to do so
	 * will result in an exception being thrown by the constructor.
	 *
	 * @param string $type Type name
	 * @return string | null the class name or NULL if no class name is found forthis type.
	 */
	public function getClassMapByType($type)
	{
		return array_key_exists($type, $this->_classMap) ? $this->_classMap[$type] : null;
	}

	/**
	 * Returns TRUE if the $object is a workflow source.
	 * An object is a workflow source if it implements the IWorkflowSource interface.
	 *
	 * @param Object $object
	 * @return boolean
	 */
	public function isWorkflowSource($object)
	{
        return $object instanceof IWorkflowSource;
	}

    /**
     * @inheritdoc
     */
	public function parseIds($val, $wfId, $model, &$wfDef=null)
	{
		if (empty($val) || !is_string($val)) {
			throw new WorkflowException('Not a valid status id : a non-empty string is expected  - status = '.VarDumper::dumpAsString($val));
		}

		$tokens = array_map('trim', explode(self::SEPARATOR_STATUS_NAME, $val));
		$tokenCount = count($tokens);
		if ($tokenCount == 1) {
			$tokens[1] = $tokens[0];
			$tokens[0] = null;
            if (isset($wfId) && is_string($wfId)){
                $tokens[0] = $wfId;
            }
            elseif (isset($model) && ($model instanceof ActiveWorkflowBehavior || ActiveWorkflowBehavior::isAttachedTo($model)) && $model->hasWorkflowStatus()) {
                $tokens[0] = $model->getWorkflowStatus()->getWorkflowId();
            }
			if ($tokens[0] === null) {
				throw new WorkflowException('Not a valid status id format: failed to get workflow id / status = '.VarDumper::dumpAsString($val));
			}
		} elseif ($tokenCount != 2) {
			throw new WorkflowException('Not a valid status id format: '.VarDumper::dumpAsString($val));
		} elseif(!isset($wfDef) && isset($model) && ($model instanceof ActiveWorkflowBehavior || ActiveWorkflowBehavior::isAttachedTo($model)) && $model->hasWorkflowStatus()) {
            $wfDef = $this->getWorkflowDefinition($wfId, $model);
        }
	
		if (!$this->isValidWorkflowId($tokens[0])) {
			throw new WorkflowException('Not a valid status id : incorrect workflow id format in '.VarDumper::dumpAsString($val));
		} elseif (!$this->isValidStatusLocalId($tokens[1])) {
			throw new WorkflowException('Not a valid status id : incorrect status local id format in '.VarDumper::dumpAsString($val));
		}
		return $tokens;
	}	

	/**
	 * Checks if the string passed as argument can be used as a status ID.
	 *
	 * This method focuses on the status ID format and not on the fact that it actually refers
	 * to an existing status.
	 *
	 * @param string $id the status ID to test
	 * @return boolean TRUE if $id is a valid status ID, FALSE otherwise.
     * 
	 * @see ArrayWorkflowItemFactory::parseIds()
	 */
	public function isValidStatusId($id)
	{
		try {
			$this->parseIds($id, null, null);
			return true;
		} catch (WorkflowException $e) {
			return false;
		}
	}
	/**
	 * Checks if the string passed as argument can be used as a workflow ID.
	 *  
	 * A workflow ID is a string that matches self::PATTERN_ID.
	 *
	 * @param string $val
	 * @return boolean TRUE if the $val can be used as workflow id, FALSE otherwise
	 */
	public function isValidWorkflowId($val)
	{
		return is_string($val) && preg_match(self::PATTERN_ID, $val) != 0;
	}

	/**
	 * Checks if the string passed as argument can be used as a status local ID.
	 *
	 * @param string $val
	 * @return boolean
	 */
	public function isValidStatusLocalId($val)
	{
		return is_string($val) && preg_match(self::PATTERN_ID, $val) != 0;
	}

    /**
     * Add a workflow definition array to the collection of workflow definitions handled by this source.
     * This method can be use for instance, by a model that holds the definition of the workflow it is
     * using.<br/>
     * If a workflow with same id already exist in this source, it is overwritten if the last parameter
     * is set to TRUE. Note that in this case the overwritten workflow is not available anymore.
     *
     * @param string $workflowId
     * @param array $definition
     * @param boolean $overwrite When set to TRUE, the operation will fail if a workflow definition
     * already exists for this ID. Otherwise the existing definition is overwritten.
     * @return bool TRUE if the workflow definition could be added, FALSE otherwise
     * @throws WorkflowException
     *
     * @see ActiveWorkflowBehavior::attach()
     */
	public function addWorkflowDefinition($workflowId, $definition, $overwrite = false)
	{
		if (!$this->isValidWorkflowId($workflowId))
        {
			throw new WorkflowException('Not a valid workflow Id : '.$workflowId);
		}

		if ($overwrite == false && isset($this->_workflowDef[$workflowId]))
        {
			return false;
		}
        else
        {
			$this->_workflowDef[$workflowId] = $this->deserialize($workflowId, $definition);
			unset($this->_w[$workflowId]);
			return true;
		}
	}
    
	/**
	 * Returns the deserializer used by this factory or NULL if no deserializer is used. In this case, it is assumed
	 * that all workflow definitions provided to this factory as PHP array which are in the normalized form.
	 * 
	 * @return IArrayDeserializer
	 */
	public function getDeserializer()
	{
		return $this->_deserializer;
	}
    
	/**
	 * Convert the $definition array in its normalized form used internally by this source.
	 * 
	 * @param string $workflowId
	 * @param array $definition
	 * @return array the workflow in its normalized format
	 */
	public function deserialize($workflowId, $definition)
	{
		if($this->getDeserializer() != null) {
			return $this->getDeserializer()->deserialize($workflowId, $definition, $this);
		}
        else
        {
			return $definition;
		}
	}
	
	/**
	 * Validate the workflow definition passed as argument.
	 * The workflow definition array format is the one rused internally by this class, and that should
	 * have been provided by a deserializer.
	 * 
	 * @param string $wId Id of the workflow to validate
	 * @param array $definition workflow definition
	 * @return array list of validation report
	 */
	public function validateWorkflowDefinition($wId, $definition)
	{
		$errors = [];
		$stat = [];
		$startStatusIds = array_keys($definition[self::KEY_NODES]);
		$stat['statusCount'] = count($startStatusIds);
		if(!in_array($definition['initialStatusId'], $startStatusIds)) {
			$errors['missingInitialStatus'] = [
				'message' => 'Initial status not defined',
				'status' => $definition['initialStatusId']
			];
		}
		$endStatusIds = [];
		$finalStatusIds = [];
		$stat['transitionCount'] = 0;
		foreach($startStatusIds as $statusId) {
			if($definition[self::KEY_NODES][$statusId][self::KEY_EDGES] != null) {
				$stat['transitionCount'] += count($definition[self::KEY_NODES][$statusId][self::KEY_EDGES]);
				$endStatusIds = array_merge($endStatusIds, array_keys($definition[self::KEY_NODES][$statusId][self::KEY_EDGES]));
			} else {
				$finalStatusIds[] = $statusId;
			}
		}
		$stat['endStatusCount'] = count($endStatusIds);
		$stat['finalStatus'] = $finalStatusIds;
		
		$missingStatusIdSuspects = array_diff($endStatusIds, $startStatusIds);
		if (count($missingStatusIdSuspects) != 0) {
			$missingStatusId = [];
			foreach ($missingStatusIdSuspects as $id) {
				list($thisWid,) = $this->parseIds($id, $wId, null);
				if ($thisWid == $wId) {
					$missingStatusId[] = $id; // refering to the same workflow, this Id is not defined
				}
			}
			if (count($missingStatusId) != 0) {
				$errors['missingStatus'] = [
					'message' => 'One or more end status are not defined',
					'status' => $missingStatusId
				];
			}
		}	

		$orphanStatusIds = array_diff($startStatusIds, $endStatusIds);
		if(in_array($definition['initialStatusId'], $orphanStatusIds)) {
			// initial status Id is not unreachable
			$orphanStatusIds = array_diff($orphanStatusIds, [$definition['initialStatusId']]);
		}
		if(count($orphanStatusIds) != 0) {
			$errors['unreachableStatus'] = [
				'message' => 'One or more statuses are unreachable',
				'status' => $orphanStatusIds
			];			
		}
		
		return [ 
			'errors' => $errors,
			'stat' => $stat
		];
	}

    /**
     * @inheritdoc
     */
	public function getAllStatuses($workflowId, $model)
	{
		$wDef = $this->getWorkflowDefinition($workflowId, $model);
		if ($wDef == null) {
			throw new WorkflowException('No workflow found with id ' . $workflowId);
		}	
		$allStatuses = [];		
		foreach($wDef[self::KEY_NODES] as $statusId => $statusDef){
			$allStatuses[$statusId] = $this->getStatus($statusId, null, $model);
		}
		return $allStatuses;
	}
}
