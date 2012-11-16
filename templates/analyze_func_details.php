<form action="<?=$this->link()?> method=" get">

    <input type="hidden" name="analyze" value=""/>
    <input type="hidden" name="view_in_xhp" value=""/>
    <input type="hidden" name="namespace" value="<?=$this->namespace?>"/>
    <input type="hidden" name="uid" value="<?=$this->uid?>"/>
    <input type="hidden" name="functions" value="<?=$this->functionsContent?>"/>
    <input type="hidden" name="desc" value="<?=$this->sortDesc?>"/>
    <input type="hidden" name="sort" value="<?=$this->sort?>"/>
    <input type="hidden" name="st" value="<?=$this->st?>"/>

    <span class="analyze_fl_search">

        Watch calls (<?=sizeof($this->watchFunctionsArray)?>):
        <textarea rows="1" cols="40" name="wf" spellcheck="false"
                  title="You can resize this element if your browser supports resizing"><?=implode(
            "\n", $this->watchFunctionsArray
        )?></textarea>

        <input type="submit" value="Apply"/>

    </span>

</form>


<table cellpadding="0" cellspacing="0" class="tbl_list">
    <thead>
    <th><?=sortableColumnHeader('#', 'index', 'num', $this)?></th>
    <th><?=sortableColumnHeader('Name', 'name', 'str', $this)?></th>
    <th><?=sortableColumnHeader('Calls', 'ct', 'num', $this)?></th>
    <th><?=sortableColumnHeader('Calls %', 'pct', 'num', $this)?></th>
    <th><?=sortableColumnHeader('IWall Time', 'wt', 'num', $this)?></th>
    <th><?=sortableColumnHeader('IWall Time %', 'pwt', 'num', $this)?></th>
    <th><?=sortableColumnHeader('CPU', 'cpu', 'num', $this)?></th>
    <th><?=sortableColumnHeader('Memory', 'mu', 'num', $this)?></th>
    </thead>
    <tbody>

    <!-- Current function -->
    <tr>
        <td colspan="8" class="file">Current function</td>
    </tr>
    <tr>
        <td width="1%">-</td>
        <td><?=getFuncNameLinkCellHTML($this->data, $this)?></td>
        <td width="1%"><?=$this->data['data']['ct']?></td>
        <td width="1%"><?=$this->data['data']['pct']?></td>
        <td width="1%"><?=$this->data['data']['wt']?></td>
        <td width="1%"><?=$this->data['data']['pwt']?></td>
        <td width="1%"><?=$this->data['data']['cpu']?></td>
        <td width="1%"><?=$this->data['data']['mu']?></td>
    </tr>

    <!-- Exclusive metrics for current function -->
    <tr class="even">
        <td colspan="2">Exclusive metrics for current function</td>
        <td width="1%">-</td>
        <td width="1%">-</td>
        <td width="1%"><?=$this->data['data']['ewt']?></td>
        <td width="1%">-</td>
        <td width="1%">-</td>
        <td width="1%">-</td>
    </tr>

    <!-- Parent calls -->
    <tr>
        <td colspan="8" class="file">Parent calls</td>
    </tr>
    <?
    $index = 0;
    foreach ($this->data['parents'] as $row) {
        ?>
    <tr<?=$index & 1 ? ' class="even"' : ''?>>
        <td width="1%"><?=$row['index'] + 1?></td>
        <td><?=getFuncNameLinkCellHTML($row, $this)?></td>
        <td width="1%"><?=$row['data']['ct']?></td>
        <td width="1%"><?=$row['data']['pct']?></td>
        <td width="1%"><?=$row['data']['wt']?></td>
        <td width="1%"><?=$row['data']['pwt']?></td>
        <td width="1%"><?=$row['data']['cpu']?></td>
        <td width="1%"><?=$row['data']['mu']?></td>
    </tr>
        <?
        $index++;
    }?>

    <!-- Child calls -->
    <tr>
        <td colspan="8" class="file">Child calls</td>
    </tr>
    <?
    $index = 0;
    foreach ($this->data['children'] as $row) {
        ?>
    <tr<?=$index & 1 ? ' class="even"' : ''?>>
        <td width="1%"><?=$row['index'] + 1?></td>
        <td><?=getFuncNameLinkCellHTML($row, $this)?></td>
        <td width="1%"><?=$row['data']['ct']?></td>
        <td width="1%"><?=$row['data']['pct']?></td>
        <td width="1%"><?=$row['data']['wt']?></td>
        <td width="1%"><?=$row['data']['pwt']?></td>
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
                    'action' => 'xhp_details',
                    'namespace' => $tpl->namespace,
                    'uid' => $tpl->uid,
                    'func' => $tpl->func,
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

function getFuncNameLinkCellHTML(array $row, $tpl)
{
    return '<a href="'
        . $tpl->link(
            array(
                'controller' => 'analyze',
                'action' => 'xhp_details',
                'namespace' => $tpl->namespace,
                'uid' => $tpl->uid,
                'func' => $row['name'],
            )
        )
        . '">' . $row['name'] . '</a>';
}

