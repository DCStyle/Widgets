<?php

namespace DC\Widgets\Finder;

use XF\Mvc\Entity\Finder;

/**
 * @method \XF\Mvc\Entity\AbstractCollection<\DC\Widgets\Entity\PromoteSchedule> fetch($limit = null, $offset = null)
 * @method \XF\Mvc\Entity\AbstractCollection<\DC\Widgets\Entity\PromoteSchedule> fetchDeferred(?int $limit = null, ?int $offset = null)
 * @method \DC\Widgets\Entity\PromoteSchedule|null fetchOne($offset = null)
 * @extends \XF\Mvc\Entity\Finder<\DC\Widgets\Entity\PromoteSchedule>
 */
class PromoteSchedule extends Finder
{
	public function toStart()
	{
		return $this->where('is_started', 0)
			->where('from_date', '<=', \XF::$time)
			->setDefaultOrder('from_date', 'DESC');
	}

	public function toExpire()
	{
		return $this->where('to_date', '<=', \XF::$time)
			->setDefaultOrder('to_date', 'DESC');
	}
}