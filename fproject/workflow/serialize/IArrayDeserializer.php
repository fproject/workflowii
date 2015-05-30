<?php
namespace fproject\workflow\serialize;

use fproject\workflow\core\IWorkflowItemFactory;
use fproject\workflow\core\WorkflowException;

/**
 * This class converts a workflow definition PHP array into its normalized form
 * as required by the ArrayWorkflowItemFactory class.
 * 
 * The normalized form apply following rules :
 * - key 'initialStatusId' : (mandatory) must contain a status Id defined in the status Id list
 * - key 'status' : (mandatory) status definition list - its value is an array where each key is a status Id
 * and each value is the status configuration
 * - all status Ids are absolute
 * 
 * TBD
 * 
 * example : 
 * [
 *   'initialStatusId' => 'WID/A'
 *   'status' => [
 *       'WID/A' => [
 *           'transition' => [
 *               'WID/B' => []
 *               'WID/C' => []
 *           ]
 *       ]
 *       'WID/B' => null
 *       'WID/C' => null
 *   ]
 * ]
 * 
 */
interface IArrayDeserializer {
	/**
	 * Parse a workflow defined as a PHP Array.
	 *
	 * The workflow definition passed as argument is turned into an array that can be
	 * used by the ArrayWorkflowItemFactory components.
	 * 
	 * @param string $wId
	 * @param array $definition
	 * @param IWorkflowItemFactory $factory
	 * @return array The parse workflow array definition
	 * @throws WorkflowException
	 */
	public function deserialize($wId, $definition, $factory);
}
