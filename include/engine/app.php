<?php


class App
{
    public static $instance;

    public $config;
    public $route;
    public $user;

    public $controller;

    public $dynamicConfig;

    public function __construct($root)
    {
        self::$instance = $this;
        $config = $this->dynamicConfig = (array)@unserialize(file_get_contents("{$root}config_dynamic.srz"));

        require_once $root . 'config.php';
        $config['root'] = $root;
        $this->config = $config;
        require_once $root . 'include/bootstrap.php';

        $this->authentication = new Auth($this->config['routes']);
    }

    public function execute()
    {
        try {
            $view = $this->processRoute($this->prepareRoute());
            $this->processView($view);
        } catch (Exception $e) {
            $this->processException($e);
        }
    }

    public function prepareRoute()
    {
        $requestObject = new Request();
        return $requestObject->getRoute(self::cfg('route_type'));
    }

    public function processRoute($data)
    {
        $controller = isset($data['controller'])
            ? self::filterText($data['controller'])
            : $this->config['default_controller'];

        $controller_file = "{$this->config['root']}controllers/{$controller}.php";

        if (is_file($controller_file)) {
            require_once $controller_file;
        } else {
            $controller = $this->config['default_controller'];
        }

        $controller_class = "Controller{$controller}";
        if (!class_exists($controller_class)) {
            $controller_class = 'ControllerDefault';
        }

        $this->controller = new $controller_class();

        $action = 'action' . (isset($data['action'])
            ? self::filterText($data['action'])
            : $this->config['default_action']);
        if (!method_exists($this->controller, $action)) {
            $action = 'action' . $this->config['default_action'];
        }

        $this->route = array(
            'controller' => $controller,
            'action' => $action,
        );

        if (method_exists($this->controller, $action)) {
            // check access
            if ($this->authentication->hasAccessToRoute($this->route)) {
                $view = $this->controller->$action();
                return $this->controller->postProcess($view);
            } else {
                $controller = 'Default';
                $controller_class = "Controller{$controller}";
                $this->controller = new $controller_class();
                $action = 'actionAccessDenied';
                $view = $this->controller->$action();
                return $this->controller->postProcess($view);
            }
        } else {
            throw new Exception('Unhandled action');
        }
    }

    public function processView($view)
    {
        if ($view instanceof Template) {
            echo $view->render();
        } elseif(is_string($view)) {
            print($view);
        } elseif(is_array($view)) {
            $responseType =  Request::get('response_type');
            if($responseType == 'json'){
                echo json_encode($view);
            }else{
                print_r($view);
            }
        }
    }

    public static function filterText($str, $add = '')
    {
        return preg_replace("/[^A-Za-z0-9{$add}]/", '', $str);
    }

    public static function cfg($k1, $k2 = null)
    {
        if ($k2) {
            return isset(self::$instance->config[$k1][$k2]) ? self::$instance->config[$k1][$k2] : null;
        } else {
            return isset(self::$instance->config[$k1]) ? self::$instance->config[$k1] : null;
        }
    }

    public static function getModel($model)
    {
        $model = strtolower($model);
        require_once self::$instance->config['root'] . "models/{$model}.php";
        $model = 'Model' . $model;
        return new $model(self::$instance->config);
    }

    public static function auth()
    {
        return self::$instance->authentication;
    }

    public static function forward($url, $add_ret_url = true)
    {
        header('Location:' . "?{$url}" . ($add_ret_url ? '&return_url=' . $_SERVER['REQUEST_URI'] : ''));
        exit;
    }

    public static function forwardSafe($controller = null, $action = null, array $exclude = array())
    {
        App::forward(self::getForwardSafeUri($controller, $action, $exclude), false);
    }

    public static function getForwardSafeUri($controller = null, $action = null, array $exclude = array())
    {
        $query = array();
        foreach ($exclude as $k) {
            unset($_GET[$k]);
        }
        foreach ($_GET as $k => $v) {
            $query[] = strlen($v) ? "$k=$v" : $k;
        }
        $query[0] = $controller ? $controller : $query[0];
        $query[1] = $action ? $action : $query[1];
        return '' . implode('&', $query);
    }

    public function processException(Exception $e)
    {
        echo $e->getMessage();
        exit;
    }
}
