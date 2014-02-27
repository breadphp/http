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
namespace Bread\Networking\HTTP;

use Bread\Networking;
use Bread\Event;
use Bread\Streaming;

abstract class Message extends Event\Emitter implements Streaming\Interfaces\Readable, Streaming\Interfaces\Writable
{
    use Streaming\Traits\Pipe;

    const DATETIME_FORMAT = 'D, j M Y G:i:s \G\M\T';

    public $connection;

    public $startLine;

    public $protocol;

    public $headers;

    public $body;

    protected $read = 0;

    protected $written = 0;

    protected $bufferSize = 4096;

    protected $readable = true;

    protected $writable = true;

    protected $closed = false;

    protected $chunkedEncoding = false;

    protected $mimeTypes = array(
        'html' => array(
            'text/html',
            '*/*'
        ),
        'json' => 'application/json',
        'xml' => array(
            'application/xml',
            'text/xml'
        ),
        'rss' => 'application/rss+xml',
        'ai' => 'application/postscript',
        'bcpio' => 'application/x-bcpio',
        'bin' => 'application/octet-stream',
        'ccad' => 'application/clariscad',
        'cdf' => 'application/x-netcdf',
        'class' => 'application/octet-stream',
        'cpio' => 'application/x-cpio',
        'cpt' => 'application/mac-compactpro',
        'csh' => 'application/x-csh',
        'csv' => array(
            'text/csv',
            'application/vnd.ms-excel',
            'text/plain'
        ),
        'dcr' => 'application/x-director',
        'dir' => 'application/x-director',
        'dms' => 'application/octet-stream',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'drw' => 'application/drafting',
        'dvi' => 'application/x-dvi',
        'dwg' => 'application/acad',
        'dxf' => 'application/dxf',
        'dxr' => 'application/x-director',
        'eot' => 'application/vnd.ms-fontobject',
        'eps' => 'application/postscript',
        'exe' => 'application/octet-stream',
        'ez' => 'application/andrew-inset',
        'flv' => 'video/x-flv',
        'gtar' => 'application/x-gtar',
        'gz' => 'application/x-gzip',
        'bz2' => 'application/x-bzip',
        '7z' => 'application/x-7z-compressed',
        'hdf' => 'application/x-hdf',
        'hqx' => 'application/mac-binhex40',
        'ico' => 'image/x-icon',
        'ips' => 'application/x-ipscript',
        'ipx' => 'application/x-ipix',
        'js' => 'application/javascript',
        'latex' => 'application/x-latex',
        'lha' => 'application/octet-stream',
        'lsp' => 'application/x-lisp',
        'lzh' => 'application/octet-stream',
        'man' => 'application/x-troff-man',
        'me' => 'application/x-troff-me',
        'mif' => 'application/vnd.mif',
        'ms' => 'application/x-troff-ms',
        'nc' => 'application/x-netcdf',
        'oda' => 'application/oda',
        'otf' => 'font/otf',
        'pdf' => 'application/pdf',
        'pgn' => 'application/x-chess-pgn',
        'pot' => 'application/vnd.ms-powerpoint',
        'pps' => 'applicatiothis->n/vnd.ms-powerpoint',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'ppz' => 'application/vnd.ms-powerpoint',
        'pre' => 'application/x-freelance',
        'prt' => 'application/pro_eng',
        'ps' => 'application/postscript',
        'roff' => 'application/x-troff',
        'scm' => 'application/x-lotusscreencam',
        'set' => 'application/set',
        'sh' => 'application/x-sh',
        'shar' => 'application/x-shar',
        'sit' => 'application/x-stuffit',
        'skd' => 'application/x-koan',
        'skm' => 'application/x-koan',
        'skp' => 'application/x-koan',
        'skt' => 'application/x-koan',
        'smi' => 'application/smil',
        'smil' => 'application/smil',
        'sol' => 'application/solids',
        'spl' => 'application/x-futuresplash',
        'src' => 'application/x-wais-source',
        'step' => 'application/STEP',
        'stl' => 'application/SLA',
        'stp' => 'application/STEP',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc' => 'application/x-sv4crc',
        'svg' => 'image/svg+xml',
        'svgz' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        't' => 'application/x-troff',
        'tar' => 'application/x-tar',
        'tcl' => 'application/x-tcl',
        'tex' => 'application/x-tex',
        'texi' => 'application/x-texinfo',
        'texinfo' => 'applithis->cation/x-texinfo',
        'tr' => 'application/x-troff',
        'tsp' => 'application/dsptype',
        'ttc' => 'font/ttf',
        'ttf' => 'font/ttf',
        'unv' => 'application/i-deas',
        'ustar' => 'application/x-ustar',
        'vcd' => 'application/x-cdlink',
        'vda' => 'application/vda',
        'xlc' => 'application/vnd.ms-excel',
        'xll' => 'application/vnd.ms-excel',
        'xlm' => 'application/vnd.ms-excel',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xlw' => 'application/vnd.ms-excel',
        'zip' => 'application/zip',
        'aif' => 'audio/x-aiff',
        'aifc' => 'audio/x-aiff',
        'aiff' => 'audio/x-aiff',
        'au' => 'audio/basic',
        'kar' => 'audio/midi',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'mp2' => 'audio/mpeg',
        'mp3' => 'audio/mpeg',
        'mpga' => 'audio/mpeg',
        'ogg' => 'audio/ogg',
        'oga' => 'audio/ogg',
        'spx' => 'audio/ogg',
        'ra' => 'audio/x-realaudio',
        'ram' => 'audio/x-pn-realaudio',
        'rm' => 'audio/x-pn-realaudio',
        'rpm' => 'audio/x-pn-realaudio-plugin',
        'snd' => 'audio/basic',
        'tsi' => 'audio/TSP-audio',
        'wav' => 'audio/x-wthis->av',
        'aac' => 'audio/aac',
        'asc' => 'text/plain',
        'c' => 'text/plain',
        'cc' => 'text/plain',
        'css' => 'text/css',
        'etx' => 'text/x-setext',
        'f' => 'text/plain',
        'f90' => 'text/plain',
        'h' => 'text/plain',
        'hh' => 'text/plain',
        'htm' => array(
            'text/html',
            '*/*'
        ),
        'ics' => 'text/calendar',
        'm' => 'text/plain',
        'rtf' => 'text/rtf',
        'rtx' => 'text/richtext',
        'sgm' => 'text/sgml',
        'sgml' => 'text/sgml',
        'tsv' => 'text/tab-separated-values',
        'tpl' => 'text/template',
        'txt' => 'text/plain',
        'text' => 'text/plain',
        'avi' => 'video/x-msvideo',
        'fli' => 'video/x-fli',
        'mov' => 'video/quicktime',
        'movie' => 'video/x-sgi-movie',
        'mpe' => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'qt' => 'video/quicktime',
        'viv' => 'video/vnd.vivo',
        'vivo' => 'video/vnd.vivo',
        'ogv' => 'video/ogg',
        'webm' => 'video/wthis->ebm',
        'mp4' => 'video/mp4',
        'm4v' => 'video/mp4',
        'f4v' => 'video/mp4',
        'f4p' => 'video/mp4',
        'm4a' => 'audio/mp4',
        'f4a' => 'audio/mp4',
        'f4b' => 'audio/mp4',
        'gif' => 'image/gif',
        'ief' => 'image/ief',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'pbm' => 'image/x-portable-bitmap',
        'pgm' => 'image/x-portable-graymap',
        'png' => 'image/png',
        'pnm' => 'image/x-portable-anymap',
        'ppm' => 'image/x-portable-pixmap',
        'ras' => 'image/cmu-raster',
        'rgb' => 'image/x-rgb',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'xbm' => 'image/x-xbitmap',
        'xpm' => 'image/x-xpixmap',
        'xwd' => 'image/x-xwindowdump',
        'ice' => 'x-conference/x-cooltalk',
        'iges' => 'model/iges',
        'igs' => 'model/iges',
        'mesh' => 'model/mesh',
        'msh' => 'model/mesh',
        'silo' => 'model/mesh',
        'vrml' => 'model/vrml',
        'wrl' => 'model/vrml',
        'mime' => 'www/mime',
        'pdb' => 'chemical/x-pdb',
        'xyz' => 'chemical/x-pdb',
        'javascript' => 'application/javascript',
        'form' => 'application/x-www-form-urlencoded',
        'file' => 'multipart/form-data',
        'xhtml' => array(
            'application/xhtml+xml',
            'application/xhtml',
            'text/xhtml'
        ),
        'xhtml-mobile' => 'application/vnd.wap.xhtml+xml',
        'atom' => 'application/atom+xml',
        'amf' => 'application/x-amf',
        'wap' => array(
            'text/vnd.wap.wml',
            'text/vnd.wap.wmlscript',
            'image/vnd.wap.wbmp'
        ),
        'wml' => 'text/vnd.wap.wml',
        'wmlscript' => 'text/vnd.wap.wmlscript',
        'wbmp' => 'image/vnd.wap.wbmp',
        'woff' => 'application/x-font-woff',
        'webp' => 'image/webp',
        'appcache' => 'text/cache-manifest',
        'manifest' => 'text/cache-manifest',
        'htc' => 'text/x-component',
        'rdf' => 'application/xml',
        'crx' => 'application/x-chrome-extension',
        'oex' => 'application/x-opera-extension',
        'xpi' => 'application/x-xpinstall',
        'safariextz' => 'application/octet-stream',
        'webapp' => 'application/x-web-app-manifest+json',
        'vcf' => 'text/x-vcard',
        'vtt' => 'text/vtt'
    );

