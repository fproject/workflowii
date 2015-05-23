<?php

namespace fproject\workflow\core;

use fproject\workflow\events\WorkflowEvent;
use fproject\workflow\factories\IWorkflowFactory;
use Yii;
use yii\base\Behavior;
use yii\base\Model;
use yii\base\ModelEvent;
use yii\base\InvalidConfigException;
use yii\base\Exception;
use fproject\workflow\events\IEventSequence;
use fproject\workflow\helpers\WorkflowScenario;
use yii\db\BaseActiveRecord;

/**
 * ActiveWorkflowBehavior implements the behavior of db model evolving inside a simple workflow.
 *
 * To use ActiveWorkflowBehavior with the default parameters, simply attach it to the model class.
 * ~~~
 * use fproject\workflow\core\ActiveWorkflowBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *         'workflow' => [
 *             'class' => ActiveWorkflowBehavior::className()
 *         ],
 *     ];
 * }
 * ~~~
 *
 * You can customize the ActiveWorkflowBehavior with the following parameters :
 *
 * - statusAttribute : name of the attribute that is used by the owner model to hold the status value. The
 * default value is "status".
 * - workflow : identifier of the default workflow for the owner model. If no value is provided, the behavior
 * creates a default workflow identifier (see  ActiveWorkflowBehavior#getDefaultWorkflowId)
 * - factory : name of the workflow factory component that the behavior must use to read the workflow. By default
 * the Yii application component "workflowFactory" is used and if it is not already available it is created by the
 * behavior using the default workflow factory component class.
 *
 *
 * Below is an example behavior initialization :
 * ~~~
 * use fproject\workflow\core\ActiveWorkflowBehavior;
 *
 * public function behaviors()
 * {
 *     return [
 *         'workflow' => [
 *             'class' => ActiveWorkflowBehavior::className(),
 *             'statusAttribute' => 'col_status',
 *             'defaultWorkflowId' => 'MyWorkflow',
 *             'factory' => 'workflowArrayFactory',
 *             'statusConverter' => 'myStatusConverter',
 *             'eventSequence' => 'myCustomEventSequence',
 *         ],
 *     ];
 * }
 * ~~~
 * Please note that the model must be an instance of yii\base\Model.
 *
 * @property Model $owner
 * @property IStatus $workflowStatus
 */
class ActiveWorkflowBehavior extends Behavior
{
	const DEFAULT_FACTORY_CLASS = 'fproject\workflow\factories\assoc\WorkflowArrayFactory';
	const DEFAULT_EVENT_SEQUENCE_CLASS = 'fproject\workflow\events\BasicEventSequence';

	/**
	 * @var string name of the owner model attribute used to store the current status value. It is also possible
	 * to use a model property but in this case you must provide a suitable status accessor component that will handle
	 * status persistence.
	 */
	public $statusAttribute = 'status';

	/**
	 * @var string name of the workflow factory component to use with the behavior
	 */
	public $factoryName = 'workflowFactory';

	/**
	 * @var string name of an existing status Id converter component, to use with this behavior. When NULL, no status converter
	 * component will be used.
	 */
	public $statusConverter = null;

	/**
	 * @var string Name of the status accessor component used by this behavior to set/get status values. By default, no external
	 * accessor is used and the behavior directly access the the status attribute in the owner model.
	 */
	public $statusAccessor = null;

	/**
	 * @var string name of the event sequence provider component. If the component does not exist it is created
	 * by this behavior using the default event sequence class.
	 * Set this attribute to NULL if you are not going to use any Workflow Event.
	 */
	public $eventSequence = 'eventSequence';

	/**
	 * @var bool|string if TRUE, the model is automatically inserted into the default workflow. If 
	 * $autoInsert contains a string, it is assumed to be an initial status Id that will be used to set the 
	 * status. If FALSE (default) the status is not modified. 
	 */
	public $autoInsert = false;

	/**
	 * @var string Read only property that contains the id of the default workflow to use with
	 * this behavior.
	 */
	private $_defaultWorkflowId;

