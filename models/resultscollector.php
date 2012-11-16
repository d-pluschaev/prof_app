<?php

class ModelResultsCollector
{
    protected $sourceNamespace;
    protected $logDirectory;
    protected $resultDirectory;
    protected $testMode;

    protected $logs;
    protected $progress_callback;

    public function prepare($sourceNamespace, $targetNamespace, $testMode, $testCount)
    {
        $this->sourceNamespace = $sourceNamespace;
        $this->logDirectory = App::cfg('path_request_logs') . $sourceNamespace;
        $this->resultDirectory = App::cfg('path_request_results') . $targetNamespace;
        $this->testMode = $testMode && $testCount ? $testCount : 0;
    }

    public function start($progress_callback)
    {
        $this->progress_callback = $progress_callback;

        $this->logs = $this->getLogs();

        $step = 5;
        $this->progress(array('step_name' => 'process requests', 'description' => 'send requests'
            . ($this->testMode ? ', test launch count: ' . $this->testMode : ''),
            'step' => $step));

        foreach ($this->logs as $index => $log) {
            $log = $this->prepareRequestData($log);
            $this->processRequest($log);
            $this->progress(
                array(
                    'step_name' => 'process requests',
                    'description' => 'send request ' . ($index + 1) . '/' . sizeof($this->logs),
                    'step' => floor((($index + 1) / sizeof($this->logs)) * (100 - $step)) + ($step - 1),
                )
            );
            if ($this->testMode && $index >= $this->testMode - 1) {
                $this->progress(
                    array(
                        'step_name' => 'finish',
                        'description' => 'Iteration limit was reached (' . $this->testMode . '), exit',
                        'step' => 100,
                    )
                );
                return;
            }
        }
    }

    protected function prepareRequestData(array $log)
    {
        $log['uid'] = pathinfo($log['file'], PATHINFO_FILENAME);
        $log['req']['debug_agent_result_file'] = $log['uid'] . '.xhp';

        $log['source_namespace'] = $this->sourceNamespace;
        unset($log['req']['PHPSESSID']);
        if (isset($log['request_headers']['Cookie'])) {
            $log['request_headers']['Cookie'] =
                str_replace('PHPSESSID', 'RPSID', $log['request_headers']['Cookie']);
        }
        unset($log['cookies']['PHPSESSID']);
        $log['req']['debug_agent_uid'] = App::cfg('debug_agent_uid');

        return $log;
    }

    protected function processRequest(array $logData)
    {
        $respData = $this->performHTTPRequest($logData);
        $this->saveResponse($logData, $respData);
    }

    protected function saveResponse(array $logData, array $responseData)
    {
        $html = $responseData['response'];
        unset($responseData['response']);
        unset($logData['req']['debug_agent_result_file']);
        unset($logData['req']['debug_agent_uid']);
        $logData['timestamp'] = microtime(1);
        $logData = array_merge($logData, $responseData);

        // save html
        $htmlFile = "{$this->resultDirectory}/{$logData['uid']}.html";
        file_put_contents($htmlFile, $html);

        // save general data
        $dataFile = "{$this->resultDirectory}/{$logData['uid']}.data";
        file_put_contents($dataFile, serialize($logData));
    }

    protected function getLogFilesList()
    {
        return glob($this->logDirectory . '/*.log');
    }

    protected function getLogs()
    {
        $out = array();
        $files = $this->getLogFilesList();
        foreach ($files as $file) {
            $buf = (array)@unserialize(file_get_contents($file));
            $buf['file'] = $file;
            $out[] = $buf;
        }
        return $out;
    }

    protected function performHTTPRequest(array $req_data)
    {
        $ch = curl_init();

        $url = App::cfg('maintaining_project_url');
        $host = parse_url($url, PHP_URL_HOST);

        // prepare POST
        $post = http_build_query((array)$req_data['req']);

        // URL
        curl_setopt($ch, CURLOPT_URL, $url);

        // standard options
        curl_setopt($ch, CURLOPT_TIMEOUT, 90);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

        // headers
        // remove session
        curl_setopt($ch, CURLOPT_HTTPHEADER, $req_data['request_headers']);

        // cookies
        $cookie_file = '/tmp/tmp_banzai_cookies_' . getmypid() . '.txt';

        // add custom cookies to existing cookies
        $custom_cookies = '';
        foreach ((array)$req_data['cookies'] as $k => $v) {
            $custom_cookies .= "{$host}	FALSE	/	FALSE	0	$k	$v\n";
        }
        if ($custom_cookies && is_file($cookie_file)) {
            $fdata = file_get_contents($cookie_file);
            file_put_contents($cookie_file, $fdata . "\n" . $custom_cookies);
        }

        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);

        $time = microtime(1);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $html_footer_time = null;
        // get time from HTML footer if exists
        if ($http_code == 200) {
            $pos = strpos($response, '<span id="responseTime">');
            if ($pos) {
                $pos2 = strpos($response, '</span>', $pos);
                $html_footer_time = floatval(substr($response, $pos + 24, $pos2 - $pos - 24));
            }
        }

        return array(
            'http_code' => $http_code,
            'response' => $response,
            'time' => microtime(1) - $time,
            'html_footer_time' => $html_footer_time,
        );
    }

    protected function progress(array $data)
    {
        static $progress = array(
            'step_name' => null,
            'step' => null,
            'max_steps' => null,
            'description' => null,
        );
        foreach ($progress as $k => $v) {
            if (isset($data[$k])) {
                $progress[$k] = $data[$k];
            }
        }
        $func = $this->progress_callback;
        if (is_callable($func)) {
            call_user_func_array($func, array($progress));
        }
    }
}
