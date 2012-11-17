<?php

class XHProfData
{
    public $dataType; // can be "single" or "diff"

    protected $dataSingle;
    protected $dataDiff;

    protected $sortConditions = null;

    public function loadSingle(array $xhpArray)
    {
        $this->dataType = 'single';
        $this->dataSingle = $this->getPreparedData($xhpArray);

        // test
        //array_splice($this->dataSingle, 50, 999999);


        //print_r($this->getParentsBy(array('name-1'=>'is_array','OR','name-2'=>'%tri%')));
        //print_r($this->getMain());

        //print_r($this->getStatByFunctions(array('is_array','rtrim')));
    }

    public function loadDiff(array $xhpArray1, array $xhpArray2)
    {
        $this->dataType = 'diff';
    }

    public function getParentsBy(array $where)
    {
        return $this->searchBy(array('child'), $where);
    }

    public function getChildrenBy(array $where)
    {
        return $this->searchBy(array('parent'), $where);
    }

    public function getMain()
    {
        $main = $this->searchBy(array('child'), array('name' => 'main()'));
        return isset($main[0]['data']) ? $main[0]['data'] : null;
    }

    public function getStatByFunctions(array $functions)
    {
        $output = array();
        $where = array('OR');
        foreach ($functions as $index => $func) {
            $where["name{$index}"] = $func;
            $output[$func] = array(
                'parents' => array(),
                'summary' => array(),
            );
        }
        $calls = $this->getParentsBy($where);

        foreach ($calls as $call) {
            $output[$call['child']['name']]['parents'][$call['parent']['name']] = $call['data'];
            foreach ($call['data'] as $metric => $value) {
                $output[$call['child']['name']]['summary'][$metric] = isset($output[$call['child']['name']]['summary'][$metric])
                    ? $output[$call['child']['name']]['summary'][$metric]
                    : 0;
                $output[$call['child']['name']]['summary'][$metric] += $value;
            }
        }
        return $output;
    }

    public function searchBy(array $searchIn, array $where)
    {
        $where = $this->prepareSearchWhere($where);

        $output = array();
        foreach ($this->dataSingle as $entity) {
            $whereResult = null;
            foreach ($searchIn as $searchElement) {
                if (isset($entity[$searchElement])) {
                    $searchArray = $entity[$searchElement];
                    $whereOperator = 'AND';
                    $whereResult = null;
                    foreach ($where as $whereData) {
                        if ($whereData['operator']) {
                            $whereOperator = $whereData['operator'];
                        } elseif (isset($searchArray[$whereData['element']])) {
                            if (is_numeric($whereData['value'])) { // numeric comparison
                                $subResult = $searchArray[$whereData['element']] === $whereData['value'];
                            } elseif (is_bool($whereData['value'])) { // boolean comparison
                                $subResult = $searchArray[$whereData['element']] == $whereData['value'];
                            } else { // string comparison
                                // is text search required?
                                if ($whereData['text_search_required']) {
                                    // full match
                                    if ($whereData['text_search_type'] === 0) {
                                        $subResult = strcasecmp(
                                            $whereData['trimmed_value'],
                                            $searchArray[$whereData['element']]
                                        ) == 0;
                                        // left % match
                                    } elseif ($whereData['text_search_type'] === 1) {
                                        $substring = substr(
                                            $searchArray[$whereData['element']],
                                            -strlen($whereData['trimmed_value'])
                                        );
                                        $subResult = strcasecmp(
                                            $whereData['trimmed_value'],
                                            $substring
                                        ) == 0;
                                        // right % match
                                    } elseif ($whereData['text_search_type'] === 2) {
                                        $substring = substr(
                                            $searchArray[$whereData['element']],
                                            0, strlen($whereData['trimmed_value'])
                                        );
                                        $subResult = strcasecmp(
                                            $whereData['trimmed_value'],
                                            $substring
                                        ) == 0;
                                        // both left and right % match
                                    } elseif ($whereData['text_search_type'] === 3) {
                                        $subResult = stripos(
                                            $searchArray[$whereData['element']],
                                            $whereData['trimmed_value']
                                        ) !== false;
                                    }
                                } else {
                                    $subResult = 1;
                                }
                            }
                            if ($whereOperator == 'AND') {
                                if (is_null($whereResult)) {
                                    $whereResult = $subResult;
                                } else {
                                    $whereResult &= $subResult;
                                }
                            } elseif ($whereOperator == 'OR') {
                                $whereResult |= $subResult;
                            }
                        }
                    }
                    if ($whereResult) {
                        break;
                    }
                }
            }
            // don't search in other $searchIn if found
            if ($whereResult) {
                $output[] = $entity;
            }
        }
        return $output;
    }

