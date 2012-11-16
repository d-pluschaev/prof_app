<?php

class ModelMaintainAgent
{
    protected static $status = array();

    public function getStatus()
    {
        if (empty(self::$status)) {
            // .htaccess
            $htAccessContent = $this->getHtAccessContent();
            self::$status['htaccess_found'] = !is_null($htAccessContent);
            self::$status['htaccess_changed'] = false;
            if ($htAccessContent) {
                $changedContent = $this->getChangedHtAccessContent($htAccessContent, true);
                self::$status['htaccess_changed'] = strcmp($changedContent, $htAccessContent) != 0;
            }

            // recording_mode & regular profiler settings
            self::$status = array_merge($this->getAgentConfig(), self::$status);

            // labels
            self::$status['label_agent_status'] = self::$status['htaccess_changed'] ? 'ON' : 'OFF';

            self::$status['label_regular_profiler_status'] = (self::$status['is_regular_profiler_on']
                ? 'ON'
                : 'OFF');

            self::$status['label_recording_mode_status'] = (self::$status['recording_mode']
                ? 'ON'
                : 'OFF');

        }
        return self::$status;
    }

    public function setAgentEnabled($flag)
    {
        $htAccessContent = $this->getHtAccessContent();
        $changedHtAccessContent = $this->getChangedHtAccessContent($htAccessContent, !$flag);
        return $this->setHtAccessContent($changedHtAccessContent) !== false;
    }

    public function setRecordingModeEnabled($flag, array $recordingData = array())
    {
        $recordingData['recording_mode'] = $flag;
        if ($recordingData['recording_mode']) {
            $recordingData['recording_directory'] = App::cfg('path_request_logs') . $recordingData['recording_namespace'];
            if (!@mkdir($recordingData['recording_directory'], 0777)) {
                throw new Exception(
                    'Could not create namespace directory. Directory already exists or no permissions'
                );
            }
        }
        return $this->setAgentConfigValue($recordingData);
    }

    public function setXhpEnabled($flag, array $profilingData = array())
    {
        $profilingData['is_regular_profiler_on'] = $flag;
        if ($profilingData['is_regular_profiler_on']) {
            $profilingData['regular_profiler_directory'] = App::cfg('path_request_results')
                . $profilingData['regular_profiler_namespace'];
        }
        return $this->setAgentConfigValue($profilingData);
    }

    public function setAgentConfigValue(array $key_values)
    {
        $apConf = array_merge($this->getAgentConfig(), $key_values);
        return file_put_contents(App::cfg('path_sc_auto_prepend_config_file'), serialize($apConf));
    }

    public function getAgentConfig()
    {
        return (array)@unserialize(file_get_contents(App::cfg('path_sc_auto_prepend_config_file')));
    }

    protected function getAdditionForHtAccess()
    {
        $apFile = App::cfg('path_sc_auto_prepend_file');
        return "# auto_prepend for debug purposes\n"
            . "php_value auto_prepend_file {$apFile}\n"
            . "# end auto_prepend for debug purposes";
    }

    protected function getHtAccessContent()
    {
        $htAccessPath = App::cfg('maintaining_project_htaccess_file');
        return is_file($htAccessPath) ? file_get_contents($htAccessPath) : null;
    }

    protected function setHtAccessContent($content)
    {
        return @file_put_contents(App::cfg('maintaining_project_htaccess_file'), $content);
    }

    protected function getChangedHtAccessContent($content, $isRemove)
    {
        $content = preg_replace(
            "~\n# auto_prepend for debug purposes.*# end auto_prepend for debug purposes\n~ms",
            '',
            $content
        );
        $content .= !$isRemove ? "\n" . $this->getAdditionForHtAccess() . "\n" : '';
        return $content;
    }
}
