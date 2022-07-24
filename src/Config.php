<?php

namespace Stackkit\LaravelGoogleCloudTasksQueue;

use Closure;
use Error;
use Exception;
use Safe\Exceptions\UrlException;

class Config
{
    public static function validate(array $config): void
    {
        if (empty($config['project'])) {
            throw new Error(Errors::invalidProject());
        }

        if (empty($config['location'])) {
            throw new Error(Errors::invalidLocation());
        }

        if (empty($config['service_account_email'])) {
            throw new Error(Errors::invalidServiceAccountEmail());
        }
    }

    /**
     * @param Closure|string $handler
     */
    public static function getHandler($handler): string
    {
        $handler = value($handler);

        try {
            $parse = \Safe\parse_url($handler);

            if (empty($parse['host'])) {
                throw new UrlException();
            }

            // A mistake which can unknowingly be made is that the task handler URL is
            // (still) set to localhost. That will never work because Cloud Tasks
            // should always call a public address / hostname to process tasks.
            if (in_array($parse['host'], ['localhost', '127.0.0.1', '::1'])) {
                throw new Exception(sprintf(
                    'Unable to push task to Cloud Tasks because the handler URL is set to a local host: %s. ' .
                    'This does not work because Google is not able to call the given local URL. ' .
                    'If you are developing on locally, consider using Ngrok or Expose for Laravel to expose your local ' .
                    'application to the internet.',
                    $handler
                ));
            }

            // Versions 1.x and 2.x required the full path (e.g. my-app.com/handle-task). In 3.x and beyond
            // it is no longer necessary to also include the path and simply setting the handler
            // URL is enough. If someone upgrades and forgets we will warn them here.
            // if (!empty($parse['path'])) {
            //     throw new Exception(
            //         'Unable to push task to Cloud Tasks because the task handler URL (' . $handler . ') is not ' .
            //         'compatible. To fix this, please remove \'' . $parse['path'] . '\' from the URL, ' .
            //         'or copy from here: STACKKIT_CLOUD_TASKS_HANDLER=' . $parse['scheme'] . '://' . $parse['host']
            //     );
            // }

            return $handler . '/handle-task';
        } catch (UrlException $e) {
            throw new Exception(
                'Unable to push task to Cloud Tasks because the task handler URL (' . $handler . ') is ' .
                'malformed. Please inspect the URL closely for any mistakes.'
            );
        }
    }
}
