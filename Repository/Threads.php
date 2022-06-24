<?php

namespace DC\Widgets\Repository;

use XF\Mvc\Entity\Repository;

class Threads extends Repository
{
    /**
     * @param string $type
     * Can use latestThreads | latestReplies | latestUnread | latestWatched | random
     */
    public function getThreadFinder($type, $limit = 20)
    {
        /** @var \XF\Finder\Thread $threadFinder */
        $threadFinder = \XF::finder('XF:Thread');;

        if ($type == 'latestReplies')
        {
            $threadFinder
                ->where('last_post_date', '>', $this->getReadMarkingCutOff())
                ->where('reply_count', '>', 0)
                ->indexHint('FORCE', 'last_post_date');
        }

        if ($type == 'latestUnread')
        {
            $threadFinder
                ->where('last_post_date', '>', $this->getReadMarkingCutOff())
                ->where('reply_count', '>', 0)
                ->indexHint('FORCE', 'last_post_date');

            $userId = \XF::visitor()->user_id;

            if ($userId)
            {
                $threadFinder->unreadOnly($userId);
            }
        }

        if ($type == 'latestWatched')
        {
            /** @var \XF\Repository\Thread $threadRepo */
		    $threadRepo = $this->repository('XF:Thread');

            $threadFinder = $threadRepo->findThreadsForWatchedList();
        }

        if ($type == 'random')
        {
            $threadFinder->order($threadFinder->expression('RAND()'));
        }

        return $threadFinder
            ->with('Forum.Node.Permissions|' . \XF::visitor()->permission_combination_id)
            ->with(['Forum', 'User'])
            ->with('fullForum')
            ->with('FirstPost')
            ->with('LastPoster')
            ->withReadData()
            ->where('discussion_state', 'visible')
            ->where('discussion_type', '<>', 'redirect')
            ->limit(max($limit * 4, 20));
    }

    /**
     * @param string $source
     * Can use current | currentChild
     * @param \XF\Entity\Thread $thread
     * 
     * @return int[] Node IDs
     */
    public function getNodeIdsByThread($source, $thread)
    {
        $forum = $thread->Forum;

        if ($source == 'currentChild')
        {
            /** @var \XF\Repository\Node $nodeRepo */
            $nodeRepo = $this->app()->repository('XF:Node');

            $children = $nodeRepo->findChildren($forum->Node)->fetch();

            $childrenIds = [
                $forum->Node->node_id
            ];

            if($children->count())
            {
                $childrenIds += $children->keys();
            }

            return $childrenIds;
        }
        else
        {
            return [
                $forum->node_id
            ];
        }
    }

    /**
     * @param string $source
     * Can use current | currentChild
     * @param \XF\Entity\Forum $forum
     * 
     * @return int[] Node IDs
     */
    public function getNodeIdsByForum($source, $forum)
    {
        if ($source == 'currentChild')
        {
            /** @var \XF\Repository\Node $nodeRepo */
            $nodeRepo = $this->app->repository('XF:Node');

            $children = $nodeRepo->findChildren($forum->Node)->fetch();

            $childrenIds = [
                $forum->Node->node_id
            ];

            if ($children->count())
            {
                $childrenIds += $children->keys();
            }

            return $childrenIds;
        }
        else
        {
            return [
                $forum->node_id
            ];
        }
    }

    public function getReadMarkingCutOff()
	{
		return \XF::$time - \XF::options()->readMarkingDataLifetime * 86400;
	}
}