<?php

class Request
{
    public static function get($key, $default = '')
    {
        return isset($_REQUEST[$key]) ? $_REQUEST[$key] : $default;
    }

    public function getRoute($type)
    {
        switch ($type) {
            case 'short':
                $route = $this->parseRequestRouteShort();
                break;
            default:
                $route = $this->parseRequestRouteStandard();
                break;
        }
        foreach ($route as $k => $v) {
            $route[$k] = strtolower(preg_replace('~[^A-Za-z0-9]~', '', $v));
        }
        return $route;
    }

    protected function parseRequestRouteShort()
    {
        $parts = explode('&', $_SERVER['QUERY_STRING']);
        return array(
            'module' => pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_FILENAME),
            'controller' => isset($parts[0]) ? $parts[0] : '',
            'action' => isset($parts[1]) ? $parts[1] : '',
        );
    }

    protected function parseRequestRouteStandard()
    {
        return array(
            'module' => pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_FILENAME),
            'controller' => isset($_REQUEST['controller']) ? $_REQUEST['controller'] : '',
            'action' => isset($_REQUEST['action']) ? $_REQUEST['action'] : '',
        );
    }
}