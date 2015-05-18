<?php
namespace fproject\workflow\base;

use yii\base\Object;

/**
 * The interface for status ID converters.
 *
 * A status ID converter is dedicated to provide a conversion between status ID which are valid
 * for the simpleWorkflow behavior, and status ID that can be stored in the configured status column
 * in the underlying table.<br/>
 *
 * @see fproject\workflow\StatusIdConverter
 *
 */
interface IStatusIdConverter
{
	/**
	 * Converts the status ID passed as argument into a status ID compatible
	 * with the simpleWorkflow.
	 *
	 * @param mixed $id
	 */
	public function toSimpleWorkflow($statusId);

	/**
	 * Converts the status ID passed as argument into a value that is compatible
	 * with the owner model attribute configured to store the simpleWorkflow status ID.
	 *
	 * @param mixed $id
	 */
	public function toModelAttribute($statusId);
}
