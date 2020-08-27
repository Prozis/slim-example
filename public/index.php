<?php
require __DIR__ . '/../vendor/autoload.php';
use Slim\Factory\AppFactory;
use DI\Container;
session_start();

$container = new Container();
$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});
$container->set('renderer', function () {
    // Параметром передается базовая директория в которой будут храниться шаблоны
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

//$validator = new Validator();

function validate($user)
{
    $errors = [];
    if (empty($user['name'])) {
        $errors['name'] = "Can't be blank";
    }
    return $errors;
}

//$errors = $validator->validate($user);


$app->get('/users', function ($request, $response) {
    $term = $request->getQueryParam('term');
  // старый вариант с хранением пользователей в файле
  //  $users = json_decode(file_get_contents('users.txt'), true);

    // получаем список пользователей из Cookie
    $users = json_decode($request->getCookieParam('users'), true);
    $messages = $this->get('flash')->getMessages();
    $params = [
      'users' => $users,
      'messages' => $messages,
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
    //$validator = new Validator();
    $user = $request->getParsedBodyParam('user');
    $errors = validate($user);

    if (count($errors) === 0) {
        //старый вариант с запасбю нового пользователя в файл
        //$usersFromFile = json_decode(file_get_contents('users.txt'), true);

          //записываем нового пользователя в cookie
        $usersCookieDecoded = json_decode($request->getCookieParam('users'), true);
        $usersCookieDecoded[] = $user;
      //  file_put_contents ('users.txt', json_encode($usersFromFile));
        $this->get('flash')->addMessage('success', 'User added');
        $usersCookieEncoded = json_encode($usersCookieDecoded);
        return $response->withHeader('Set-Cookie', "users={$usersCookieEncoded}")
        ->withRedirect('/users', 302);
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
