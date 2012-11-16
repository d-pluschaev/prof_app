<?php

// Debug agent, should be used as auto prepend file

$configFile = dirname(__FILE__) . '/auto_prepend_conf.srz';
$config = is_file($configFile) ? @unserialize(file_get_contents($configFile)) : '';

// if config exists
if (!empty($config)) {

    // 1. recording mode
    if ($config['recording_mode']) {
        $uid = microtime(1);
        $GLOBALS['debug_agent_result_file'] = "{$config['recording_directory']}/$uid.log";

        // save primary data
        @file_put_contents($GLOBALS['debug_agent_result_file'], serialize(profilerAgentGetRequestData()));

        register_shutdown_function('profilerAgentShutdownHandlerForRecordingMode');

        // 2. collect mode
    } elseif (
        isset($_REQUEST['debug_agent_uid'])
        && $_REQUEST['debug_agent_uid'] == $config['agent_uid']
        && isset($_REQUEST['debug_agent_result_file'])
        && function_exists('xhprof_enable')
    ) {
        $GLOBALS['debug_agent_result_file'] = "{$config['collecting_directory']}/{$_REQUEST['debug_agent_result_file']}";

        register_shutdown_function('profilerAgentShutdownHandlerForCollectingMode');
        xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY, array('profilerAgentShutdownHandlerForCollectingMode'));

        // 3. regular profiler is on
    } elseif ($config['is_regular_profiler_on'] && function_exists('xhprof_enable')) {

        $file_uid = ''
            . (isset($_REQUEST['entryPoint']) ? $_REQUEST['entryPoint'] : 'noep')
            . '_' . (isset($_REQUEST['module']) ? $_REQUEST['module'] : 'nomodule')
            . '_' . (isset($_REQUEST['action']) ? $_REQUEST['action'] : 'noaction')
            . '_' . microtime(1);

        // save primary data
        $GLOBALS['debug_agent_result_data_file'] = "{$config['regular_profiler_directory']}/$file_uid.data";
        $data = profilerAgentGetRequestData();
        $data['uid'] = $file_uid;
        $data['file'] = '';
        $data['source_namespace'] = '';
        $data['http_code'] = '';
        $data['time'] = 0;
        $data['timestamp'] = microtime(1);
        @file_put_contents($GLOBALS['debug_agent_result_data_file'], serialize($data));

        // prepare
        $GLOBALS['debug_agent_result_file'] = "{$config['regular_profiler_directory']}/$file_uid.xhp";
        register_shutdown_function('profilerAgentShutdownHandlerForRegularProfilingMode');
        xhprof_enable(XHPROF_FLAGS_CPU | XHPROF_FLAGS_MEMORY, array('profilerAgentShutdownHandlerForRegularProfilingMode'));
    }
}

/**
 * Shutdown handler for recording mode
 */
function profilerAgentShutdownHandlerForRecordingMode()
{
    profilerAgentSaveAdditionalRequestData($GLOBALS['debug_agent_result_file']);
}

/**
 * Shutdown handler for collecting mode
 */
function profilerAgentShutdownHandlerForCollectingMode()
{
    ini_set('memory_limit', '1024M'); // TODO: related to platform, solve
    $xhprof_data = xhprof_disable();
    @file_put_contents($GLOBALS['debug_agent_result_file'], serialize($xhprof_data));
}

/**
 * Shutdown handler for regular profiling mode
 */
function profilerAgentShutdownHandlerForRegularProfilingMode()
{
    $endTimestamp = microtime(true) - $GLOBALS['startTime'];

    // save xhprof results
    ini_set('memory_limit', '1024M'); // TODO: related to platform, solve
    $xhprof_data = xhprof_disable();
    @file_put_contents($GLOBALS['debug_agent_result_file'], serialize($xhprof_data));

    // save additional data to data file
    $data = array(
        'html_footer_time' => $endTimestamp,
        'time' => microtime(true) - $GLOBALS['startTime'],
    );
    @profilerAgentSaveAdditionalRequestData($GLOBALS['debug_agent_result_data_file'], $data);
}

/**
 * Get primary request data
 */
function profilerAgentGetRequestData()
{
    return array(
        'req' => $_REQUEST,
        'cookies' => $_COOKIE,
        'request_headers' => apache_request_headers(),
    );
}

/**
 * Get primary request data
 */
function profilerAgentSaveAdditionalRequestData($file, array $additionalData = array())
{
    $data = @unserialize(file_get_contents($file));
    $data['finished'] = 1;
    $data['response_headers'] = headers_list();
    $data = array_merge($data, $additionalData);
    @file_put_contents($file, serialize($data));
}

