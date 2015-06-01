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
