<?php

namespace DC\Widgets\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $thread_id
 * @property int $from_date
 * @property int $to_date
 * @property array $target_widget_ids
 * @property bool $is_started
 *
 * RELATIONS
 * @property \XF\Entity\Thread $Thread
 */

class PromoteSchedule extends Entity
{
	public function getTargetPromoteWidgets()
	{
		/** @var \DC\Widgets\Repository\Threads $threadsRepo */
		$threadsRepo = $this->repository('DC\Widgets:Threads');

		if (in_array('all', $this->target_widget_ids))
		{
			return $threadsRepo->findThreadPromotableWidgetList();
		} else {
			return $this->finder('XF:Widget')
				->whereIds($this->target_widget_ids)
				->fetch();
		}
	}

	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_dcWidgets_widget_promote_schedule';
		$structure->primaryKey = 'thread_id';
		$structure->shortName = 'DC\Widgets:PromoteSchedule';
		$structure->columns = [
			'thread_id' => ['type' => self::UINT, 'required' => true],
			'from_date' => ['type' => self::UINT, 'default' => 0],
			'to_date' => ['type' => self::UINT, 'required' => true],
			'target_widget_ids' => ['type' => self::LIST_COMMA, 'default' => [],
				'list' => ['type' => 'str', 'unique' => true, 'sort' => SORT_ASC],
				'changeLog' => false
			],
			'is_started' => ['type' => self::BOOL, 'default' => false]
		];

		$structure->relations = [
			'Thread' => [
				'entity' => 'XF:Thread',
				'type' => self::TO_ONE,
				'conditions' => 'thread_id',
				'primary' => true
			]
		];

		$structure->getters += [
			'target_promote_widgets' => ['getter' => 'getTargetPromoteWidgets', 'cache' => true]
		];

		return $structure;
	}

	protected function _postDelete()
	{
		// If this schedule already started, delete related thread promotion
		if ($this->is_started)
		{
			$thread = $this->Thread;

			$thread->widgetPromoted = [];
			$thread->widgetPromoteDate = 0;
			$thread->save();

			$this->repository('DC\Widgets:Action')->logAction(\XF::visitor(), $thread, 'unpromote');
		}
	}
}