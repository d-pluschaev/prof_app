<? if (!is_null($this->maintaningAppStatus)) { ?>
<div class="top_status">
    <span class="ts_title">status:</span>

    <?
    $agentStatus = $this->maintaningAppStatus['htaccess_changed']
        ? array(
            'label' => 'on',
            'class' => 'on',
        )
        : array(
            'label' => 'off',
            'class' => 'off',
        );
    $recordingModeStatus = $this->maintaningAppStatus['recording_mode']
        ? array(
            'label' => "on (namespace: `{$this->maintaningAppStatus['recording_namespace']}`)",
            'class' => 'on',
        )
        : array(
            'label' => 'off',
            'class' => 'off',
        );
    $regularProfilerStatus = $this->maintaningAppStatus['is_regular_profiler_on']
        ? array(
            'label' => "on (namespace: `{$this->maintaningAppStatus['regular_profiler_namespace']}`)",
            'class' => 'on',
        )
        : array(
            'label' => 'off',
            'class' => 'off',
        );
    ?>

    <span class="<?=$agentStatus['class']?>">
        agent: <?=$agentStatus['label']?>
    </span>
    <span class="<?=$recordingModeStatus['class']?>">
        recording mode: <?=$recordingModeStatus['label']?>
    </span>
    <span class="<?=$regularProfilerStatus['class']?>">
        regular profiler: <?=$regularProfilerStatus['label']?>
    </span>
</div>
<? } ?>
