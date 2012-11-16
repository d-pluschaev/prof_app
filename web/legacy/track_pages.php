<?
$sugar_credentials = array(
    'login' => 'admin',
    'pass'  => 'asdf',
);


$uri = str_replace('xhprof/track_pages.php', 'index.php?', 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);


$detailview_ids = array(
    'opportunity' => 'f639090e-5d7a-23ed-c8ea-4fbfa508a5a8',
    'rli'         => '2c61e0b3a3fdadbd4ca5',
    'client'      => 'C1B1A1S',
    'contact'     => '13930b1b600d337',
    'call'        => 'edd1e5e5-e3d9-4c11-1bb3-4fbfa9e0067e',
);

// name => url OR name => array(url,cookies)
$pages = array(
    'Home Page'             =>
    'module=Users&action=Authenticate&return_module=Users&return_action=Login&'
    . 'cant_login=&login_module=&login_action=&login_record=&login_token=&user_name='
    . $sugar_credentials['login'] . '&user_password=' . $sugar_credentials['pass'] . '&login_language=en_us&Login=Log+In',
    'OpportunitiesListView' => 'module=Opportunities&action=index',
    'OpportunityDetailView' => array(
        'url'     => 'module=Opportunities&offset=1&stamp=1338381342028206900&return_module=Opportunities&action=DetailView&record=' . $detailview_ids['opportunity'],
        // expand collapsed panels
        'cookies' => array('Opportunities_divs' => 'opportun_revenuelineitems_v%3D%23contacts_v%3D%23history_v%3D%23'),
    ),
    'RLIDetailView'      => 'module=ibm_RevenueLineItems&action=DetailView&record=' . $detailview_ids['rli'],
    'ClientsListView'    => 'module=Accounts&action=index',
    'ClientsDetailView'  => 'module=Accounts&action=DetailView&record=' . $detailview_ids['client'],
    'ContactsListView'   => 'module=Contacts&action=index',
    'ContactsDetailView' => 'module=Contacts&offset=1&stamp=1338381641017236400&return_module=Contacts&action=DetailView&record=' . $detailview_ids['contact'],
    'CallsListView'      => 'module=Calls&action=index&favorites_only_basic=1',
    'CallsDetailView'    => 'module=Calls&offset=1&stamp=1338381729075547400&return_module=Calls&action=DetailView&record=' . $detailview_ids['call'],
    'TasksListView'      => 'module=Tasks&action=index&favorites_only_basic=1',
    'MeetingsListView'      => 'module=Meetings&action=index&favorites_only_basic=1',
    'MeetingsDetailView'    => 'module=Meetings&offset=1&stamp=1340811001079345500&return_module=Meetings&action=DetailView&record=' . $detailview_ids['meeting'],
);

ini_set('error_reporting', 'E_ALL & ~E_NOTICE');
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);

$timer = microtime(1);

ob_start();
///////////////////////////
$profile_namespace_file = dirname(__FILE__) . '/../profiler.nsp';
$old_namespace = is_file($profile_namespace_file) ? file_get_contents($profile_namespace_file) : 'no-profiler-file';


$cnt = 0;
?>
<html>
    <head>
        <title>Page profiling</title>
        <style>
            table{border-right:1px solid silver;border-bottom:1px solid silver;font-size:14px;}
            th,td{border-left:1px solid silver;border-top:1px solid silver;padding:4px;}
        </style>
    </head>	
    <body>

        <?
        $link = str_replace('index.php?', 'xhprof/last_result.html', $uri);
        ?>
        <p>Last results will be available here: <a href="<?= $link ?>"><?= $link ?></a></p>

        <table cellpadding="0" cellspacing="0">
            <thead>
            <th>Page Title</th>
            <th>Request URI</th>
            <th>Content on Page</th>
            <th>HTML loading time</th>
            <th>SQL summary time</th>
            <th>Sum of SQL queries</th>
            <th>Number of processes</th>
            <th>SQL time on each process</th>
            <th>SQL queries on each process</th>
            <th>Link on profile</th>
        </thead>
        <tbody>
            <?
            if (is_file('/tmp/xhprof_tpc_' . getmypid() . '.txt'))
            {
                unlink('/tmp/xhprof_tpc_' . getmypid() . '.txt');
            }

            foreach ($pages as $k => $v)
            {
                $cnt++;
                $namespace = 'page_track_' . getmypid() . '_' . $cnt;

                // remove old files
                foreach (glob(dirname(__FILE__) . '/profiler_files/*.' . $namespace) as $file)
                {
                    unlink($file);
                }

                file_put_contents($profile_namespace_file, $namespace);
                $query = is_array($v) ? $v['url'] : $v;
                $cookies = is_array($v) && isset($v['cookies']) ? $v['cookies'] : array();
                $parsed = _call($uri . $query, $cookies, $cnt);
                $data = _get_call_info($parsed['time'], $namespace);
                ?>
                <tr>
                    <td><?= $k ?></td>
                    <td style="font-size:10px"><?= $query ?></td>
                    <td style="font-size:10px"><?=
                    ($parsed['listview'] > -1 ? 'Listview: ' . $parsed['listview'] . ' rows' : (
                                    is_array($parsed['subpanels']) ? '<div title="' . implode("\r\n", array_keys($parsed['subpanels'])) . '">Subpanels: '
                                            . sizeof($parsed['subpanels']) . ', rows: ' . array_sum($parsed['subpanels']) . '</div>' : 'Unknown'
                                    ))
                    ?></td>
                    <td><?= $data['page_time'] ?></td>
                    <td><?= round($data['sql_time_total'], 2) ?></td>
                    <td><?= $data['sql_queries_total'] ?></td>
                    <td><?= $data['processes'] ?></td>
                    <td><?= implode('|', $data['sql_time']) ?></td>
                    <td><?= implode('|', $data['sql_queries']) ?></td>
                    <td><?= implode(' | ', $data['links']) ?></td>
                </tr>
    <?
}
?>
        </tbody>
    </table>
    <div>Time total: <?= round(microtime(1) - $timer, 3) ?>s</div>

