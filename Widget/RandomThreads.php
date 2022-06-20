<?php

/*
 * File: RandomThreads.php
 * File Created: Monday, 1st July 2019 1:53:57 pm
 * Author: D.C Style (datdaik000@gmail.com)
 * Website: http://dcforo.com
 * Blog: https://facebook.com/DCStyleXF
 * -----
 * Copyright D.C Style 2019
 */

namespace DC\Widgets\Widget;

use \XF\Widget\AbstractWidget;

class RandomThreads extends AbstractWidget
{
    protected $defaultOptions = [
        'limit' => 5,
        'node_ids' => '',
        'source' => 'current',
        'threadTitleLimit' => 55,
        'threadUser' => 'all',
        'username' => '',
        'sticky' => 'none',
        'promoteOnly' => 'no',
        'timeLapse' => 'alltime',
        'customTime' => 1,
        'widgetTypes' => 'narrow1',
        'slideTypes' => 'slideNarrow',
        'template' => ''
    ];

    protected function getDefaultTemplateParams($context)
	{
		$params = parent::getDefaultTemplateParams($context);
		if ($context == 'options')
		{
			/** @var \XF\Repository\Node $nodeRepo */
            $nodeRepo = $this->app->repository('XF:Node');
			$params['nodeTree'] = $nodeRepo->createNodeTree($nodeRepo->getFullNodeList());
		}
		return $params;
    }

