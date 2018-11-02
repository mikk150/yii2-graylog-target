<?php
/**
 * Created by Kirill Djonua <k.djonua@gmail.com>
 */

namespace devgroup\grayii;

use devgroup\grayii\message\Message;
use devgroup\grayii\publisher\Publisher;
use devgroup\grayii\transport\HttpTransport;
use Gelf\MessageValidator;
use Gelf\MessageValidatorInterface;
use Gelf\PublisherInterface;
use Gelf\Transport\TransportInterface;
use Psr\Log\LogLevel;
use Yii;
use yii\di\Container;
use yii\di\Instance;
use yii\log\Logger;
use yii\log\Target;

/**
 * Class GelfTarget
 * @package devgroup\grayii
 */
class GelfTarget extends Target
{
    public $transport = [
        'class' => HttpTransport::class
    ];

    public $publisher = [
        'class' => Publisher::class
    ];

    public $messageValidator = [
        'class' => MessageValidator::class
    ];

    public $messageConfig;

    /**
     * @var Container
     */
    public $container;

    public $version = '1.1';
    public $appName;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        $this->appName = $this->appName ?: Yii::$app->id;
        $this->container = $this->container ?: Yii::$container;

        if (!$this->messageConfig) {
            $this->messageConfig = [
                'class' => Message::class
            ]
        }

        $this->container->set(TransportInterface::class, $this->transport);
        $this->container->set(MessageValidatorInterface::class, $this->messageValidator);
        $this->container->set(PublisherInterface::class, $this->publisher);
    }

    /**
     * @inheritdoc
     */
    public function export()
    {
        $messageGenerator = $this->messageGeneratorExtractor();
        foreach ($messageGenerator as $message) {
            $gelfMessage = $this->createMessage($message);
            $this->publishMessage($gelfMessage);
        }
    }

    /**
     * @return \Generator
     */
    protected function messageGeneratorExtractor()
    {
        foreach ($this->messages as $message) {
            yield $message;
        }
    }

    /**
     * @return TransportInterface
     */
    public function getTransport()
    {
        return $this->container->get(TransportInterface::class);
    }

    /**
     * @return PublisherInterface
     */
    public function getPublisher()
    {
        return $this->container->get(PublisherInterface::class);
    }

    /**
     * @return MessageValidatorInterface
     */
    public function getMessageValidator()
    {
        return $this->container->get(MessageValidatorInterface::class);
    }

    /**
     * @return PhpVersionCheckerInterface
     */
    public function getPhpVersionChecker()
    {
        return $this->container->get(PhpVersionCheckerInterface::class);
    }

    /**
     * @param array $data
     * @return Message
     */
    protected function createMessage($data)
    {
        list($msg, $level, $category, $time) = $data;
        
        /**
         * @var Message $message
         */
        $message = Instance::ensure($this->messageConfig, Message::class);

        $message->setLevel($level);
        $message->setTimestamp($time);
        $message->setVersion($this->version);
        $message->setHost($this->appName ?: Yii::$app->id);

        if ($msg instanceof \Exception || $msg instanceof \Throwable) {
            $short = 'Exception';

            if ($msg instanceof \Error) {
                $short = 'Error';
            }

            $message->setShortMessage($short . ' ' . get_class($msg) . ' ' . $msg->getMessage());
            $message->setFullMessage($msg->getTraceAsString());
            $message->setFile($msg->getFile());
            $message->setLine($msg->getLine());
        } elseif (is_string($msg)) {
            $message->setShortMessage($msg);
        } elseif (is_array($msg)) {
            if (!empty($msg['short'])) {
                $message->setShortMessage($msg['short']);
            } elseif (!empty($msg[0])) {
                $message->setShortMessage($msg[0]);
            } else {
                $message->setShortMessage(array_shift($msg));
            }

            if (!empty($msg['full'])) {
                $message->setFullMessage($msg['full']);
            }

            foreach ($msg as $key => $value) {
                if ((!in_array($key, ['short', 'full'])) && $key !== 0) {
                    $message->setAdditional('_' . $key, $value);
                }
            }
        }

        $message->setAdditional('category', $category);

        return $message;
    }

    /**
     * @param $gelfMessage
     */
    protected function publishMessage(Message $gelfMessage)
    {
        $gelfMessage->trigger(Message::BEFORE_PUBLISH);
        $this->getPublisher()->publish($gelfMessage);
        $gelfMessage->trigger(Message::AFTER_PUBLISH);
    }
}
