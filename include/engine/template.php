<?php

class Template
{
    private static $_add_vars = array();

    private $_tpl;
    private $_vars = array();
    private $_path;
    private $_layout;

    public function __construct($_tpl, $layout_name = 'layout')
    {
        $this->app = App::$instance;
        $this->_path = App::cfg('root');
        $this->_setTpl($_tpl);
        if ($layout_name) {
            $this->_setLayout($layout_name);
        }
    }

    public function _setLayout($layout_name = 'layout')
    {
        $this->_layout = $this->_path . "templates/$layout_name.php";
    }

    public static function addVar($name, $value)
    {
        self::$_add_vars[$name] = $value;
    }

    public function render()
    {
        $html = $this->_prepare();

        if ($this->_layout) {
            ob_start();
            require($this->_layout);
            return ob_get_clean();
        } else {
            return $html;
        }
    }

    public function includeFragment($name)
    {
        $file = $this->_path . "templates/includes/{$name}.php";
        if (is_file($file)) {
            ob_start();
            require($file);
            return ob_get_clean();
        } else {
            throw new Exception("Unable to include fragment `$name`: file not exisits");
        }
    }

    public function e($text)
    {
        return htmlspecialchars($text);
    }

    /**
     * Returns URI created based on the $params.
     * 
     * @param array $params
     * @return string
     */
    public function link(array $params = array())
    {
        return App::link($params);
    }

    public function getSort($default_col, $default_dir)
    {
        $_REQUEST['sort'] = isset($_REQUEST['sort']) ? $_REQUEST['sort'] : $default_col;
        $_REQUEST['sort_dir'] = isset($_REQUEST['sort_dir']) ? $_REQUEST['sort_dir'] : $default_dir;

        $cols = explode(',', $_REQUEST['sort']);
        $dirs = explode(',', $_REQUEST['sort_dir']);
        $out = array();
        foreach ($cols as $index => $col) {
            $out[] = $col . ' ' . (isset($dirs[$index]) ? $dirs[$index] : '');
        }
        return $out;
    }

    public function __set($key, $value)
    {
        $this->_vars[$key] = $value;
    }

    public function __get($key)
    {
        return isset($this->_vars[$key]) ? $this->_vars[$key] : null;
    }

    private function _setTpl($tpl)
    {
        $_tpl = $this->_path . "templates/{$tpl}.php";
        if (is_file($_tpl)) {
            $this->_tpl = $_tpl;
        } else {
            throw new Exception("Unable to load template `$_tpl`");
        }
    }

    private function _prepare()
    {
        $this->_addSystemVariables();
        ob_start();
        require($this->_tpl);
        return ob_get_clean();
    }

    private function _addSystemVariables()
    {
        foreach (self::$_add_vars as $k => $v) {
            $this->$k = $v;
        }
    }
}

?>
