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

use yii\base\Object;
use yii\base\InvalidConfigException;
use yii\base\Exception;

/**
 * This class implements a status Id converter.
 *
 * The conversion is based on an array where key are valid status ID from the workflow
 * behavior point of view, and values are status ID suitable to be stored in the owner model.
 *
 * A typical usage for this converter is when the definition of the status column in the underlying table
 * is not able to store a string value and when modifying column type is not an option. If
 * for instance the status column type is integer, then the following example conversion table
 * could be used :
 *
 * <pre>
 * $map = [
 * 		'post/new' => 12,
 * 		'post/corrected' => 25,
 * 		'post/published' => 1,
 * 		'post/archived' => 6,
 *  	'null' => 'some value',
 *  	'workflow/Status' => 'null'
 * ]
 * </pre>
 *
 * Note that if the NULL value must be part of the conversion, you should use the VALUE_NULL
 * constant instead of the actual 'null' value.<br/>
 * For example in the conversion table below, the fact for the owner model to be outside a workflow,
 * would mean that the actual status column would be set to 25. In the same way, any model with a
 * status column equals to NULL, is considered as being in status 'post/toDelete' :
 *
 * <pre>
 * 	$map = [
 * 		StatusIdConverter::VALUE_NULL => 25,
 *      'post/toDelete' => StatusIdConverter::VALUE_NULL
 *  ];
 * </pre>
 *
 *
 * @see fproject\workflow\core\IStatusIdConverter
 */
class StatusIdConverter extends Object implements IStatusIdConverter
{
	const VALUE_NULL = 'null';
	private $_map = [];

	/**
	 * Construct an instance of the StatusIdConverter
	 *
	 * @param array $config
	 * @throws InvalidConfigException
	 */
	public function __construct($config = [])
	{
		if (!empty($config['map'])) {
			$this->_map = $config['map'];
			if (!is_array($this->_map)) {
				throw new InvalidConfigException('The map must be an array');
			}
			unset($config['map']);
		} else {
			throw new InvalidConfigException('missing map');
		}
		parent::__construct($config);
	}
	/**
	 * @return array the conversion map used by this converter
	 */
	public function getMap()
	{
		return $this->_map;
	}

    /**
     * @param mixed $id
     * @return mixed|null
     * @throws Exception
     * @see IStatusIdConverter::toWorkflow()
     */
	public function toWorkflow($id)
	{
		if ($id === null) {
			$id = self::VALUE_NULL;
		}
		$statusId = array_search($id, $this->_map);
		if ($statusId === false) {
			throw new Exception('Conversion to Workflow failed : no value found for id = '.$id);
		}
		return ($statusId == self::VALUE_NULL ? null : $statusId);
	}

    /**
     * @param mixed $id
     * @return null
     * @throws Exception
     * @see IStatusIdConverter::toModelAttribute()
     */
	public function toModelAttribute($id)
	{
		if ($id === null) {
			$id = self::VALUE_NULL;
		}

		if (!array_key_exists($id,	$this->_map) ) {
			throw new Exception('Conversion from Workflow failed : no key found for id = '.$id);
		}
		$value = $this->_map[$id];
		return ($value == self::VALUE_NULL ? null : $value);
	}
}
