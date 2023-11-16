<?php declare(strict_types=1);

namespace RestClient;
use RestClient\Exception;

define('DEBUG', (bool) getenv('DEBUG') ?: FALSE);

/**
 * `Log`
 * Use the global `log` instance to log messages. Example: `log::info('foo')`.
 * Log levels below `info` are ignored unless the environment var `DEBUG` is TRUE.
 * 
 * @method static void debug(mixed ...$args): Log a debug message.
 * @method static void info(mixed ...$args): Log an info message.
 * @method static void warn(mixed ...$args): Log a warning message.
 * @method static void error(mixed ...$args): Log an error message.
 */
class Log {
    public static string $LEVEL = 'info';
    const methods = [
        'debug' => LOG_DEBUG, 
        'info' => LOG_INFO, 
        'warn' => LOG_WARNING, 
        'error' => LOG_ERR];
    /**
     * Construct the logger.
     */
    public function __construct() {
        openlog('RestClient', LOG_PID | LOG_PERROR, LOG_USER);
        if(DEBUG){
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            ini_set('error_log', './restclient.log');
            error_reporting(E_ALL);
            self::$LEVEL = 'debug';
        }
    }
    /**
     * Destruct the logger.
     */
    public function __destruct() {
        closelog();
    }
    /**
     * Log a message.
     * @param string $method: The method to log the message with.
     * @param array $args: The arguments to log.
     * @return void
     */
    public static function __callStatic($method, $args) : void {
        $level = self::methods[$method] ?? throw new Exception\BadMethodCall(
            "Invalid log method '{$method}'");
        if($level >= self::methods[self::$LEVEL]) {
            $body = implode("\n", array_map(function($v) {
                return var_export($v, TRUE);
            }, $args, $args));
            error_log(sprintf("[%s] RestClient %s", $method, $body, $level));
        }
    }
}

/**
 * Log \RestClient\log
 * The global logger.
 */
define('\RestClient\log', new Log);
