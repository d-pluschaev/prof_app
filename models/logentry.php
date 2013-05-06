<?php

class ModelLogEntry
{
    protected $data;
    protected $file;
    
    public function __construct($logName, ModelResultsCollector $collector)
    {
        $this->file = realpath($collector->getLogDirectory().'/'.$logName);
    }
    
    public function load()
    {
        $this->data = unserialize(file_get_contents($this->file));
        return $this;
    }
    
    public function save()
    {
        file_put_contents($this->file, serialize($this->data));
        return $this;
    }
    
    public function getName()
    {
        return $this->data['name'];
    }
    
    public function setName($name)
    {
        $this->data['name'] = $name;
        return $this;
    }
}