	/**
	 * @property IStatus|null Internal value of the owner model status. This is the real value of the owner model status. It is
	 * maintained internally, depending on the path the owner model is going through within a workflow.
	 * Use getWorkflowStatus() to get the actual Status instance.
	 */
	private $_status = null;

	/**
	 * @var IStatusIdConverter Instance of the status ID converter used by this behavior or NULL if no status conversion is done
	 */
	private $_statusConverter = null;

	/**
	 * @var IWorkflowFactory reference to the workflow factory component used by this behavior
	 */
	private $_factory;

	/**
	 * @var array workflow events that are fired after save
	 */
	private $_pendingEvents = [];

	/**
	 * @var IEventSequence|null the Event sequence component that provides events to this behavior. If NULL, no event will be
	 * fired by this behavior.
	 */
	private $_eventSequence = null;

	/**
	 * @var IStatusAccessor|null the status accessor component used by this behavior or NULL if no such component is used.
	 */
	private $_statusAccessor = null;

    /**
     * @param array $config
     * @throws InvalidConfigException
     */
	public function __construct($config = [])
	{
		if (array_key_exists('defaultWorkflowId', $config)) {
			if ( is_string($config['defaultWorkflowId'])) {
				$this->_defaultWorkflowId = $config['defaultWorkflowId'];
			} else {
				throw new InvalidConfigException("Invalid property Type : 'defaultWorkflowId' must be a string" );
			}
			unset($config['defaultWorkflowId']);
		}
		parent::__construct($config);
	}

	/**
	 * At initialization time, following actions are taken :
	 * - perform validation
	 * - get a reference to the workflow factory component. If it doesn't exist, it is created.
	 * - get a reference to the event model component or create it if needed
	 * - get a reference to the status converter component
	 * - get a reference to the status accessor component
	 *  
	 * @see \yii\base\Object::init()
	 */
	public function init()
	{
		parent::init();

		if (empty($this->statusAttribute)) {
			throw new InvalidConfigException('The "statusAttribute" configuration for the Behavior is required.');
		}

		// init source
		if (empty($this->factoryName)) {
			throw new InvalidConfigException('The "source" configuration for the Behavior can\'t be empty.');
		} elseif ( !Yii::$app->has($this->factoryName)) {
			Yii::$app->set($this->factoryName, ['class'=> self::DEFAULT_FACTORY_CLASS]);
		}
		$this->_factory = Yii::$app->get($this->factoryName);

		// init Event Sequence
		if ($this->eventSequence == null) {
			$this->_eventSequence = null;
		} elseif (is_string($this->eventSequence)) {
			if ( !Yii::$app->has($this->eventSequence)) {
				Yii::$app->set($this->eventSequence, ['class'=> self::DEFAULT_EVENT_SEQUENCE_CLASS]);
			}
			$this->_eventSequence = Yii::$app->get($this->eventSequence);
		} else {
			throw new InvalidConfigException('Invalid "eventSequence" value.');
		}

		// init status converter
		if (!empty($this->statusConverter) ) {
			$this->_statusConverter = Yii::$app->get($this->statusConverter);
		}
		// init status accessor
		if (!empty($this->statusAccessor) ) {
			$this->_statusAccessor = Yii::$app->get($this->statusAccessor);
		}
	}

    /**
     * Attaches the behavior to the model.
     *
     * This method is automatically called by Yii. The behavior can successfully be attached to the model if :
     *
     * - the model is an instance of yii\base\Model
     * - the model has a attribute to hold the status value. The name of this attribute can be configured  when the behavior
     * is created (see statusAttribute). By Default the status attribute name is 'status'. Note that a property name can also be used
     * but in this case you must provide a suitable Status Accessor component to handle status persistence.
     *
     * If previous requirements are met, the internal status value is initialized.
     * @param Model $owner
     * @throws InvalidConfigException
     * @throws WorkflowException
     * @see \yii\base\Behavior::attach()
     * @see \fproject\workflow\core\ActiveWorkflowBehavior::InitStatus()
     */
	public function attach($owner)
	{
		parent::attach($owner);
		if (!($this->owner instanceof Model)) {
			throw new InvalidConfigException('The attached model is not an instance of yii\base\Model ('.get_class($this->owner).')');
		}

        if (!$this->owner->hasProperty($this->statusAttribute)) {
            if($this->owner instanceof BaseActiveRecord && $this->owner->hasAttribute($this->statusAttribute) )
            {
            }
            else
            {
                throw new InvalidConfigException('Attribute or property not found for owner model : \''.$this->statusAttribute.'\'');
            }
        }

		$this->initStatus();
		if(!$this->hasWorkflowStatus()) {
			$this->doAutoInsert();
		}
	}

