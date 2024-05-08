<?php

namespace DC\Widgets\Finder;

use XF\Mvc\Entity\Finder;

/**
 * @method \XF\Mvc\Entity\AbstractCollection<\DC\Widgets\Entity\Action> fetch($limit = null, $offset = null)
 * @method \XF\Mvc\Entity\AbstractCollection<\DC\Widgets\Entity\Action> fetchDeferred(?int $limit = null, ?int $offset = null)
 * @method \DC\Widgets\Entity\Action|null fetchOne($offset = null)
 * @extends \XF\Mvc\Entity\Finder<\DC\Widgets\Entity\Action>
 */
class Action extends Finder
{
	public function visibleThreadsOnly()
	{
		$this->where('Thread.discussion_state', 'visible');

		return $this;
	}

	public function recentPostedThreadsOnly($cutOff = null)
	{
		if ($cutOff === null)
		{
			$cutOff = \XF::$time - 86400 * 30;
		}

		$this->where('Thread.post_date', '>=', $cutOff);

		return $this;
	}

	public function latestRepliedThreadsOnly($cutOff = null)
	{
		if ($cutOff === null)
		{
			$cutOff = \XF::$time - 86400 * 30;
		}

		$this->where('reply_count', '>=', 1);
		$this->where('Thread.last_post_date', '>=', $cutOff);

		return $this;
	}

	public function recentOnly($cutOff = null)
	{
		if ($cutOff === null)
		{
			$cutOff = \XF::$time - 86400 * 30;
		}

		$this->where('action_date', '>=', $cutOff);

		return $this;
	}
}