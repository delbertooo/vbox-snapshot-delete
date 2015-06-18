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

use Delbertooo\VirtualBox\SnapshotDelete\ApiCommunication\SnapshotApiInterface;
use Delbertooo\VirtualBox\SnapshotDelete\Model\Snapshot;
use Delbertooo\VirtualBox\SnapshotDelete\Model\VirtualMachine;
use LogicException;

/**
 * @author Robert Worgul <robert.worgul@scitotec.de>
 */
class VboxmanageSnapshotApi implements SnapshotApiInterface {

    private $vboxmanageCommand;

    /**
     * Constructor.
     * @param string $vboxmanageCommand The command for the vboxmanage tool - maybe with path.
     */
    public function __construct($vboxmanageCommand) {
        $this->vboxmanageCommand = $vboxmanageCommand;
    }

    /**
     * {@inheritdoc}
     */
    public function findSnapshots(VirtualMachine $vm) {
        $this->vboxmanage('snapshot ' . escapeshellarg($vm->uuid) . ' list --machinereadable', $snapshots);
        $rootSnapshot = null;
        $parsedSnapshots = [];
        for ($i = 0; $i < count($snapshots); $i += 2) {
            if (preg_match('/^CurrentSnapshotNode="(.+)"$/', $snapshots[$i + 2], $matchesCurrent)) {
                $current = $matchesCurrent[1];
                break;
            }
            if (!preg_match('/^(SnapshotName.*)="(.+)"$/', $snapshots[$i], $matchesName)) {
                throw new LogicException("Invalid VM string from vboxmanage '$snapshots[$i]'.");
            }
            $name = $matchesName[2];
            $path = $matchesName[1];
            if (!preg_match('/^SnapshotUUID(.*)="(.+)"$/', $snapshots[$i + 1], $matchesUuid)) {
                throw new LogicException("Invalid VM string from vboxmanage '${snapshots[$i + 1]}'.");
            }
            $uuid = $matchesUuid[2];
            $parsedSnapshots[$path] = new Snapshot($vm, $name, $uuid, false);
            if (preg_match('/^(.+)\-\d+$/', $path, $matchesParent)) {
                $parsedSnapshots[$matchesParent[1]]->children[] = $parsedSnapshots[$path];
            }
            if ($rootSnapshot === null) {
                $rootSnapshot = $parsedSnapshots[$path];
            }
        }
        $parsedSnapshots[$current]->active = true;
        return $rootSnapshot;
    }

    /**
     * {@inheritdoc}
     */
    public function findVirtualMachines() {
        $this->vboxmanage('list vms', $vms);
        return array_map(function ($line) {
            if (!preg_match('/^"(.*)" {(.*)}$/', $line, $matches)) {
                throw new LogicException('Invalid VM string from vboxmanage.');
            }
            return new VirtualMachine($matches[1], $matches[2]);
        }, $vms);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteSnapshot(Snapshot $snapshot) {
        if (count($snapshot->children) > 1) {
            throw new \RuntimeException('Can not delete a snapshot with multiple children.');
        }
        $this->vboxmanage(sprintf('snapshot %s delete %s 2>&1', $snapshot->vm->uuid, $snapshot->uuid));
    }

    private function vboxmanage($command, &$output = null) {
        $execCommand = "$this->vboxmanageCommand $command";
        exec($execCommand, $output, $returnValue);
        if ($returnValue !== 0) {
            throw new \RuntimeException("The vboxmanage command '$execCommand' returned $returnValue.");
        }
        return $returnValue;
    }

}
