<?php

class Auth
{
    protected $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
        $_SESSION['_AUTH'] = isset($_SESSION['_AUTH']) ? $_SESSION['_AUTH'] : array();
    }

    public function set(array $user)
    {
        $_SESSION['_AUTH'] = $user;
    }

    public function get($key = null)
    {
        return $key
            ? (isset($_SESSION['_AUTH'][$key]) ? $_SESSION['_AUTH'][$key] : null)
            : $_SESSION['_AUTH'];
    }

    public function getRole()
    {
        $role = $this->get('role');
        return $role ? $role : 'guest';
    }

    public function hasAccessToRoute(array $def_route)
    {
        $role = $this->getRole();
        $allowed_routes = $this->routes[$role];

        foreach ($allowed_routes as $route) {
            list($r_controller, $r_action) = explode(':', $route);
            $r_controller = strtolower(App::filterText($r_controller, '*'));
            $r_action = strtolower(App::filterText($r_action, '*'));
            $r_action = $r_action == '*' ? '*' : 'action' . $r_action;

            if ($r_controller == $def_route['controller'] || $r_controller == '*') {
                if ($r_action == $def_route['action'] || $r_action == '*') {
                    return true;
                }
            }
        }
        return false;
    }

    public function isLogged()
    {
        return $this->get('id') ? true : false;
    }
}


