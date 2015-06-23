<?php

/*
 * This file is part of the VirtualBox Snapshot Delete.
 *
 * (c) Robert Worgul <robert.worgul@scitotec.de>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Delbertooo\VirtualBox\SnapshotDelete\Console;

use Delbertooo\VirtualBox\SnapshotDelete\Console\Command\RangeCommand;
use Symfony\Component\Console\Application as BaseApplication;

/**
 * @author Robert Worgul <robert.worgul@scitotec.de>
 */
class Application extends BaseApplication {

    /**
     * Constructor.
     */
    public function __construct() {
        error_reporting(-1);
        parent::__construct('VirtualBox Snapshot Delete', '1.0.1');
        $this->add(new RangeCommand());
    }

    public function getLongVersion() {
        $version = parent::getLongVersion() . ' by <comment>Robert Worgul</comment>';
        $commit = '@git-commit@';
        if ('@' . 'git-commit@' !== $commit) {
            $version .= ' (' . substr($commit, 0, 7) . ')';
        }
        return $version;
    }

}
