<?php
class StorageGroup extends FOGController {
    protected $databaseTable = 'nfsGroups';
    protected $databaseFields = array(
        'id' => 'ngID',
        'name' => 'ngName',
        'description' => 'ngDesc',
    );
    protected $databaseFieldsRequired = array(
        'name',
    );
    protected $additionalFields = array(
        'allnodes',
        'enablednodes',
    );
    protected function loadAllnodes() {
        if (!$this->get('id')) return;
        $this->set('allnodes',self::getSubObjectIDs('StorageNode',array('storageGroupID'=>$this->get('id')),'id'));
    }
    protected function loadEnablednodes() {
        if (!$this->get('id')) return;
        if (!$this->isLoaded('allnodes')) $this->loadAllnodes();
        $this->set('enablednodes',self::getSubObjectIDs('StorageNode',array('storageGroupID'=>$this->get('id'),'id'=>$this->get('allnodes'),'isEnabled'=>1)));
    }
    public function getTotalSupportedClients() {
        if (!$this->get('id')) return;
        if (!$this->isLoaded('enablednodes')) $this->loadEnablednodes();
        return self::getSubObjectIDs('StorageNode',array('id'=>$this->get('enablednodes')),'maxClients',false,'AND','name',false,'array_sum');
    }
    public function getMasterStorageNode() {
        if (!$this->get('id')) return;
        if (!$this->isLoaded('enablednodes')) $this->loadEnablednodes();
        $masternode = self::getSubObjectIDs('StorageNode',array('id'=>$this->get('enablednodes'),'isMaster'=>1,'isEnabled'=>1),'id');
        $masternode = array_shift($masternode);
        if (!$masternode > 0) $masternode = @min($this->get('enablednodes'));
        return self::getClass('StorageNode',$masternode);
    }
    public function getOptimalStorageNode($image) {
        if (!$this->get('id')) return;
        if (!$this->isLoaded('enablednodes')) $this->loadEnablednodes();
        $this->winner = null;
        array_map(function(&$StorageNode) use ($image) {
            if (!$StorageNode->isValid()) return;
            if (!in_array($image,$StorageNode->get('images'))) return;
            if ($StorageNode->get('maxClients') < 1) return;
            if ($this->winner == null || !$this->winner->isValid()) {
                $this->winner = $StorageNode;
                return;
            }
            if ($StorageNode->getClientLoad() < $this->winner->getClientLoad()) $this->winner = $StorageNode;
            unset($StorageNode);
        },(array)self::getClass('StorageNodeManager')->find(array('id'=>$this->get('enablednodes'))));
        if (empty($this->winner) || !($this->winner instanceof StorageNode)) $this->winner = self::getClass('StorageNode',(int)@min($this->get('enablednodes')));
        return $this->winner;
    }
    public function getUsedSlotCount() {
        return self::getClass('TaskManager')->count(array(
            'stateID'=>$this->getProgressState(),
            'typeID'=>explode(',',self::getSetting('FOG_USED_TASKS')),
            'NFSGroupID'=>$this->get('id'),
        ));
    }
    public function getQueuedSlotCount() {
        return self::getClass('TaskManager')->count(array(
            'stateID'=>$this->getQueuedStates(),
            'typeID'=>array(1,2,8,15,16,17,24),
            'NFSGroupID'=>$this->get('id'),
        ));
    }
}
