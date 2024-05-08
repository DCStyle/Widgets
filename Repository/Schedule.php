<?php

namespace DC\Widgets\Repository;

use DateTime;
use XF\Mvc\Entity\Repository;

class Schedule extends Repository
{
	/**
	 * @param \DC\Widgets\Entity\PromoteSchedule $schedule
	 * @return bool
	 */
	public function startSchedule($schedule)
	{
		if ($schedule->is_started)
		{
			return false;
		}

		/** @var \DC\Widgets\XF\Entity\Thread $thread */
		$thread = $schedule->Thread;
		if ($thread)
		{
			$thread->widgetPromoted = $schedule->target_widget_ids;
			$thread->widgetPromoteDate = \XF::$time;

			$thread->save();
		}

		$schedule->is_started = true;
		$schedule->save();

		return true;
	}

	/**
	 * @param \DC\Widgets\Entity\PromoteSchedule $schedule
	 * @return bool
	 */
	public function expireSchedule($schedule)
	{
		/** @var \DC\Widgets\XF\Entity\Thread $thread */
		$thread = $schedule->Thread;
		if ($thread)
		{
			$thread->widgetPromoted = [];
			$thread->widgetPromoteDate = 0;

			$thread->save();
		}

		$schedule->delete();

		return true;
	}

	/**
	 * @param int $threadId
	 * @param int $fromDate
	 * @param int $toDate
	 * @param array $widgetIds
	 * @return \DC\Widgets\Entity\PromoteSchedule|false
	 * Return a widget promote schedule if add / edit,
	 * otherwise return false if there is no changes
	 *
	 * @throws \XF\PrintableException
	 */
	public function insertWidgetPromoteSchedule($threadId, $fromDate, $toDate, $widgetIds)
	{
		$now = \XF::$time;

		// If 'fromDate' no larger than current date
		// consider the schedule to started immediately
		if ($fromDate < $now || $this->checkSameDate($now, $fromDate))
		{
			$fromDate = 0;
		}

		if (($toDate < $now || $this->checkSameDate($now, $toDate)))
		{
			throw new \Exception(\XF::phrase('the_minimum_expiry_date_for_widget_promote_schedule_is_x_days', ['days' => 1]));
		}

		if (!is_array($widgetIds))
		{
			$widgetIds = array($widgetIds);
		}

		// Check exists
		/** @var \DC\Widgets\Entity\PromoteSchedule $promoteSchedule */
		$promoteSchedule = $this->em->find('DC\Widgets:PromoteSchedule', $threadId);
		if (!$promoteSchedule)
		{
			/** @var \DC\Widgets\Entity\PromoteSchedule $promoteSchedule */
			$promoteSchedule = $this->em->create('DC\Widgets:PromoteSchedule');
		}

		if ($promoteSchedule->from_date !== $fromDate
			|| $promoteSchedule->to_date !== $toDate
			|| $promoteSchedule->target_widget_ids !== $widgetIds)
		{
			$promoteSchedule->bulkSet([
				'thread_id' => $threadId,
				'to_date' => $toDate
			]);

			if (!$promoteSchedule->is_started)
			{
				if (empty($widgetIds))
				{
					throw new \Exception(\XF::phrase('target_promote_widgets_is_empty'));
				}

				$promoteSchedule->bulkSet([
					'from_date' => $fromDate,
					'target_widget_ids' => $widgetIds
				]);
			}

			$promoteSchedule->save();

			// Start the schedule immediately if possible
			if ($fromDate == 0)
			{
				$this->startSchedule($promoteSchedule);
			}

			return $promoteSchedule;
		}

		return false;
	}

	protected function checkSameDate($firstTimeStamp, $secondTimeStamp)
	{
		$firstDate = new DateTime();
		$firstDate->setTimestamp($firstTimeStamp);
		$firstDateFormatted = $firstDate->format('Y-m-d');

		$secondDate = new DateTime();
		$secondDate->setTimestamp($secondTimeStamp);
		$secondDateFormatted = $secondDate->format('Y-m-d');

		return $firstDateFormatted == $secondDateFormatted;
	}
}