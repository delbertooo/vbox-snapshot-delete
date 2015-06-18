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
class VirtualMachine {

    public $name;
    public $uuid;

    public function __construct($name, $uuid) {
        $this->name = $name;
        $this->uuid = $uuid;
    }
    
    public function __toString() {
        return "[VirtualMachine name=$this->name uuid=$this->uuid]";
    }

}
