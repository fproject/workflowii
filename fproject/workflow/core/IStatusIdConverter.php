<?php
namespace fproject\workflow\core;

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
     * with the Workflow.
     *
     * @param mixed $statusId
     * @return mixed
     */
	public function toWorkflow($statusId);

	/**
	 * Converts the status ID passed as argument into a value that is compatible
	 * with the owner model attribute configured to store the simpleWorkflow status ID.
	 *
	 * @param mixed $statusId
	 */
	public function toModelAttribute($statusId);
}
