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
 * Implements HTTP status code "404 Not Found"
 *
 * The requested resource could not be found but may be available again in the
 * future. Subsequent requests by the client are permissible.
 */
class NotFound extends Exception
{

    protected $code = 404;

    protected $message = "Not Found";

    public function __construct($resource)
    {
        parent::__construct(sprintf("Resource %s not found", $resource));
    }
}
