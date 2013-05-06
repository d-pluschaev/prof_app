<?php

class ControllerCollect extends ControllerDefault
{
    public function actionDefault()
    {
        $tpl = new Template('collect');
        $tpl->title = 'Profiler Web Interface: Collect Profiler Data';
        $tpl->breadcrumb = 'Collect Profiler Data';

        $tpl->status = App::getModel('maintainAgent')->getStatus();

        $tpl->namespaces = App::getModel('resources')->getRequestNamespaces();

        return $tpl;
    }

    public function actionStart()
    {
        $namespace_source = App::filterText(Request::get('namespace_source'), '_');
        $namespace_target = App::filterText(Request::get('namespace_target'), '_');
        $test_mode = Request::get('test_mode');
        $test_count = Request::get('test_count');
        $description = Request::get('description');
        $response = App::filterText(Request::get('response_type'));

        if ($namespace_source) {
            if ($namespace_target) {

                $noErrors = $this->applyDependencyErrorIfExists(
                    array(
                        'agent' => 1,
                        'recording_mode' => 0,
                        'regular_profiler' => 0,
                    )
                );

                if ($noErrors) {
                    // create destination namespace
                    App::getModel('resources')->setResultNamespaceInfo(
                        $namespace_target,
                        array(
                            'source_namespace' => $namespace_source,
                            'description' => $description,
                            'test_mode' => $test_mode,
                            'test_count' => $test_count,
                        )
                    );

                    // update agent config
                    App::getModel('maintainAgent')->setAgentConfigValue(
                        array(
                            'collecting_directory' => App::cfg('path_request_results') . $namespace_target,
                        )
                    );

                    // main process
                    $collector = App::getModel('resultsCollector');
                    try {
                        $collector->prepare(
                            $namespace_source,
                            $namespace_target,
                            $test_mode,
                            $test_count
                        );

                        $collector->start(
                            $response == 'json' ? null : array($this, 'resultsCollectorProgressExtraOutput')
                        );

                        App::getModel('resources')->setResultNamespaceInfo($namespace_target);

                    } catch (Exception $e) {
                        $this->addMessage('error', $e->getMessage());
                    }

                    $errors = $this->getMessages('error');

                    if ($response == 'json') {
                        return array(
                            'namespace_target' => $namespace_target,
                            'finished_correctly' => empty($errors),
                            'errors' => $errors,
                        );
                    }

                    $tpl = new Template('collect_log', '');
                    $tpl->errors = $errors;
                    $tpl->namespace_target = $namespace_target;
                    return $tpl;
                }
            } else {
                $this->addMessage('error', 'Destination namespace should be valid directory name');
            }
        } else {
            $this->addMessage('error', 'Source namespace was not selected');
        }
        App::forwardSafe(null, 'default');
    }

    public function actionRemove()
    {
        $namespace = App::filterText(Request::get('namespace'), '_');
        $response = App::filterText(Request::get('response_type'));
        $error = '';
        try {
            App::getModel('resources')->removeResultNamespace($namespace);
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        return array(
            'code' => empty($error) ? 0 : 1,
            'error' => $error,
        );
    }

    public function resultsCollectorProgressExtraOutput(array $data)
    {
        if ($data['step'] == 5) {
            echo "{$data['step_name']}: {$data['description']} [{$data['step']}%] \n";
        } else {
            $time = round($data['response']['time'] * 1000);
            echo "[{$data['step']}%]	{$data['response']['http_code']}	{$time}	{$data['log']['name']}    \n";
        }
        
    }

    protected function applyDependencyErrorIfExists(array $dependencies)
    {
        $result = true;
        $flags = array('OFF', 'ON');
        $status = App::getModel('maintainAgent')->getStatus();
        foreach ($dependencies as $dep => $val) {
            switch ($dep) {
                case 'agent':
                    if ((bool)$status['htaccess_changed'] !== (bool)$val) {
                        $this->addMessage('error', 'Agent should be turned ' . $flags[(int)$val]);
                        $result = false;
                    }
                    break;
                case 'regular_profiler':
                    if ((bool)$status['is_regular_profiler_on'] !== (bool)$val) {
                        $this->addMessage('error', 'Regular profiler should be turned ' . $flags[(int)$val]);
                        $result = false;
                    }
                    break;
                case 'recording_mode':
                    if ((bool)$status['recording_mode'] !== (bool)$val) {
                        $this->addMessage('error', 'Recording mode should be turned ' . $flags[(int)$val]);
                        $result = false;
                    }
                    break;
                default:
                    break;
            }
        }

        return $result;
    }
}

