<?php

class ModelDiff
{
    public function getDiffData(ModelResultsAnalyzer $target, ModelResultsAnalyzer $source, $functions)
    {
        $sourceHash = array();
        foreach ($source->data as $element) {
            $sourceHash[pathinfo($element['file'], PATHINFO_FILENAME)] = $element;
        }

        $targetHash = array();
        foreach ($target->data as $element) {
            $targetHash[pathinfo($element['file'], PATHINFO_FILENAME)] = $element;
        }

        $diff = array();
        foreach ($targetHash as $key => $element) {
            $diff[$key] = array();
            $diff[$key]['added'] = !isset($sourceHash[$key]);
        }
        foreach ($sourceHash as $key => $element) {
            $diff[$key]['removed'] = !isset($targetHash[$key]);
        }
        foreach ($diff as $key => $element) {
            if (empty($element['removed']) && empty($element['added'])) {
                $diff[$key]['data'] = $this->compare($targetHash[$key], $sourceHash[$key], $functions);
            }
        }
        return $diff;
    }

    protected function compare($target, $source, $functions)
    {
        $funcDiff = array();
        foreach ($functions as $func) {
            if (isset($target["func_$func"]) && isset($source["func_$func"])) {
                $funcDiff[$func] = $target["func_$func"]['summary'];
                foreach ($funcDiff[$func] as $k => $v) {
                    $funcDiff[$func][$k] -= $source["func_$func"]['summary'][$k];
                }
                $funcDiff[$func]['status'] = 0;
            } elseif (isset($target["func_$func"])) {
                $funcDiff[$func] = $target["func_$func"]['summary'];
                $funcDiff[$func]['status'] = 1;
            } elseif (isset($source["func_$func"])) {
                $funcDiff[$func] = $source["func_$func"]['summary'];
                foreach ($funcDiff[$func] as $k => $v) {
                    $funcDiff[$func][$k] = $v * -1;
                }
                $funcDiff[$func]['status'] = -1;
            }
        }

        return array(
            'time' => (float)$target['time'] - (float)$source['time'],
            'html_footer_time' => (float)$target['html_footer_time'] - (float)$source['html_footer_time'],
            'http_code_changed' => $target['http_code'] !== $source['http_code'],
            'html_footer_time_changed' => (bool)strlen($target['html_footer_time'])
                !== (bool)strlen($source['html_footer_time']),
            'func_diff' => $funcDiff,
        );
    }

    public function getDiffSummary(array $diff)
    {
        $summary = array(
            'total' => 0,
            'removed' => 0,
            'added' => 0,
            'time' => 0,
            'html_footer_time' => 0,
            'functions' => array(),
        );
        foreach ($diff as $element) {
            $summary['total']++;
            $summary['removed'] += isset($element['removed']) ? (int)$element['removed'] : 0;
            $summary['added'] += isset($element['added']) ? (int)$element['added'] : 0;
            $summary['time'] += isset($element['data']['time']) ? (float)$element['data']['time'] : 0;
            $summary['html_footer_time'] += isset($element['data']['html_footer_time'])
                ? (float)$element['data']['html_footer_time']
                : 0;

            if (isset($element['data']['func_diff'])) {
                foreach ($element['data']['func_diff'] as $func => $funcData) {
                    if (!isset($summary['functions'][$func])) {
                        $summary['functions'][$func] = $funcData;
                    } else {
                        foreach ($funcData as $k => $v) {
                            $summary['functions'][$func][$k] += $v;
                        }
                    }
                }
            }
        }

        return $summary;
    }

}
