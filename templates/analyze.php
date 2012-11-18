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
        <td width="80px"<?=$tdAttr?>>
            <a href="<?=$this->link(
                array(
                    'controller' => 'analyze',
                    'action' => 'report',
                    'namespace' => $ns['name'],
                )
            )?>">details</a>

            <a href="javascript:void(0)" style="margin-left: 10px;" onclick="diffPrompt(this)">diff</a>
        </td>
    </tr>
        <? }?>

    </tbody>


    <script type="text/javascript">
        function diffPrompt(el) {
            if (document.getElementById('popup')) {
                return false;
            }
            var tr = el.parentNode.parentNode;
            var tbody = tr.parentNode;
            var trs = tbody.getElementsByTagName('tr');
            var tds = tr.getElementsByTagName('td');
            var source = tds[2].getElementsByTagName('span')[0].innerHTML;
            var sourceLogCount = tds[4].getElementsByTagName('span')[0].innerHTML;
            var sourceNs = tds[1].getElementsByTagName('span')[0].innerHTML;
            var namespaces = [], ns;
            for (var i = 0; i < trs.length; i++) {
                if (trs[i] != tr) {
                    tds = trs[i].getElementsByTagName('td');
                    ns = tds[1].getElementsByTagName('span')[0].innerHTML;
                    if (tds[2].getElementsByTagName('span')[0].innerHTML == source) {
                        namespaces.push([ns, tds[4].getElementsByTagName('span')[0].innerHTML]);
                    }
                }
            }
            if (namespaces.length == 0) {
                alert('Results from `' + source + '` can not be compared with any others here');
            } else {

                var linkPart = '<?=$this->link(
                    array(
                        'controller' => 'diff',
                        'action' => 'namespaces',
                        'source' => '',
                    )
                )?>' + sourceNs + '';

                var popup = dce('div', {'className':'d_popup', 'id':'popup'}), link;
                popup.appendChild(dce('div', {'innerHTML':'Compare `' + sourceNs + '` (' + sourceLogCount + ' logs) with:'}));

                var form = dce('form', {'method':'post', 'action':linkPart});

                for (var i = 0; i < namespaces.length; i++) {
                    link = '<label>' + (i + 1) + '. <input type="radio" name="target" value="' + namespaces[i][0] + '" /> '
                            + namespaces[i][0] + ' (' + namespaces[i][1] + ' logs)</label>';
                    form.appendChild(dce('div', {'innerHTML':link}));
                }

                form.appendChild(dce('div', {'innerHTML':'Function names as additional criteria:'}));
                form.appendChild(dce('textarea', {'name':'functions'}));

                var button = '<input type="submit" value="Send data" />';
                button += '<input type="button" value="Close" '
                        + 'onclick="document.body.removeChild(this.parentNode.parentNode.parentNode)" />';
                form.appendChild(dce('div', {'innerHTML':button, 'className':'submit'}));

                popup.appendChild(form);
                popup.style.top = (document.body.scrollTop + 200) + 'px';
                document.body.appendChild(popup);
            }
        }

        function dce(s, a) {
            var d = document.createElement(s);
            a = a ? a : {};
            for (var i in a) {
                if (typeof(a[i]) == 'object') {
                    for (var j in a[i]) {
                        d[i][j] = a[i][j];
                    }
                    ;
                } else {
                    d[i] = a[i];
                }
                ;
            }
            ;
            return d;
        }
        ;

    </script>
</table>
