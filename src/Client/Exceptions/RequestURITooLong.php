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
 * Implements HTTP status code "414 Request-URI Too Long"
 *
 * The URI provided was too long for the server to process.
 */
class RequestURITooLong extends Exception
{

    protected $code = 414;

    protected $message = "Request-URI Too Long";
}
