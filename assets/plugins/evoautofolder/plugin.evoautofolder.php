<?php
$e = &$modx->event;
if ($e->activePlugin == 'evoAutoFolder') {
    include_once(MODX_BASE_PATH.'assets/plugins/evoautofolder/lib/plugin.class.php');
    $plugin = new \evoAutoFolder\Plugin($modx);
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
if ($e->name == 'OnManagerMainFrameHeaderHTMLBlock') {
    $plugin->unsetFolder();
}
if ($e->name == 'OnDocDuplicate') {
    $plugin->onDuplicate();
}