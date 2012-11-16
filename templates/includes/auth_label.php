<? if (App::auth()->isLogged()) {
    $name = App::auth()->get('full_name');
    $name = $name ? $name : App::auth()->get('login');
    ?>
<div class="auth_label">Hello, <?=$name?><a href="<?=$this->link(
    array(
        'controller' => 'login',
        'action' => 'logout',
    )
)?>">sign out</a></div>
<? } else { ?>
<div class="auth_label">
    not authorized
</div>
<? } ?>

