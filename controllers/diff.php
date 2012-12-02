<?php

class ControllerDiff extends ControllerDefault
{
    public function actionNamespaces()
    {
        $namespaceSource = App::filterText(Request::get('source'), '_');
        $namespaceTarget = App::filterText(Request::get('target'), '_');
        $response = App::filterText(Request::get('response_type'));

        if ($namespaceSource && $namespaceTarget) {

            $functions = $this->getWatchFunctionsArray();

            // prepare source
            $modelResultsAnalyzerSource = App::getModel('resultsAnalyzer');
            $modelResultsAnalyzerSource->directory = App::cfg('path_request_results') . $namespaceSource;
            $modelResultsAnalyzerSource->prepareFilesData();
            $modelResultsAnalyzerSource->performSearch($functions);
            $modelResultsAnalyzerSource->cleanSearchResults();

            // prepare target
            $modelResultsAnalyzerTarget = App::getModel('resultsAnalyzer');
            $modelResultsAnalyzerTarget->directory = App::cfg('path_request_results') . $namespaceTarget;
            $modelResultsAnalyzerTarget->prepareFilesData();
            $modelResultsAnalyzerTarget->performSearch($functions);
            $modelResultsAnalyzerTarget->cleanSearchResults();

            $diff = App::getModel('diff')->getDiffData(
                $modelResultsAnalyzerTarget,
                $modelResultsAnalyzerSource,
                $functions
            );

            $diffSummary = App::getModel('diff')->getDiffSummary($diff);

            if ($response == 'json') {
                return array(
                    'namespace_source' => $namespaceSource,
                    'namespace_target' => $namespaceTarget,
                    'diff' => $diff,
                    'functions' => $functions,
                    'diff_summary' => $diffSummary,
                );
            }

            $tpl = new Template('diff_namespaces');
            $tpl->title = "Profiler Web Interface: Diff namespaces '$namespaceSource' and `$namespaceTarget`";
            $tpl->namespace = $namespaceSource;
            $tpl->breadcrumb = "Diff namespaces '$namespaceSource' and `$namespaceTarget`";
            $tpl->diff = $diff;
            $tpl->functions = $functions;
            $tpl->diffSummary = $diffSummary;

            return $tpl;
        } else {
            $this->addMessage('error', 'Invalid request');
        }
        App::forwardSafe(null, 'default');
    }

    protected function getWatchFunctionsArray()
    {
        $watchFunctionsData = explode("\n", Request::get('functions', ''));
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

