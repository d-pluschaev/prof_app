<?php

class ControllerAnalyze extends ControllerDefault
{
    protected $sort;
    protected $sortDesc;
    protected $sortType;

    public function actionDefault()
    {
        $tpl = new Template('analyze');
        $tpl->title = 'Profiler Web Interface: Analyze Collected Data - List of Namespaces';
        $tpl->breadcrumb = 'Analyze Collected Data - List of Namespaces';

        $tpl->namespaces = App::getModel('resources')->getResultNamespaces();
        return $tpl;
    }

    public function actionReport()
    {
        $namespace = App::filterText(Request::get('namespace'), '_');
        $nsDir = App::cfg('path_request_results') . $namespace;
        $functionsContent = Request::get('functions');
        $functions = explode("\n", $functionsContent);

        if ($namespace && is_dir($nsDir)) {
            $modelResultsAnalyzer = App::getModel('resultsAnalyzer');
            $modelResultsAnalyzer->directory = $nsDir;
            $modelResultsAnalyzer->prepareFilesData();

            foreach ($functions as $k => $v) {
                $functions[$k] = trim($v);
                if (!$functions[$k]) {
                    unset($functions[$k]);
                }
            }

            if (!empty($functions)) {
                $modelResultsAnalyzer->performSearch($functions);
                $functions = $modelResultsAnalyzer->cleanSearchResults();
            }

            $tpl = new Template('analyze_report');
            $tpl->title = "Profiler Web Interface: Analyze Collected Data - Details for `$namespace`";
            $tpl->breadcrumb = "Analyze Collected Data - Details for `$namespace`";

            $tpl->namespace = $namespace;
            $tpl->functions = $functions;

            $this->sort = $tpl->sort = App::filterText(Request::get('sort', 'index'), '_:');
            $this->sortDesc = $tpl->sortDesc = Request::get('desc') ? 1 : 0;
            $this->sortType = $tpl->sortType = App::filterText(Request::get('st'), '_');

            usort($modelResultsAnalyzer->data, array($this, 'dataSort'));

            $tpl->data = $modelResultsAnalyzer->data;
            $tpl->functionsContent = $functionsContent;

            return $tpl;
        } else {
            $this->addMessage('error', 'Namespace is invalid or not exists');
            App::forwardSafe(null, 'default');
        }
    }

    public function dataSort($a, $b)
    {
        $a = isset($a[$this->sort]) ? $a[$this->sort] : '';
        $b = isset($b[$this->sort]) ? $b[$this->sort] : '';
        $buf = $a;
        $a = $this->sortDesc ? $b : $a;
        $b = $this->sortDesc ? $buf : $b;

        switch ($this->sortType) {
            case 'ct':
            case 'wt':
            case 'cpu':
            case 'ewt':
                $a = is_array($a) ? $a : 0;
                $b = is_array($b) ? $b : 0;
                $a = isset($a['summary'][$this->sortType]) ? $a['summary'][$this->sortType] : $a;
                $b = isset($b['summary'][$this->sortType]) ? $b['summary'][$this->sortType] : $b;
                break;
            default:
                break;
        }
        return $this->sortType != 'str' || (is_numeric($a) && is_numeric($b)) ? floatval($a) > floatval($b) : strcmp($a, $b);
    }

    public function actionViewHtml()
    {
        $namespace = App::filterText(Request::get('namespace'), '_');
        $uid = App::filterText(Request::get('uid'), '_.');
        $file = App::cfg('path_request_results') . $namespace . '/' . $uid . '.html';
        if (is_file($file)) {
            readfile($file);
        } else {
            $this->addMessage('error', 'File not exists');
            App::forwardSafe(null, 'details');
        }
    }

