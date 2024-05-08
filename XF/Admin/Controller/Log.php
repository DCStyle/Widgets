<?php

namespace DC\Widgets\XF\Admin\Controller;

class Log extends XFCP_Log
{
	public function actionWidgetAction()
	{
		$page = $this->filterPage();
		$perPage = 20;

		/** @var \DC\Widgets\Repository\Action $widgetActionRepo */
		$widgetActionRepo = $this->repository('DC\Widgets:Action');
		$widgetActionFinder = $widgetActionRepo->findActionsForList()
			->limitByPage($page, $perPage);

		$widgetActions = $widgetActionFinder->fetch();

		$viewParams = [
			'widgetActions' => $widgetActions,
			'page' => $page,
			'perPage' => $perPage,
			'total' => $widgetActionFinder->total()
		];
		return $this->view('XF:Log\WidgetActionLog\Listing', 'dcWidgets_widget_action_list', $viewParams);
	}
}