	/**
	 * (non-PHPDoc)
	 * @see \yii\base\Behavior::events()
	 */
	public function events()
	{
        if($this->owner instanceof BaseActiveRecord)
        {
            return [
                BaseActiveRecord::EVENT_AFTER_FIND 		=> 'initStatus',
                BaseActiveRecord::EVENT_BEFORE_INSERT 	=> 'beforeSaveStatus',
                BaseActiveRecord::EVENT_BEFORE_UPDATE 	=> 'beforeSaveStatus',
                BaseActiveRecord::EVENT_AFTER_UPDATE 	=> 'afterSaveStatus',
                BaseActiveRecord::EVENT_AFTER_INSERT 	=> 'afterSaveStatus',
            ];
        }
		return [];
	}

	// NOT USED YET
	private function doAutoInsert()
	{
		if ($this->autoInsert !== false) {
			$workflowId = $this->autoInsert === true ? $this->getDefaultWorkflowId() : $this->autoInsert;
			$workflow = $this->_factory->getWorkflow($workflowId, $this->owner);
			if ($workflow !== null) {
				$this->setStatusInternal(
					$this->_factory->getStatus($workflow->getInitialStatusId(), null, $this->owner)
				);
			} else {
				throw new WorkflowException("autoInsert failed - No workflow found for id : ".$workflowId);
			}
		}		
	}

	/**
	 * Initialize the internal status value based on the owner model status attribute.
	 *
	 * The <b>status</b> attribute belonging to the owner model is retrieved and if not
	 * empty, converted into the corresponding Status.
	 * This method does not trigger any event, it is only restoring the model into its workflow.
	 *
	 * @throws WorkflowException if the status attribute could not be converted into a Status object
	 */
	public function initStatus()
	{
		if ($this->_statusAccessor != null) {
			$oStatus = $this->_statusAccessor->readStatus($this->owner);
		} else {
			$oStatus = $this->getOwnerStatus();
		}

		if (!empty($oStatus) ) {
            $wfId = self::isAttachedTo($this->owner) ? $this->selectDefaultWorkflowId() : null;
			$status = $this->_factory->getStatus($oStatus, $wfId, $this->owner);
			if ($status === null) {
				throw new WorkflowException('Status not found : '.$oStatus);
			}
			$this->setStatusInternal($status);
		} else {
			$this->_status = null;
		}
	}

    /**
     * Puts the owner model into the workflow $workflowId or into its default workflow if no
     * $workflowId is provided.
     * If the owner model is already in a workflow, an exception is thrown. If this method ends
     * with no error, the owner model's status is the initial status of the selected workflow.
     *
     * @param string $workflowId the ID of the workflow the owner model must be inserted to.
     * @return bool
     * @throws WorkflowException
     */
	public function enterWorkflow($workflowId = null)
	{
		if ($this->hasWorkflowStatus() ) {
			throw new WorkflowException("Model already in a workflow");
		}
		$wId = ($workflowId === null ? $this->getDefaultWorkflowId() : $workflowId);
		$workflow = $this->_factory->getWorkflow($wId, $this->owner);
		if ($workflow !== null) {
			$initialStatusId = $workflow->getInitialStatusId();
			$result = $this->sendToStatusInternal($initialStatusId, false);
		} else {
			throw new WorkflowException("No workflow found for id : ".$wId);
		}
		return $result;
	}

