<?php

class ControllerDefault
{
    public function actionIndex()
    {
        $tpl = new Template('home');
        $tpl->title = 'Profiler Web Interface';
        $tpl->breadcrumb = 'Home Page';
        return $tpl;
    }

    public function postProcess($view)
    {
        if ($view instanceof Template) {
            $view->maintaningAppStatus = App::getModel('maintainAgent')->getStatus();
        }
        return $view;
    }

    public function actionAccessDenied()
    {
        if (!App::auth()->isLogged()) {
            App::forward('login&default');
        } else {
            $tpl = new Template('access_denied');
            $tpl->title = 'Profiler Web Interface: access denied';
            return $tpl;
        }
    }

    // MESSAGES
    public function addMessage($type, $text)
    {
        $_SESSION['_MESSAGES'] = isset($_SESSION['_MESSAGES']) ? $_SESSION['_MESSAGES'] : array();
        $_SESSION['_MESSAGES'][$type] = isset($_SESSION['_MESSAGES'][$type]) ? $_SESSION['_MESSAGES'][$type] : array();
        $_SESSION['_MESSAGES'][$type][] = $text;
    }

    public function getMessageTypes()
    {
        $_SESSION['_MESSAGES'] = isset($_SESSION['_MESSAGES']) ? $_SESSION['_MESSAGES'] : array();
        return array_keys($_SESSION['_MESSAGES']);
    }

    public function getMessages($type, $clear = true)
    {
        if (isset($_SESSION['_MESSAGES'][$type])) {
            $res = $_SESSION['_MESSAGES'][$type];
            if ($clear) {
                $_SESSION['_MESSAGES'][$type] = array();
            }
            return array_unique($res);
        }
        return array();
    }
}
