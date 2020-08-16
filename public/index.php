<?php
require __DIR__ . '/../vendor/autoload.php';
use Slim\Factory\AppFactory;
use DI\Container;

$container = new Container();
$container->set('renderer', function () {
    // Параметром передается базовая директория в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    return $response->write('Welcome slim');
});
$app->get('/users/{id}', function ($request, $response, $args) {
    $params = ['id' => $args['id'], 'nickname' => 'user-' . $args['id']];
    // Указанный путь считается относительно базовой директории для шаблонов, заданной на этапе конфигурации
    // $this доступен внутри анонимной функции благодаря https://php.net/manual/ru/closure.bindto.php
    // $this в Slim это контейнер зависимостей
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
});

$app->get('/users', function ($request, $response) {
    $term = $request->getQueryParam('term');
    $params = [
      'users' => ['mike', 'mishel', 'adel', 'keks', 'kamila'],
      'term' => $term
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
});



$app->post('/users', function ($request, $response) {
    return $response->withStatus(302);
});

// $app->get('/courses/{id}', function ($request, $response, array $args) {
//     $id = $args['id'];
//     return $response->write("Course id: {$id}");
// });

$app->run();