	/**
	 * After the owner model has been saved, fire pending events.
	 *
	 * @param mixed $insert
	 * @return boolean
     *
	 */
	public function afterSaveStatus($insert)
	{
		$this->firePendingEvents();
	}

	/**
	 * Send owner model into status if needed.
	 *
	 * @see ActiveWorkflowBehavior::sendToStatusInternal()
	 * @param ModelEvent $event
	 */
	public function beforeSaveStatus($event)
	{
		$event->isValid = $this->sendToStatusInternal($this->getOwnerStatus(), true);
	}

    /**
     * Send the owner model into the status passed as argument.
     *
     * If the transition between the current status and $status can be performed,
     * the status attribute in the owner model is updated with the value of the new status, otherwise
     * it is not changed.
     * This method can be invoked directly but you should keep in mind that it does not handle status
     * persistance.
     *
     * @param IStatus|string $status the destination status to reach. If NULL, then the owner model
     * is going to leave its current workflow.
     * @return bool TRUE if the transition could be performed, FALSE otherwise
     */
	public function sendToStatus($status)
	{
		return $this->sendToStatusInternal($status, false);
	}

	/**
	 * Performs status change and event fire.
	 *
	 * This method is called when the status is changed during a save event, or directly through the sendToStatus method.
	 * Based on the current value of the owner model status attribute, and the behavior status, it checks if a transition
	 * is about to occur. If that's the case, this method fires all "before" events provided by the event sequence component
	 * and then updates status attributes values (both internal and at the owner model level).
	 * Finally it fires the "after" events, or if we are in a save operation, store them for as pending events that are fired
	 * on the *afterSave" event.
	 * Note that if an event handler attached to a "before" event sets the event instance as invalid, all remaining handlers
	 * are ingored and the method returns immediately.
	 *
	 * @param mixed $status
	 * @param boolean $onSave
	 * @return boolean
	 */
	private function sendToStatusInternal($status, $onSave)
	{
		$this->_pendingEvents = [];

		list($newStatus, , $events) = $this->createTransitionItems($status, false, true);

		if (!empty($events['before']) ) {
			foreach ($events['before'] as $eventBefore) {
				$this->owner->trigger($eventBefore->name, $eventBefore);
				if ($eventBefore->isValid === false) {
					return false;
				}
			}
		}

		$this->setStatusInternal($newStatus);

		if (!empty($events['after']) ) {
			if ($onSave ) {
				$this->_pendingEvents = $events['after'];
			} else {
				foreach ($events['after'] as $eventAfter) {
					$this->owner->trigger($eventAfter->name, $eventAfter);
				}
			}
		}

		if ($this->_statusAccessor != null) {
			$this->_statusAccessor->updateStatus($this->owner, $newStatus);
		}
		return true;
	}

	/**
	 * Creates and returns the list of events that will be fire when the owner model is sent from its current status to the one passed as argument.
	 *
	 * The event list returned by this method depends on the event sequence component that was configured for this behavior at construction time.
	 *
	 * @param string $status the target status
	 * @return WorkflowEvent[] The list of events
	 */
	public function getEventSequence($status)
	{
		list(,,$events) = $this->createTransitionItems($status, false, true);
		return $events;
	}

	/**
	 * Creates and returns the list of scenario names that will be used to validate the owner model when it is sent from its current
	 * status to the one passed as argument.
	 * @param string $status the target status
	 * @return string[] list of scenario names
	 */
	public function getScenarioSequence($status)
	{
		list(, $scenario) = $this->createTransitionItems($status, true, false);
		return $scenario;
	}

