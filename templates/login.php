<div class="login">
    <span>Please enter your user name and password.</span>

    <form action="?login&default<?=isset($_REQUEST['return_url'])
        ? '&returnUrl=' . urlencode($_REQUEST['return_url'])
        : ''?>" method="post">
        <table>
            <tr>
                <td>User name:</td>
                <td><input type="text" name="user_login"></td>
            </tr>
            <tr>
                <td>Password:</td>
                <td><input type="password" name="user_pass"></td>
            </tr>
            <tr>
                <td></td>
                <td><input type="submit" value="Log In"/></td>
            </tr>
        </table>
    </form>
</div>