    public function applyCausedCallsMetricsFor(array $functions)
    {
        foreach ($functions as $funcName) {
            $this->applyCausedCallsMetricsForFunction($funcName);
        }
    }

    public function getFunctionLists($searchFunction = '', $sortBy = 'wt', $sortDesc = 1, $sortType = 'num', $limit = 50)
    {
        $data = $searchFunction
            ? $this->searchBy(array('child'), array('name' => "%{$searchFunction}%"))
            : $this->dataSingle;

        $hash = array();

        // prepare structure
        foreach ($data as $item) {
            if (isset($item['child']['name'])) {
                $hash[$item['child']['name']] = array(
                    'data' => $this->getPossibleMetrics(),
                    'caused_calls' => array(),
                    'name' => $item['child']['name'],
                    'index' => $item['index'] + 1,
                );
            }
        }

        // main loop
        foreach ($data as $item) {
            if (isset($item['child']['name']) && isset($item['parent']['name'])) {

                // aggregate metrics
                foreach ($hash[$item['child']['name']]['data'] as $metricName => &$metric) {
                    $metric += $item['data'][$metricName];
                }
                // aggregate caused calls
                foreach ($item['caused_calls'] as $ccKey => $ccMetrics) {

                    $hash[$item['parent']['name']]['caused_calls'][$ccKey]
                        = isset($hash[$item['parent']['name']]['caused_calls'][$ccKey])
                        ? $hash[$item['parent']['name']]['caused_calls'][$ccKey]
                        : $this->getPossibleMetrics();

                    foreach ($ccMetrics as $ccMetricName => $ccMetricVal) {
                        $hash[$item['parent']['name']]['caused_calls'][$ccKey][$ccMetricName] += $ccMetricVal;
                    }
                }

                // calculate exclusive time
                if (isset($item['parent']['name'])) {
                    $hash[$item['child']['name']]['data']['ewt'] = isset($hash[$item['child']['name']]['data']['ewt'])
                        ? $hash[$item['child']['name']]['data']['ewt']
                        : 0;
                    if (isset($hash[$item['parent']['name']])) {
                        $hash[$item['parent']['name']]['data']['ewt'] += $item['data']['wt'];
                    }
                }
            }
        }

        // finish exclusive wall time calculation
        foreach ($hash as &$item) {
            $item['data']['ewt'] = $item['data']['wt'] - $item['data']['ewt'];
        }

        $this->sortFunctionList($hash, $sortBy, $sortDesc, $sortType);
        return array_splice($hash, 0, $limit);
    }

    public function sortFunctionList(&$functionList, $sortBy, $sortDesc, $sortType)
    {
        $this->sortConditions = array('desc' => $sortDesc, 'sort_by' => $sortBy, 'sort_type' => $sortType);
        usort($functionList, array($this, 'sortFuncList'));
    }

