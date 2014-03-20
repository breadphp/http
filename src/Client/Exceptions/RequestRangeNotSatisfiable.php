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
namespace Bread\Networking\HTTP\Client\Exceptions;

use Bread\Networking\HTTP\Exception;

/**
 * Implements HTTP status code "416 Request Range Not Satisfiable"
 *
 * The client has asked for a portion of the file, but the server cannot supply
 * that portion. For example, if the client asked for a part of the file that
 * lies beyond the end of the file.
 */
class RequestRangeNotSatisfiable extends Exception
{

    protected $code = 416;

    protected $message = "Request Range Not Satisfiable";
}