    /**
     * Creates and returns workflow event sequence or scenario for the pending transition.
     *
     * Being given the current status and the status value passed as argument
     * this method returns the event sequence that will occur in the workflow or an empty array if no event is found.<br/>
     * A "event sequence" is an array containing an ordered set of WorkflowEvents instances.
     * Possible events sequences are :
     *
     * <ul>
     *    <li>[enterWorkflow, enterStatus] : when the current Workflow status is null and $status contains the id of an initial status</li>
     *    <li>[leaveStatus,leaveWorkflow] : when the current Workflow status is not null, and $status is null</li>
     *    <li>[leaveStatus,changeStatus,enterStatus] : when a transition exists between the current workflow status and $status</li>
     * </ul>
     *
     * Note that if the current workflow status and $status are refering to the same status, then a <b>changeStatus event</b> is returned
     * <b>only if a reflexive transition exists</b> for this status, otherwise no event is returned.
     *
     * @param mixed | null $status a status Id or a IStatus instance considered as the target status to reach
     * @param $scenarioNames
     * @param $eventSequence
     * @return array
     * @throws Exception
     * @throws WorkflowException
     */
	public function createTransitionItems($status, $scenarioNames, $eventSequence)
	{
		$start = $this->getWorkflowStatus();
		$end = $status;

		$scenario = [];
		$events = [];
		$newStatus = null;

		if ($start === null && $end !== null) {

			// (potential) entering workflow -----------------------------------

			$end = $this->ensureStatusInstance($end, true);
			$workflow = $this->_factory->getWorkflow($end->getWorkflowId(), $this->owner);
			$initialStatusId = $workflow->getInitialStatusId();
			if ($end->getId() !== $initialStatusId) {
				throw new WorkflowException('Not an initial status : '.$end->getId().' ("'.$initialStatusId.'" expected)');
			}
			if ($scenarioNames) {
				$scenario = [
					WorkflowScenario::enterWorkflow($end->getWorkflowId()),
					WorkflowScenario::enterStatus($end->getId())
				];
			}
			if ($eventSequence && $this->_eventSequence !== null) {
                /** @var Object|ActiveWorkflowBehavior $this */
				$events = $this->_eventSequence->createEnterWorkflowSequence($end, $this);
			}
			$newStatus = $end;

		} elseif ($start !== null && $end == null) {

			// leaving workflow -------------------------------------------------

			if ($scenarioNames) {
				$scenario = [
					WorkflowScenario::leaveWorkflow($start->getWorkflowId()),
					WorkflowScenario::leaveStatus($start->getId())
				];
			}
			if ($eventSequence && $this->_eventSequence !== null) {
                /** @var Object|ActiveWorkflowBehavior $this */
				$events = $this->_eventSequence->createLeaveWorkflowSequence($start, $this);
			}
			$newStatus = $end;
		} elseif ($start !== null && $end !== null ) {

			// change status ---------------------------------------

			$end = $this->ensureStatusInstance($end, true);

			$transition = $this->_factory->getTransition($start->getId(), $end->getId(), $this->selectDefaultWorkflowId(), $this->owner);

			if ($transition === null && $start->getId() != $end->getId() ) {
				throw new WorkflowException('No transition found between status '.$start->getId().' and '.$end->getId());
			}
			if ($transition != null) {

				if ($scenarioNames) {
					$scenario = [
						WorkflowScenario::leaveStatus($start->getId()),
						WorkflowScenario::changeStatus($start->getId(), $end->getId()),
						WorkflowScenario::enterStatus($end->getId())
					];
				}
				if ($eventSequence && $this->_eventSequence !== null) {
                    /** @var Object|ActiveWorkflowBehavior $this */
					$events = $this->_eventSequence->createChangeStatusSequence($transition, $this);
				}
			}
			$newStatus = $end;
		}
		if (count($events) != 0 && (!isset($events['before']) || !isset($events['after']))) {
			throw new Exception('Invalid event sequence format : "before" and "after" keys are mandatory');
		}
		return [$newStatus, $scenario, $events];
	}

