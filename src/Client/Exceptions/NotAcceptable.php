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
 * Implements HTTP status code "406 Not Acceptable"
 *
 * The requested resource is only capable of generating content not acceptable
 * according to the Accept headers sent in the request.
 */
class NotAcceptable extends Exception
{

    protected $code = 406;

    protected $message = "Not Acceptable";

    public function __construct($accept)
    {
        parent::__construct(sprintf("Request doesn't accept %s", $accept));
    }
}
