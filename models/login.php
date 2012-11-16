<?php

class ModelLogin
{
    public function getUser($login)
    {
        $users = file(App::cfg('auth_file'));
        $id = 0;
        foreach ($users as $row) {
            if (preg_match('~^\s*([A-Za-z0-9_]+)\s+([A-Za-z0-9_]+)\s+([A-Za-z0-9_]+)~', $row, $fields)) {
                $id++;
                if (strcasecmp($fields[1], $login) == 0) {
                    return array(
                        'id' => $id,
                        'login' => $fields[1],
                        'pass' => $fields[2],
                        'role' => $fields[3],
                    );
                }
            }
        }
        return false;
    }

    public function cryptPass($pass)
    {
        return crypt($pass, substr($pass, 0, 2));
    }

    public function checkPass(array $user, $pass)
    {
        return ($user['pass'] == $this->cryptPass($pass));
    }

}
