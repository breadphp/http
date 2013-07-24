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
namespace Bread\Networking\HTTP\Server\Exceptions;

use Bread\Networking\HTTP\Exception;

/**
 * Implements HTTP status code "500 Internal Server Error"
 *
 * A generic error message, given when no more specific message is suitable.
 */
class InternalServerError extends Exception
{

    protected $code = 500;

    protected $message = "Internal Server Error";
}
