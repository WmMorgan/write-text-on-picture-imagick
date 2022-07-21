<?php


use App\Controllers\RestApiController;
use App\Photo;
use App\PhotoException;
use App\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\UploadedFile;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

require_once __DIR__.'/vendor/autoload.php';


$loader = new FilesystemLoader('templates');
$twig = new Environment($loader);

$app = AppFactory::create();
$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();

$session = new Session();
$sessionMiddleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) use ($session) {
   $session->start();
    $response = $handler->handle($request);
    $session->save();
    return $response;
};
$app->add($sessionMiddleware);
$photo = new Photo();
$app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $session) {
   $body = $twig->render('index.twig', [
   'message' => $session->flush('message')
   ]);
    $response->getBody()->write($body);
   return $response;

});
$app->get('/new', function (ServerRequestInterface $request, ResponseInterface  $response) use ($session) {
    $photo = $session->flush('filename');
    unlink('uploads/'.$photo);
    return $response->withHeader('Location', '/');
});

$app->get('/down', function (ServerRequestInterface $request, ResponseInterface  $response) use ($session) {
    $file = __DIR__ . '/uploads/'.$session->getData('filename');
    if (file_exists($file)) {
        header('Content-Description: File Transfer');
        header('Content-Type: image/jpg');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file));
        readfile($file);
        exit;
    }
});

$app->post('/edit-pic', function (ServerRequestInterface $request, ResponseInterface $response) use ($twig, $photo, $session) {
$params = (array) $request->getParsedBody();
$uploadedFiles =  $request->getUploadedFiles();
$uploadedFile = $uploadedFiles['formFile'];
$params['filename'] = $uploadedFile->getClientFilename();
try {
    $photo->imagick($params);
    if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
        $filename = moveUploadedFile($uploadedFile);
        $session->setData('filename', $filename);
        $body = $twig->render('edit-pic.twig', [
            'filename' => $session->getData('filename')
        ]);
        $response->getBody()->write($body);

    }
    $photo->editSave($session->getData('filename'), $params['username']);

} catch (PhotoException $exception) {
    $session->setData('message', $exception->getMessage());
    return $response->withHeader('Location', '/')
        ->withStatus(302);
}

    return $response;
});

function moveUploadedFile(UploadedFile $uploadedFile, $directory = "uploads")
{
    $extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    $basename = bin2hex(random_bytes(8)); // see http://php.net/manual/en/function.random-bytes.php
    $filename = sprintf('%s.%0.8s', $basename, $extension);

    $uploadedFile->moveTo($directory . DIRECTORY_SEPARATOR . $filename);

    return $filename;
}

/**
 * Rest api with Controller
 */
$app->get('/api', RestApiController::class);
$app->post('/api/create', RestApiController::class . ':create');
$app->delete('/api/delete', RestApiController::class . ':delete');
$app->post('/api/sign', RestApiController::class . ':sign');


$errorMiddleware = $app->addErrorMiddleware(false, false, false);

$app->run();
