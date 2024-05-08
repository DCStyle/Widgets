<?php

namespace DC\Widgets\Repository;

use XF\Mvc\Entity\Repository;

class Action extends Repository
{
	public function getAllowedActions()
	{
		return ['promote', 'edit_promote', 'unpromote', 'create_schedule', 'edit_schedule', 'delete_schedule'];
	}

	public function findActionsForList(): \DC\Widgets\Finder\Action
	{
		return $this->finder('DC\Widgets:Action')
			->setDefaultOrder('action_date', 'DESC');
	}

	public function findActionHistoryForThread($threadId): \DC\Widgets\Finder\Action
	{
		return $this->finder('DC\Widgets:Action')
			->where('thread_id', $threadId)
			->setDefaultOrder('action_date', 'DESC');
	}

	public function findActionHistoryByUser($userId): \DC\Widgets\Finder\Action
	{
		return $this->finder('DC\Widgets:Action')
			->where('user_id', $userId)
			->setDefaultOrder('action_date', 'DESC');
	}

	public function getUserLastPromoteAction($userId): \DC\Widgets\Entity\Action|null
	{
		$conditionsOr = [
			['action', '=', 'promote'],
			['action', '=', 'edit_promote'],
			['action', '=', 'create_schedule'],
			['action', '=', 'edit_schedule']
		];

		return $this->finder('DC\Widgets:Action')
			->where('user_id', $userId)
			->whereOr($conditionsOr)
			->order('action_date', 'DESC')
			->fetchOne();
	}

	/**
	 * @param \XF\Entity\User $user
	 * @param \XF\Entity\Thread $thread
	 * @param string $action Can be: promote | unpromote
	 *
	 * @return \DC\Widgets\Entity\Action
	 * @throws \Exception
	 */
	public function logAction($user, $thread, $action)
	{
		if (!in_array($action, $this->getAllowedActions()))
		{
			throw new \Exception('Invalid action (promote, unpromote)');
		}

		/** @var \DC\Widgets\Entity\Action $widgetAction */
		$widgetAction = $this->em->create('DC\Widgets:Action');

		$widgetAction->bulkSet([
			'user_id' => $user->user_id,
			'username' => $user->username,
			'thread_id' => $thread->thread_id,
			'thread_title' => $thread->title,
			'thread_username' => $thread->username,
			'action' => $action,
		]);

		$widgetAction->save();

		return $widgetAction;
	}
}