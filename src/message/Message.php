<?php

namespace devgroup\grayii\message;

use Gelf\MessageInterface;
use yii\base\InvalidArgumentException;
use yii\log\Logger;

class Message extends Component implements MessageInterface
{
    private $_timestamp;
    private $_host;
    private $_level;
    private $_version;
    private $_shortMessage;
    private $_fullMessage;
    private $_facility;
    private $_file;
    private $_line;
    private $_additionals = [];

    const SYSLOG_EMERGENCY = 0;
    const SYSLOG_ALERT = 1;
    const SYSLOG_CRITICAL = 2;
    const SYSLOG_ERROR = 3;
    const SYSLOG_WARNING = 4;
    const SYSLOG_NOTICE = 5;
    const SYSLOG_INFORMATIONAL = 6;
    const SYSLOG_DEBUG = 7;

    const LOG_LEVELS = [
        Logger::LEVEL_ERROR => self::SYSLOG_ERROR,
        Logger::LEVEL_INFO => self::SYSLOG_INFORMATIONAL,
        Logger::LEVEL_PROFILE_BEGIN => self::SYSLOG_DEBUG,
        Logger::LEVEL_PROFILE => self::SYSLOG_DEBUG,
        Logger::LEVEL_PROFILE_END => self::SYSLOG_DEBUG,
        Logger::LEVEL_TRACE => self::SYSLOG_DEBUG,
        Logger::LEVEL_WARNING => self::SYSLOG_WARNING,
    ];

    /**
     * @inheritDoc
     */
    public function init()
    {
        if (!$this->_timestamp) {
            $this->_timestamp = microtime(true);
        }
        if (!$this->_host) {
            $this->_host = gethostname();
        }
        if (!$this->_level) {
            $this->_level = 1; //ALERT
        }
        if ($this->_version) {
            $this->_version = "1.0";
        }

        $this->trigger(self::EVENT_INIT);
    }

    /**
     * @inheritDoc
     */
    public function getVersion()
    {
        return $this->_version;
    }

    public function setVersion($version)
    {
        $this->_version = $version;
    }

    /**
     * @inheritDoc
     */
    public function getHost()
    {
        return $this->_host;
    }

    public function setHost($host)
    {
        $this->_host = $host;
    }

    /**
     * @inheritDoc
     */
    public function getShortMessage()
    {
        return $this->_shortMessage;
    }

    public function setShortMessage($message)
    {
        $this->_shortMessage = $message;
    }

    /**
     * @inheritDoc
     */
    public function getFullMessage()
    {
        return $this->_fullMessage;
    }

    public function setFullMessage($message)
    {
        $this->_fullMessage = $message;
    }

    /**
     * @inheritDoc
     */
    public function getTimestamp()
    {
        return $this->_timestamp;
    }

    public function setTimestamp($timestamp)
    {
        $this->_timestamp = $timestamp;
    }

    /**
     * @inheritDoc
     */
    public function getLevel()
    {
        return $this->_level;
    }

    public function setLevel($level)
    {
        if (!in_array($level, array_keys(self::LOG_LEVELS))) {
            throw new InvalidArgumentException('Level "' . $level . '" does not exist');
        }

        $this->_level = $level;
    }

    /**
     * @inheritDoc
     */
    public function getSyslogLevel()
    {
        return ;
    }

    /**
     * @inheritDoc
     */
    public function getFacility()
    {
        return $this->_facility
    }

    /**
     * @inheritDoc
     */
    public function getFile()
    {
        return $this->_file
    }

    /**
     * @inheritDoc
     */
    public function getLine()
    {
        return $this->_line
    }

    /**
     * @inheritDoc
     */
    public function getAdditional($key)
    {
        if (!isset($this->additionals[$key])) {
            throw new RuntimeException(
                sprintf("Additional key '%s' is not defined", $key)
            );
        }

        return $this->additionals[$key];
    }

    /**
     * @inheritDoc
     */
    public function hasAdditional($key)
    {
        return isset($this->additionals[$key]);
    }

    public function addAdditional($key, $value)
    {
        if (!$key) {
            throw new RuntimeException("Additional field key cannot be empty");
        }

        $this->additionals[$key] = $value;
    }

    /**
     * @inheritDoc
     */
    public function getAllAdditionals()
    {
        return $this->additionals;
    }

    protected function getSyslogLevel()
    {
        return 
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        $message = array(
            'version' => $this->getVersion(),
            'host' => $this->getHost(),
            'short_message' => $this->getShortMessage(),
            'full_message' => $this->getFullMessage(),
            'level' => $this->getSyslogLevel(),
            'timestamp' => $this->getTimestamp(),
            'facility' => $this->getFacility(),
            'file' => $this->getFile(),
            'line' => $this->getLine()
        );

        // Transform 1.1 deprecated fields to additionals
        // Will be refactored for 2.0, see #23
        if ($this->getVersion() == "1.1") {
            foreach (array('line', 'facility', 'file') as $idx) {
                $message["_" . $idx] = $message[$idx];
                unset($message[$idx]);
            }
        }

        // add additionals
        foreach ($this->getAllAdditionals() as $key => $value) {
            $message["_" . $key] = $value;
        }

        // return after filtering empty strings and null values
        return array_filter($message, function ($message) {
            return is_bool($message)
                || (is_string($message) && strlen($message))
                || !empty($message);
        });
    }
}
