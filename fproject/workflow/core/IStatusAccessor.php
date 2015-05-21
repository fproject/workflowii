<?php
namespace fproject\workflow\core;

use yii\base\Component;
use yii\db\BaseActiveRecord;

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
