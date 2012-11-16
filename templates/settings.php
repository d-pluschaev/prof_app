
Note: Please don't forget to turn OFF the agent before adjusting the options
<div class="settings_global" style="margin-bottom: 30px;width:100%" xmlns="http://www.w3.org/1999/html">
    <form action="<?=$this->link(
        array(
            'controller' => 'settings',
            'action' => 'save_settings',
        )
    )?>" method="post">

        <b>Application options</b><br/>

        <div class="container">
            <div>
                Maintaining application URL:
                <input class="field" type="text" name="maintaining_application_url"
                       value="<?=$this->globalSettings['maintaining_application_url']?>"/>
            </div>

            <div>
                Maintaining application web directory:
                <input class="field" type="text" name="maintaining_application_web_dir"
                       value="<?=$this->globalSettings['maintaining_application_web_dir']?>"/>
            </div>
        </div>

        <input type="submit" value="Save options"/>
    </form>
</div>

Note: You can not use any profiling functions when agent is OFF
<div class="ns_selector" style="margin-bottom: 30px;">
    <form action="<?=$this->link(
        array(
            'controller' => 'settings',
            'action' => 'toggle_agent',
        )
    )?>" method="post">

        Agent status: <b><?=$this->status['label_agent_status']?></b>

        <?if ($this->status['htaccess_changed']) { ?>
        <input type="submit" value="Turn OFF"/>
        <? } else { ?>
        <input type="submit" value="Turn ON"/>
        <? }?>
    </form>
</div>

Regular profiler writes results for all requests in selected directory (namespace).
Please don't collect a lot of results (100 and more) in one directory because it will prevent fast directory listing.
<div style="margin-bottom: 30px;">

    <?
    $this->ns_status_label = 'Regular profiler:';
    $this->ns_status = $this->status['label_regular_profiler_status'];
    $this->ns_namespace = $this->status['regular_profiler_namespace'];
    $this->ns_description = $this->status['regular_profiler_description'];
    $this->ns_link = array(
        'controller' => 'settings',
        'action' => 'toggle_regular_profiler',
    );
    ?>
    <?=$this->includeFragment('ns_selector')?>

</div>


In recording mode all requests on
<a target="_blank" href="<?=App::cfg('maintaining_project_url');?>">
    <?=App::cfg('maintaining_project_url');?>
</a>
will be collected in directory named "&lt;namespace&gt;"

<?
$this->ns_status_label = 'Recording mode:';
$this->ns_status = $this->status['label_recording_mode_status'];
$this->ns_namespace = $this->status['recording_namespace'];
$this->ns_description = $this->status['recording_description'];
$this->ns_link = array(
    'controller' => 'settings',
    'action' => 'toggle_recording_mode',
);
?>
<?= $this->includeFragment('ns_selector') ?>
