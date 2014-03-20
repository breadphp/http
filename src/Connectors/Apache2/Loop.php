<?php
/**
 * Bread PHP Framework (http://github.com/saiv/Bread)
 * Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 *
 * Licensed under a Creative Commons Attribution 3.0 Unported License.
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright  Copyright 2010-2012, SAIV Development Team <development@saiv.it>
 * @link       http://github.com/saiv/Bread Bread PHP Framework
 * @package    Bread
 * @since      Bread PHP Framework
 * @license    http://creativecommons.org/licenses/by/3.0/
 */
namespace Bread\Networking\HTTP\Connectors\Apache2;

use Bread\Event;

class Loop extends Event\Loop\StreamSelect implements Event\Interfaces\Loop
{

    public function tick()
    {
        if (empty($this->readStreams) && empty($this->writeStreams)) {
            $this->stop();
        }
        foreach ($this->readStreams as $stream) {
            $listener = $this->readListeners[(int) $stream];
            if (call_user_func($listener, $stream, $this) === false) {
                $this->removeReadStream($stream);
            }
        }
        foreach ($this->writeStreams as $stream) {
            if (!isset($this->writeListeners[(int) $stream])) {
                continue;
            }
            $listener = $this->writeListeners[(int) $stream];
            if (call_user_func($listener, $stream, $this) === false) {
                $this->removeWriteStream($stream);
            }
        }
        return $this->running;
    }
}
