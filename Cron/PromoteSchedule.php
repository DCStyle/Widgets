<?php

namespace DC\Widgets\Cron;

class PromoteSchedule
{
	public static function handleScheduledPromotions()
	{
		/** @var \DC\Widgets\Repository\Schedule $scheduleRepo */
		$scheduleRepo = \XF::repository('DC\Widgets:Schedule');

		/** @var \XF\Mvc\Entity\AbstractCollection<\DC\Widgets\Entity\PromoteSchedule> $scheduledPromotionsToStart */
		$scheduledPromotionsToStart = \XF::finder('DC\Widgets:PromoteSchedule')
			->toStart()
			->limit(10)
			->fetch();

		foreach($scheduledPromotionsToStart AS $schedule)
		{
			$scheduleRepo->startSchedule($schedule);
		}

		/** @var \XF\Mvc\Entity\AbstractCollection<\DC\Widgets\Entity\PromoteSchedule> $scheduledPromotionsToExpire */
		$scheduledPromotionsToExpire = \XF::finder('DC\Widgets:PromoteSchedule')
			->toExpire()
			->limit(10)
			->fetch();

		foreach($scheduledPromotionsToExpire AS $schedule)
		{
			$scheduleRepo->expireSchedule($schedule);
		}
	}
}