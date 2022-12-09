<?php
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: HEAD, GET, POST, PUT, PATCH, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
header('Content-Type: application/json');
header('Access-Control-Allow-Credentials: true');
header("Access-Control-Expose-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
$method = $_SERVER['REQUEST_METHOD'];
if ($method == "OPTIONS") {
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
    header("HTTP/1.1 200 OK");
    die();
}

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Tuupola\Middleware\HttpBasicAuthentication;
use \Firebase\JWT\JWT;
require __DIR__ . '/../vendor/autoload.php';

const JWT_SECRET = "violet1234567";

$app = AppFactory::create();

function createJwT (Response $response) : Response {
    $issuedAt = time();
    $expirationTime = $issuedAt + 123456;
    $payload = array(
        'userid' => '2291512520',
        'email' => 'mikl@violet.fr',
        'pseudo' => 'skillshare',
        'iat' => $issuedAt,
        'exp' => $expirationTime
    );

    $token_jwt = JWT::encode($payload, JWT_SECRET, "HS256");
    return $response->withHeader("Authorization", "Bearer {$token_jwt}");
}


$app->get('/api/hello/{name}', function (Request $request, Response $response, $args) {
    $array = [];
    $array ["nom"] = $args ['name'];
    $response->getBody()->write(json_encode ($array));
    return $response;
});

$app->post('/api/signin', function (Request $request, Response $response, $args) {
    $err = false;
    $inputJSON = file_get_contents('php://input');
    $body = json_decode( $inputJSON, TRUE); //convert JSON into array
    $login = $body['email'] ?? "";
    $pass = $body['password'] ?? "";

    if (!preg_match("/[a-zA-Z0-9]{1,20}/",$login))   {
        $err = true;
    }
    if (!preg_match("/[a-zA-Z0-9]{1,20}/",$pass))  {
        $err = true;
    }

    if (!$err) {
        $response = createJwT($response);
        $response = $response->withHeader("test", "test");
    } else {
        $response = $response->withStatus(401);
    }
    return $response;
});

$app->post('/api/signup', function (Request $request, Response $response, $args) {
    $err=false;
    $inputJSON = file_get_contents('php://input');
    $response->getBody()->write($inputJSON);
    return $response;
});

$offers = [
    [
        "id" => 1,
        "name" => "Vivre boite",
        "img" => "https://proxymedia.woopic.com/api/v1/images/1127%2Fone-i%2FONE-I-ParcoursMarchants%2Ff62%2Faf6%2F881af3d274f8c476a554bc2263%2Ff62af6881af3d274f8c476a554bc2263.jpg?saveas=webp&saveasquality=70&format=405x270&quality=85",
        "price" => 100
    ],
    [
        "id" => 2,
        "name" => "Recepteur GGGG+",
        "img" => "https://cdn.woopic.com/9ffb653181284b0abe5e45d7014095b2/myshop-myshop-prod-268a2709/images/orange/broadband/internet/home4g/flybox/XL@3x.jpg",
        "price" => 200
    ]
];

$app->get('/api/offers', function (Request $request, Response $response, $args) {
    global $offers;

    // get queryparameters
    $query = $request->getQueryParams();
    $q = $query['q'] ?? "";

    // filter offers
    $filteredOffers = array_filter($offers, function($offer) use ($q) {
        return stripos($offer['name'], $q) !== false;
    });

    $response->getBody()->write(json_encode($filteredOffers));
    return $response;
});

$app->get('/api/offers/{id}', function (Request $request, Response $response, $args) {
    global $offers;
    $id = $args['id'];
    if($id > 0) {
        $response->getBody()->write(json_encode ($offers[$id-1]));
    } else {
        $response = $response->withStatus(404);
    }
    return $response;
});

$options = [
    "attribute" => "token",
    "header" => "Authorization",
    "regexp" => "/Bearer\s+(.*)$/i",
    "secure" => false,
    "algorithm" => ["HS256"],
    "secret" => JWT_SECRET,
    "path" => ["/api"],
    "ignore" => ["/api/signin", '/api/signup', '/api/offers', '/api/offers/{id}'],
    "error" => function ($response, $arguments) {
        $data = array('ERREUR' => 'Connexion', 'ERREUR' => 'JWT Non valide');
        $response = $response->withStatus(401);
        return $response->withHeader("Content-Type", "application/json")->getBody()->write(json_encode($data));
    }
];

$app->add(new Tuupola\Middleware\JwtAuthentication($options));
$app->run ();