    public function sortFuncList($ap, $bp)
    {
        // for watching calls
        if (substr($this->sortConditions['sort_by'], 0, 3) == 'wf_') {
            $func = substr($this->sortConditions['sort_by'], 3);
            $a = isset($ap['caused_calls'][$func]['ct']) ? (int)$ap['caused_calls'][$func]['ct'] : -1;
            $b = isset($bp['caused_calls'][$func]['ct']) ? (int)$bp['caused_calls'][$func]['ct'] : -1;
            return $this->sortConditions['desc'] ? $a < $b : $a > $b;
        }

        // static columns
        switch ($this->sortConditions['sort_by']) {
            case 'ct':
            case 'wt':
            case 'cpu':
            case 'ewt':
            case 'mu':
            case 'pct':
            case 'pwt':
                $a = (float)$ap['data'][$this->sortConditions['sort_by']];
                $b = (float)$bp['data'][$this->sortConditions['sort_by']];
                return $this->sortConditions['desc'] ? $a < $b : $a > $b;
            case 'name':
                $a = $ap[$this->sortConditions['sort_by']];
                $b = $bp[$this->sortConditions['sort_by']];
                return $this->sortConditions['desc'] ? strcmp($a, $b) : strcmp($b, $a);
            case 'index':
                $a = (float)$ap['index'];
                $b = (float)$bp['index'];
                return $this->sortConditions['desc'] ? $a < $b : $a > $b;
            default:
                return 0;
        }
    }

    public function getFullFunctionInfo($funcName, $sortBy = 'wt', $sortDesc = 1, $sortType = 'num')
    {
        $parents = $this->getParentsBy(array('name' => $funcName));

        $funcData = array(
            'name' => $funcName,
            'data' => $this->getPossibleMetrics(),
            'parents' => array(),
            'children' => array(),
            'index' => 0,
        );

        $totalCalls = $totalWT = 0;
        foreach ($parents as $index => $parentData) {

            $buffer = $parentData['parent'];
            $buffer['data'] = $parentData['data'];
            $buffer['index'] = $index;
            if (!empty($parentData['parent'])) {
                $funcData['parents'][] = $buffer;
            }

            foreach ($parentData['data'] as $k => $v) {
                $funcData['data'][$k] += $v;
            }
            $funcData['data']['ewt'] += $parentData['data']['wt'];

            $totalCalls += $parentData['data']['ct'];
            $totalWT += $parentData['data']['wt'];
        }
        // set percentage values
        foreach ($funcData['parents'] as $index => $parentData) {
            $funcData['parents'][$index]['data']['pct'] = round($parentData['data']['ct'] / $totalCalls * 100, 1) . '%';
            $funcData['parents'][$index]['data']['pwt'] = round($parentData['data']['wt'] / $totalWT * 100, 1) . '%';
        }

        $children = $this->getChildrenBy(array('name' => $funcName));

        $totalCalls = $totalWT = 0;
        foreach ($children as $index => $childData) {
            $buffer = $childData['child'];
            $buffer['data'] = $childData['data'];
            $buffer['index'] = $index;
            $funcData['children'][] = $buffer;

            $funcData['data']['ewt'] -= $childData['data']['wt'];

            $totalCalls += $childData['data']['ct'];
            $totalWT += $childData['data']['wt'];
        }
        // set percentage values
        foreach ($funcData['children'] as $index => $childData) {
            $funcData['children'][$index]['data']['pct'] = round($childData['data']['ct'] / $totalCalls * 100, 1) . '%';
            $funcData['children'][$index]['data']['pwt'] = round($childData['data']['wt'] / $totalWT * 100, 1) . '%';
        }

        $funcData['data']['pct'] = '-';
        $funcData['data']['pwt'] = '-';

        $this->sortFunctionList($funcData['parents'], $sortBy, $sortDesc, $sortType);
        $this->sortFunctionList($funcData['children'], $sortBy, $sortDesc, $sortType);

        return $funcData;
    }

    protected function applyCausedCallsMetricsForFunction($funcName)
    {
        $parents = $this->getParentsBy(array('name' => $funcName));
        // exclude unused metrics and set up "caused calls" values
        $usefulMetrics = array('ct', 'wt');
        foreach ($parents as $index => $parent) {
            $tmp = array();
            foreach ($usefulMetrics as $metric) {
                $tmp[$metric] = $parent['data'][$metric];
            }
            $parents[$index]['caused_calls'][$funcName] = $tmp;
            $this->dataSingle[$parent['index']]['caused_calls'][$funcName] = $tmp;
        }
        $this->applyCausedCallsMetricsForFunctionBranch($funcName, $parents);
    }

