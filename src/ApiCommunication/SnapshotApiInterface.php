<?php

/*
 * This file is part of the VirtualBox Snapshot Delete.
 *
 * (c) Robert Worgul <robert.worgul@scitotec.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Delbertooo\VirtualBox\SnapshotDelete\ApiCommunication;

use Delbertooo\VirtualBox\SnapshotDelete\Model\Snapshot;
use Delbertooo\VirtualBox\SnapshotDelete\Model\VirtualMachine;

/**
 * @author Robert Worgul <robert.worgul@scitotec.de>
 */
interface SnapshotApiInterface {

    /**
     * @return VirtualMachine[] All known virtual machines.
     */
    function findVirtualMachines();

    /**
     * @param VirtualMachine $vm The VM to find snapshots of.
     * @return Snapshot The root snapshot containing all other snapshots as children.
     */
    function findSnapshots(VirtualMachine $vm);
    
    /**
     * @param Snapshot $snapshot The snapshot which should be deleted.
     */
    function deleteSnapshot(Snapshot $snapshot);
}
