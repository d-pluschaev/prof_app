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
            $diff[$key]['req']=$element['req'];
        }
        foreach ($sourceHash as $key => $element) {
            $diff[$key]['removed'] = !isset($targetHash[$key]);
            $diff[$key]['req']=$element['req'];
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
                $funcDiff[$func] = array();
                foreach ($target["func_$func"]['summary'] as $k => $v) {
                    $funcDiff[$func][$k] = array(
                        't' => $v,
                        's' => $source["func_$func"]['summary'][$k],
                        'd' => $source["func_$func"]['summary'][$k] - $v,
                    );
                }
                $funcDiff[$func]['status'] = 0;
            } elseif (isset($target["func_$func"])) {
                $funcDiff[$func] = array();
                foreach ($target["func_$func"]['summary'] as $k => $v) {
                    $funcDiff[$func][$k] = array(
                        't' => $v,
                        's' => 0,
                        'd' => -$v,
                    );
                }
                $funcDiff[$func]['status'] = 1;
            } elseif (isset($source["func_$func"])) {
                $funcDiff[$func] = array();
                foreach ($source["func_$func"]['summary'] as $k => $v) {
                    $funcDiff[$func][$k] = array(
                        't' => 0,
                        's' => $v,
                        'd' => $v,
                    );
                }
                $funcDiff[$func]['status'] = -1;
            }
        }

        return array(
            'time' => array(
                't' => (float)$target['time'],
                's' => (float)$source['time'],
                'd' => (float)$source['time'] - (float)$target['time'],
            ),
            'html_footer_time' => array(
                't' => (float)$target['html_footer_time'],
                's' => (float)$source['html_footer_time'],
                'd' => (float)$source['html_footer_time'] - (float)$target['html_footer_time'],
            ),
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
            'time' => array(),
            'html_footer_time' => array(),
            'functions' => array(),
        );
        foreach ($diff as $element) {
            $summary['total']++;
            $summary['removed'] += isset($element['removed']) ? (int)$element['removed'] : 0;
            $summary['added'] += isset($element['added']) ? (int)$element['added'] : 0;

            foreach (array(
                         'time',
                         'html_footer_time',
                     ) as $metric) {
                if (isset($element['data'][$metric])) {
                    foreach ($element['data'][$metric] as $k => $v) {
                        if (!isset($summary[$metric][$k])) {
                            $summary[$metric][$k] = 0;
                        }
                        $summary[$metric][$k] += $v;
                    }
                }
            }

            if (isset($element['data']['func_diff'])) {
                foreach ($element['data']['func_diff'] as $func => $funcData) {
                    if (!isset($summary['functions'][$func])) {
                        $summary['functions'][$func] = $funcData;
                    } else {
                        foreach ($funcData as $k => $v) {
                            if (is_array($v)) {
                                foreach ($v as $mk => $mv) {
                                    $summary['functions'][$func][$k][$mk] += $mv;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $summary;
    }

}
