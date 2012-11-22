<?php

class ModelResources
{
    protected $dirInfoFile = '.dirinfo';

    public function getRequestNamespaces()
    {
        $output = array();
        $directories = glob(App::cfg('path_request_logs') . '*', GLOB_ONLYDIR);
        foreach ($directories as $dir) {
            $dirInfo = $this->getNamespaceInfo('path_request_logs', basename($dir));
            if ($dirInfo) {
                $output[] = array(
                    'name' => basename($dir),
                    'data' => $dirInfo,
                    'mtime' => filemtime($dir),
                );
            }
        }
        usort($output, array($this, 'sortByMTime'));
        return $output;
    }

    public function setRequestNamespaceInfo($namespace, array $data = array())
    {
        $dir = App::cfg('path_request_logs') . $namespace;
        $fileCount = glob("{$dir}/*.log");
        $data['file_count'] = sizeof($fileCount);
        return $this->setNamespaceInfo($dir, $data);
    }

    public function setResultNamespaceInfo($namespace, array $data = array())
    {
        $dir = App::cfg('path_request_results') . $namespace;
        if (!is_dir($dir)) {
            if (!@mkdir($dir)) {
                throw new Exception("Could not create directory for namespace `$namespace`");
            }
        }
        $fileCount = glob("{$dir}/*.data");
        $data['file_count'] = sizeof($fileCount);
        return $this->setNamespaceInfo($dir, $data);
    }

    public function getResultNamespaces()
    {
        $output = array();
        $directories = glob(App::cfg('path_request_results') . '*', GLOB_ONLYDIR);
        foreach ($directories as $dir) {
            $dirInfo = $this->getNamespaceInfo('path_request_results', basename($dir));

            // get file count on-the-fly
            if (isset($dirInfo['source_namespace']) && $dirInfo['source_namespace'] == ' - regular profiler -') {
                $dirInfo['is_regular'] = 1;
                $fileCount = glob("{$dir}/*.data");
                $dirInfo['file_count'] = sizeof($fileCount);

            } else {
                $dirInfo['is_regular'] = 0;
            }

            if ($dirInfo) {
                $output[] = array(
                    'name' => basename($dir),
                    'data' => $dirInfo,
                    'mtime' => filemtime($dir),
                );
            }
        }
        usort($output, array($this, 'sortByMTime'));
        return $output;
    }

    public function removeResultNamespace($namespace)
    {
        if ($namespace) {
            $dir = App::cfg('path_request_results') . $namespace;
            if (is_dir($dir)) {
                if (!$this->cleanDir($dir, true)) {
                    throw new Exception('Could not remove namespace directory');
                }
            } else {
                throw new Exception('Namespace directory not found');
            }
        } else {
            throw new Exception('Namespace is empty');
        }
    }

    protected function sortByMTime($a, $b)
    {
        return floatval($a['mtime']) < floatval($b['mtime']);
    }

    public function getNamespaceInfo($namespaceType, $namespace)
    {
        $dir = App::cfg($namespaceType) . $namespace;
        $dirInfoFile = "{$dir}/{$this->dirInfoFile}";
        return is_file($dirInfoFile) ? unserialize(file_get_contents($dirInfoFile)) : null;
    }

    protected function setNamespaceInfo($dir, array $data = array())
    {
        if (is_dir($dir)) {
            $dirInfoFile = "{$dir}/{$this->dirInfoFile}";
            $info = is_file($dirInfoFile) ? @unserialize(file_get_contents($dirInfoFile)) : array();
            $info['timestamp'] = microtime(1);

            $info = array_merge($info, $data);
            $this->lastSavedNamespaceInfo = $info;
            return file_put_contents($dirInfoFile, serialize($info));
        } else {
            throw new Exception("Namespace directory `" . basename($dir) . "` not found");
        }
    }

    protected function cleanDir($dir, $also_remove_dir = false)
    {
        $dir = substr($dir, -1) == '/' ? substr($dir, 0, -1) : $dir;
        if (!file_exists($dir) || !is_dir($dir)) {
            return false;
        } elseif (!is_readable($dir)) {
            return false;
        } else {
            $dir_handle = opendir($dir);
            while ($contents = readdir($dir_handle)) {
                if ($contents != '.' && $contents != '..') {
                    $path = $dir . '/' . $contents;
                    if (is_dir($path)) {
                        if (!$this->cleanDir($path, true)) {
                            return false;
                        }
                    } else {
                        unlink($path);
                    }
                }
            }
            closedir($dir_handle);
            if ($also_remove_dir && !rmdir($dir)) {
                return false;
            }
            return true;
        }
    }
}
