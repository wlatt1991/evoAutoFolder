<?php namespace evoAutoFolder;
require_once (MODX_BASE_PATH . 'assets/lib/Helpers/FS.php');
class Plugin {
    public $pluginName = 'evoAutoFolder';
    public $modx = null;
    public $params = array();
    protected $fs = null;
    public $table = 'evoAutoFolder';
    public $_table = '';
    public function __construct($modx) {
        $this->modx = $modx;
        $this->params = $modx->event->params;
        $this->fs = \Helpers\FS::getInstance();
        $this->_table = $modx->getFullTableName($this->table);
        if (!$this->checkTable()) {
            $result = $this->createTable();
            if (!$result) {
                $modx->logEvent(0, 3, "Cannot create {$this->table} table.", $this->pluginName);
                return;
            }
        }
    }
    public function checkTable() {
        $sql = "SHOW TABLES LIKE '{$this->_table}'";
        return $this->modx->db->getRecordCount( $this->modx->db->query($sql));
    }
    public function createTable() {
        $sql = <<< OUT
CREATE TABLE IF NOT EXISTS {$this->_table} (
`temp_id` int,
`temp_dir` TEXT NOT NULL default '',
`id` int,
`move` int
) ENGINE=MyISAM COMMENT='Datatable for evoAutoFolder plugin';
OUT;
        return $this->modx->db->query($sql);
    }
    public function render() {
        if (!$this->params['id']) {
            $tempId = $this->getTempId();
            $tempDir = $tempId;
            $out = "<input type='hidden' name='temporary_id' value='{$tempId}'>";
            $this->saveTempdir($tempId, $tempDir);
        } else {
            $tempId = $this->getTempId();
            $this->params['contentDir'] = $this->ParentDir($this->params['id']);
            if ($this->params['contentDir'] != '') {
                $tempDir = "{$this->params['contentDir']}/{$this->params['id']}";
            } else {
                $tempDir = "{$this->params['id']}";
            }
            
            $out = "<input type='hidden' name='temporary_id' value='{$tempId}'>";
            $this->saveTempdir($tempId, $tempDir);
        }
        $this->setFolder($tempDir);
        return $out;
    }
    public function getTempId() {
        if (!empty($_POST) && isset($_POST['temporary_id'])) {
            $tempId = (int)$_POST['temporary_id'];
        } else {
            $tempId = time();
        }
        return $tempId;
    }
    public function setFolder($dir) {
        if (!$this->fs->checkDir($dir)) $this->fs->makeDir('assets/uploads/'.$dir);
        $_SESSION['KCFINDER']['browser_dir'] = "uploads/". $dir."/";
        $_SESSION['KCFINDER']['uploadDir'] = MODX_BASE_PATH."assets/uploads/". $dir."/";
    }
    public function unsetFolder() {
        $_SESSION['KCFINDER']['browser_dir'] = '';
        $_SESSION['KCFINDER']['uploadDir'] = '';
    }
    public function saveTempdir($tempId, $dir, $id=0, $move=0) {
        $tempId = (int)$tempId;
        $dir = $this->modx->db->escape($dir);
        $sql = "INSERT INTO {$this->_table} (`temp_id`,`temp_dir`,`id`,`move`) VALUES ({$tempId},'{$dir}','{$id}','{$move}')";
        return $this->modx->db->query($sql);
    }
    public function deleteTempDir() {
        if (isset($_POST['temporary_id'])) {
            $tempId = (int)$_POST['temporary_id'];
            $sql = "SELECT `temp_dir` FROM {$this->_table} WHERE `temp_id`={$tempId}";
            $res = $this->modx->db->query($sql);
            $tempDir = $this->modx->db->getValue($res);
            $sql = "DELETE FROM {$this->_table} WHERE `temp_id`={$tempId}";
            $res = $this->modx->db->query($sql);
            $this->params['contentDir'] = $this->ParentDir($this->params['id']);
            $this->updateResource($this->params['id'], $tempDir, 1);
            if ($this->params['contentDir'] != '') {
                $this->params['contentDir'] = $this->params['contentDir'].'/'.$this->params['id'];
            } else {
                $this->params['contentDir'] = $this->params['id'];
            }
            if ($this->params['contentDir'] != $tempDir) {
                $ChildIds = $this->modx->getChildIds($this->params['id']);
                foreach ($ChildIds as $Child) {
                    $this->updateResource($Child, $tempDir, 0);
                }
            }
        }
    }
    public function updateResource($id,$tempDir, $mode) {
        include_once(MODX_BASE_PATH.'assets/lib/MODxAPI/modResource.php');
        if ($mode == 1) {
            if ($this->params['contentDir'] != '') {
                @rename(MODX_BASE_PATH.'assets/uploads/'.$tempDir, MODX_BASE_PATH.'assets/uploads/'.$this->params['contentDir'].'/'.$id);
                $doc = new \modResource($this->modx);
                $fields = $doc->edit($id)->toArray();
                foreach ($fields as &$field) {
                    if (is_string($field)) $field = str_replace('/'.$tempDir.'/', '/'.$this->params['contentDir'].'/'.$id.'/', $field);
                }
                $doc->fromArray($fields)->save(false,true);
            } else {
                @rename(MODX_BASE_PATH.'assets/uploads/'.$tempDir, MODX_BASE_PATH.'assets/uploads/'.$id);
                $doc = new \modResource($this->modx);
                $fields = $doc->edit($id)->toArray();
                foreach ($fields as &$field) {
                    if (is_string($field)) $field = str_replace('/'.$tempDir.'/', '/'.$id.'/', $field);
                }
                $doc->fromArray($fields)->save(false,true);
            }     
        }
        if ($mode == 0) {
            $doc = new \modResource($this->modx);
            $fields = $doc->edit($id)->toArray();
            foreach ($fields as &$field) {
                if (is_string($field)) $field = str_replace('/'.$tempDir.'/', '/'.$this->params['contentDir'].'/',$field);
            }
            $doc->fromArray($fields)->save(false,true);
        }
    }
    public function ParentDir($pid) {
        $pids = array();
        while ($this->modx->getParent($pid, '', 'id')['id'] != '') {
            array_push($pids, $this->modx->getParent($pid, '', 'id')['id']);
            $pid = $this->modx->getParent($pid, '', 'id')['id'];
        }
        return implode('/', array_reverse($pids));
    }
    public function clearTable() {
        $lifetime = $this->params['lifetime'] * 60 * 60;
        $lifetime = time() - $lifetime;
        $sql = "SELECT `temp_dir` FROM {$this->_table} WHERE `temp_id`<{$lifetime}";
        $res = $this->modx->db->query($sql);
        while ($row = $this->modx->db->getRow($res)) {
            $dir = "assets/uploads/{$row['temp_dir']}";
            $this->fs->rmDir($dir);
        }
        $sql = "DELETE FROM {$this->_table} WHERE `temp_id`<{$lifetime}";
        $res = $this->modx->db->query($sql);
    }
    public function deleteDir() {
        $col = $this->params['ids'];
        for($i = 0; $i < count($col); $i++) {
            if ($this->ParentDir($col[$i]) != '') {
                $this->fs->rmDir('assets/uploads/' . $this->ParentDir($col[$i]) . '/' . $col[$i]);
            } else {
                $this->fs->rmDir('assets/uploads/' . $col[$i]);
            }
        }
    }
    public function beforeMove() {
        $id_document = $this->params['id_document'];
        $bParentDir = $this->ParentDir($id_document);
        $tempId = $this->getTempId();
        if ($bParentDir != '') {
            $tempDir = "{$bParentDir}/{$id_document}";
        } else {
            $tempDir = "{$id_document}";
        }
        $this->saveTempdir($tempId, $tempDir, $id_document, 1);
    }
    public function afterMove() {
        $id_document = $this->params['id_document'];
        $aParentDir = $this->ParentDir($id_document);
        $sql = "SELECT `temp_dir` FROM {$this->_table} WHERE `id`={$id_document} AND `move`=1";
        $res = $this->modx->db->query($sql);
        $tempDir = $this->modx->db->getValue($res);
        $sql = "DELETE FROM {$this->_table} WHERE `id`={$id_document} AND `move`=1";
        $res = $this->modx->db->query($sql);
        $this->params['contentDir'] = $this->ParentDir($id_document);
        $this->updateResource($id_document, $tempDir, 1);
        if ($this->params['contentDir'] != '') {
            $this->params['contentDir'] = $this->params['contentDir'].'/'.$id_document;
        } else {
            $this->params['contentDir'] = $id_document;
        }
        $ChildIds = $this->modx->getChildIds($id_document);
        foreach ($ChildIds as $Child) {
            $this->updateResource($Child, $tempDir, 0);
        }
    }
    public function copyDirectory($old_dir, $new_dir) {
        $this->fs->makeDir($new_dir);
        if ($objs = glob($old_dir."/*")) {
            foreach($objs as $obj) {
                if (is_dir($obj)) {
                    $name_dir = str_replace($old_dir . '/', "", $obj);
                    $this->copyDirectory($old_dir . '/' . $name_dir, $new_dir . '/' . $name_dir);
                } else {
                    $name_file = str_replace($old_dir . '/', "", $obj);
                    $this->fs->copyFile($old_dir . '/' . $name_file, $new_dir . '/' . $name_file);
                }
            }
        }
    }
    public function onDuplicate() {
        $old_id = $this->params['id'];
        $new_id = $this->params['new_id'];
        if ($this->ParentDir($old_id) != '') {
            $old_dir = $this->ParentDir($old_id) . '/' . $old_id;
            $new_dir = $this->ParentDir($new_id) . '/' . $new_id;
        } else {
            $old_dir = $old_id;
            $new_dir = $new_id;
        }
        if ($this->fs->checkDir('assets/uploads/' . $old_dir . '/files')) { $this->copyDirectory(MODX_BASE_PATH . 'assets/uploads/' . $old_dir . '/files', MODX_BASE_PATH . 'assets/uploads/' . $new_dir . '/files'); };
        if ($this->fs->checkDir('assets/uploads/' . $old_dir . '/images')) { $this->copyDirectory(MODX_BASE_PATH . 'assets/uploads/' . $old_dir . '/images', MODX_BASE_PATH . 'assets/uploads/' . $new_dir . '/images'); };
        if ($this->fs->checkDir('assets/uploads/' . $old_dir . '/media')) { $this->copyDirectory(MODX_BASE_PATH . 'assets/uploads/' . $old_dir . '/media', MODX_BASE_PATH . 'assets/uploads/' . $new_dir . '/media'); };
        if ($this->fs->checkDir('assets/uploads/' . $old_dir . '/flash')) { $this->copyDirectory(MODX_BASE_PATH . 'assets/uploads/' . $old_dir . '/flash', MODX_BASE_PATH . 'assets/uploads/' . $new_dir . '/flash'); };
        if ($this->fs->checkDir('assets/uploads/' . $old_dir . '/.thumbs')) { $this->copyDirectory(MODX_BASE_PATH . 'assets/uploads/' . $old_dir . '/.thumbs', MODX_BASE_PATH . 'assets/uploads/' . $new_dir . '/.thumbs'); };
        $this->params['contentDir'] = $new_dir;
        $tempDir = $old_dir;
        $this->updateResource($new_id, $tempDir, 0);
    }
}