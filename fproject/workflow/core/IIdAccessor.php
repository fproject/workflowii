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
interface IIdAccessor
{
	/**
	 * This method is invoked each time you want to read the Workflow ID stored in the model.
	 *
	 * @param Component $model
	 * @return string the Workflow Id
	 */
	public function readId($model);

    /**
     * This method is invoked each time you want to update the Workflow ID stored in the model.
     *
     * @param Component $model
     * @param String $wfId
     * @return mixed
     */
	public function updateId($model, $wfId = null);
}
