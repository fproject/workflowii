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

/**
 * The interface for status ID converters.
 *
 * A status ID converter is dedicated to provide a conversion between status ID which are valid
 * for the workflow behavior, and status ID that can be stored in the configured status column
 * in the underlying table.<br/>
 *
 * @see fproject\workflow\StatusIdConverter
 *
 */
interface IStatusIdConverter
{
    /**
     * Converts the status ID passed as argument into a status ID compatible
     * with the Workflow.
     *
     * @param mixed $statusId
     * @return mixed
     */
	public function toWorkflow($statusId);

	/**
	 * Converts the status ID passed as argument into a value that is compatible
	 * with the owner model attribute configured to store the workflow status ID.
	 *
	 * @param mixed $statusId
	 */
	public function toModelAttribute($statusId);
}
