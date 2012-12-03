<?
$requestForm = array('controller' => 'diff', 'action' => 'namespaces') + $_REQUEST;
unset($requestForm['diff']);
unset($requestForm['namespaces']);
$requestForm['source'] = $requestForm['target'];
$requestForm['target'] = $this->namespace;
$link = $this->link($requestForm);
?>

<b>All metrics calculated for namespace `<?=$this->namespace?>`</b>
<a href="<?=$link?>">Swap namespaces</a>
<br/><br/>


<b>Summary Diagram:</b>
<?=
$this->includeFragment('diff_diagram')
; ?>

<br/>


<b>Summary:</b>
<table cellpadding="0" cellspacing="0" class="tbl_list" style="width:400px;">
    <thead>
    <th>Metric</th>
    <th>Diff</th>
    </thead>

    <tbody>

    <tr>
        <td>Request count:</td>
        <td><?=getDetailedMetricHTML(
            array(
                's' => $this->diffSummary['added'],
                't' => $this->diffSummary['removed'],
                'd' => $this->diffSummary['added'] - $this->diffSummary['removed'],
            )
        )?></td>
    </tr>

    <tr>
        <td>Time:</td>
        <td><?=getDetailedMetricHTML($this->diffSummary['time']);?></td>
    </tr>

    <tr>
        <td>HTML Footer Time:</td>
        <td><?=getDetailedMetricHTML($this->diffSummary['html_footer_time']);?></td>
    </tr>

    <?if (sizeof($this->diffSummary['functions'])) { ?>
    <tr>
        <td colspan="2">By function calls:</td>
    </tr>
        <? }?>

    <?foreach ($this->diffSummary['functions'] as $func => $funcData) { ?>
    <tr>
        <td><?=$func?>:</td>
        <td><?=getFuncMetricHTML($funcData)?></td>
    </tr>
        <? }?>

    </tbody>
</table>



<br/>
<b>Details:</b>
<table cellpadding="0" cellspacing="0" class="tbl_list">
    <thead>
    <th>#</th>
    <th>UID</th>
    <th>Request</th>
    <th>Changes</th>
    <th>Time</th>
    <th>HTML Footer Time</th>

    <?foreach ($this->functions as $func) { ?>
    <th><?=$func?></th>
        <? }?>

    </thead>

    <tbody>
    <?
    $index = 0;
    foreach ($this->diff as $uid => $row) {
        $row['index']=$index;
    ?>

    <tr<?=$index & 1 ? ' class="even"' : ''?>>
        <td width="1%"><?=$index + 1?></td>
        <td><?=$uid?></td>
        <td><?=getRequestCellHTML($row)?></td>
        <td width="1%"><?=getChangesCellHTML($row)?></td>
        <td><?=getFloatCalcCellHTML($row, 'data', 'time')?></td>
        <td><?=getFloatCalcCellHTML($row, 'data', 'html_footer_time')?></td>

        <?foreach ($this->functions as $func) { ?>
        <td width="1%" style="white-space: nowrap;"><?=getFuncCalcCellHTML($row, $func)?></td>
        <? } ?>

    </tr>

        <?
        $index++;
    }?>
    </tbody>
</table>



<div style="padding:20px 6px;">
    <b>Legend:</b>
    <br/>
    <span class="diff_func_metrics">
        <span>Calls</span><span>Time</span><span>CPU</span><span>Memory</span> /

        <span>Not changed</span>
        <span class="inc">Improvement</span>
        <span class="dec">Regression</span>
    </span>
    <br/>
    Improvement %:
    <?foreach (getColorScheme('green') as $k => $v) { ?>
    <span style="background-color:#<?=$v?>;"><?=$k?></span>
    <? }?>
    <br/>
    Regression %:
    <?foreach (getColorScheme('red') as $k => $v) { ?>
    <span style="background-color:#<?=$v?>;"><?=$k?></span>
    <? }?>
    <br/>
    See tooltips for details
</div>

<div style="padding:20px 6px;">
    <?
    $link = $this->link($requestForm);
    ?>
    Direct link on this page: <a href="<?=$link?>"><?=$link?></a>
</div>


<script type="text/javascript">
    function toggleReq(id, full) {
        if (full) {
            document.getElementById('b_' + id).style.display = 'block';
            document.getElementById('s_' + id).style.display = 'none';
        } else {
            document.getElementById('s_' + id).style.display = 'block';
            document.getElementById('b_' + id).style.display = 'none';
        }
    }
</script>



<?

function getChangesCellHTML(array $row)
{
    $color = '#BBBBFF';
    $labels = array();
    $title = 'Calculated';
    if (!empty($row['removed'])) {
        $labels[] = 'removed';
        $color = '#FFCCCC';
        $title = 'Removed';
    } elseif (!empty($row['added'])) {
        $labels[] = 'added';
        $color = '#CCFFCC';
        $title = 'Added';
    } else {
        if ($row['data']['http_code_changed']) {
            $labels[] = 'C';
            $color = '#CC0033';
            $title = 'HTTP code changed';
        }
        if ($row['data']['html_footer_time_changed']) {
            $labels[] = 'F';
            $color = '#CC0033';
            $title = 'Time in HTML footer visibility changed';
        }
    }
    return '<div class="diff_status" style="background-color:' . $color . '" title="' . $title . '">'
        . (!empty($labels) ? implode('', $labels) : 'calculated') . '</div>';
}

