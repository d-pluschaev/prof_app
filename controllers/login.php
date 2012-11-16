<?php

class ControllerLogin extends ControllerDefault
{

    public function actionDefault()
    {
        if (!App::auth()->isLogged()) {

            if (!empty($_REQUEST['user_login']) && !empty($_REQUEST['user_pass'])) {
                $loginModel = App::getModel('login');
                $user = $loginModel->getUser($_REQUEST['user_login']);
                if ($user) {
                    if ($loginModel->checkPass($user, $_REQUEST['user_pass'])) {
                        unset($user['pass']);
                        App::auth()->set($user);
                        $url = !empty($_REQUEST['return_url']) ? $_REQUEST['return_url'] : '?';
                        App::forward($url, false);
                    } else {
                        $this->addMessage('error', 'Wrong password');
                    }

                } else {
                    $this->addMessage('error', 'Wrong user name');
                }
            } elseif (isset($_REQUEST['user_login']) || isset($_REQUEST['user_pass'])) {
                $this->addMessage('error', 'Please enter login and password');
            }

            $tpl = new Template('login');
            $tpl->title = 'Profiler Web Interface: login';
            $tpl->breadcrumb = 'Login';

            return $tpl;

        } else {
            App::forward("?", false);
        }
    }

    public function  actionLogout()
    {
        App::auth()->set(array());
        App::forward("?", false);
    }
}