    public function render()
    {
        $visitor = \XF::visitor();

        $options = $this->options;
        $sticky = $options['sticky'];
        $promoteOnly = $options['promoteOnly'];
        $threadTitleLimit = $options['threadTitleLimit'];
        $limit = $options['limit'];
        $timeLapse = $options['timeLapse'];
        $customTime = $options['customTime'];
        $nodeIds = $options['node_ids'];
        $source = $options['source'];
        $threadUser = $options['threadUser'];
        $username = $options['username'];
        $widgetTypes = $options['widgetTypes'];
        $slideTypes = $options['slideTypes'];
        $template = $options['template'];
        $titleLink = $options['titleLink'];

        $router = $this->app->router('public');
        
        $threadFinder = \XF::finder('XF:Thread');

        $title = \XF::phrase('random_threads');

        if ($titleLink)
        {
            $link = $titleLink;
        } 
        else 
        {
            $link = $router->buildLink('whats-new');
        }

        /** @var \XF\Finder\Thread $threadFinder */
        $threadFinder
            ->with('Forum.Node.Permissions|' . $visitor->permission_combination_id)
            ->where('discussion_state', 'visible')
            ->where('discussion_type', '<>', 'redirect')
            ->limit(max($limit * 4, 20));

        if ($source == 'current' || $source == 'currentChild')
        {
            if (isset($this->contextParams['thread']))
            {
                $thread = $this->contextParams['thread'];
                $forum = $thread->Forum;
                $threadFinder->where('thread_id', '<>', $thread->thread_id);

                if ($source == 'currentChild')
                {
                    /** @var \XF\Repository\Node $nodeRepo */
                    $nodeRepo = $this->app->repository('XF:Node');
                    $children = $nodeRepo->findChildren($forum->Node)->fetch();
                    $childrenIds = [$forum->Node->node_id];
                    if($children->count())
                        foreach($children as $child)
                            $childrenIds[] = $child->node_id;
                    
                    $threadFinder->whereOr([["node_id", $childrenIds]]);
                } 
                else 
                {
                    $threadFinder->where('node_id', $forum->node_id);
                }
            } 
            elseif (isset($this->contextParams['forum']))
            {
                $forum = $this->contextParams['forum'];
                if ($source == 'currentChild')
                {
                    /** @var \XF\Repository\Node $nodeRepo */
                    $nodeRepo = $this->app->repository('XF:Node');
                    $children = $nodeRepo->findChildren($forum->Node)->fetch();
                    $childrenIds = [$forum->Node->node_id];
                    if($children->count())
                        foreach($children as $child)
                            $childrenIds[] = $child->node_id;
                    
                    $threadFinder->whereOr([["node_id", $childrenIds]]);
                } 
                else 
                {
                    $threadFinder->where('node_id', $forum->node_id);
                }
            }
        } 
        else 
        {
            if ($nodeIds && !in_array(0, $nodeIds))
            {
                $threadFinder->where('node_id', $nodeIds);
            }
        }

        if ($threadUser == 'current')
        {
            if (isset($this->contextParams['thread']))
            {
                $thread = $this->contextParams['thread'];
                $user = $thread->User;
                $threadFinder->where('user_id', $user->user_id);
            }
            else 
            {
                $user = $this->contextParams['user'];
                $memberId = $user->user_id;
                $threadFinder->where('user_id', $memberId);
            }
        } 
        elseif ($threadUser == 'custom')
        {
            $threadFinder->where('username', $username);
        }

        if ($sticky == 'yes')
        {
            $threadFinder->where('sticky', 1);
        } 
        elseif ($sticky == 'no')
        {
            $threadFinder->where('sticky', 0);
        }

        if ($promoteOnly == 'yes')
        {
            $threadFinder->where('widgetPromoted', 1);
        }

        if ($timeLapse == 'custom')
        {
            $date = new \DateTime();
            $now = $date->getTimestamp();
            $optionsDays = $customTime * 86400;
            $days = $now - $optionsDays;
            $threadFinder->where('post_date', '>=', $days);
        }

        $threadFinder
            ->with('fullForum')
            ->with('FirstPost')
            ->with('LastPoster')
            ->withReadData()
            ->order($threadFinder->expression('RAND()'));

		/** @var \XF\Entity\Thread $thread */
		foreach ($threads = $threadFinder->fetch() AS $threadId => $thread)
		{
			if (!$thread->canView()
				|| $visitor->isIgnoring($thread->user_id)
			)
			{
				unset($threads[$threadId]);
			}
        }
        $threads = $threads->slice(0, $limit, true);
        
        if ($widgetTypes == 'custom')
        {
            $template = $options['template'];
        } else {
            $template = 'DC_Widgets_newThreads_widget';
        }

		$viewParams = [
            'widgetTypes' => $widgetTypes,
            'slideTypes' => $slideTypes,
			'title' => $this->getTitle() ?: $title,
			'link' => $link,
            'threads' => $threads,
            'threadTitleLimit' => $threadTitleLimit
		];
		return $this->renderer($template, $viewParams);
    }

    public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
	{
		$options = $request->filter([
			'limit' => 'uint',
            'node_ids' => 'array-uint',
            'sticky' => 'str',
            'promoteOnly' => 'str',
            'timeLapse' => 'str',
            'customTime' => 'uint',
            'widgetTypes' => 'str',
            'slideTypes' => 'str',
            'template' => 'str',
            'titleLink' => 'str',
            'source' => 'str',
            'threadUser' => 'str',
            'username' => 'str'
		]);

		if (in_array(0, $options['node_ids']))
		{
			$options['node_ids'] = [0];
        }

		if ($options['limit'] < 1)
		{
			$options['limit'] = 1;
        }

        if ($options['threadTitleLimit'])
        {
            $options['threadTitleLimit'] = (int) $options['threadTitleLimit'];
        }
        else
        {
            $options['threadTitleLimit'] = 55;
        }

        if ($options['timeLapse'] != 'custom')
		{
			$options['customTime'] = 1;
        }
        
        if ($options['template'] == '' && $options['widgetTypes'] == 'custom')
		{
			$options['template'] = 'DC_Widgets_newThreads_default';
		}
		
		return true;
	}

    public function getReadMarkingCutOff()
	{
		return \XF::$time - \XF::options()->readMarkingDataLifetime * 86400;
	}
}