    /**
     * Returns all status that can be reached from the current status.
     *
     * The list of reachable statuses is returned as an array where keys are status ids and value is an associative
     * array that contains at least the status instance. By default, no validation is performed and no event is fired by this method, however you may use
     * $validate and $beforeEvents argument to enable them.
     *
     * When $validate is true, the model is validated for each scenario and for each possible transition.
     * When $beforeEvents is true, all "before" events are fired and if a handler is attached it is executed.
     *
     * Each entry of the returned array has the following structure :
     *
     * <pre>
     *
     *    [
     *        targetStatusId => [
     *            'status' => the status instance
     *        ],
     *        // the 'validation' key is present only if $validate is true
     *        'validation' => [
     *            0 => [
     *                'scenario' => scenario name
     *                'success' => true (validation success) | false (validation failure) | null (no validation for this scenario)
     *            ],
     *            1 => [ ... ]
     *        ],
     *        // the 'event' key is present only if $beforeEvent is TRUE
     *        'event' => [
     *            0 => [
     *                'name' => event name
     *                'success' => true (event handler success) | false (event handler failed : the event has been invalidated) | null (no event handler)
     *            ]
     *            1 => [...]
     *        ],
     *        // if $validate is true or if $beforeEvent is TRUE
     *        'isValid' => true   (being given the verifications that were done, the target status can be reached)
     *                    | false (being given the verifications that were done, the target status cannot be reached)
     *    ]
     *
     * </pre>
     *
     *
     * If the owner model is not currently in a workflow, this method returns the initial status of its default
     * workflow for the model.
     *
     * @param bool $validate
     * @param bool $beforeEvents
     * @return array
     * @throws WorkflowException
     */
	public function getNextStatuses($validate = false, $beforeEvents = false)
	{
		$nextStatus = [];
		if (!$this->hasWorkflowStatus() ) {
			$workflow = $this->_factory->getWorkflow($this->getDefaultWorkflowId(), $this->owner);
			if ($workflow === null) {
				throw new WorkflowException("Failed to load default workflow ID = ".$this->getDefaultWorkflowId());
			}
			$initialStatus =
                $this->_factory->getStatus($workflow->getInitialStatusId(), $this->selectDefaultWorkflowId(), $this->owner);
			$nextStatus[$initialStatus->getId()] = ['status' => $initialStatus];
		} else {
			$transitions = $this->_factory->getTransitions($this->getWorkflowStatus()->getId(), $this->selectDefaultWorkflowId(), $this->owner);
			foreach ($transitions as $transition) {
				$nextStatus[$transition->getEndStatus()->getId()] = [ 'status' => $transition->getEndStatus()];
			}
		}
		if (count($nextStatus)) {

			if ($beforeEvents ) {
				// fire before events
				foreach (array_keys($nextStatus) as $endStatusId) {
					$transitionIsValid = true;
					$eventSequence = $this->getEventSequence($endStatusId);
                    /** @var WorkflowEvent $beforeEvent */
                    foreach ($eventSequence['before'] as $beforeEvent) {
						$eventResult = [];
						$beforeEventName = $beforeEvent->name;
						$eventResult['name'] = $beforeEventName;

						if ($this->owner->hasEventHandlers($beforeEventName)) {
							$this->owner->trigger($beforeEventName, $beforeEvent);
							$eventResult['success'] = $beforeEvent->isValid;
							$eventResult['messages'] = $beforeEvent->getErrors();
							if ($beforeEvent->isValid === false ) {
								$transitionIsValid = false;
							}
						} else {
							$eventResult['success'] = null;
						}
						$nextStatus[$endStatusId]['event'][] = $eventResult;
					}
					$nextStatus[$endStatusId]['isValid'] = $transitionIsValid;
				}
			}

			if ($validate ) {
				// save scenario name and errors
				$saveScenario = $this->owner->getScenario();
				$saveErrors = $this->owner->getErrors();

				// validate
				$modelScenarios = array_keys($this->owner->scenarios());
				foreach (array_keys($nextStatus) as $endStatusId) {
					$transitionIsValid = true;
					$scenarioSequence = $this->getScenarioSequence($endStatusId);
					foreach ($scenarioSequence as $scenario) {
						$validationResult = [];

						// perform validation only if $scenario is registered for the owner model

						if (in_array($scenario, $modelScenarios)) {
							$this->owner->clearErrors();
							$this->owner->setScenario($scenario);

							$validationResult['scenario'] = $scenario;
							if ($this->owner->validate() == true ) {
								$validationResult['success'] = true;
							} else {
								$validationResult['success'] = false;
								$validationResult['errors'] = $this->owner->getErrors();
								$transitionIsValid = false;
							}

						} else {
							$validationResult['scenario'] = $scenario;
							$validationResult['success'] = null;
						}
						$nextStatus[$endStatusId]['validation'][] = $validationResult;
					}
					if (isset($nextStatus[$endStatusId]['isValid'])) {
						$nextStatus[$endStatusId]['isValid'] = $nextStatus[$endStatusId]['isValid'] && $transitionIsValid;
					} else {
						$nextStatus[$endStatusId]['isValid'] = $transitionIsValid;
					}
				}
				// restore scenario name and errors
				$this->owner->setScenario($saveScenario);
				$this->owner->clearErrors();
				foreach ($saveErrors as $attributeName => $errorMessage) {
					$this->owner->addError($attributeName, $errorMessage);
				}
			}
		}
		return $nextStatus;
	}