<body>
</html>
<?
if ($old_namespace != 'no-profiler-file')
{
    file_put_contents($profile_namespace_file, $old_namespace);
}
else
{
    unlink($profile_namespace_file);
}
//////////////////////
$contents = ob_get_contents();
ob_end_flush();

file_put_contents(dirname(__FILE__) . '/last_result.html', $contents);

function _get_call_info($page_time, $namespace)
{
    $files = glob(dirname(__FILE__) . '/profiler_files/*.' . $namespace);
    $sql_time = $sql_queries = array();
    $prof_links = array();
    foreach ($files as $file)
    {
        $sql_data = unserialize(file_get_contents($file . '.sql'));
        $sql_time[] = round($sql_data['summary_time'], 2);
        $sql_queries[] = sizeof($sql_data['sql']);

        $pi = pathinfo($file);
        $prof_links[] = '<a href="index.php?run=' . $pi['filename'] . '&source=' . $pi['extension'] . '">see</a>';
    }
    return array(
        'page_time'         => $page_time > -1 ? $page_time . 's' : 'FAIL',
        'processes'         => sizeof($files),
        'sql_time_total'    => array_sum($sql_time),
        'sql_queries_total' => array_sum($sql_queries),
        'sql_time'          => $sql_time,
        'sql_queries'       => $sql_queries,
        'links'             => $prof_links,
    );
}

function _call($uri, array $cookies, $cnt)
{
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $uri);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:12.0) Gecko/20100101 Firefox/12.0');

    curl_setopt($ch, CURLOPT_TIMEOUT, 90);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_AUTOREFERER, 1);

    // add custom cookies to existing cookies
    $cust_cookies = '';
    foreach ($cookies as $k => $v)
    {
        $cust_cookies.="{$_SERVER['HTTP_HOST']}	FALSE	/	FALSE	0	$k	$v\n";
    }
    if ($cust_cookies && is_file('/tmp/xhprof_tpc_' . getmypid() . '.txt'))
    {
        $fdata = file_get_contents('/tmp/xhprof_tpc_' . getmypid() . '.txt');
        file_put_contents('/tmp/xhprof_tpc_' . getmypid() . '.txt', $fdata . "\n" . $cust_cookies);
    }

    curl_setopt($ch, CURLOPT_COOKIEJAR, '/tmp/xhprof_tpc_' . getmypid() . '.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, '/tmp/xhprof_tpc_' . getmypid() . '.txt');

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $rtime = -1;
    if ($http_code == 200)
    {
        $pos = strpos($response, '<span id="responseTime">');
        if ($pos)
        {
            $pos2 = strpos($response, '</span>', $pos);
            $rtime = floatval(substr($response, $pos + 24, $pos2 - $pos - 24));

            // parse subpanels or listviews
            $buf = explode('<div id="list_subpanel_', $response);
            // if subpanels exists
            $subpanels = $listview = -1;
            if (sizeof($buf) > 1)
            {
                $subpanels = array();

                foreach ($buf as $index => $html)
                {
                    if ($index)
                    {
                        // sp name
                        $spname = substr($html, 0, strpos($html, '"'));

                        // sp rows
                        $spro = explode('class="oddListRowS1"', $html);
                        $spre = explode('class="evenListRowS1"', $html);

                        $subpanels[$spname] = sizeof($spro) + sizeof($spre) - 2;
                    }
                }
            }
            else
            {
                // if listview
                $buf = explode('class="list view"', $response);
                $buf = sizeof($buf) > 1 ? $buf : explode("class='list view'", $response);

                if (sizeof($buf) > 1)
                {
                    $lvro = explode('oddListRowS1', $buf[1]);
                    $lvre = explode('oddListRowS1', $buf[1]);
                    $listview = sizeof($lvro) + sizeof($lvre) - 2;
                }
            }
        }
    }
    //print_r($subpanels);
    //file_put_contents(dirname(__FILE__).'/profiler_files/'.$cnt.'.html',$response);
    return array(
        'time'      => $rtime,
        'subpanels' => $subpanels,
        'listview'  => $listview,
    );
}