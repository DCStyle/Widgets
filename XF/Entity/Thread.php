<?php

namespace DC\Widgets\XF\Entity;

use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property array $widgetPromoted
 * @property int $widgetPromoteDate
 *
 * RELATIONS
 * @property \DC\Widgets\Entity\PromoteSchedule $PromoteSchedule
 */

class Thread extends XFCP_Thread
{
	/**
	 * @return false|\XF\Mvc\Entity\AbstractCollection<\XF\Entity\Widget>
	 *     Return false if not permitted or no promotable widgets available
	 * else return promotable widget list
	 */
    public function canWidgetPromote(&$error = null)
    {
        $visitor = \XF::visitor();
        if ($this->user_id !== $visitor->user_id
			&& !$visitor->hasNodePermission($this->node_id, 'widgetPromoteAny'))
        {
            $error = \XF::phraseDeferred('you_may_not_perform_this_action_because_this_not_your_content');
			return false;
        }

		$promoteTimeLimit = $visitor->hasNodePermission($this->node_id, 'widgetPromoteTimeLimit');
		if ($promoteTimeLimit !== 0 && $promoteTimeLimit !== -1)
		{
			/** @var \DC\Widgets\Repository\Action $actionRepo */
			$actionRepo = $this->repository('DC\Widgets:Action');
			$lastPromoteAction = $actionRepo->getUserLastPromoteAction($visitor->user_id);
			if ($lastPromoteAction && \XF::$time < ($lastPromoteAction->action_date + $promoteTimeLimit * 60))
			{
				$error = \XF::phraseDeferred('you_must_wait_at_least_x_minutes_between_promote_actions', ['minutes' => $promoteTimeLimit]);
				return false;
			}
		}

		/** @var \DC\Widgets\Repository\Threads $threadsRepo */
        $threadsRepo = $this->repository('DC\Widgets:Threads');
		$promotableWidgets = $threadsRepo->findThreadPromotableWidgetList($visitor, $this);
		if (empty($promotableWidgets))
		{
			$error = \XF::phraseDeferred('you_may_not_perform_this_action_because_no_promotable_widgets_available');
			return false;
		}

		return $promotableWidgets;
    }

	public function canScheduleWidgetPromote()
	{
		return \XF::visitor()->hasNodePermission($this->node_id, 'widgetPromoteSchedule');
	}

	/**
	 * @return \XF\Mvc\Entity\AbstractCollection<\XF\Entity\Widget>|null
	 */
	public function getPromotedWidgets()
	{
		if (!$this->widgetPromoted || empty($this->widgetPromoted))
		{
			return null;
		}

		/** @var \DC\Widgets\Repository\Threads $threadsRepo */
		$threadsRepo = $this->repository('DC\Widgets:Threads');

		if (in_array('all', $this->widgetPromoted))
		{
			return $threadsRepo->findThreadPromotableWidgetList();
		} else {
			return $this->finder('XF:Widget')
				->whereIds($this->widgetPromoted)
				->fetch();
		}
	}

    public static function getStructure(Structure $structure)
    {
        $parent = parent::getStructure($structure);

		$structure->columns += [
			'widgetPromoted' => ['type' => self::LIST_COMMA, 'default' => [],
				'list' => ['type' => 'str', 'unique' => true, 'sort' => SORT_ASC],
				'changeLog' => false
			],
			'widgetPromoteDate' => ['type' => self::UINT, 'default' => \XF::$time]
		];

		$structure->relations += [
			'PromoteSchedule' => [
				'entity' => 'DC\Widgets:PromoteSchedule',
				'type' => self::TO_ONE,
				'conditions' => 'thread_id',
				'primary' => true
			]
		];

		$structure->getters += [
			'promoted_widgets' => ['getter' => 'getPromotedWidgets', 'cache' => true]
		];

        return $parent;
    }

	protected function _postDelete()
	{
		parent::_postDelete();

		// Delete related widget promote schedule
		$this->db()->delete(
			'xf_dcWidgets_widget_promote_schedule',
			'thread_id = ?',
			[$this->thread_id]
		);
	}
}