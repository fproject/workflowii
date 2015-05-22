<?php
namespace fproject\workflow\core;

interface IStatus extends IAbstractWorkflowItem
{
    /**
     * Returns the id of this status.
     *
     * Note that the status id returned must be unique inside the workflow it belongs to, but it
     * doesn't have to be unique among all workflows
     * @return string the id for this status
     * @see getLabel
     */
	public function getId();

	/**
	 * Returns the label for this status.
	 *
	 * @return string the label for this status. .
	 */
	public function getLabel();

	/**
	 * @return string the id of the workflow this status belongs to.
	 */
	public function getWorkflowId();

	/**
	 * @return Transition[] the list of out-going transitions for this status. Note that an empty array can be returned if this
	 * status has no out-going transition (i.e. no other status can be reached).
	 */
	public function getTransitions();
}
