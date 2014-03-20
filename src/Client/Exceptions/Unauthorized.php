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
 * Implements HTTP status code "401 Unauthorized"
 *
 * Similar to 403 Forbidden, but specifically for use when authentication is
 * required and has failed or has not yet been provided. The response must
 * include a WWW-Authenticate header field containing a challenge applicable to
 * the requested resource.
 */
class Unauthorized extends Exception
{

    protected $code = 401;

    protected $message = "Unauthorized";
}
