//<?php
/**
 * kcAutoFolder
 * 
 * Plugin to create folders named with resource id
 *
 * @category    plugin
 * @version     0.1
 * @license     http://www.gnu.org/copyleft/gpl.html GNU Public License (GPL)
 * @author      Pathologic (m@xim.name)
 * @internal    @properties &contentDir=Content folder;text;content &lifetime=DB records lifetime, hours;text;24
 * @internal    @events OnDocFormRender,OnDocFormSave,OnManagerPageInit
 * @internal    @installset base
 */

require MODX_BASE_PATH.'assets/plugins/kcautofolder/plugin.kcautofolder.php';