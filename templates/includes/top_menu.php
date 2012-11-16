<?

$menu = array(
    array(
        'title' => 'Collect Profiler Data',
        'link' => array(
            'controller' => 'collect',
            'action' => 'default',
        ),
        'controllers' => array(
            'collect',
        ),
    ),
    array(
        'title' => 'Analyze Collected Data',
        'link' => array(
            'controller' => 'analyze',
            'action' => 'default',
        ),
        'controllers' => array(
            'analyze',
        ),
    ),
    array(
        'title' => 'Settings',
        'link' => array(
            'controller' => 'settings',
            'action' => 'default',
        ),
        'controllers' => array(
            'settings',
        ),
    ),
);


?>

<div class="top_menu">


    <ul>

        <?foreach ($menu as $item) { ?>
        <li><a <?=in_array($this->app->route['controller'], $item['controllers'])
            ? 'class="active" '
            : ''?>href="<?=$this->link($item['link'])?>"><?=$item['title']?></a></li>
        <? }?>

    </ul>

    <?=$this->includeFragment('auth_label');?>
</div>
