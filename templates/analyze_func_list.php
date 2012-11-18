<form action="<?=$this->link()?> method=" get">

    <input type="hidden" name="analyze" value=""/>
    <input type="hidden" name="view_in_xhp" value=""/>
    <input type="hidden" name="namespace" value="<?=$this->namespace?>"/>
    <input type="hidden" name="uid" value="<?=$this->uid?>"/>
    <input type="hidden" name="functions" value="<?=$this->functionsContent?>"/>
    <input type="hidden" name="desc" value="<?=$this->sortDesc?>"/>
    <input type="hidden" name="sort" value="<?=$this->sort?>"/>
    <input type="hidden" name="st" value="<?=$this->st?>"/>

    <span class="xhp_fl_summary">
        <span class="title">Summary:</span>
        <span class="metric">wall time [ <span class="value"><?=$this->main['wt']?></span> ] microseconds</span>
        <span class="metric">cpu [ <span class="value"><?=$this->main['cpu']?></span> ] microseconds</span>
        <span class="metric">peak memory usage [ <span class="value"><?=$this->main['pmu']?></span> ] bytes</span>
    </span>

    <span class="analyze_fl_search">

        Watch calls (<?=sizeof($this->watchFunctionsArray)?>):
        <textarea rows="1" cols="40" name="wf" spellcheck="false"
                  title="You can resize this element if your browser supports resizing"><?=implode(
            "\n", $this->watchFunctionsArray
        )?></textarea>


        Search by name:
        <input type="text" name="sf" value="<?=$this->searchFunction?>"/>
        <input type="submit" value="Apply"/>

        <a href="<?=$this->link(
            array(
                'controller' => 'analyze',
                'action' => 'view_in_xhp',
                'namespace' => $this->namespace,
                'uid' => $this->uid,
                'functions' => $this->functionsContent,
                'desc' => $this->sortDesc,
                'sort' => $this->sort,
                'st' => $this->st,
                'da' => 1,
                'wf' => implode("\n", $this->watchFunctionsArray),
            )
        )?>">Display all</a>
    </span>

</form>


<table cellpadding="0" cellspacing="0" class="tbl_list">
    <thead>
    <th><?=sortableColumnHeader('#', 'index', 'num', $this)?></th>
    <th><?=sortableColumnHeader('Name', 'name', 'str', $this)?></th>

    <?foreach($this->watchFunctionsArray as $func){?>
    <th><?=sortableColumnHeader($func, "wf_$func", 'num', $this)?></th>
    <?}?>

    <th><?=sortableColumnHeader('Calls', 'ct', 'num', $this)?></th>
    <th><?=sortableColumnHeader('IWall Time', 'wt', 'num', $this)?></th>
    <th><?=sortableColumnHeader('EWall Time', 'ewt', 'num', $this)?></th>
    <th><?=sortableColumnHeader('CPU', 'cpu', 'num', $this)?></th>
    <th><?=sortableColumnHeader('Memory', 'mu', 'num', $this)?></th>
    </thead>

    <tbody>
    <?
    $index = 0;
    foreach ($this->list as $row) {
        ?>
    <tr<?=$index & 1 ? ' class="even"' : ''?>>
        <td width="1%"><?=$row['index']?></td>
        <td><?=getNameCellHTML($row, $this)?></td>

        <?foreach($this->watchFunctionsArray as $func){?>
        <td width="1%"><?=$row['name'] != $func ?
            (isset($row['caused_calls'][$func]['ct']) ? $row['caused_calls'][$func]['ct'] : '-')
            : '<span class="inactive_cell">n/a</span>'
            ?></td>
        <?}?>

        <td width="1%"><?=$row['data']['ct']?></td>
        <td width="1%"><?=$row['data']['wt']?></td>
        <td width="1%"><?=$row['data']['ewt']?></td>
        <td width="1%"><?=$row['data']['cpu']?></td>
        <td width="1%"><?=$row['data']['mu']?></td>
    </tr>

        <?
        $index++;
    }?>
    </tbody>
</table>

<?

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
                    'action' => 'view_in_xhp',
                    'namespace' => $tpl->namespace,
                    'uid' => $tpl->uid,
                    'functions' => $tpl->functionsContent,
                    'desc' => $linkSortDesc,
                    'sort' => $linkSort,
                    'st' => $linkSortType,
                    'da' => 0,
                    'wf' => implode("\n", $tpl->watchFunctionsArray),
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

function getNameCellHTML(array $row, $tpl)
{
    return '<a href="'
        . $tpl->link(
            array(
                'controller' => 'analyze',
                'action' => 'xhp_details',
                'namespace' => $tpl->namespace,
                'uid' => $tpl->uid,
                'func' => $row['name'],
                'wf' => implode("\n", $tpl->watchFunctionsArray),
            )
        )
        . '">' . $row['name'] . '</a>';
}

