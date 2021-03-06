<?php
/**
 * @link https://github.com/notamedia/yii2-sentry
 * @copyright Copyright © 2015 Notamedia Ltd.
 * @license MIT
 */

namespace notamedia\sentry;

use yii\log\Logger;
use yii\log\Target;

/**
 * SentryTarget records log messages in a Sentry.
 * 
 * @see https://getsentry.com
 */
class SentryTarget extends Target
{
    /**
     * @var string Client key.
     */
    public $dsn;
    /**
     * @var array Options of the \Raven_Client.
     */
    public $clientOptions = [];
    /**
     * @var bool Write the context information. The default implementation will dump user information, system variables, etc.
     */
    public $context = true;
    /**
     * @var \Raven_Client
     */
    protected $client;
    
    /**
     * {@inheritdoc}
     */
    public function collect($messages, $final)
    {
        if (!isset($this->client)) {
            $this->client = new \Raven_Client($this->dsn, $this->clientOptions);
        }
        
        parent::collect($messages, $final);
    }

    /**
     * {@inheritdoc}
     */
    protected function getContextMessage()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function export()
    {
        foreach ($this->messages as $message) {
            list($context, $level, $category, $timestamp, $traces) = $message;
            $extra = [];

            if ($context instanceof \Exception) {
                $this->client->captureException($context);
                $description = $context->getMessage();
            } elseif (isset($context['msg'])) {
                $description = $context['msg'];
                $extra = $context;
                unset($extra['msg']);
            } else {
                $description = $context;
            }

            if ($this->context) {
                $extra['context'] = parent::getContextMessage();
            }

            $data = [
                'level' => static::getLevelName($level),
                'timestamp' => $timestamp,
                'message' => $description,
                'extra' => $extra,
                'tags' => [
                    'category' => $category
                ]
            ];

            $this->client->capture($data, $traces);
        }
    }

    /**
     * Returns the text display of the specified level for the Sentry.
     * 
     * @param integer $level The message level, e.g. [[LEVEL_ERROR]], [[LEVEL_WARNING]].
     * @return string
     */
    public static function getLevelName($level)
    {
        static $levels = [
            Logger::LEVEL_ERROR => 'error',
            Logger::LEVEL_WARNING => 'warning',
            Logger::LEVEL_INFO => 'info',
            Logger::LEVEL_TRACE => 'debug',
            Logger::LEVEL_PROFILE_BEGIN => 'debug',
            Logger::LEVEL_PROFILE_END => 'debug',
        ];

        return isset($levels[$level]) ? $levels[$level] : 'error';
    }
}
