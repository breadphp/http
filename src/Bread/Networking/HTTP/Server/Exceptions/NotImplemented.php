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
 * Implements HTTP status code "501 Not Implemented"
 *
 * The server either does not recognize the request method, or it lacks the
 * ability to fulfill the request.
 */
class NotImplemented extends Exception
{

    protected $code = 501;

    protected $message = "Not Implemented";
    
    public function __construct($method)
    {
        parent::__construct(sprintf("Method %s not implemented", $method));
    }
}
