<?php

class ControllerSettings extends ControllerDefault
{
    public function actionDefault()
    {
        $tpl = new Template('settings');
        $tpl->title = 'Profiler Web Interface: Settings';
        $tpl->breadcrumb = 'Analyze Collected Data - Settings';

        $tpl->status = App::getModel('maintainAgent')->getStatus();

        $tpl->globalSettings=array(
            'maintaining_application_url'=>App::cfg('maintaining_project_url'),
            'maintaining_application_web_dir'=>App::cfg('maintaining_project_web_dir'),
        );

        return $tpl;
    }

    public function actionSaveSettings()
    {
        $url=App::filterText(Request::get('maintaining_application_url'), '._:\/');
        $path=App::filterText(Request::get('maintaining_application_web_dir'), '_\/');

        $config = App::$instance->dynamicConfig;

        if($url && $path){
            $config['maintaining_application_url'] = $url;
            $config['maintaining_application_web_dir'] = $path;

            if(file_put_contents(App::cfg('path_dynamic_config'), serialize($config)) !== false){
                $this->addMessage('message', 'Settings has been saved');
            }else{
                $this->addMessage('error', 'Unable to save config values, permissions or path error');
            }
        }else{
            $this->addMessage('error', 'Some required fields are invalid');
        }
        App::forwardSafe(null, 'default');
    }

    public function actionToggleAgent()
    {
        $status = App::getModel('maintainAgent')->getStatus();
        ;
        if (!$status['htaccess_changed']) {
            if (!App::getModel('maintainAgent')->setAgentEnabled(true)) {
                $this->addMessage('error', 'Can not turn on agent');
            } else {
                $this->addMessage('message', 'Agent turned on');
            }
        } else {
            if (!App::getModel('maintainAgent')->setAgentEnabled(false)) {
                $this->addMessage('error', 'Can not turn off agent');
            } else {
                $this->addMessage('message', 'Agent turned off');
            }
        }

        App::forwardSafe(null, 'default');
    }

    public function actionToggleRegularProfiler()
    {
        $dependencies=array(
            'agent' => 1,
            'recording_mode' => 0,
        );

        $status = App::getModel('maintainAgent')->getStatus();

        // dependent on XHProf extension if somebody wants to turn ON
        if (!$status['is_regular_profiler_on']) {
            $dependencies['xhprof_installed'] = 1;
        }

        $noErrors = $this->applyDependencyErrorIfExists($dependencies);

        if ($noErrors) {
            $data = array(
                'regular_profiler_namespace' => App::filterText(Request::get('namespace'), '_'),
                'regular_profiler_description' => Request::get('description'),
            );

            try {
                if (!$status['is_regular_profiler_on']) {

                    if ($data['regular_profiler_namespace']) {

                        $prepareResult = App::getModel('resources')->setResultNamespaceInfo(
                            $data['regular_profiler_namespace'],
                            array(
                                'source_namespace' => ' - regular profiler -',
                                'description' => $data['regular_profiler_description'],
                            )
                        );

                        if (!$prepareResult || !App::getModel('maintainAgent')->setXhpEnabled(true, $data)) {
                            $this->addMessage('error', 'Can not turn on Regular Profiler');
                        } else {
                            $this->addMessage(
                                'message',
                                "Regular Profiler turned on and namespace is `{$data['regular_profiler_namespace']}`"
                            );
                        }
                    } else {
                        $this->addMessage('error', 'Namespace value should be valid directory name');
                    }
                } else {
                    if (!App::getModel('maintainAgent')->setXhpEnabled(false)) {
                        $this->addMessage('error', 'Can not turn off Regular Profiler');
                    } else {
                        $this->addMessage('message', 'Regular Profiler turned off');
                    }
                }
            } catch (Exception $e) {
                $this->addMessage('error', 'Fatal error. ' . $e->getMessage());
            }
        }
        App::forwardSafe(null, 'default');
    }

    public function actionToggleRecordingMode()
    {
        $noErrors = $this->applyDependencyErrorIfExists(
            array(
                'agent' => 1,
                'regular_profiler' => 0,
            )
        );

        if ($noErrors) {
            $namespace = App::filterText(Request::get('namespace'), '_');
            $description = Request::get('description');
            $status = App::getModel('maintainAgent')->getStatus();
            if ($namespace) {
                $recordingModeData = array(
                    'recording_namespace' => $namespace,
                    'recording_description' => $description,
                );
                try {
                    if (App::getModel('maintainAgent')->setRecordingModeEnabled(!$status['recording_mode'], $recordingModeData)) {
                        // turn OFF
                        if ($status['recording_mode']) {
                            $modelResources = App::getModel('resources');
                            $dirInfo = $modelResources->getNamespaceInfo('path_request_logs', '$oldNamespace');
                            $oldNamespace = $status['recording_namespace'];
                            $modelResources->setRequestNamespaceInfo(
                                $oldNamespace,
                                array(
                                    'description' => $status['recording_description']
                                )
                            );

                            // create additional detailed message
                            if ($modelResources->lastSavedNamespaceInfo['file_count']) {
                                $additionalText = $modelResources->lastSavedNamespaceInfo['file_count']
                                    . " files was added to namespace `$oldNamespace`";
                            } else {
                                $additionalText = 'there is no new files';
                            }
                            //

                            $this->addMessage('message', "Recording mode turned off, " . $additionalText);
                            // turn ON
                        } else {
                            $this->addMessage('message', "Recording mode turned on and namespace is `$namespace`");
                        }
                    } else {
                        $this->addMessage('error', 'Error: could not save data');
                    }
                } catch (Exception $e) {
                    $this->addMessage('error', $e->getMessage());
                }
            } else {
                $this->addMessage('error', 'Namespace value should be valid directory name');
            }
        }
        App::forwardSafe(null, 'default');
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
                case 'xhprof_installed':
                    if ((bool)$status['xhprof_installed'] !== (bool)$val) {
                        $this->addMessage('error', 'XHProf extension is not installed');
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


