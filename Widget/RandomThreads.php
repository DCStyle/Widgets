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

		$widgetThreadsRepo = $this->getWidgetThreadsRepo();

        $threadFinder = $widgetThreadsRepo->getThreadFinder('random', $limit);
		
        $title = \XF::phrase('latest_threads');
        $link = $titleLink ?: $router->buildLink('whats-new');

        if ($source == 'current' || $source == 'currentChild')
        {
            // If current page is thread view
            if (isset($this->contextParams['thread']))
            {
                $thread = $this->contextParams['thread'];
                $threadFinder->where('thread_id', '<>', $thread->thread_id);

                $nodeIds = $widgetThreadsRepo->getNodeIdsByThread($source, $thread);

                $threadFinder->where('node_id', $nodeIds);
            } 
            // If current page is forum view
            elseif (isset($this->contextParams['forum']))
            {
                $forum = $this->contextParams['forum'];
                
                $nodeIds = $widgetThreadsRepo->getNodeIdsByForum($source, $forum);

                $threadFinder->where('node_id', $nodeIds);
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
            // If current page is thread view
            if (isset($this->contextParams['thread']))
            {
                $thread = $this->contextParams['thread'];
                $user = $thread->User;

                $threadFinder->where('user_id', $user->user_id);
            }
            // If current page is member view
            elseif (isset($this->contextParams['user']))
            {
                $user = $this->contextParams['user'];

                $threadFinder->where('user_id', $user->user_id);
            }
        } 
        elseif ($threadUser == 'custom')
        {
            $threadFinder->where('username', $username);
        }

        $threadFinder->where('sticky', $sticky == 'yes' ? 1 : 0);

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
        
        $template = $widgetTypes == 'custom' ? $options['template'] : 'DC_Widgets_newThreads_widget';

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
            'username' => 'str',
            'threadTitleLimit' => 'uint'
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

    /**
     * @return \DC\Widgets\Repository\Threads
     */
    protected function getWidgetThreadsRepo()
    {
        return $this->repository('DC\Widgets:Threads');
    }
}