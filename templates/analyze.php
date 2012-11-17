List of namespaces. Open detailed view clicking on the &quot;details&quot; link:

<table cellpadding="0" cellspacing="0" class="tbl_list">
    <thead>
    <th>#</th>
    <th>Namespace</th>
    <th>Source</th>
    <th>Description</th>
    <th>Log count</th>
    <th>Timestamp</th>
    <th>&nbsp;</th>
    </thead>
    <tbody>

    <?foreach ($this->namespaces as $index => $ns) {
        $tdAttr = $ns['data']['is_regular'] ? ' class="regular"' : '';
        ?>
    <tr<?=$index & 1 ? ' class="even"' : ''?>>
        <td width="20px"<?=$tdAttr?>>
            <?=$index + 1?>
        </td>
        <td width="100px"<?=$tdAttr?>>
            <span><?=$ns['name']?></span>
        </td>
        <td width="100px"<?=$tdAttr?>>
            <span><?=$ns['data']['source_namespace']?></span>
        </td>
        <td<?=$tdAttr?>>
            <span><?=$ns['data']['description']?></span>
        </td>
        <td width="80px"<?=$tdAttr?>>
            <span><?=$ns['data']['file_count']?></span>
        </td>
        <td width="130px"<?=$tdAttr?>>
            <span><?=date('M d, Y H:i', $ns['data']['timestamp'])?></span>
        </td>
        <td width="130px"<?=$tdAttr?>>
            <a href="<?=$this->link(
                array(
                    'controller' => 'analyze',
                    'action' => 'report',
                    'namespace' => $ns['name'],
                )
            )?>">details</a>
        </td>
    </tr>
        <? }?>

    </tbody>
</table>
