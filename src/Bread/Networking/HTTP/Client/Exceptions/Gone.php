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
 * Implements HTTP status code "410 Gone"
 *
 * Indicates that the resource requested is no longer available and will not be
 * available again. This should be used when a resource has been intentionally
 * removed and the resource should be purged. Upon receiving a 410 status code,
 * the client should not request the resource again in the future. Clients such
 * as search engines should remove the resource from their indices. Most use
 * cases do not require clients and search engines to purge the resource, and a
 * "404 Not Found" may be used instead.
 */
class Gone extends Exception
{

    protected $code = 410;

    protected $message = "Gone";
}
