<?php

class ControllerEdit extends ControllerDefault 
{
    public function actionDefault()
    {
        $tpl = new Template('edit');

        $namespace = App::filterText(Request::get('namespace'), '_');
        $tpl->namespace = $namespace;
        $collector = $this->prepareCollector($namespace);
        $tpl->logs = $collector->getLogs();

        $tpl->namespaces = App::getModel('resources')->getRequestNamespaces();
        
        return $tpl;
    }

    public function actionDelete()
    {
        $namespace = App::filterText(Request::get('namespace'), '_');
        $logName = Request::get('log');
        $collector = $this->prepareCollector($namespace);
        if ($collector->deleteLogEntry($logName)) {
            $this->addMessage('message', "Log entry `$logName` was deleted successfully");
        } else {
            $this->addMessage('error', "Unable to delete log entry `$logName`");
        }
        
        App::redirect(array(
            'controller' => 'edit',
            'action' => 'default',
            'namespace' => $namespace
        ));
    }
    
    public function actionMove()
    {
        $namespaceSource = App::filterText(Request::get('namespace_source'), '_');
        $namespaceTarget = App::filterText(Request::get('namespace_target'), '_');
        $logName = Request::get('log');
        
        $collectorSource = $this->prepareCollector($namespaceSource);
        $collectorTarget = $this->prepareCollector($namespaceTarget);
        
        if (file_exists($collectorSource->getLogDirectory().'/'.$logName) &&
            file_exists($collectorTarget->getLogDirectory()) &&
            rename($collectorSource->getLogDirectory().'/'.$logName, $collectorTarget->getLogDirectory().'/'.$logName)
        ) {
            $this->addMessage('message', "Log entry `$logName` was successfully moved from `$namespaceSource` to `$namespaceTarget`");
        } else {
            $this->addMessage('error', "Unable to move log entry `$logName` from `$namespaceSource` to `$namespaceTarget`");
        }

        App::redirect(array(
                'controller' => 'edit',
                'action' => 'default',
                'namespace' => $namespaceSource
            ));
    }
    
    public function actionSwap()
    {
        $namespace = App::filterText(Request::get('namespace'), '_');
        $log1 = Request::get('log1');
        $log2 = Request::get('log2');
        
        $collector = $this->prepareCollector($namespace);
        $dir = $collector->getLogDirectory();
        if (file_exists($dir.'/'.$log1) && file_exists($dir.'/'.$log2)) {
            $log1data = unserialize(file_get_contents($dir.'/'.$log1));
            $log2data = unserialize(file_get_contents($dir.'/'.$log2));
            
            $tmp = $log1data['order'];
            $log1data['order'] = $log2data['order'];
            $log2data['order'] = $tmp;
            
            file_put_contents($dir.'/'.$log1, serialize($log1data));
            file_put_contents($dir.'/'.$log2, serialize($log2data));
        }

        App::redirect(array(
                'controller' => 'edit',
                'action' => 'default',
                'namespace' => $namespace
            ));
    }
    
    public function actionRename()
    {
        $namespace = App::filterText(Request::get('namespace'), '_');
        $logName = Request::get('log');
        $name = Request::get('name');
        $logEntry = $this->prepareCollector($namespace)
            ->getLogEntry($logName);
        $logEntry->load()
            ->setName($name)
            ->save();
        
        App::redirect(array(
                'controller' => 'edit',
                'action' => 'default',
                'namespace' => $namespace
            ));
    }

    /**
     * @param $namespace
     * @return ModelResultsCollector
     */
    protected function prepareCollector($namespace)
    {
        /** @var $collector ModelResultsCollector */
        $collector = App::getModel('resultsCollector');
        $collector->prepare($namespace);
        return $collector;
    }

}