    public function actionViewInXhp()
    {
        $namespace = App::filterText(Request::get('namespace'), '_');
        $uid = App::filterText(Request::get('uid'), '_.');
        $file = App::cfg('path_request_results') . $namespace . '/' . $uid . '.xhp';
        $sort = App::filterText(Request::get('sort', 'wt'), '_:');
        $sortDesc = Request::get('desc', 1) ? 1 : 0;
        $sortType = App::filterText(Request::get('st'), '_');
        $displayAll = App::filterText(Request::get('da'), '_');
        $searchFunction = App::filterText(Request::get('sf', ''), '_:');

        // get filtered watch functions
        $watchFunctions = $this->getWatchFunctionsArray();

        if (is_file($file)) {
            // prepare data
            $xhpData = unserialize(file_get_contents($file));
            $xhpDataProcessor = App::getModel('resultsAnalyzer')->getXhpDataProcessor();
            $xhpDataProcessor->loadSingle($xhpData);
            $main = $xhpDataProcessor->getMain();
            $xhpDataProcessor->applyCausedCallsMetricsFor($watchFunctions);
            $list = $xhpDataProcessor->getFunctionLists($searchFunction, $sort, $sortDesc, $sortType, $displayAll ? 9999 : 50);

            // template
            $tpl = new Template('analyze_func_list');
            $tpl->title = "Profiler Web Interface: Profiler for '$namespace: $uid'";
            $tpl->breadcrumb = "Profiler for '$namespace: $uid'";

            $tpl->namespace = $namespace;
            $tpl->uid = $uid;
            $tpl->sort = $sort;
            $tpl->sortDesc = $sortDesc;
            $tpl->sortType = $sortType;
            $tpl->searchFunction = $searchFunction;
            $tpl->watchFunctionsArray = $watchFunctions;

            $tpl->list = $list;
            $tpl->main = $main;

            return $tpl;
        } else {
            $this->addMessage('error', 'File not exists');
        }
        App::forwardSafe(null, 'details');
    }

    public function actionViewInXhpLegacy()
    {
        $namespace = App::filterText(Request::get('namespace'), '_');
        $uid = App::filterText(Request::get('uid'), '_.');
        $file = App::cfg('path_request_results') . $namespace . '/' . $uid . '.xhp';

        if (is_file($file)) {
            if (@copy($file, App::cfg('xhp_legacy_files_location') . '/' . basename($file))) {
                header('Location:' . sprintf(App::cfg('xhp_legacy_composite_link'), $uid));
                exit;
            } else {
                $this->addMessage('error', 'Could not copy file to legacy XHProf directory');
            }
        } else {
            $this->addMessage('error', 'File not exists');
        }
        App::forwardSafe(null, 'details');
    }

    public function actionXhpDetails()
    {
        $namespace = App::filterText(Request::get('namespace'), '_');
        $uid = App::filterText(Request::get('uid'), '_.');
        $file = App::cfg('path_request_results') . $namespace . '/' . $uid . '.xhp';
        $sort = App::filterText(Request::get('sort', 'wt'), '_:');
        $sortDesc = Request::get('desc', 1) ? 1 : 0;
        $sortType = App::filterText(Request::get('st'), '_');
        $func = App::filterText(Request::get('func'), '_:()\/.');

        // get filtered watch functions
        $watchFunctions = $this->getWatchFunctionsArray();

        if (is_file($file)) {
            $xhpData = unserialize(file_get_contents($file));

            $xhpDataProcessor = App::getModel('resultsAnalyzer')->getXhpDataProcessor();
            $xhpDataProcessor->loadSingle($xhpData);
            $xhpDataProcessor->applyCausedCallsMetricsFor($watchFunctions);

            try {
                $funcData = $xhpDataProcessor->getFullFunctionInfo($func, $sort, $sortDesc, $sortType);

                // render
                $tpl = new Template('analyze_func_details');
                $tpl->title = "Profiler Web Interface: Profiler for '$namespace: $uid'";
                $tpl->breadcrumb = "Profiler for '$namespace: $uid' - $func";

                $tpl->namespace = $namespace;
                $tpl->uid = $uid;
                $tpl->sort = $sort;
                $tpl->sortDesc = $sortDesc;
                $tpl->sortType = $sortType;
                $tpl->func = $func;
                $tpl->watchFunctionsArray = $watchFunctions;

                $tpl->data = $funcData;

                return $tpl;
            } catch (Exception $e) {
                $this->addMessage('error', 'Invalid request. ' . $e->getMessage());
                App::forwardSafe(null, 'view_in_xhp');
            }
        }
    }

    protected function getWatchFunctionsArray()
    {
        $watchFunctionsData = explode("\n", Request::get('wf', ''));
        $watchFunctions = array();
        foreach ($watchFunctionsData as $func) {
            $func = App::filterText($func, '_:\/.');
            if ($func) {
                $watchFunctions[] = $func;
            }
        }
        return $watchFunctions;
    }
}

