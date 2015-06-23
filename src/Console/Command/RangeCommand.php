<?php

/*
 * This file is part of the VirtualBox Snapshot Delete.
 *
 * (c) Robert Worgul <robert.worgul@scitotec.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Delbertooo\VirtualBox\SnapshotDelete\Console\Command;

use Delbertooo\VirtualBox\SnapshotDelete\ApiCommunication\Vboxmanage\VboxmanageSnapshotApi;
use Delbertooo\VirtualBox\SnapshotDelete\Model\Snapshot;
use RuntimeException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

/**
 * @author Robert Worgul <robert.worgul@scitotec.de>
 */
class RangeCommand extends Command {

    private $snapshotApi;

    /**
     * {@inheritdoc}
     */
    protected function configure() {
        $this
                ->setName('range')
                ->addOption('vboxmanage', null, InputOption::VALUE_OPTIONAL, 'The command to run for vboxmanage.', 'vboxmanage')
                ->setDescription('Wizard to delete a range of snapshots.')
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output) {
        $input->setInteractive(true);
        
        $this->snapshotApi = new VboxmanageSnapshotApi($input->getOption('vboxmanage'));
        
        $selectedVm = $this->askForVm($input, $output);
        $rootSnapshot = $this->snapshotApi->findSnapshots($selectedVm);
        
        $output->writeln("<info>Snapshots of '$selectedVm->name'</info>");
        $startSnapshot = $this->askForSnapshot($input, $output, $rootSnapshot, 'Please select a start snapshot by number');
        
        $output->writeln("<info>Selected start: '$startSnapshot->name'</info>");
        $endSnapshot = $this->askForSnapshot($input, $output, $startSnapshot, 'Please select an end snapshot by number', true);
        
        if (!$this->questionHelper()->ask($input, $output, new ConfirmationQuestion("<question>Delete snapshot range from '$startSnapshot->name' to '$endSnapshot->name'? [y/N]:</question>", false))) {
            $output->writeln('Aborting!');
            return;
        }
        
        $snapshotsToDelete = $this->listSnapshotsToDelete($startSnapshot, $endSnapshot);
        $progress = new ProgressBar($output, count($snapshotsToDelete));
        $progress->start();
        foreach ($snapshotsToDelete as $snapshot) {
            $this->snapshotApi->deleteSnapshot($snapshot);
            $progress->advance();
        }
        $progress->finish();
        $output->writeln("\n\nDone.");
    }
    
    private function listSnapshotsToDelete(Snapshot $startSnapshot, Snapshot $endSnapshot) {
        $currentSnapshot = $startSnapshot;
        $result = [$currentSnapshot];
        while ($currentSnapshot != $endSnapshot) {
            $currentSnapshot = $currentSnapshot->children[0];
            $result[] = $currentSnapshot;
        }
        return $result;
    }
    
    private function askForVm(InputInterface $input, OutputInterface $output) {
        $vms = $this->snapshotApi->findVirtualMachines();
        $output->writeln('<info>Available virtual machines:</info>');
        foreach ($vms as $index => $vm) {
            $output->writeln(sprintf('<info>% 4d</info>: %s <comment>(UUID: %s)</comment>', ($index + 1), $vm->name, $vm->uuid));
        }
        $vmCount = count($vms);
        $question = new Question("<question>Please select a VM by number (1-$vmCount):</question> ");
        $question->setValidator($this->ennumerationValidator($vmCount));
        $number = $this->questionHelper()->ask($input, $output, $question);
        return $vms[$number - 1];
    }
    
    private function askForSnapshot(InputInterface $input, OutputInterface $output, Snapshot $snapshot, $questionText, $onlyDeletable = false) {
        $this->outputSnapshot($output, $snapshot, $enumeration, $onlyDeletable ? 1 : null);
        $enumerationCount = count($enumeration);
        if ($enumerationCount < 1) {
            throw new \RuntimeException('This snapshot can not be deleted. It has multiple children.');
        }
        $question = new Question("<question>$questionText (1-$enumerationCount):</question> ");
        $question->setValidator($this->ennumerationValidator($enumerationCount));
        $selected = $this->questionHelper()->ask($input, $output, $question);
        return $enumeration[$selected - 1];
    }
    
    private function outputSnapshot(OutputInterface $output, Snapshot $snapshot, &$enumeration = [], $maxChildren = null, $indent = '') {
        if ($maxChildren !== null && count($snapshot->children) > $maxChildren) {
            return;
        }
        $enumeration[] = $snapshot;
        $output->writeln(sprintf("<info>% 4d</info>: $indent$snapshot->name%s <comment>(UUID: $snapshot->uuid)</comment>", count($enumeration), $snapshot->active ? ' (*)' : ''));
        if (empty($snapshot->children)) {
            return;
        }
        foreach ($snapshot->children as $child) {
            $this->outputSnapshot($output, $child, $enumeration, $maxChildren, $indent . '  ');
        }
    }
    
    private function ennumerationValidator($max) {
        return function ($answer) use ($max) {
            $result = (int) $answer;
            if ($result < 1 || $result > $max) {
                throw new RuntimeException("Invalid number '$answer'. Please enter a valid number.");
            }
            return $result;
        };
    }

    /**
     * @return QuestionHelper
     */
    private function questionHelper() {
        return $this->getHelper('question');
    }

}
