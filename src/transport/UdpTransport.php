<?php
/**
 * Created by Kirill Djonua <k.djonua@gmail.com>
 */

namespace kdjonua\grayii\transport;

use yii\base\Configurable;

/**
 * Class UdpTransport
 * @package kdjonua\grayii\transport
 */
class UdpTransport extends \Gelf\Transport\UdpTransport implements Configurable
{
    public function __construct($config = [])
    {
        parent::__construct(
            $config['host'] ?: self::DEFAULT_HOST,
            $config['port'] ?: self::DEFAULT_PORT,
            $config['chunkSize'] ?: self::CHUNK_SIZE_WAN
        );
    }
}
