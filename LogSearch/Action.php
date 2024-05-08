<?php

namespace DC\Widgets\LogSearch;

use XF\Entity\User;
use XF\LogSearch\AbstractHandler;
use XF\Mvc\Entity\Entity;

class Action extends AbstractHandler
{
	protected $searchFields = [
		'username',
		'thread_title',
		'thread_username'
	];

	protected function getFinderName()
	{
		return 'DC\Widgets:Action';
	}

	protected function getDateField()
	{
		return 'action_date';
	}

	protected function getRouteName()
	{
		return 'logs/widget-action';
	}

	/**
	 * @param \DC\Widgets\Entity\Action $record
	 * @return array
	 */
	protected function getLabel(Entity $record)
	{
		return [
			$record->username,
			$record->thread_title,
			$record->thread_username,
			\XF::phrase($record->action . '_thread')
		];
	}

	/**
	 * @param \DC\Widgets\Entity\Action $record
	 * @return User|null
	 */
	protected function getLogUser(Entity $record)
	{
		return $record->User ?? null;
	}
}