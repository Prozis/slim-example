<?php
require __DIR__ . '/../vendor/autoload.php';
use Slim\Factory\AppFactory;
use DI\Container;

// php -S localhost:8080 -t public public/index.php
$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);



$app->get('/users', function ($request, $response) {
    $term = $request->getQueryParam('term');
    $users = json_decode(file_get_contents('users.txt'));
    $params = [
      'users' => ['mike', 'mishel', 'adel', 'keks', 'kamila'],
      'term' => $term
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

$app->get('/users/new', function ($request, $response) {
    $params = [
        'user' => ['name' => '', 'email' => '', 'password' => '', 'passwordConfirmation' => '', 'city' => ''],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
})->setName('userNew');

$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    // $this в Slim это контейнер зависимостей

    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('user');

$app->post('/users', function ($request, $response) use ($repo) {
    $validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    $errors = $validator->validate($user);
    if (count($errors) === 0) {
        //$repo->save($user);
        file_put_contents ('users.txt', json_encode($user));
        return $response->withRedirect('/users', 302);
    }
    $params = [
        'user' => $user,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) use ($router) {

    $params = [
      'users' => $router->urlFor('users'), // /users
      //'user' => $router->urlFor('user', ['id' => 4]), // /users/4
      'userNew' => $router->urlFor('userNew')
    ];
    return $this->get('renderer')->render($response, "index.phtml", $params);
    //return $response->write('Welcome slim');
});

$app->run();