    public function __construct(Networking\Interfaces\Connection $connection, $protocol = 'HTTP/1.1', $startLine = '', $headers = array(), $body = null)
    {
        $this->connection = $connection;
        $this->protocol = $protocol;
        $this->startLine = $startLine;
        $this->headers = new Message\Header($headers);
        $this->body($body);
    }

    public function __destruct()
    {
        if (is_resource($this->body)) {
            fclose($this->body);
        }
    }

    public function __get($name)
    {
        switch ($name) {
            case 'type':
                $type = $this->headers['Content-Type'];
                $type = explode(';', $type);
                $type = array_shift($type);
                return $type;
            case 'length':
                return isset($this->headers['Content-Length']) ? (int) $this->headers['Content-Length'] : null;
        }
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'type':
                $this->type($value);
                break;
            case 'length':
                $this->headers['Content-Length'] = $value;
                break;
        }
    }

    public function __toString()
    {
        return implode("\r\n", array(
            $this->startLine,
            (string) $this->headers,
            null
        ));
    }

    public function headers($headers = array())
    {
        $this->headers = new Message\Header($headers);
    }

    public function header($name, $value, $parameters = array())
    {
        $this->headers[$name] = $value;
    }

    public function body($body = null)
    {
        if (!is_resource($body)) {
            $this->body = fopen("php://temp", 'r+');
            fwrite($this->body, $body);
            rewind($this->body);
        } else {
            $this->body = $body;
        }
    }

    public function isReadable()
    {
        return $this->readable;
    }

    public function isWritable()
    {
        return $this->writable;
    }

    public function pause()
    {
        $this->emit('pause');
    }

    public function resume()
    {
        $this->emit('resume');
    }

    public function write($data)
    {
        $this->emit('headers', array(
            $this
        ));
        if (!$this->writable || !$this->length) {
            $this->end();
            return false;
        }
        if (is_resource($data)) {
            $data = fread($data, min((int) $this->length, (int) $this->bufferSize));
        }
        if ($this->chunkedEncoding) {
            $len = strlen($data);
            $chunk = dechex($len) . "\r\n" . $data . "\r\n";
            $flushed = $this->connection->write($chunk);
        } else {
            $flushed = $this->connection->write($data);
        }
        $this->written += strlen($data);
        if ($this->written >= $this->length) {
            $this->end();
            return false;
        }
        return $this->written;
    }

    public function flush($data = null)
    {
        if (null !== $data) {
            if (!isset($this->headers['Content-Length'])) {
                if (is_resource($data)) {
                    $this->headers['Content-Length'] = ftell($data);
                    rewind($data);
                } else {
                    $this->headers['Content-Length'] = strlen($data);
                }
            }
            $this->body($data);
        } else {
            $this->headers['Content-Length'] = 0;
        }
        $this->connection->loop->addReadStream($this->body, array(
            $this,
            'write'
        ));
        $this->emit('headers', array(
            $this
        ));
    }

    public function end($data = null)
    {
        if ($this->chunkedEncoding) {
            $this->connection->write("0\r\n\r\n");
        }
        $this->emit('headers', array(
            $this
        ));
        $this->emit('end');
        fclose($this->body);
        $this->readable = false;
        $this->writable = false;
        $this->removeAllListeners();
    }

    public function close()
    {
        if ($this->closed) {
            return;
        }
        $this->emit('headers', array(
            $this
        ));
        $this->emit('end');
        $this->readable = false;
        $this->writable = false;
        $this->emit('close');
        $this->closed = true;
        $this->removeAllListeners();
        $this->connection->end();
    }

    public function type($contentType, $charset = 'utf-8')
    {
        if (isset($this->mimeTypes[$contentType])) {
            $contentType = $this->mimeTypes[$contentType];
            $contentType = is_array($contentType) ? current($contentType) : $contentType;
        }
        if ($charset) {
            $contentType = "{$contentType}; charset={$charset}";
        }
        $this->headers['Content-Type'] = $contentType;
    }

    protected function explodeHeader($headerName)
    {
        $keys = array();
        $values = array();
        foreach (explode(';', $this->headers[$headerName]) as $part) {
            list ($key, $value) = explode('=', urldecode($part), 2) + array(
                null,
                null
            );
            $keys[] = trim($key);
            $values[] = trim($value);
        }
        return array_combine($keys, $values);
    }
}
