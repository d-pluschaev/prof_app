<?php

$GLOBALS['XHPROF_LIB_ROOT'] = dirname(__FILE__) . '/xhprof_lib';
require_once($GLOBALS['XHPROF_LIB_ROOT'].'/display/xhprof.php');
require_once(dirname(__FILE__).'/config.php');


function hasSqlFile($file)
{
    return is_file($file.'.sql');
}

if(!isset($_GET['run']) && !isset($_GET['run1'])){

    echo "<html>";
    echo "<head><title>XHProf: Hierarchical Profiler Report</title>";
    xhprof_include_js_css();
    ?>
</head>
	<body>
	<h4 style="text-align:center">List of profiler files</h4>
<input id="b_cd" type="button" value="Check difference between 2 files" disabled="disabled" onclick="checkdiff()" />
<table id="tbl" style="border-top:1px solid #ccc">
    <thead>
    <th>&nbsp;</th>
    <th>#</th>
    <th>Profile</th>
    <th>Timestamp</th>
    <th>Namespace</th>
    <th>File Size</th>
    <th>Age</th>
    <th>SQL</th>
    </thead>
    <tbody>
        <?
        $files=array();
        foreach(glob($dir.'/*') as $index=>$file){
            $pi=pathinfo($file);
            if($pi['extension']!='sql'){
                $name_parts=explode('.',$pi['filename']);
                $shortname=array_shift($name_parts);
                $slq_queries=array_pop($name_parts);
                $timestamp=implode('.',$name_parts);
                $buf=array(
                    'stat'=>stat($file),
                    'pi'=>$pi,
                    'file'=>str_replace($dir.'/','',$file),
                    'path'=>$file,
                    'shortname'=>$shortname,
                    'timestamp'=>$timestamp,
                    'sql_queries'=>$slq_queries,
                );
                $files[]=$buf;
            }
        }
        function sortbyftime($a,$b)
        {
            return $a['stat']['mtime']<$b['stat']['mtime'];
        }
        usort($files,'sortbyftime');


        foreach($files as $index=>$file){
            ?>
        <tr>
            <td><input type="checkbox" onclick="setTimeout('checkbutton()',20)" name="<?=urlencode($file['file'])?>"></td>
            <td><?=($index+1)?>.</td>
            <td><a href="?run=<?=$file['pi']['filename']?>&source=<?=$file['pi']['extension']?>"><?=$file['shortname']?></a></td>
            <td><?=$file['timestamp']?></td>
            <td style="padding-left:20px;<?=strstr($file['pi']['extension'],'page_track_') ? 'color:orange' : ''?>"><?=$file['pi']['extension']?></td>
            <td style="padding-left:20px"><?=toBytes($file['stat']['size'])?></td>
            <td style="padding-left:20px"><?=toTimePcs(microtime(1)-$file['stat']['mtime'])?> ago</td>
            <td style="padding-left:20px"><?=(hasSqlFile($file['path']) ? '<span style="color:silver">has SQL: </span>'.$file['sql_queries'] : '&nbsp;')?></td>

            <?


            ?>
        </tr>
            <?
        }

        ?>
    </tbody>
</table>
<script type="text/javascript">
    function checkbutton()
    {
        var trs=document.getElementById('tbl').getElementsByTagName('TR');
        var cb,cnt=0,b_cd=document.getElementById('b_cd');
        for(var i=0;i<trs.length;i++){
            cb=trs[i].getElementsByTagName('input')[0];
            if(cb && cb.checked){
                cnt++;
            }
        }
        if(cnt<2){
            b_cd.disabled=true;
            for(var i=0;i<trs.length;i++){
                cb=trs[i].getElementsByTagName('input')[0];
                if(cb && cb.checked){
                    cb.disabled=false;
                }
            }
        }else if(cnt==2){
            b_cd.disabled=false;
        }else{
            b_cd.disabled=true;
            for(var i=0;i<trs.length;i++){
                cb=trs[i].getElementsByTagName('input')[0];
                if(cb && !cb.checked){
                    cb.disabled=true;
                }
            }
        }
    }

    function checkdiff()
    {
        var trs=document.getElementById('tbl').getElementsByTagName('TR');
        var cb,names=[];
        for(var i=0;i<trs.length;i++){
            cb=trs[i].getElementsByTagName('input')[0];
            if(cb && cb.checked){
                names.push(cb.name);
            }
        }
        if(names.length==2){
            var buf=names[0].split('.');
            var ext1=buf.pop();
            names[0]=buf.join('.');
            var buf=names[1].split('.');
            var ext2=buf.pop();
            names[1]=buf.join('.');
            var url='?source='+ext1+'&source2='+ext2+'&run1='+names[0]+'&run2='+names[1];
            window.location.href=url;
        }
    }
</script>
    <?
    echo "</body>";
    echo "</html>";
    exit();
}
function toTimePcs($s,$getmin=1,$usekey='float')
{
    $os=$s=intval($s);
    $l=array('seconds','minutes','hours','days');
    $fl=array(1,60,60*60,60*60*24);
    $r=array('float'=>array(),'int'=>array());
    for($i=sizeof($l)-1;$i>=0;$i--){
        $r['int'][$l[$i]]=floor($s/$fl[$i]);
        $s-=$r['int'][$l[$i]]*$fl[$i];
    }
    for($i=sizeof($fl)-1;$i>=0;$i--){
        if(($os/$fl[$i])>=1){
            $r['float'][$l[$i]]=$os/$fl[$i];
        }
    }
    $rnd=(reset($r[$usekey])/10)>=1?0:(reset($r[$usekey])<3?2:1);
    return $getmin?round(reset($r[$usekey]),$rnd).' '.reset(array_keys($r[$usekey])):$r;
}
function toBytes($v)
{
    $v=intval($v);
    $e=array(' bytes','KB','MB','GB','TB');
    $level=0;
    while ($level<sizeof($e)&&$v>=1024)
    {
        $v=$v/1024;
        $level++;
    }
    return ($level>0?round($v,2):$v).$e[$level];
}








