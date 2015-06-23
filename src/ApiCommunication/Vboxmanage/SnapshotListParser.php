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

/**
 * @author Robert Worgul <robert.worgul@scitotec.de>
 */
class SnapshotListParser {

    private $lines;

    /**
     * Constructor.
     * @param string $lines The lines output by the vboxmanage command.
     */
    public function __construct(array $lines) {
        $this->lines = $lines;
    }

    /**
     * Checks if the actual line key matches the given pattern.
     * 
     * @param string $key The prefix of the key, i.e. "SnapshotName" or "CurrentSnapshotUUID".
     * @return bool
     */
    public function currentLineIs($key) {
        return ($this->parseSnapshotValue($key, current($this->lines)) !== null);
    }

    /**
     * Returns the full key and value for the actual line. After reading the actual line is skipped. If the actual line
     * does not match <code>$key</code>, an Exception is thrown.
     *
     * @param string $key The prefix of the key, i.e. "SnapshotName" or "CurrentSnapshotUUID".
     * @return array An array with two elements. The first element is the lines value and the second element is the full key.
     * @throws \RuntimeException
     */
    public function readLine($key) {
        $current = current($this->lines);
        $result = $this->parseSnapshotValue($key, $current);
        if ($result === null) {
            throw new \RuntimeException("Expected '$key' in '$current'.");
        }
        $this->skipLine();
        return $result;
    }

    /**
     * Skips the actual line.
     */
    public function skipLine() {
        next($this->lines);
    }

    /**
     * Returns if there is more to read or not.
     * 
     * @return bool
     */
    public function hasMoreLines() {
        return current($this->lines) !== false;
    }

    private function parseSnapshotValue($value) {
        $subject = current($this->lines);
        if ($subject === false || !preg_match('/^(' . $value . '.*)="(.*?)("?)$/', $subject, $matches)) {
            return null;
        }

        $parsedKey = $matches[1];
        $parsedValue = $matches[2];

        if ($matches[3] !== '"') {
            do {
                $subject = next($this->lines);
                if (preg_match('/(.*)"$/', $subject, $matches)) {
                    $parsedValue .= $matches[1];
                    break;
                }
                $parsedValue .= $subject;
            } while (current($this->lines) !== false);
        }
        return [$parsedValue, $parsedKey];
    }

}
