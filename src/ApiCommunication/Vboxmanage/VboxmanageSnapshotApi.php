<?php

/*
 * This file is part of the VirtualBox Snapshot Delete.
 *
 * (c) Robert Worgul <robert.worgul@scitotec.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Delbertooo\VirtualBox\SnapshotDelete\ApiCommunication\Vboxmanage;

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
        $parser = new SnapshotListParser($snapshots);
        $parsedSnapshots = [];
        while ($parser->hasMoreLines()) {
            if ($parser->currentLineIs('CurrentSnapshotName')) {
                $parser->skipLine(); // skip name
                $parser->skipLine(); // skip uuid
                list($current) = $parser->readLine('CurrentSnapshotNode');
                continue; // check if we are done
            }
            list($name, $path) = $parser->readLine('SnapshotName');
            list($uuid) = $parser->readLine('SnapshotUUID');

            if ($parser->currentLineIs('SnapshotDescription')) {
                $parser->skipLine(); // description
            }

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
        sleep(2); // give virtualbox some time, maybe parse some status instead?
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
