<div class="messages">
    <?foreach ($this->app->controller->getMessageTypes() as $type) {
    foreach ($this->app->controller->getMessages($type, true) as $error) {
        ?>
        <div class="<?=$type?>">&bull; <?=$error?></div>
        <?
    }
}?>
</div>