function getFloatCalcCellHTML(array $array, $key1, $key2)
{
    if (isset($array[$key1][$key2]['s'])) {
        return getDetailedMetricHTML($array[$key1][$key2]);
    } else {
        return '&nbsp;';
    }
}

function getDetailedMetricHTML(array $std)
{
    if ($std['s'] == 0 || $std['t'] == 0) {
        $percentage = $std['t'] == $std['s'] ? 0 : 100;
    } else {
        $percentage = round((($std['s'] / $std['t']) - 1) * 100, 2);
    }

    $color = '';
    foreach (($std['d'] > 0 ? getColorScheme('red') : getColorScheme('green')) as $pVal => $col) {
        if ($pVal == 100 || $pVal >= abs($percentage)) {
            $color = "#$col";
            break;
        }
    }

    $percentage = ($percentage > 0 ? '+' . $percentage : $percentage) . '%';

    $class = $std['d'] > 0 ? 'dec' : ($std['d'] < 0 ? 'inc' : '');
    return "<span title=\"{$percentage} [{$std['t']}=>{$std['s']}]\""
        . " class=\"{$class}\" "
        . ($percentage == 0 ? '' : "style=\"background-color:{$color}\"")
        . ">{$std['d']}</span>";
}

function getFuncMetricHTML($metrics)
{
    return '<div title="Calls / Time / CPU / Memory" class="diff_func_metrics">'
        . getDetailedMetricHTML($metrics['ct'])
        . getDetailedMetricHTML($metrics['wt'])
        . getDetailedMetricHTML($metrics['cpu'])
        . getDetailedMetricHTML($metrics['mu'])
        . '</div>';
}

function getFuncCalcCellHTML(array $row, $func)
{
    if (isset($row['data']['func_diff'][$func])) {
        return getFuncMetricHTML($row['data']['func_diff'][$func]);
    } else {
        return '&nbsp;';
    }
}

function getColorScheme($key)
{
    $data = array(
        'green' => array(
            1 => 'DBFFDB',
            5 => 'C2FFC2',
            10 => 'A8FFA8',
            15 => '8FFF8F',
            20 => '75FF75',
            25 => '5CFF5C',
            30 => '42FF42',
            40 => '0FFF0F',
            50 => '00F500',
            60 => '00DB00',
            70 => '00C200',
            85 => '00A800',
            100 => '008F00',
        ),

        'red' => array(
            1 => 'FFE4DB',
            5 => 'FFD1C2',
            10 => 'FFBEA8',
            15 => 'FFAB8F',
            20 => 'FF9875',
            30 => 'FF855C',
            40 => 'FF7142',
            50 => 'FF5E29',
            65 => 'FF4B0F',
            80 => 'F53D00',
            100 => 'DB3700',
        )
    );
    return $data[$key];
}

function getRequestCellHTML(array $row)
{
    $url = http_build_query($row['req']);
    $url = App::cfg('maintaining_project_url') . ($url ? '?' . $url : '');

    $full_req = '<div style="display:none" id="b_' . $row['index'] . '">'
        . '<pre style="width:99%;">' . printr($row['req']) . '</pre>'
        . '<a href="javascript:toggleReq(' . $row['index'] . ')">Show short</a> '
        . '| <a target="_blank" href="' . $url . '">Link</a>'
        . '<div>';

    $short_req = '<div id="s_' . $row['index'] . '">'
        . '<pre style="width:99%;">' . printr(
        array(
            'module' => isset($row['req']['module']) ? $row['req']['module'] : 'n/a',
            'action' => isset($row['req']['action']) ? $row['req']['action'] : 'n/a',
        )
    )
        . '</pre>'
        . '<a href="javascript:toggleReq(' . $row['index'] . ',1)">Show full</a> '
        . '| <a target="_blank" href="' . $url . '">Link</a>'
        . '</div>';
    $row['freq'] = $short_req . $full_req;

    return $short_req . $full_req;
}

function printr($data, $eof = "\r\n", $sep = '   ', $lev = 0)
{
    $key = '%s: ';
    $val = '<b>%s</b> ';
    $out = '';
    if (is_array($data)) {
        foreach ($data as $k => $v) {
            $k = trim($k);
            if (is_array($v)) {
                $out .= $eof . str_repeat($sep, $lev)
                    . sprintf($key, $k)
                    . printr($v, $eof, $sep, $lev + 1);
            } else {
                $v = trim($v);
                $out .= $eof . str_repeat($sep, $lev)
                    . sprintf($key, $k)
                    . sprintf($val, wordwrap($v, 50, $eof . str_repeat(' ', strlen(sprintf($key, $k))), 1));
            }
        }
    } else {
        $out .= $eof . str_repeat($sep, $lev) . $data;
    }
    return $out;
}

