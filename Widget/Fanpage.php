<?php

/*
 * File: Fanpage.php
 * File Created: Saturday, 29th June 2019 12:31:36 am
 * Author: D.C Style (datdaik000@gmail.com)
 * Website: http://dcforo.com
 * Blog: https://facebook.com/DCStyleXF
 * -----
 * Copyright D.C Style 2019
 */

namespace DC\Widgets\Widget;

use \XF\Widget\AbstractWidget;

class Fanpage extends AbstractWidget
{
    protected $defaultOptions = [
        'fanpageUrl' => 'https://www.facebook.com/facebook',
        'tabs' => ['timeline', 'events', 'messages'],
        'width' => 310,
        'height' => 500,
        'smallHeader' => true,
        'hideCover' => true,
        'hide_cta' => true,
        'showFacepile' => false
    ];

    public function render()
	{
        $options = $this->options;
        $fanpageUrl = $options['fanpageUrl'];
        $tabs = $options['tabs'];
        $width = $options['width'];
        $height = $options['height'];
        $smallHeader = $options['smallHeader'];
        $hideCover = $options['hideCover'];
        $hideCta = $options['hideCta'];
        $showFacepile = $options['showFacepile'];

        $viewParams = [
            'fanpageUrl' => $fanpageUrl,
            'tabs' => $tabs,
            'width' => $width,
            'height' => $height,
            'smallHeader' => $smallHeader,
            'hideCover' => $hideCover,
            'hideCta' => $hideCta,
            'showFacepile' => $showFacepile
        ];

        return $this->renderer('DC_Widgets_fanpage_widget', $viewParams);
    }

    public function verifyOptions(\XF\Http\Request $request, array &$options, &$error = null)
	{
		$options = $request->filter([
            'fanpageUrl' => 'str',
            'tabs' => 'array-str',
            'width' => 'int',
            'height' => 'int',
            'smallHeader' => 'bool',
            'hideCover' => 'bool',
            'hide_cta' => 'bool',
            'showFacepile' => 'bool'
        ]);

        if ($options['fanpageUrl'] == '')
        {
            $options['fanpageUrl'] = 'https://www.facebook.com/facebook';
        }
        if ($options['width'] < 180)
        {
            $options['width'] = 180;
        }
        if ($options['height'] < 70)
        {
            $options['height'] = 70;
        }
    
        return true;
    }
}