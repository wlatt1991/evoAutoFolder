<?php
$e = &$modx->event;
if ($e->activePlugin == 'kcAutoFolder') {
    include_once(MODX_BASE_PATH.'assets/plugins/kcautofolder/lib/plugin.class.php');
    $plugin = new \kcAutoFolder\Plugin($modx);
}
if ($e->name == 'OnDocFormRender') {
    $e->output($plugin->render());
}
if ($e->name == 'OnDocFormSave') {
    $plugin->deleteTempDir();
}
if ($e->name == 'OnManagerPageInit') {
    $plugin->clearTable();
}
if ($e->name == 'OnBeforeEmptyTrash') {
    $plugin->deleteDir();
}
if ($e->name == 'onBeforeMoveDocument') {
    $plugin->beforeMove();
}
if ($e->name == 'onAfterMoveDocument') {
    $plugin->afterMove();
}