// param name, its type, and default value
$params = array('run'        => array(XHPROF_STRING_PARAM, ''),
    'wts'        => array(XHPROF_STRING_PARAM, ''),
    'symbol'     => array(XHPROF_STRING_PARAM, ''),
    'sort'       => array(XHPROF_STRING_PARAM, 'wt'), // wall time
    'run1'       => array(XHPROF_STRING_PARAM, ''),
    'run2'       => array(XHPROF_STRING_PARAM, ''),
    'source'     => array(XHPROF_STRING_PARAM, 'xhprof'),
    'all'        => array(XHPROF_UINT_PARAM, 0),
    'source2'    => array(XHPROF_STRING_PARAM, 'xhprof'),
);

// pull values of these params, and create named globals for each param
xhprof_param_init($params);

/* reset params to be a array of variable names to values
   by the end of this page, param should only contain values that need
   to be preserved for the next page. unset all unwanted keys in $params.
 */
foreach ($params as $k => $v) {
    $params[$k] = $$k;

    // unset key from params that are using default values. So URLs aren't
    // ridiculously long.
    if ($params[$k] == $v[1]) {
        unset($params[$k]);
    }
}

echo "<html>";

echo "<head><title>XHProf: Hierarchical Profiler Report</title>";
xhprof_include_js_css();
echo "</head>";

echo "<body>";

?>
<div style="text-align:center"><a href="?">List of profiler files</a></div>
<?


$vbar  = ' class="vbar"';
$vwbar = ' class="vwbar"';
$vwlbar = ' class="vwlbar"';
$vbbar = ' class="vbbar"';
$vrbar = ' class="vrbar"';
$vgbar = ' class="vgbar"';

$xhprof_runs_impl = new XHProfRuns_Default($dir);

