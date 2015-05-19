<?php
namespace fproject\workflow\base;

use Yii;
use yii\base\InvalidConfigException;

/**
 * A Status object is a component of a workflow.
 *
 * @author Bui Sy Nguyen
 */
class Status extends WorkflowBaseObject implements StatusInterface
{
	/**
	 * @var string the status Id
	 */
	private $_id;
	/**
	 * @var string the status label
	 */
	private $_label = '';
	/**
	 * @var string the workflow Id
	 */
	private $_workflow_id;
	/**
	 * @var Transition[] list of all out-going transitions for this status
	 */
	private $_transitions = [];


	/**
	 * Status constructor.
	 *
	 * To create a Status you must provide following values
	 * in the $config array passed as argument:
	 *
	 * - **id** : the id for this status.
	 * - **workflowId ** : the id of the workflow this status belongs to.
	 *
	 * Following values are optional :
	 *
	 * - **label** : human readable name for this status.
	 *
	 * @param array $config
	 * @throws InvalidConfigException
	 */
	public function __construct($config = [])
	{
		if ( ! empty($config['workflowId'])) {
			$this->_workflow_id = $config['workflowId'];
			unset($config['workflowId']);
		} else {
			throw new InvalidConfigException('missing workflow id');
		}

		if ( ! empty($config['id'])) {
			$this->_id = $config['id'];
			unset($config['id']);
		} else {
			throw new InvalidConfigException('missing status id');
		}

		if ( ! empty($config['label'])) {
			$this->_label = $config['label'];
			unset($config['label']);
		}
		parent::__construct($config);
	}

    /**
     * Add an out-going transition to this status.
     *
     * @param Transition $transition
     * @throws WorkflowException
     */
	public function addTransition($transition)
	{
		if ( empty($transition) || ! $transition instanceof Transition) {
			throw new WorkflowException('"transition" must be an instance of Transition');
		}
		$this->_transitions[$transition->getEndStatus()->getId()] = $transition;
	}
	/**
	 * Returns the id of this status.
	 *
	 * Note that the status id returned must be unique inside the workflow it belongs to, but it
	 * doesn't have to be unique among all workflows (@see getName)
	 * @return string the id for this status
	 */
	public function getId()
	{
		return $this->_id;
	}
	/**
	 * Returns the label for this status.
	 *
	 * @return string the label for this status. .
	 */
	public function getLabel()
	{
		return $this->_label;
	}
	/**
	 * @return string the id of the workflow this status belongs to.
	 */
	public function getWorkflowId()
	{
		return $this->_workflow_id;
	}
	/**
	 * @return Transition[] the list of out-going transitions for this status. Note that an empty array can be returned if this
	 * status has no out-going transition (i.e. no other status can be reached).
	 */
	public function getTransitions()
	{
		return $this->_transitions;
	}
}
