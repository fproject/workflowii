<?php
namespace fproject\workflow\source\php;

use fproject\workflow\base\WorkflowBehavior;
use Yii;
use yii\base\Object;
use yii\base\InvalidConfigException;
use yii\db\BaseActiveRecord;
use yii\helpers\Inflector;
use yii\helpers\VarDumper;
use fproject\workflow\base\Status;
use fproject\workflow\base\Transition;
use fproject\workflow\base\Workflow;
use fproject\workflow\base\WorkflowException;
use fproject\workflow\base\IWorkflowDefinitionProvider;
use fproject\workflow\source\IWorkflowSource;


/**
 * This class provides workflow items (Workflow, Status, Transitions) from
 * a PHP array workflow definition.
 */
class WorkflowPhpSource extends Object implements IWorkflowSource
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
	 * Name of the parser class to use by default
	 */
	const DEFAULT_PARSER_CLASS = '\fproject\workflow\source\php\PhpArrayParser';
	/**
	 * Name of the default parser component to use with the behavior. This value can be overwritten
	 * by the 'parser' configuration setting.
	 * Example : 
	 * 'workflowSource' => [
	 * 		'class' => 'fproject\workflow\source\php\WorkflowPhpSource',
	 * 		'parser' => 'myparser'
	 * ]
	 */	
	const DEFAULT_PARSER_NAME = 'workflowArrayParser';
	/**
	 * @var string namespace where workflow definition class are located
	 */
	public $namespace = 'app\models';
	/**
	 * @var Object reference to the parser to use with this WorkflowPhpSource
	 */
	private $_parser;
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
	 * Built-in types names used for class map configuration.
	 */
	const TYPE_STATUS = 'status';
	const TYPE_TRANSITION = 'transition';
	const TYPE_WORKFLOW = 'workflow';

	/**
	 * The class map is used to allow the use of alternate classes to implement built-in types. This way
	 * you can provide your own implementation for status, transition or workflow.
	 * The class map can be configured when this component is created but can't be modified afterwards.
	 *
	 * @var array
	 */
	private $_classMap = [
		self::TYPE_WORKFLOW   => 'fproject\workflow\base\Workflow',
		self::TYPE_STATUS     => 'fproject\workflow\base\Status',
		self::TYPE_TRANSITION => 'fproject\workflow\base\Transition'
	];

    /**
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

				// classmap validation

				foreach ([self::TYPE_STATUS, self::TYPE_TRANSITION, self::TYPE_WORKFLOW] as $type) {
					$className = $this->getClassMapByType($type);
					if (empty($className)) {
						throw new InvalidConfigException("Invalid class map value : missing class for type ".$type);
					}
				}
			} else {
				throw new InvalidConfigException("Invalid property type : 'classMap' must be a non-empty array");
			}
		}
		
		// create the parser component or get it by name from Yii::$app
		
		$parserName = isset($config['parser']) ? $config['parser'] : 'workflowArrayParser';
		if ($parserName == null ) {
			$this->_parser = null;
		} elseif (is_string($parserName)) {
			if (!Yii::$app->has($parserName)) {
				Yii::$app->set($parserName, ['class'=> self::DEFAULT_PARSER_CLASS]);
			}
			$this->_parser = Yii::$app->get($parserName);
			unset($config['parser']);
		} else {
			throw new InvalidConfigException("Invalid property type : 'parser' must be a a string or NULL");
		}		

		parent::__construct($config);
	}

    /**
     * Returns the status whose id is passed as argument.
     * If this status was never loaded before, it is loaded now and stored for later use (lazy loading).
     *
     * If a $model is provided, it must be a BaseActiveRecord instance with a WorkflowBehavior attached. This model
     * is used to complete the status ID if the one defined by the $id argument is not complete (e.g. 'draft' instead of 'post/draft').
     *
     * @param string $id ID of the status to get
     * @param null $defaultWorkflowId
     * @return Status the status instance
     *
     * @throws InvalidConfigException
     * @throws WorkflowException
     *
     * @see IWorkflowSource::getStatus()
     * @see WorkflowPhpSource::evaluateWorkflowId()
     * @see WorkflowPhpSource::parseStatusId()
     */
	public function getStatus($id, $defaultWorkflowId = null)
	{
		list($wId, $stId) = $this->parseStatusId($id, $defaultWorkflowId);
		
		$canonicalStId = $wId . self::SEPARATOR_STATUS_NAME . $stId;
		
		if (!array_key_exists($canonicalStId, $this->_s) ) {
			$wDef = $this->getWorkflowDefinition($wId);
			if ($wDef == null) {
				throw new WorkflowException('No workflow found with id ' . $wId);
			}
			if (!array_key_exists($canonicalStId, $wDef[self::KEY_NODES])) {
				throw new WorkflowException('No status found with id '. $canonicalStId);
			}
			$stDef = $wDef[self::KEY_NODES][$canonicalStId] != null ? $wDef[self::KEY_NODES][$canonicalStId] : [];
			unset($stDef[self::KEY_EDGES]);
			
			$stDef['class'] = $this->getClassMapByType(self::TYPE_STATUS);
			$stDef['workflowId'] = $wId;
			$stDef['id'] = $canonicalStId;
			$stDef['label'] = (isset($stDef['label']) ? $stDef['label'] : Inflector::camel2words($stId, true));
			
			$this->_s[$canonicalStId] = Yii::createObject($stDef);	
		}
		return $this->_s[$canonicalStId];
	}

    /**
     * Returns all out going transitions leaving the status whose id is passed as argument.
     * This method also create instances for the initial status and all statuses that can be
     * reached from it.
     *
     * @see fproject\workflow\source\IWorkflowSource::getTransitions()
     * @param mixed $statusId
     * @param null $defaultWorkflowId
     * @return Transition|Transition[]
     * @throws InvalidConfigException
     * @throws WorkflowException
     */
	public function getTransitions($statusId, $defaultWorkflowId = null)
	{
		list($wId, $lid) = $this->parseStatusId($statusId, $defaultWorkflowId);
		$statusId = $wId.self::SEPARATOR_STATUS_NAME.$lid;

		if (!array_key_exists($statusId, $this->_t) ) {

			$start = $this->getStatus($statusId);
			if ($start == null) {
				throw new WorkflowException('start status not found : id = '. $statusId);
			}

			$wDef = $this->getWorkflowDefinition($wId);

			$trDef = isset($wDef[self::KEY_NODES][$start->getId()][self::KEY_EDGES])
				? $wDef[self::KEY_NODES][$start->getId()][self::KEY_EDGES]
				: null;

			$transitions = [];
			if ($trDef != null) {
				
				foreach ($trDef as $endStId => $trCfg) {					
					$ids = $this->parseStatusId($endStId, $wId);
					$endId =  implode(self::SEPARATOR_STATUS_NAME, $ids);
					$end = $this->getStatus($endId);
					
					if ($end == null ) {
						throw new WorkflowException('end status not found : start(id='.$statusId.') end(id='.$endStId.')');
					} else {
						$trCfg['class'] = $this->getClassMapByType(self::TYPE_TRANSITION);
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
     * @param mixed $startId
     * @param mixed $endId
     * @param null $defaultWorkflowId
     * @return Transition|null
     * @throws WorkflowException
     * @see IWorkflowSource::getTransition()
     */
	public function getTransition($startId, $endId, $defaultWorkflowId = null)
	{
		$tr = $this->getTransitions($startId, $defaultWorkflowId);
		if (count($tr) > 0 ) {
			foreach ($tr as $aTransition) {
				if ($aTransition->getEndStatus()->getId() == $endId) {
					return $aTransition;
				}
			}
		}
		return null;
	}

    /**
     * Returns the Workflow instance whose id is passed as argument.
     *
     * @param mixed $id
     * @return Workflow|null The workflow instance or NULL if no workflow could be found
     * @throws InvalidConfigException
     * @throws WorkflowException
     *
     * @see IWorkflowSource::getWorkflow()
     *
     */
	public function getWorkflow($id)
	{
		if (!array_key_exists($id, $this->_w) ) {

			$workflow = null;
			$def =  $this->getWorkflowDefinition($id);

			if ($def != null ) {
				unset($def[self::KEY_NODES]);
				$def['id'] = $id;
				if (isset($def[Workflow::PARAM_INITIAL_STATUS_ID])) {
					$ids = $this->parseStatusId($def[Workflow::PARAM_INITIAL_STATUS_ID], $id);
					$def[Workflow::PARAM_INITIAL_STATUS_ID] = implode(self::SEPARATOR_STATUS_NAME, $ids);
				} else {
					throw new WorkflowException('failed to load Workflow '.$id.' : missing initial status id');
				}
				$def['class'] = $this->getClassMapByType(self::TYPE_WORKFLOW);
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
     * @param string $id
     *
     * @return mixed the definition could not be loaded
     *
     * @throws InvalidConfigException
     * @throws WorkflowException
     *
     */
	public function getWorkflowDefinition($id)
	{
		if (!$this->isValidWorkflowId($id)) {
			throw new WorkflowException('Invalid workflow Id : '.VarDumper::dumpAsString($id));
		}

		if (!isset($this->_workflowDef[$id]) ) {
			$wfClassName = $this->getClassName($id);
			try {
				$wfProvider = Yii::createObject(['class' => $wfClassName]);
			} catch (\ReflectionException $e) {
				throw new WorkflowException('Failed to load workflow definition : '.$e->getMessage());
			}
			if ($this->isWorkflowProvider($wfProvider)) {
				$this->_workflowDef[$id] = $this->parse($id, $wfProvider->getDefinition());
			} else {
				throw new WorkflowException('Invalid workflow provider class : '.$wfClassName);
			}
		}
		return $this->_workflowDef[$id];
	}

    /**
     * Returns the complete name for the Workflow Provider class used to retrieve the definition of workflow $workflowId.
     * The class name is built by appending the workflow id to the namespace parameter set for this source component.
     *
     * @param string $workflowId a workflow id
     * @return string the full qualified class name used to provide definition for the workflow
     * @throws WorkflowException
     */
	public function getClassName($workflowId)
	{
		if (! $this->isValidWorkflowId($workflowId)) {
			throw new WorkflowException('Not a valid workflow Id : '.$workflowId);
		}
		return $this->namespace . '\\' . $workflowId;
	}

    /**
     * Returns the class map array for this Workflow source instance.
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
	 * Returns TRUE if the $object is a workflow provider.
	 * An object is a workflow provider if it implements the IWorkflowDefinitionProvider interface.
	 *
	 * @param Object $object
	 * @return boolean
	 */
	public function isWorkflowProvider($object)
	{
        return $object instanceof IWorkflowDefinitionProvider;
	}

    /**
     * Parses the string $val assuming it is a status id and returns and array
     * containing the workflow ID and status local ID.
     *
     * If $val does not include the workflow ID part (i.e it is not in formated like "workflowID/statusID")
     * this method uses $model and $defaultWorkflowId to get it.
     *
     * @param string $val the status ID to parse. If it is not an absolute ID, $helper is used to get the
     * workflow ID.
     * @param BaseActiveRecord|string $helper model used as workflow ID provider if needed
     * @return string[] array containing the workflow ID in its first index, and the status Local ID
     * in the second
     * @throws WorkflowException
     *
     * @see WorkflowPhpSource::evaluateWorkflowId()
     */
	public function parseStatusId($val, $helper = null)
	{
		if (empty($val) || ! is_string($val)) {
			throw new WorkflowException('Not a valid status id : a non-empty string is expected  - status = '.VarDumper::dumpAsString($val));
		}
	
		$tokens = array_map('trim', explode(self::SEPARATOR_STATUS_NAME, $val));
		$tokenCount = count($tokens);
		if ($tokenCount == 1) {
			$tokens[1] = $tokens[0];
			$tokens[0] = null;
			if (!empty($helper)) {
				if (is_string($helper)){
					$tokens[0] = $helper;
				} elseif ($helper instanceof WorkflowBehavior && $helper->hasWorkflowStatus()) {
					$tokens[0] = $helper->getWorkflowStatus()->getWorkflowId();
				}
			}
			if ($tokens[0] === null ) {
				throw new WorkflowException('Not a valid status id format: failed to get workflow id - status = '.VarDumper::dumpAsString($val));
			}
		} elseif ($tokenCount != 2) {
			throw new WorkflowException('Not a valid status id format: '.VarDumper::dumpAsString($val));
		}
	
		if (!$this->isValidWorkflowId($tokens[0]) ) {
			throw new WorkflowException('Not a valid status id : incorrect workflow id format in '.VarDumper::dumpAsString($val));
		}elseif (!$this->isValidStatusLocalId($tokens[1]) ) {
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
	 * @see WorkflowPhpSource::parseStatusId()
	 */
	public function isValidStatusId($id)
	{
		try {
			$this->parseStatusId($id);
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
     * @see WorkflowBehavior::attach()
     * @param string $workflowId
     * @param array $definition
     * @param boolean $overwrite When set to TRUE, the operation will fail if a workflow definition
     * already exists for this ID. Otherwise the existing definition is overwritten.
     * @return bool TRUE if the workflow definition could be added, FALSE otherwise
     * @throws WorkflowException
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
			$this->_workflowDef[$workflowId] = $this->parse($workflowId, $definition);
			unset($this->_w[$workflowId]);
			return true;
		}
	}
	/**
	 * Returns the parser used by this source or NULL if no parser is used. In this case, it is assumed
	 * that all workflow definitions provided to this source as PHP array, are in the normalized form.
	 * 
	 * @return IArrayParser
	 */
	public function getWorkflowParser()
	{
		return $this->_parser;
	}
	/**
	 * Convert the $definition array in its normalized form used internally by this source.
	 * 
	 * @param string $workflowId
	 * @param array $definition
	 * @return array the workflow in its normalized format
	 */
	public function parse($workflowId, $definition) 
	{
		if($this->getWorkflowParser() != null) {
			return $this->getWorkflowParser()->parse($workflowId, $definition, $this);
		}
        else
        {
			return $definition;
		}
	}
	
	/**
	 * Validate the workflow definition passed as argument.
	 * The workflow definition array format is the one rused internally by this class, and that should
	 * have been provided by a parser. 
	 * 
	 * @param string $wId Id of the workflow to validate
	 * @param array $definition workflow definition
	 * @return array list of validation report
	 */
	public function validateWorkflowDefinition($wId,$definition)
	{
		$errors = [];
		$stat = [];
		$startStatusIds = array_keys($definition[self::KEY_NODES]);
		$stat['statusCount'] = count($startStatusIds);
		if(! in_array($definition['initialStatusId'], $startStatusIds) ) {
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
		
		$missingStatusIdSuspects = \array_diff($endStatusIds, $startStatusIds);
		if (count($missingStatusIdSuspects) != 0) {
			$missingStatusId = [];
			foreach ($missingStatusIdSuspects as $id) {
				list($thisWid, $thisSid) = $this->parseStatusId($id,$wId);
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

		$orphanStatusIds = \array_diff($startStatusIds, $endStatusIds);
		if(\in_array($definition['initialStatusId'], $orphanStatusIds)) {
			// initial status Id is not unreachable
			$orphanStatusIds = \array_diff($orphanStatusIds, [ $definition['initialStatusId'] ]);
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
     * @param mixed $workflowId
     * @return array
     * @throws WorkflowException
     * @see IWorkflowSource::getAllStatuses()
     */
	public function getAllStatuses($workflowId)
	{
		$wDef = $this->getWorkflowDefinition($workflowId);
		if ($wDef == null) {
			throw new WorkflowException('No workflow found with id ' . $workflowId);
		}	
		$allStatuses = [];		
		foreach($wDef[self::KEY_NODES] as $statusId => $statusDef){
			$allStatuses[$statusId] = $this->getStatus($statusId);
		}
		return $allStatuses;
	}
}