    protected function applyCausedCallsMetricsForFunctionBranch($funcName, array $parents)
    {
        foreach ($parents as $parent) {
            if (isset($parent['parent']['name'])) {
                $ascendants = $this->getParentsBy(array('name' => $parent['parent']['name']));
                foreach ($ascendants as $ascendantIndex => $ascendant) {
                    // init array if need
                    $ascendants[$ascendantIndex]['caused_calls'][$funcName] = isset($ascendant['caused_calls'][$funcName])
                        ? $ascendant['caused_calls'][$funcName]
                        : array('ct' => 0, 'wt' => 0);
                    // increase metrics
                    foreach ($parent['caused_calls'][$funcName] as $metricName => $metric) {
                        $ascendants[$ascendantIndex]['caused_calls'][$funcName][$metricName] += $metric;
                    }
                    // save data
                    $this->dataSingle[$ascendant['index']]['caused_calls'][$funcName]
                        = $ascendants[$ascendantIndex]['caused_calls'][$funcName];
                }
                $this->applyCausedCallsMetricsForFunctionBranch($funcName, $ascendants);
            }
        }
    }

    protected function getPossibleMetrics()
    {
        return array(
            'ct' => 0,
            'wt' => 0,
            'cpu' => 0,
            'mu' => 0,
            'pmu' => 0,
            'ewt' => 0,
            'pct' => 0,
            'pwt' => 0,
        );
    }

    protected function prepareSearchWhere(array $where)
    {
        $out = array();
        foreach ($where as $whereElement => $whereValue) {

            $whereString = trim($whereValue);
            $textSearchType = 0;
            if ($whereString) {
                if ($whereString{0} == '%') {
                    $textSearchType = 1;
                }
                if (substr($whereString, -1) == '%') {
                    $textSearchType += 2;
                }
                $whereString = str_replace('%', '', $whereString);
            }

            $out[] = array(
                'element' => preg_replace('~[^a-z_]~', '', $whereElement),
                'value' => $whereValue,
                'trimmed_value' => $whereString,
                'operator' => in_array($whereString, array('AND', 'OR')) ? $whereString : '',
                'text_search_type' => $textSearchType,
                'text_search_required' => strlen($whereString) > 0,
            );
        }
        return $out;
    }

    protected function getPreparedData(array $xhpArray)
    {
        $possibleMetrics = $this->getPossibleMetrics();
        $out = array();
        $index = 0;
        foreach ($xhpArray as $functionCall => $callData) {
            $entity = $this->parseEntity($functionCall);
            $callData = array_merge($possibleMetrics, $callData);
            $entity['data'] = $callData;
            $entity['index'] = $index;
            $entity['caused_calls'] = array();
            $out[] = $entity;
            $index++;
        }
        return $out;
    }

    protected function parseEntity($functionCall)
    {
        if ($functionCall != 'main()') {
            list($parent, $child) = explode('==>', $functionCall);
            return array(
                'parent' => $this->getEntityName($parent),
                'child' => $this->getEntityName($child),
            );
        } else {
            return array(
                'parent' => array(),
                'child' => $this->getEntityName($functionCall),
            );
        }
    }

    protected function getEntityName($functionName)
    {
        $nameParts = explode('::', $functionName);
        $callSource = '';
        $name = $functionName;
        $is_method = false;
        $cleanName = '';
        if (sizeof($nameParts) == 2) {
            if (strpos($nameParts[1], '.') !== false) {
                $cleanName = $nameParts[0];
                $callSource = $nameParts[1];
            } else {
                $is_method = true;
            }
        }
        return array(
            'name' => $name,
            'source' => $callSource,
            'is_main' => $name == 'main()',
            'is_method' => $is_method,
            'is_include' => $callSource && $cleanName == 'run_init',
            'is_compile' => $callSource && $cleanName == 'load',
        );
    }
}

