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
`temp_id` int(10),
`temp_dir` TEXT NOT NULL default ''
) ENGINE=MyISAM COMMENT='Datatable for evoAutoFolder plugin.';
OUT;
        return $this->modx->db->query($sql);
    }

    public function render() {
        if (!$this->params['id']) {
            $tempId = $this->getTempId();
            $tempDir = "{$this->params['contentDir']}/{$tempId}";
            $out = "<input type='hidden' name='temporary_id' value='{$this->getTempId()}'>";
            $this->saveTempdir($tempId, $tempDir);
        } else {
            $tempDir = "{$this->params['contentDir']}/{$this->params['id']}";
            $out = "";
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
        if (!$this->fs->checkDir($dir)) $this->fs->makeDir('assets/'.$this->params['upload_Dir'].'/'.$dir);
        if (!empty($_SESSION['KCFINDER'])) {
            $_SESSION['KCFINDER']['dir'] = $this->params['upload_Dir'].'/'.$dir;
        } else {
            $_SESSION['dir'] = $this->params['upload_Dir'].'/'.$dir;
        }
    }

    public function saveTempdir($id, $dir) {
        $id = (int)$id;
        $dir = $this->modx->db->escape($dir);
        $sql = "INSERT INTO {$this->_table} (`temp_id`,`temp_dir`) VALUES ({$id},'{$dir}')";
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
            $this->updateResource($this->params['id'],$tempDir);
        }
    }

    public function updateResource($id,$tempDir) {
        include_once(MODX_BASE_PATH.'assets/lib/MODxAPI/modResource.php');
        @rename(MODX_BASE_PATH.'assets/'.$this->params['upload_Dir'].'/'.$tempDir,MODX_BASE_PATH.'assets/'.$this->params['upload_Dir'].'/'.$this->params['contentDir'].'/'.$id);
        $doc = new \modResource($this->modx);
        $fields = $doc->edit($id)->toArray();
        foreach ($fields as &$field) {
            if (is_string($field)) $field = str_replace($tempDir,$this->params['contentDir'].'/'.$id,$field);
        }
        $doc->fromArray($fields)->save(false,true);
    }

    public function clearTable() {
        $lifetime = $this->params['lifetime'] * 60 * 60;
        $lifetime = time() - $lifetime;
        $sql = "SELECT `temp_dir` FROM {$this->_table} WHERE `temp_id`<{$lifetime}";
        $res = $this->modx->db->query($sql);
        while ($row = $this->modx->db->getRow($res)) {
            $dir = "assets/{$this->params['upload_Dir']}/{$row['temp_dir']}";
            $this->fs->rmDir($dir);
        }
        $sql = "DELETE FROM {$this->_table} WHERE `temp_id`<{$lifetime}";
        $res = $this->modx->db->query($sql);
    }
    public function removeDirectory($dir) {
        if ($objs = glob($dir."/*")) {
            foreach($objs as $obj) {
                is_dir($obj) ? removeDirectory($obj) : unlink($obj);
            }
        }
        rmdir($dir);
    }
    public function deleteDir() {
        $col = $this->params['ids'];
        for($i = 0; $i < count($col); $i++) {  
            $rdir = MODX_BASE_PATH . 'assets/'.$this->params['upload_Dir'].'/' . $col[$i];
            $this->removeDirectory($rdir);
        } 
    }

}
