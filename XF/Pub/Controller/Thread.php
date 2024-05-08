<?php

namespace DC\Widgets\XF\Pub\Controller;

use XF\Mvc\ParameterBag;

class Thread extends XFCP_Thread
{
    public function actionWidgetPromote(ParameterBag $params)
    {
        /** @var \DC\Widgets\XF\Entity\Thread $thread */
		$thread = $this->assertViewableThread($params->thread_id);
		if (!$thread->canWidgetPromote($error) && !$thread->canScheduleWidgetPromote())
        {
            return $this->noPermission($error);
        }

		if ($this->isPost())
		{
			/** @var \DC\Widgets\Repository\Action $actionRepo */
			$actionRepo = $this->repository('DC\Widgets:Action');

			$promoteTarget = $this->filter('promote_target', 'str');

			$targetWidgetIds = [];
			$noChanges = false;

			if ($promoteTarget == 'all')
			{
				$targetWidgetIds = ['all'];

				if ($thread->widgetPromoted && in_array('all', $thread->widgetPromoted)) // No changes
				{
					$noChanges = true;
				} else {
					$action = (!$thread->widgetPromoted || empty($thread->widgetPromoted)) ? 'promote' : 'edit_promote';
				}
			} elseif ($promoteTarget == 'specific')
			{
				$targetWidgetIds = $this->filter('specific_widget_ids', 'array-uint');
				if ($targetWidgetIds == $thread->widgetPromoted) // No changes
				{
					$noChanges = true;
				} else {
					if (!$thread->widgetPromoted || empty($thread->widgetPromoted))
					{
						$action = 'promote';
					} else
					{
						$action = 'edit_promote';
					}
				}
			} else {
				if (empty($thread->widgetPromoted) || !$thread->widgetPromoted) // No changes
				{
					$noChanges = true;
				} else {
					$action = 'unpromote';
				}
			}

			$promoteScheduleOptions = $this->filter('promote_schedule', 'array');
			if (isset($promoteScheduleOptions['enabled']) && $promoteScheduleOptions['enabled'])
			{
				// Create or edit thread widget promote schedule
				$isCreatingSchedule = false;

				// If create new schedule, then log action
				if (!$thread->PromoteSchedule)
				{
					$isCreatingSchedule = true;

					// Check for widget promote time limit
					if (!$thread->canWidgetPromote($error))
					{
						return $this->noPermission($error);
					}
				}

				/** @var \DC\Widgets\Repository\Schedule $scheduleRepo */
				$scheduleRepo = $this->repository('DC\Widgets:Schedule');
				try {
					$schedule = $scheduleRepo->insertWidgetPromoteSchedule(
						$thread->thread_id,
						$this->filter('promote_schedule.from_date', 'datetime'),
						$this->filter('promote_schedule.to_date', 'datetime'),
						$targetWidgetIds
					);

					if ($isCreatingSchedule)
					{
						$actionRepo->logAction(\XF::visitor(), $thread, 'create_schedule');
					} elseif ($schedule) {
						$actionRepo->logAction(\XF::visitor(), $thread, 'edit_schedule');
					}
				} catch (\Exception $e)
				{
					return $this->error($e->getMessage());
				}
			} else {
				// Thread promotion schedule already exist
				// deleting the current schedule
				if ($thread->PromoteSchedule)
				{
					$thread->PromoteSchedule->delete();
					$actionRepo->logAction(\XF::visitor(), $thread, 'delete_schedule');
				}
				// Normal promoting thread to widget, no schedule
				elseif (!$noChanges)
				{
					// Check for widget promote time limit
					if (!$thread->canWidgetPromote($error) && $action !== 'unpromote')
					{
						return $this->noPermission($error);
					}

					$thread->widgetPromoted = $targetWidgetIds;
					$thread->widgetPromoteDate = \XF::$time;
					$thread->save();

					$actionRepo->logAction(\XF::visitor(), $thread, $action);
				}
			}

			return $this->redirect($this->buildLink('threads', $thread), '');
		} else {
			$promoteSchedule = $thread->PromoteSchedule;
			if ($promoteSchedule)
			{
				if (in_array('all', $promoteSchedule->target_widget_ids))
				{
					$promoteTarget = 'all';
					$specificWidgetIds = [];
				} elseif (!empty($promoteSchedule->target_widget_ids))
				{
					$promoteTarget = 'specific';
					$specificWidgetIds = $promoteSchedule->target_widget_ids;
				} else {
					$promoteTarget = 'none';
					$specificWidgetIds = [];
				}
			} else {
				if ($thread->widgetPromoted && in_array('all', $thread->widgetPromoted))
				{
					$promoteTarget = 'all';
					$specificWidgetIds = [];
				} elseif (!empty($thread->widgetPromoted))
				{
					$promoteTarget = 'specific';
					$specificWidgetIds = $thread->widgetPromoted;
				} else {
					$promoteTarget = 'none';
					$specificWidgetIds = [];
				}
			}

			$viewParams = [
				'thread' => $thread,
				'promoteSchedule' => $promoteSchedule,
				'promoteTarget' => $promoteTarget,
				'specificWidgetIds' => $specificWidgetIds,
				'promotableWidgets'	=> $this->repository('DC\Widgets:Threads')->findThreadPromotableWidgetList(\XF::visitor(), $thread)
			];

			return $this->view('XF:Thread\WidgetPromote', 'dcWidgets_thread_widget_promote', $viewParams);
		}
    }
}