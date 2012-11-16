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

        if ($namespace_source) {
            if ($namespace_target) {
                if ($this->adjustEnvironment()) {
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

                        $collector->start(array($this, 'resultsCollectorProgressExtraOutput'));

                    } catch (Exception $e) {
                        $this->addMessage('error', $e->getMessage());
                    }

                    $tpl = new Template('collect_log', '');
                    $tpl->errors = $this->getMessages('error');
                    $tpl->namespace_target = $namespace_target;
                    App::getModel('resources')->setResultNamespaceInfo($namespace_target);
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

    public function resultsCollectorProgressExtraOutput(array $data)
    {
        echo "{$data['step_name']}: {$data['description']} [{$data['step']}%]<br/>";
    }

    protected function adjustEnvironment()
    {
        $errors = false;
        // turn on agent, turn off recording mode and turn off regular profiler
        $maintainAgentModel = App::getModel('maintainAgent');
        $status = $maintainAgentModel->getStatus();
        if (!$status['htaccess_changed']) {
            if (!$maintainAgentModel->setAgentEnabled(true)) {
                $this->addMessage('error', 'Can not turn on agent');
                $errors = true;
            }
        }
        if ($status['is_regular_profiler_on']) {
            if (!$maintainAgentModel->setXhpEnabled(false)) {
                $this->addMessage('error', 'Can not turn off regular profiler');
                $errors = true;
            }
        }
        if ($status['recording_mode']) {
            if (!$maintainAgentModel->setRecordingModeEnabled(false)) {
                $this->addMessage('error', 'Can not turn off recording_mode');
                $errors = true;
            }
        }
        return !$errors;
    }
}

