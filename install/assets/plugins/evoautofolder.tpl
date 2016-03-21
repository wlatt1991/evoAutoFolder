//<?php
/**
 * evoAutoFolder
 * 
 * Plugin to create folders named with resource id
 *
 * @category    plugin
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Wlatt (wlatt.ru)
 * @internal    @properties &upload_Dir=Content folder (images or files);text;images &lifetime=DB records lifetime, hours;text;24
 * @internal    @events OnDocFormRender, OnDocFormSave, OnManagerPageInit, onBeforeMoveDocument, onAfterMoveDocument, OnBeforeEmptyTrash
 * @internal    @installset base
 */

require MODX_BASE_PATH.'assets/plugins/evoautofolder/plugin.evoautofolder.php';
