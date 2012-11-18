<?php

require_once dirname(__FILE__) . '/requestanalyzer/xhprof_data.php';

class ModelResultsAnalyzer
{
    public $directory;

    public $data;

    public function prepareFilesData()
    {
        $this->data = array();
        $files = glob($this->directory . '/*.data');

        foreach ($files as $index => $file) {
            $path_info = pathinfo($file);
            $uid = $path_info['dirname'] . '/' . $path_info['filename'];

            $fileData = unserialize(file_get_contents($file));
            $fileData['index'] = $index;
            $fileData['files'] = array(
                'html_path' => "$uid.html",
                'html_is_exists' => is_file("$uid.html"),
                'xhp_path' => "$uid.xhp",
                'xhp_is_exists' => is_file("$uid.xhp"),
            );
            $fileData['timestamp'] = isset($fileData['timestamp']) ? $fileData['timestamp'] : filemtime($file);
            $this->data[] = $fileData;
        }
    }

    public function performSearch(array $functions)
    {
        foreach ($this->data as $index => $data) {
            $xhp = $this->getXhpDataProcessor();
            if (is_file($data['files']['xhp_path'])) {
                $xhp->loadSingle(unserialize(file_get_contents($data['files']['xhp_path'])));
                $this->data[$index]['xhp_found'] = $xhp->getStatByFunctions($functions);
            } else {
                throw new Exception('Could not load XHP file');
            }
        }
    }

    public function cleanSearchResults()
    {
        $functions = array();
        foreach ($this->data as $index => $data) {
            if (isset($data['xhp_found'])) {
                foreach ($data['xhp_found'] as $func => $found) {
                    if (empty($found['summary'])) {
                        unset($this->data[$index]['xhp_found'][$func]);
                    } else {
                        $functions[$func] = isset($functions[$func]) ? $functions[$func] : 0;
                        $functions[$func]++;
                        $this->data[$index]['func_' . $func] = $found;
                    }
                }
            }
            unset($this->data[$index]['xhp_found']);
        }
        return $functions;
    }

    public function getXhpDataProcessor()
    {
        return new XHProfData();
    }
}
