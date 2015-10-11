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

use Yii;

/**
 * A transition is a link between a start and an end status.
 *
 * If status "A" has a transition to status "B", then it only means that it is possible to go from
 * status "A" to status "B".
 */
interface ITransition
{
	/**
	 * @return string the transition id
	 */
	public function getId();
	/**
	 * Returns the Status instance representing the destination status.
	 *
	 * @return Status the Status instance this transition ends
	 */
	public function getEndStatus();
	/**
	 * Returns the Status instance representing the starting point fo the transition.
	 *
	 * @return Status the Status instance this transition ends
	 */
	public function getStartStatus();

	/**
	 * Returns the label for this status.
	 *
	 * @return string the label for this transition.
	 */
	public function getLabel();
}