	/**
	 * Returns the id of the default workflow associated with the owner model.
	 *
	 * If no default workflow id has been configured, it is created by using the
	 * short-name of the owner model class (i.e. the class name without the namespace part),
	 * suffixed with 'Workflow'.
	 *
	 * For instance, class 'app\model\Post' has a default workflow id equals to 'PostWorkflow'.
	 *
	 * @return string id for the workflow the owner model is in.
	 */
	public function getDefaultWorkflowId()
	{
		if (empty($this->_defaultWorkflowId)) {
			$tokens = explode('\\', get_class($this->owner));
			$this->_defaultWorkflowId = end($tokens) . 'Workflow';
		}
		return $this->_defaultWorkflowId;
	}

	/**
	 * @return IWorkflowFactory the workflow factory component instance used by this behavior
	 */
	public function getWorkflowFactory()
	{
		return $this->_factory;
	}

	/**
	 * @return IStatusAccessor|null returns the Status accessor component used by this behavior
	 * or NULL if no status accessor is used.
	 */
	public function getStatusAccessor()
	{
		return $this->_statusAccessor;
	}

	/**
	 * @return IStatus the value of the status.
	 */
	public function getWorkflowStatus()
	{
		return $this->_status;
	}

	/**
	 * @return Workflow | null the workflow the owner model is currently in, or null if the owner
	 * model is not in a workflow
	 */
	public function getWorkflow()
	{
		return $this->hasWorkflowStatus() ? $this->getWorkflowFactory()->getWorkflow($this->getWorkflowStatus()->getWorkflowId(), $this->owner) : null;
	}

	/**
	 * The owner model is considered as being in a workflow if its current Status is not null.
	 *
	 * @return boolean TRUE if the owner model is in a workflow, FALSE otherwise
	 */
	public function hasWorkflowStatus()
	{
		return $this->getWorkflowStatus() !== null;
	}
	
	/**
	 * This helper method test if the current status is equal to the status passed as argument.
	 * TRUE is returned when :
	 * 
	 * - $status is empty and the owner model has no current status
	 * - $status is not empty and refers to the same statusas the current one 
	 * 
	 * All other condition return FALSE.
	 * Example : 
	 * <pre>
	 * 		$post->statusEquals('draft');
	 * 		$post->statusEquals($otherPost->getWorkflowStatus());
	 * </pre>
	 * 
	 * @param IStatus|string $status the status to test
	 * @return boolean
	 */
	public function statusEquals($status=null)
	{
		if(!empty($status)) {
			try {
				$oStatus = $this->ensureStatusInstance($status);
			}catch(Exception $e) {
				return false;
			}
		} else {
			$oStatus = null;
		}
		
		if ($oStatus == null) {
			return !$this->hasWorkflowStatus();
		} elseif($this->hasWorkflowStatus()) {
			return $this->getWorkflowStatus()->getId() == $oStatus->getId();
		} else {
			return false;
		}
	}

