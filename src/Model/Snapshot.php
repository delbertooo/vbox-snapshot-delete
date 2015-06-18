<?php

/*
 * This file is part of the VirtualBox Snapshot Delete.
 *
 * (c) Robert Worgul <robert.worgul@scitotec.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Delbertooo\VirtualBox\SnapshotDelete\Model;

/**
 * @author Robert Worgul <robert.worgul@scitotec.de>
 */
class Snapshot {

    public $vm;
    public $name;
    public $uuid;
    public $active;
    public $children = [];

    public function __construct(VirtualMachine $vm, $name, $uuid, $active) {
        $this->vm = $vm;
        $this->name = $name;
        $this->uuid = $uuid;
        $this->active = $active;
    }
    
    public function __toString() {
        $children = implode(', ', array_map(function($x) { return $x->name; }, $this->children));
        return "[Snapshot name=$this->name uuid=$this->uuid active=$this->active children=[$children]]";
    }

}
