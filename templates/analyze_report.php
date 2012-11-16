<?
/*  Generate report form   */
?>
<div class="analyze_report_form">
    <form action="<?=$this->link()?>" method="get">

        <input type="hidden" name="analyze" value=""/>
        <input type="hidden" name="report" value=""/>
        <input type="hidden" name="namespace" value="<?=$this->namespace?>"/>

        <div class="half">
            Generate report for selected calls.<br/>
            Multiline resizeable input, please type one call per line.
        </div>

        <div class="half">
            <textarea name="functions" onfocus="this.select()" spellcheck="false"
                    ><?=!trim($this->functionsContent)
                ? "&lt;function_call&gt;\n&lt;Class::method&gt;"
                : $this->functionsContent?></textarea>
            <input type="submit" value="Generate report"/>
        </div>

        <div style="clear:both"></div>
    </form>
</div>
<?
/*  END Generate report form   */
?>







<?
/*   TABLE   */
?>

Collected data. Function cells: (calls number / wall time / CPU resource) also see parents in tooltip:
<table cellpadding="0" cellspacing="0" class="tbl_list">
    <thead>
    <th><?=sortableColumnHeader('#', 'index', 'num', $this)?></th>
    <th><?=sortableColumnHeader('UID', 'uid', 'str', $this)?></th>
    <th><?=sortableColumnHeader('Request', '', '', $this)?></th>
    <th><?=sortableColumnHeader('Age', 'timestamp', 'num', $this)?></th>
    <th><?=sortableColumnHeader('Time', 'time', 'num', $this)?></th>
    <th><?=sortableColumnHeader('Time from Footer', 'html_footer_time', 'num', $this)?></th>

    <?
    // add functions column headers
    foreach ($this->functions as $func => $matches) {
        ?>
    <th><?=sortableColumnHeader($func, "func_{$func}", 'ct|wt|cpu', $this)?></th>
        <? }?>

    <th><?=sortableColumnHeader('Action', '', '', $this)?></th>
    </thead>
    <tbody>
    <?
    $index = 0;
    foreach ($this->data as $row) {
        ?>
    <tr<?=$index & 1 ? ' class="even"' : ''?>>
        <td width="1%"><?=$row['index'] + 1?></td>
        <td width="1%"><?=$row['uid']?></td>
        <td><?=getRequestCellHTML($row)?></td>
        <td width="1%"><?=microtimeToTimeUnits(microtime(1) - $row['timestamp'])?></td>
        <td width="1%"><?=number_format($row['time'], 4)?></td>
        <td width="1%"><?=getFooterTimeCellHTML($row)?></td>

        <?
        // function columns
        foreach ($this->functions as $func => $matches) {
            if (isset($row['func_' . $func])) {
                $funcData = $row['func_' . $func];

                $parents = array();
                foreach ($funcData['parents'] as $parentName => $parentData) {
                    $parentData['wt'] = number_format($parentData['wt'] / 1000000, 6);
                    $parents[] = "$parentName: {$parentData['ct']} / {$parentData['wt']} / {$parentData['cpu']}";
                }

                $label = "<pre title=\"" . implode("\n", $parents) . "\">"
                    . "{$funcData['summary']['ct']}\n"
                    . number_format($funcData['summary']['wt'] / 1000000, 6) . "\n"
                    . $funcData['summary']['cpu']
                    . '</pre>';
            } else {
                $label = '-';
            }
            ?>

            <td width="1%"><?=$label?></td>

            <?
        }
        // end functions
        ?>

        <td width="1%"><?=getActionLinksCellHTML($row, $this);?></td>
    </tr>

        <?
        $index++;
    }?>
    </tbody>
</table>


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

function sortableColumnHeader($title, $key, $sortType, $tpl)
{
    if ($key) {
        $sortTypes = explode('|', $sortType);
        $linkSort = $key;
        $currentSortType = in_array($tpl->sortType, $sortTypes) ? $tpl->sortType : reset($sortTypes);

        if ($tpl->sort == $key) {
            for ($i = 0; $i < sizeof($sortTypes); $i++) {
                if ($currentSortType == $sortTypes[$i]) {
                    if (isset($sortTypes[$i + 1])) {
                        $linkSortType = $sortTypes[$i + 1];
                    } else {
                        $linkSortType = reset($sortTypes);
                    }
                    $linkSortDesc = $tpl->sortDesc ? 1 : 0;
                    if ($i == 0) {
                        $linkSortDesc = $tpl->sortDesc ? 0 : 1;
                    }
                    break;
                }
            }
        } else {
            $linkSortDesc = 1;
            $linkSortType = reset($sortTypes);
        }

        $linkSortDirClass = 'class="' . ($tpl->sortDesc ? 'down' : 'up') . '"';

        return
            '<a href="'
            . $tpl->link(
                array(
                    'controller' => 'analyze',
                    'action' => 'report',
                    'namespace' => $tpl->namespace,
                    'functions' => $tpl->functionsContent,
                    'desc' => $linkSortDesc,
                    'sort' => $linkSort,
                    'st' => $linkSortType,
                )
            )
            . '">'
            . $title
            . ($tpl->sort == $key ? "<sup {$linkSortDirClass}>{$currentSortType}</sup>" : '')
            . '</a>';
    } else {
        return $title;
    }
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

function getActionLinksCellHTML(array $row, $tpl)
{
    $links = array();
    if ($row['files']['xhp_is_exists']) {
        $links[] = '<a target="_blank" href="' . $tpl->link(
            array(
                'controller' => 'analyze',
                'action' => 'view_in_xhp',
                'namespace' => $tpl->namespace,
                'uid' => $row['uid'],
            )
        ) . '">XHP</a>';
        $links[] = '<a target="_blank" href="' . $tpl->link(
            array(
                'controller' => 'analyze',
                'action' => 'view_in_xhp_legacy',
                'namespace' => $tpl->namespace,
                'uid' => $row['uid'],
            )
        ) . '">XHP_Legacy</a>';
    }
    if ($row['files']['html_is_exists']) {
        $links[] = '<a target="_blank" href="' . $tpl->link(
            array(
                'controller' => 'analyze',
                'action' => 'view_html',
                'namespace' => $tpl->namespace,
                'uid' => $row['uid'],
            )
        ) . '">HTML</a>';
    }
    return implode('<br/>', $links);
}

function getFooterTimeCellHTML(array $row)
{
    $label = $row['html_footer_time'] ? number_format($row['html_footer_time'], 4) : '-';
    // redirect headers
    $wasRedirected = '';
    foreach ($row['response_headers'] as $header) {
        if (preg_match('~Location:\s(.*)~', $header, $matches)) {
            $wasRedirected = '<pre title="' . htmlspecialchars($matches[0]) . '"> + Redirect</pre>';
            break;
        }
    }
    return $label . $wasRedirected;
}

?>
