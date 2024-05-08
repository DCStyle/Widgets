<?php

namespace DC\Widgets\Entity;

use XF\Mvc\Entity\Entity;
use XF\Mvc\Entity\Structure;

/**
 * COLUMNS
 * @property int $log_id
 * @property int $user_id
 * @property string $username
 * @property int $thread_id
 * @property string $thread_title
 * @property string $thread_username
 * @property string $action
 * @property int $action_date
 *
 * RELATIONS
 * @property \XF\Entity\User $User
 * @property \XF\Entity\Thread $Thread
 */

class Action extends Entity
{
	public static function getStructure(Structure $structure)
	{
		$structure->table = 'xf_dcWidgets_widget_action_log';
		$structure->primaryKey = 'log_id';
		$structure->contentType = 'widget_action';
		$structure->shortName = 'DC\Widgets:Action';
		$structure->columns = [
			'log_id' => ['type' => self::UINT, 'nullable' => true, 'autoIncrement' => true],
			'user_id' => ['type' => self::UINT, 'required' => true],
			'username' => ['type' => self::STR, 'maxlength' => 50, 'required' => true],
			'thread_id' => ['type' => self::UINT, 'required' => true],
			'thread_title' => ['type' => self::STR, 'maxlength' => 250, 'required' => true],
			'thread_username' => ['type' => self::STR, 'maxlength' => 50, 'required' => true],
			'action' => ['type' => self::STR,
				'allowedValues' => \XF::repository('DC\Widgets:Action')->getAllowedActions(),
				'default' => 'promote'
			],
			'action_date' => ['type' => self::UINT, 'default' => \XF::$time]
		];

		$structure->relations = [
			'User' => [
				'entity' => 'XF:User',
				'type' => self::TO_ONE,
				'conditions' => 'user_id',
				'primary' => true
			],
			'Thread' => [
				'entity' => 'XF:Thread',
				'type' => self::TO_ONE,
				'conditions' => 'thread_id',
				'primary' => true
			]
		];

		return $structure;
	}
}