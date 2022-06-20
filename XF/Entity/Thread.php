<?php

namespace DC\Widgets\XF\Entity;

use XF\Mvc\Entity\Structure;

class Thread extends XFCP_Thread
{
    public function canWidgetPromote()
    {
        $visitor = \XF::visitor();

        if ($visitor->hasPermission('forum', 'widgetPromote'))
        {
            return true;
        }

        return;
    }
    
    public static function getStructure(Structure $structure)
    {
        $parent = parent::getStructure($structure);

        $structure->columns['widgetPromoted'] = ['type' => self::UINT, 'default' => 0];
        $structure->columns['widgetPromoteDate'] = ['type' => self::UINT, 'default' => 0];

        return $parent;
    }
}