	/**
	 * Returns a IStatus instance for the value passed as argument.
	 *
	 * If $idOrInstance is a IStatus instance, it is returned the instance itself without change,
     * otherwise $mixed is considered as a status id that is used to retrieve the corresponding status instance.
	 *
	 * @param mixed $idOrInstance status id or IStatus instance
	 * @param boolean $strict when TRUE and exception is thrown if no status instance can be returned.
	 * @throws WorkflowException
	 * @return IStatus the status instance or NULL if no IStatus instance could be found
	 */
	private function ensureStatusInstance($idOrInstance, $strict = false)
	{
		if (empty($idOrInstance)) {
			if ($strict ) {
				throw new WorkflowException('Invalid argument : null');
			} else {
				return null;
			}
		} elseif ($idOrInstance instanceof IStatus ) {
			return $idOrInstance;
		} else {
			$status = $this->_factory->getStatus($idOrInstance, $this->selectDefaultWorkflowId(), $this->owner);
			if ($status === null && $strict) {
				throw new WorkflowException('Status not found : '.$idOrInstance);
			}
			return $status;
		}
	}

	/**
	 * @return string the value of the status attribute in the owner model
	 */
	private function getOwnerStatus()
	{
		$ownerStatus = $this->owner->{$this->statusAttribute};

		if ($this->_statusConverter != null) {
			$ownerStatus = $this->_statusConverter->toWorkflow($ownerStatus);
		}
		return $ownerStatus;
	}

    /**
     * Set the internal status value and the owner model status attribute.
     *
     * @param IStatus|null $status
     * @throws WorkflowException
     */
	private function setStatusInternal($status)
	{
		if ($status !== null && !$status instanceof IStatus) {
			throw new WorkflowException('Status instance expected');
		}

		$this->_status = $status;

		$statusId = ($status === null ? null : $status->getId());
		if ($this->_statusConverter != null ) {
			$statusId = $this->_statusConverter->toModelAttribute($statusId);
		}

		$this->owner->{$this->statusAttribute} = $statusId;
	}

	/**
	 * Send pending events.
	 *
	 * When the status is changed during a save operation, all the "after" events must be sent after the owner model is actually saved.
	 * This method is invoked on events ActiveRecord::EVENT_AFTER_UPDATE and ActiveRecord::EVENT_AFTER_INSERT.
	 */
	private function firePendingEvents()
	{
		if (!empty($this->_pendingEvents)) {
			foreach ($this->_pendingEvents as $event) {
				$this->owner->trigger($event->name, $event);
			}
			$this->_pendingEvents = [];

			if ($this->_statusAccessor) {
				$this->_statusAccessor->commitStatus($this->owner);
			}
		}
	}

	/**
	 * Returns the default workflow ID to use with this model.
	 * The workflow ID returned is the current workflow ID (if the model is in a workflow)
	 * or the default workflow id as it has been configured.
	 * 
	 * @return string workflow Id
	 * @see \fproject\workflow\core\ActiveWorkflowBehavior::getDefaultWorkflowId()
	 */
	private function selectDefaultWorkflowId()
	{
		if($this->getWorkflowStatus() != null){
			return $this->getWorkflowStatus()->getWorkflowId();
		} else {
			return $this->getDefaultWorkflowId();
		}
	}

    /**
     * Tests that a ActiveWorkflowBehavior behavior is attached to the object passed as argument.
     *
     * This method returns FALSE if $model is not an instance of BaseActiveRecord (has ActiveWorkflowBehavior can only be attached
     * to instances of this class) or if none of its attached behaviors is a or inherit from ActiveWorkflowBehavior.
     *
     * @param Model|ActiveWorkflowBehavior $model the model to test.
     * @return bool TRUE if at least one ActiveWorkflowBehavior behavior is attached to $model, FALSE otherwise
     * @throws WorkflowException
     */
	public static function isAttachedTo($model)
	{
		if ($model instanceof BaseActiveRecord) {
			foreach ($model->getBehaviors() as $behavior) {
				if ($behavior instanceof ActiveWorkflowBehavior) {
					return true;
				}
			}
		} else {
			throw new WorkflowException('Invalid argument type : $model must be a BaseActiveRecord');
		}
		return false;
	}
}