$run_fname=$xhprof_runs_impl->file_name($run,$source);
if(hasSqlFile($run_fname)){
    ?>
<div style="border:1px solid silver;">
    <?
    $data=$additional_data=unserialize(file_get_contents($run_fname.'.sql'));
//print_r($data['backtrace_calls']);die();
    // prepare backtrace_calls
    if(isset($additional_data['backtrace_calls'])){
        $additional_data['backtrace_calls_prepared']=array();
        foreach($additional_data['backtrace_calls'] as $k=>$v){
            $additional_data['backtrace_calls_prepared'][str_replace('->','::',$k)]=$v;
        }
    }

    if(isset($_POST['sort_sql_by_hits']))
    {
        if($_POST['sort_sql_by_hits']){
            $_SESSION['sort_sql_by_hits']=1;
        }else{
            unset($_SESSION['sort_sql_by_hits']);
        }
    }
    if(isset($_POST['show_custom_data']))
    {
        if($_POST['show_custom_data']){
            $_SESSION['show_custom_data']=1;
        }else{
            unset($_SESSION['show_custom_data']);
        }
    }
    ?>
    <form method="post" action="?<?=$_SERVER['QUERY_STRING']?>">
        <input type="checkbox" value="1" name="show_custom_data" <?=isset($_SESSION['show_custom_data']) ? 'checked="checked"' : ''?>
               onclick="this.parentNode.submit();" />
        Show custom data
    </form>
    <?
    if(isset($_SESSION['show_custom_data']) && $_SESSION['show_custom_data']){
        ?>
        <div>Custom data:<pre><?=isset($data['app_data']) ? printr(data2array($data['app_data'])) : 'n/a'?></pre></div>
        <?}?>
    <div>SQL queries: <?=sizeof($data['sql'])?></div>
    <div>SQL summary time: <?=$data['summary_time']?></div>
    <a href="javascript:void()" onclick="document.getElementById('d_sql').style.display=document.getElementById('d_sql').style.display=='none' ? 'block' : 'none';">See all SQL queries</a>
    <div id="d_sql" style="border:1px solid silver;display:none;padding:5px;">

        <form method="post" action="?<?=$_SERVER['QUERY_STRING']?>">
            <input type="checkbox" value="1" name="sort_sql_by_hits" <?=isset($_SESSION['sort_sql_by_hits']) ? 'checked="checked"' : ''?>
                   onclick="this.parentNode.submit();" />
            Sort by Hits (unchecked - sorted by time)<br/>
        </form>
        <?
        $dump_hash=array();
        foreach($data['sql'] as $row){
            $dump_hash[$row[0]]=isset($dump_hash[$row[0]]) ? $dump_hash[$row[0]] : array('time'=>0,'hits'=>0,'dumps'=>array());
            $dump_hash[$row[0]]['hits']++;
            $dump_hash[$row[0]]['time']+=$row[1];
            $dump_hash[$row[0]]['sql']=$row[0];
            $dump_hash[$row[0]]['dumps'][$row[2]]=isset($dump_hash[$row[0]]['dumps'][$row[2]])
                ? $dump_hash[$row[0]]['dumps'][$row[2]]
                : array('hits'=>0,'time'=>0);
            $dump_hash[$row[0]]['dumps'][$row[2]]['hits']++;
            $dump_hash[$row[0]]['dumps'][$row[2]]['time']+=$row[1];
            $dump_hash[$row[0]]['dumps'][$row[2]]['content']=$row[2];
        }
        // sort dumps
        function sortbytime($a,$b)
        {
            return $a['time']<$b['time'];
        }
        function sortbyhits($a,$b)
        {
            return $a['hits']<$b['hits'];
        }
        $sort_method=isset($_SESSION['sort_sql_by_hits']) ? 'sortbyhits' : 'sortbytime';
        usort($dump_hash,$sort_method);
        foreach($dump_hash as $sql=>&$data){
            usort($data['dumps'],$sort_method);
        }


        $ind=0;
        foreach($dump_hash as $sql=>$data){
            $ind++;
            ?>
            <div style="border:1px solid orange;margin:6px 3px;">
                <div style="color:gray">Hits: <span style="color:navy"><?=$data['hits']?></span> Time: <span style="color:navy"><?=$data['time']?>s</span></div>
                <pre style="white-space:pre-line;"><?=$data['sql']?></pre>
                <a href="javascript:void(0)"
                   onclick="document.getElementById('bt_cont<?=$ind?>').style.display
                       = document.getElementById('bt_cont<?=$ind?>').style.display=='block' ? 'none' : 'block';"
                    ><?=sizeof($data['dumps'])?> unique backtrace(s) for this query<a>
                    <div style="display:none" id="bt_cont<?=$ind?>">
                        <?foreach($data['dumps'] as $dd){?>
                        <div style="border:1px solid silver;margin:3px;font-size:10px;color:#777;">
                            <b style="color:#000">Hits: <?=$dd['hits']?>
                                <span style="margin-left:20px">[<?=$dd['time']?>s]</span>
                                <span style="margin-left:20px"><?=round(($dd['time']/$data['time'])*100,2)?>%</span>
                            </b>
                            <pre><?=$dd['content']?></pre>
                        </div>
                        <?}?>
                    </div>
            </div>
            <?}?>
    </div>
    <?
    ?>
</div>
    <?
}

displayXHProfReport($xhprof_runs_impl, $params, $source, $run, $wts,
    $symbol, $sort, $run1, $run2,$source2);
?>
<script type="text/javascript">
    function expand_node(el)
    {
        var n=el.parentNode.nextSibling;
        if(n && n.className=='node'){
            if(n.style.display!='none'){
                n.style.display='none';
                el.innerHTML='+';
            }else{
                el.innerHTML='-';
                n.style.display='block';
            }
        }
    }
</script>
<?



echo "</body>";
echo "</html>";



function data2array(array $data,$l=0) {
    $arr = array();
    if($l<8){
        foreach($data as $index=>$row){
            if(is_array($row) || ($row instanceof __PHP_Incomplete_Class)){
                $arr[$index]=data2array((array)$row,$l+1);
            }else{
                $arr[$index]=$row;
            }
        }
    }else{
        $arr='... too much recursion';
    }
    return $arr;
}

function printr($a,$l=0)
{
    if(!empty($a))
    {
        $plus='<span style="margin:1px 5px;border:1px solid #ccc;cursor:pointer;" onclick="expand_node(this);">+</span>';
        $head='<div class="node" style="margin-left:'.(20).'px;border-left:1px solid #ccc;'.($l ? 'display:none;' : '').'" >';
        $out='';
        if(is_array($a)){
            $s='';
            foreach($a as $k=>$v){
                if(is_array($v)){
                    $buf=printr($v,$l+1);
                    $s.='<div style="'.(!empty($buf) ? '' : 'color:#999;').'margin-left:'.(20).'px"> '
                        .(!empty($buf) ? $plus : '').($k.': ').'</div>'.$buf;
                }else{
                    $s.='<div style="margin-left:'.(20).'px">'.$k.': '.print_r($v,1).'</div>';
                }
            }
            $out.='<div style="margin-left:'.(20).'px">'.$s.'</div>';
        }else{
            $out.='<div style="margin-left:'.(20).'px">'.(is_object($a) ? print_r($a,1) : $a).'</div>';
        }
        return $out ? $head.$out.'</div>' : '';
    }else{
        return '';
    }
}