<?php

namespace DC\Widgets\XF\Finder;

class Thread extends XFCP_Thread
{
	/**
	 * @param string|array $widgetIds
	 */
	public function isPromotedToWidget($widgetIds)
	{
		if (!is_array($widgetIds))
		{
			$widgetIds = array($widgetIds);
		}

		$columnName = $this->columnSqlName('widgetPromoted');
		$parts = [];
		$parts[] = 'FIND_IN_SET(' . $this->quote('all') . ', ' . $columnName . ')';
		foreach ($widgetIds as $widgetId)
		{
			$parts[] = 'FIND_IN_SET(' . $this->quote($widgetId) . ', ' . $columnName . ')';
		}
		$this->whereSql(implode(' OR ', $parts));

		return $this;
	}

	public function isTagged($tags)
	{
		if (!is_array($tags))
		{
			$tags = array($tags);
		}

		$tagIds = [];
		foreach($tags AS $tag)
		{
			$tag = trim($tag);

			$tagFinder = $this->app()->finder('XF:Tag');
			$tagIds[] = $tagFinder
				->where('tag', 'LIKE', $tagFinder->escapeLike($tag))
				->pluckFrom('tag_id')
				->fetchOne();
		}

		if (empty($tagIds))
		{
			return $this->whereImpossible();
		}

		$taggedThreadIds = $this->app()->finder('XF:TagContent')
			->where('tag_id', $tagIds)
			->where('content_type', 'thread')
			->order('add_date', 'DESC')
			->pluckFrom('content_id')
			->fetch()
			->toArray();

		if (empty($taggedThreadIds))
		{
			return $this->whereImpossible();
		}

		return $this->whereIds($taggedThreadIds);
	}
}