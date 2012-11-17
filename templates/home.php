<h2>Profiler Web interface</h2>
Please use top menu for navigation.

<br/>
<br/>
<br/>
<h4>Configuration</h4>

XHProf extension: <b><?=function_exists('xhprof_enable') ? 'YES' : 'NO'?></b></br>

currently maintaining application:

<a target="_blank" href="<?=App::cfg('maintaining_project_url');?>">
    <?=App::cfg('maintaining_project_url');?>
</a>
<br/>
project diectory: <b><?=is_dir(App::cfg('maintaining_project_web_dir')) ? 'OK' : 'ERROR'?></b>
