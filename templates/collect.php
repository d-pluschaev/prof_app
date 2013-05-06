Send requests using previously recorded data and collect profiler results<br/>

<? if (!$this->status['htaccess_changed'] || $this->status['is_regular_profiler_on'] || $this->status['recording_mode']) { ?>
<?
    $problems = array();
    if (!$this->status['htaccess_changed']) {
        $problems[] = 'Agent is OFF';
    }
    if ($this->status['is_regular_profiler_on']) {
        $problems[] = 'Regular profiler is ON';
    }
    if ($this->status['recording_mode']) {
        $problems[] = 'Recording_mode is ON';
    }
    ?>

<br/>
Issues which prevents launch: <?= implode(' and ', $problems) ?>

<? } else { ?>

<? } ?>

<br/>
<br/>

<form action="<?=$this->link(
    array(
        'controller' => 'collect',
        'action' => 'start',
    )
)?>" method="post">

    Select previously recorded data:

    <table cellpadding="0" cellspacing="0" class="tbl_list">
        <thead>
        <th>&nbsp;</th>
        <th>Namespace</th>
        <th>Description</th>
        <th>Log count</th>
        <th>Timestamp</th>
        <th>Actions</th>
        </thead>
        <tbody>

        <?foreach ($this->namespaces as $index => $ns) { ?>
        <tr<?=$index & 1 ? ' class="even"' : ''?>>
            <td width="20px">
                <input type="radio" name="namespace_source" value="<?=$ns['name']?>"/>
            </td>
            <td width="100px">
                <span><?=$ns['name']?></span>
            </td>
            <td>
                <pre><?=$ns['data']['description']?></pre>
            </td>
            <td width="80px">
                <span><?=$ns['data']['file_count']?></span>
            </td>
            <td width="130px">
                <span><?=date('M d, Y H:i', $ns['data']['timestamp'])?></span>
            </td>
            <td width="10px">
                <a href="<?=$this->link(array(
                        'controller' => 'edit',
                        'action' => 'default',
                        'namespace' => $ns['name']
                    )) ?>">Edit</a>
            </td>
        </tr>
            <? }?>

        </tbody>
    </table>

    <div class="collect_start">

        <?
        $redStar = '<span title="The field is required" class="required">*</span>';
        ?>

        <div class="part1">
            <input type="checkbox" name="test_mode" checked="checked"/> Start in test mode and use iteration limit:
            <input class="it_limit" type="text" name="test_count" value="1"/><br/>

            <?=$redStar?>Results namespace (directory): <input type="text" name="namespace_target" value=" - "/>
        </div>

        <div class="part2">
            Description: <textarea name="description"></textarea>

            <input class="button" type="submit" value="Start process"/>
        </div>
        <div style="clear:both"></div>
    </div>

</form>
