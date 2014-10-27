<?php

foreach (glob(CFG.DS."*.php") as $filename)
{
    require $filename;
}

foreach (glob(CFG.DS.$GLOBALS['conf']['environment'].DS."*.php") as $filename)
{
    require $filename;
}

$GLOBALS['Template'] = $template;

if(isset($hook)){
    $allHooks['pre_system'] = [];
    $allHooks['pre_controller'] = [];
    $allHooks['post_controller_constructor'] = [];
    $allHooks['post_controller'] = [];
    $allHooks['post_system'] = [];
    $allHooks['before_rendering'] = [];
    $GLOBALS['hooks'] = array_merge_recursive($hook,$allHooks);
}else{
    $GLOBALS['hooks']['pre_system'] = [];
    $GLOBALS['hooks']['pre_controller'] = [];
    $GLOBALS['hooks']['post_controller_constructor'] = [];
    $GLOBALS['hooks']['post_controller'] = [];
    $GLOBALS['hooks']['post_system'] = [];
    $GLOBALS['hooks']['before_rendering'] = [];
}

/*  EOF  */