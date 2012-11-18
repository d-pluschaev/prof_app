<b>Summary:</b>
<table cellpadding="0" cellspacing="0" class="tbl_list" style="width:400px;">
    <thead>
    <th>Metric</th>
    <th>Diff</th>
    </thead>

    <tbody>

    <tr>
        <td>Request count:</td>
        <td><?=getMetricHTML($this->diffSummary['added'] - $this->diffSummary['removed'])?></td>
    </tr>

    <tr>
        <td>Time:</td>
        <td><?=getMetricHTML($this->diffSummary['time']);?></td>
    </tr>

    <tr>
        <td>HTML Footer Time:</td>
        <td><?=getMetricHTML($this->diffSummary['html_footer_time']);?></td>
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

<div>
    Legend:
    <span class="inc">Improvement</span> /
    <span class="dec">Regression</span> /
    <span class="diff_func_metrics">
        <span>Calls</span><span>Time</span><span>CPU</span><span>Memory</span> /

        <span>Not changed</span>
        <span class="inc">Improvement</span>
        <span class="dec">Regression</span>
    </span>
</div>


<br/>
<b>Details:</b>
<table cellpadding="0" cellspacing="0" class="tbl_list">
    <thead>
    <th>#</th>
    <th>UID</th>
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
        ?>

    <tr<?=$index & 1 ? ' class="even"' : ''?>>
        <td width="1%"><?=$index + 1?></td>
        <td><?=$uid?></td>
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
    if (isset($array[$key1][$key2])) {
        return getMetricHTML($array[$key1][$key2]);
    } else {
        return '&nbsp;';
    }
}

function getMetricHTML($metric)
{
    $class = $metric > 0 ? 'dec' : ($metric < 0 ? 'inc' : '');
    return "<span class=\"{$class}\">{$metric}</span>";
}

function getFuncMetricHTML($metrics)
{
    return '<div title="Calls / Time / CPU / Memory" class="diff_func_metrics">'
        . getMetricHTML($metrics['ct'])
        . getMetricHTML($metrics['wt'])
        . getMetricHTML($metrics['cpu'])
        . getMetricHTML($metrics['mu'])
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
