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
 * Implements HTTP status code "415 Unsupported Media Type"
 *
 * The request entity has a media type which the server or resource does not
 * support.
 */
class UnsupportedMediaType extends Exception
{

    protected $code = 415;

    protected $message = "Unsupported Media Type";
    
    public function __construct($mediaType)
    {
        parent::__construct(sprintf("Unsupported media type %s\n", $mediaType));
    }
}
