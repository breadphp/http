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
 * Implements HTTP status code "405 Method Not Allowed"
 *
 * A request was made of a resource using a request method not supported by that
 * resource; for example, using GET on a form which requires data to be
 * presented via POST, or using PUT on a read-only resource.
 */
class MethodNotAllowed extends Exception
{

    protected $code = 405;

    protected $message = "Method Not Allowed";

    public function __construct($method)
    {
        parent::__construct(sprintf("Method %s not allowed", $method));
    }
}
