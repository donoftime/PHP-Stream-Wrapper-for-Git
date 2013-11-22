<?php
/*
 * Copyright (C) 2011 by TEQneers GmbH & Co. KG
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * Git Stream Wrapper for PHP
 *
 * @category   TQ
 * @package    TQ_Git
 * @subpackage Cli
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */

/**
 * @namespace
 */
namespace TQ\Git\Cli;
use TQ\Vcs\Cli\Binary as VcsBinary;
use TQ\Vcs\Cli\Call;

/**
 * Encapsulates access to th Git command line binary
 *
 * @author     Stefan Gehrig <gehrigteqneers.de>
 * @category   TQ
 * @package    TQ_Git
 * @subpackage Cli
 * @copyright  Copyright (C) 2011 by TEQneers GmbH & Co. KG
 */
class Binary extends VcsBinary
{
    /**
     * Try to find the Git binary on the system
     *
     * @todo    implement platform independent searching strategies
     * @return  string
     */
    public static function locateBinary()
    {
        if (!self::isWindows()) {
            $result = Call::create('which git')->execute();
            return $result->getStdOut();
        }
        return '';
    }

    /**
     * Creates a Git binary interface
     *
     * If no path is given the class tries to find the correct
     * binary {@see locateBinary()}
     *
     * @param   string|null $path           The path to the Git binary or NULL to auto-detect
     * @throws  \InvalidArgumentException   If no binary is found
     */
    public function __construct($path = null)
    {
        if (!$path) {
            $path  = self::locateBinary();
        }
        parent::__construct($path);
    }

    /**
     * Create a call to the VCS binary for later execution
     *
     * @param   string  $path           The full path to the VCS repository
     * @param   string  $command        The VCS command, e.g. show, commit or add
     * @param   array   $arguments      The command arguments
     * @return  Call
     */
    public function createCall($path, $command, array $arguments)
    {
        $handleArg  = function($key, $value) {
            $key  = ltrim($key, '-');
            if (strlen($key) == 1 || is_numeric($key)) {
                $arg = sprintf('-%s', escapeshellarg($key));
                if ($value !== null) {
                    $arg    .= ' '.escapeshellarg($value);
                }
            } else {
                $arg = sprintf('--%s', escapeshellarg($key));
                if ($value !== null) {
                    $arg    .= '='.escapeshellarg($value);
                }
            }
            return $arg;
        };

        if (!self::isWindows()) {
            $binary = escapeshellcmd($this->path);
        } else {
            $binary = $this->path;
        }
        if (!empty($command)) {
            $command    = escapeshellarg($command);
        }
        $args       = array();
        $files      = array();
        $fileMode   = false;
        foreach ($arguments as $k => $v) {
            if ($v === '--' || $k === '--') {
                $fileMode   = true;
                continue;
            }
            if (is_int($k)) {
                if (strpos($v, '-') === 0) {
                    $args[]  = $handleArg($v, null);
                } else if ($fileMode) {
                    $files[] = escapeshellarg($v);
                } else {
                    $args[]  = escapeshellarg($v);
                }
            } else {
                if (strpos($k, '-') === 0) {
                    $args[] = $handleArg($k, $v);
                }
            }
        }

        $cmd    = trim(sprintf('%s %s %s', $binary, $command, implode(' ', $args)));
        if (count($files) > 0) {
            $cmd    .= ' -- '.implode(' ', $files);
        }

        $call   = Call::create($cmd, $path);
        return $call;
    }
}

