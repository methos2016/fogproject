<?php
class AddLocationGroup extends Hook {
    public function __construct() {
        parent::__construct();
        $this->name = 'AddLocationGroup';
        $this->description = 'Add menu items to the management page';
        $this->author = 'Rowlett';
        $this->active = true;
        $this->node = 'location';
    }
    public function GroupFields($arguments) {
        if (!in_array($this->node,(array)$_SESSION['PluginsInstalled'])) return;
        if ($_REQUEST['node'] != 'group') return;
        $locationID = $this->getSubObjectIDs('LocationAssociation',array('hostID'=>$arguments['Group']->get('hosts')),'locationID');
        $locID = array_shift($locationID);
        $arguments['fields'] = $this->array_insert_after(_('Group Product Key'),$arguments['fields'],_('Group Location'),$this->getClass('LocationManager')->buildSelectBox($locID));
    }
    public function GroupAddLocation($arguments) {
        if (!in_array($this->node,(array)$_SESSION['PluginsInstalled'])) return;
        if ($_REQUEST['node'] != 'group') return;
        if (str_replace('_','-',$_REQUEST['tab']) != 'group-general') return;
        if (!$_REQUEST['location']) return;
        $Location = $this->getClass('Location',$_REQUEST['location']);
        if (!$Location->isValid()) return;
        $Location->addHost($arguments['Group']->get('hosts'))->save(false);
    }
}
$AddLocationGroup = new AddLocationGroup();
$HookManager->register('GROUP_FIELDS', array($AddLocationGroup, 'GroupFields'));
$HookManager->register('GROUP_EDIT_SUCCESS', array($AddLocationGroup, 'GroupAddLocation'));
