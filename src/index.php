<?php

require_once __DIR__ . '/../.env.config.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Silex\Application;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Silex\Provider\FormServiceProvider;
use RedBean_Facade as R;

// use Acme\MyClass;
// $myClass = new MyClass();

$app = new Application();

$env = getenv('APP_ENV') ? : 'prod';
define('APP_ENV', $env);

$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.json"));

// use $app['debug'], $app['db_name'], etc...
R::setup("mysql:host={$app['db_host']};dbname={$app['db_name']}","{$app['db_user']}","{$app['db_pass']}");


$app->register(new Silex\Provider\HttpCacheServiceProvider(), [
    'http_cache.cache_dir' => __DIR__ . '/http_cache/'
]);

$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale' => 'de',
    'translation.class_path' => __DIR__ . '/../locales',
    'translator.messages' => array()
));

$app->register(new Silex\Provider\ValidatorServiceProvider());
$app->register(new FormServiceProvider());

$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/logging.log',
));

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path'    => __DIR__ . '/views',
    'twig.options' => array(
        'debug'                => $app['debug'],
        'strict_variables'      => false,
        'cache'                => __DIR__ . '/twig_cache/',
        'auto_reload'          => $app['debug']
    )
));

$app['twig']->addExtension(new Twig_Extension_Debug());

$addCacheMiddleware = function (Request $request, Response $response, Application $app) {
    return $response->setCache(['s_maxage' => 3600]);
};

// prepare structure for current request site
$app->before(function ($request, $app)
{

});

// ------ ROUTING ------

// remove favicon spam
$app->get('/favicon.ico', function() use ($app) { return true; });

$app->match('/', 'Acme\\HomepageController::showIndex');

$app->match('/player/{player}', 'Acme\\HomepageController::showPlayer');

$app->match('/game/{game}', 'Acme\\HomepageController::showGame');

$app->match('/admin', 'Acme\\HomepageController::showForm');


// -------- ERROR HANDLING -------

$app->error(function (\Exception $e, $code) use ($app)
{
    switch ($code)
    {
        case 404:
            $message = 'The requested page could not be found.';
            break;
        default:
            $message = 'Something went wrong.';
    }

    if ($app['debug']) {
        echo "<pre>";
        echo $e;
        echo "</pre>";
    } else {
        $app['logger']->addError(sprintf("%s: %s: %d", $code, $message, $e));
        return $app['twig']->render('layout/404.twig', ['code' => $code, 'message' => $message]);
    }

});



// ------- APP START -------
if ($app['debug']) {
    $app->run();
} else {
    $app['http_cache']->run();
}
