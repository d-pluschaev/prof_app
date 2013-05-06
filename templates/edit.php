<?php /** @var $this Template */ ?>
<table class="tbl_list" cellpadding="0" cellspacing="0" >
    <thead>
        <tr>
            <th style="width:10px">#</th>
            <th style="width:50px">Log File Name</th>
            <th>Request</th>
            <th style="width:60px">Order</th>
            <th style="width:10px">Actions</th>
        </tr>
    </thead>

    <tbody>
        <?php   
                $count = count($this->logs);
        ?>
        <?php for ($i = 0; $i < $count; $i++) {
                    $log = $this->logs[$i];
            ?>
            <tr>
                <td><?= $i+1 ?></td>
                <td><?= $log['base_name']; ?></td>
                <td>
                    <a name="<?= $log['base_name']; ?>"></a>
                    <?= getRequestCellHTML($log) ?>
                </td>
                <td>
                    <? if (isset($this->logs[$i-1])) { ?>
                        <a href="<?= $this->link(array(
                                'controller' => 'edit',
                                'action' => 'swap',
                                'namespace' => $this->namespace,
                                'log1' => $this->logs[$i-1]['base_name'],
                                'log2' => $log['base_name'],
                            ))?>#<?= $log['base_name']; ?>">Up</a>
                    <? } ?>
                    <? if (isset($this->logs[$i+1])) { ?>
                        <a href="<?= $this->link(array(
                                'controller' => 'edit',
                                'action' => 'swap',
                                'namespace' => $this->namespace,
                                'log1' => $log['base_name'],
                                'log2' => $this->logs[$i+1]['base_name'],
                            ))?>#<?= $log['base_name']; ?>">Down</a>
                    <? } ?>
                </td>
                <td>
<!--                    <a onclick="return false;" href="#" style="white-space: nowrap;">Copy To ...</a>-->
                    <a onclick="showRenameLogEntryDialog('<?=$log['base_name']  ?>'); return false;" href="javascript:void(0)" style="white-space: nowrap;">Rename ...</a>
                    <a onclick="showMoveLogEntryDialog('<?=$log['base_name']  ?>'); return false;" href="javascript:void(0)" style="white-space: nowrap;">Move To ...</a>
                    <a onclick="return confirm('Are you sure?')" href="<?=$this->link(array(
                            'controller' => 'edit',
                            'action'     => 'delete',
                            'namespace'  => $this->namespace,
                            'log' => $log['base_name']
                        )) ?><?= isset($this->logs[$i-1]) ? '#'.$this->logs[$i-1]['base_name'] : ''  ?>">Delete</a>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<div style="display:none">
    
    <div id="dialog-move-log" title="Move log entry into another namespace">
        <form action="<?= $this->link(array(
                                            'controller' => 'edit',
                                            'action' => 'move',
                                            'namespace_source' => $this->namespace,
                                        )) ?>" 
              method="post" name="move_log_entry">
            <input type="hidden" name="log" value="" />
            Move to 
            <select name="namespace_target">
                <option value="">-</option>    
                <?php foreach ($this->namespaces as $namespace) {
                        if ($namespace['name'] == $this->namespace) {
                            continue;
                        } ?>
                <option value="<?= $namespace['name'] ?>"><?= $namespace['name'] ?></option>
                <?php } ?>
            </select>
        </form>
    </div>
    
    <div id="dialog-rename-log" title="Rename log entry">
        <form action="<?= $this->link(array(
                                        'controller' => 'edit',
                                        'action' => 'rename',
                                        'namespace' => $this->namespace,
                                    )) ?>" 
              method="post" name="rename_log_entry">
            <input type="hidden" name="log" value="" />
            <input type="text" name="name" value="" />
        </form>
    </div>
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
    
    function showMoveLogEntryDialog(entryName) {
        document.forms.move_log_entry.elements.log.value = entryName;
        $('#dialog-move-log').dialog({
            resizeable:false,
            height:140,
            width:300,
            modal: true,
            buttons: {
                'Move' : function() {
                    $(this).dialog("close");
                    document.forms.move_log_entry.submit();
                },
                Cancel : function() {
                    $(this).dialog("close");
                }
            }
        });
    }
    
    function showRenameLogEntryDialog(entryName) {
        document.forms.rename_log_entry.elements.log.value = entryName;
        $('#dialog-rename-log').dialog({
            resizeable:false,
            height:140,
            width:300,
            modal: true,
            buttons: {
                'Rename' : function() {
                    $(this).dialog("close");
                    document.forms.rename_log_entry.action += '#' + entryName;
                    document.forms.rename_log_entry.submit();
                },
                Cancel : function() {
                    $(this).dialog("close");
                }
            }
        });
    }
</script>

<?php


function getRequestCellHTML(array $row)
{
    $full_req = '<div style="display:none"  id="b_' . $row['base_name'] . '">'
        . '<pre style="width:99%;">'
        . (isset($row['name']) ? "<span style='text-decoration:underline;'><b>{$row['name']}</b></span>" : '')
        . printr($row['req']) . '</pre>'
        . '<a href="javascript:toggleReq(\'' . $row['base_name'] . '\')">Show short</a> '
        . '<div>';

    $short_req = '<div      id="s_' . $row['base_name'] . '">'
        . '<pre style="width:99%;">'
        . (isset($row['name']) ? "<span style='text-decoration:underline;'><b>{$row['name']}</b></span>" : '')
        . printr(
        array(
            'module' => isset($row['req']['module']) ? $row['req']['module'] : 'n/a',
            'action' => isset($row['req']['action']) ? $row['req']['action'] : 'n/a',
        )
    )
        . '</pre>'
        . '<a href="javascript:toggleReq(\'' . $row['base_name'] . '\',1)">Show full</a> '
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