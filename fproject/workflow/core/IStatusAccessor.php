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

use yii\base\Component;

/**
 *
 *
 */
interface IStatusAccessor
{
	/**
	 * This method is invoked each time a status value must be read.
	 *
	 * @param Component $model
	 * @return string the status Id
	 */
	public function readStatus($model);

    /**
     * This method is invoked each time a status value must be updated.
     *
     * Updating a status value differs from actually saving the status in persistent storage (the database).
     *
     * @param Component $model
     * @param Status $status
     * @return mixed
     */
	public function updateStatus($model, $status = null);

	/**
	 * This method is invoked when the status needs to be saved.
	 * @param Component $model
	 */
	public function commitStatus($model);
}
