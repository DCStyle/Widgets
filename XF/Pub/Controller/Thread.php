<?php

namespace DC\Widgets\XF\Pub\Controller;

use XF\Mvc\ParameterBag;

class Thread extends XFCP_Thread
{
    public function actionWidgetPromote(ParameterBag $params)
    {
        $this->assertPostOnly();
        
        $thread = $this->assertViewableThread($params->thread_id);

        if (!$thread->canWidgetPromote())
        {
            return $this->noPermission();
        }

        if ($thread->widgetPromoted)
        {
            $thread->widgetPromoted = 0;
            $text = \XF::phrase('promote_thread');
        }
        else
        {
            $thread->widgetPromoted = 1;
            $text = \XF::phrase('unpromote_thread');
        }

        $thread->widgetPromoteDate = \XF::$time;

        $thread->save();

        $reply = $this->redirect($this->getDynamicRedirect());
		$reply->setJsonParams([
			'text' => $text,
			'widgetPromoted' => $thread->widgetPromoted
		]);
		return $reply;
    }
}