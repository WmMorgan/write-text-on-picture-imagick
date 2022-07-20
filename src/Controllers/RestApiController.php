<?php
namespace App\Controllers;
error_reporting(0);


use App\Models\Sign;
use App\Photo;


class RestApiController
{
private $AdminToken;
private $sqlite; //connect to base

public function __construct() {
    $this->AdminToken = json_decode(file_get_contents('tokens.json'), true)['token'];
    $this->sqlite =  new Sign();
}
    /**
     * @param $request
     * @param $response
     * @param $args
     * @return mixed
     * api
     */
    public function __invoke($request, $response, $args) {
        try {
        $token = $request->getHeader("token")[0];
        $userTokenValidate = $this->authUserToken($token);

            $your_token = json_encode($userTokenValidate);
            $response = $response->withHeader('Content-type', 'application/json');
            $response->getBody()->write($your_token);

        } catch(\Exception $exception) {
$error = json_encode([
    'status' => "error",
    'message' => $exception->getMessage()
]);
 $response = $response->withHeader('Content-type', 'application/json')->withStatus(401);
 $response->getBody()->write($error);
        }
        return $response;
    }


    /**
     * @param array $data
     * @return bool
     * api/create validate text and link
     */
    public function validate(array $data) :bool {
        if (empty($data['text'])) {
            throw new \Exception('no text available', 400);
        }
        if (empty($data['link'])) {
            throw new \Exception('no link available', 400);
        }
        return true;
    }

    /**
     * @param string $token
     * @return array
     * USER token validate
     */
    public function authUserToken(string $token = null) :array {
        $user = $this->sqlite->db->queryRow('SELECT * FROM tokens WHERE token=:token', array(':token' => $token));
        if (empty($token) || $user == false) {
            throw new \Exception('user token not found', 401);
        } else {
            return $user;
        }
    }
    /**
     * @param $request
     * @param $response
     * @return mixed
     * api/create
     */
    public function create($request, $response) {
        try {
            $token = $request->getHeader("token")[0];
            $this->authUserToken($token);
            $request_main = (array) $request->getParsedBody();
            $this->validate($request_main);
            $text = $request_main['text'];
            $link = $request_main['link'];
            $filename = bin2hex(random_bytes(8)).'.jpg';
            if (!copy($link, 'uploads/'.$filename)) {
                throw new \RuntimeException('photo not found', 400);
            }
            $image = new Photo();
            $image->editSave($filename, $text);

            $response_main = json_encode([
                'status' => 'success',
                'filename' => $filename,
                'link' => 'http://'.$_SERVER['HTTP_HOST'].'/uploads/'.$filename]);
            $response = $response->withHeader('Content-type', 'application/json');
            $response->getBody()->write($response_main);

        } catch (\Exception $exception) {
            $response_main_err = json_encode([
                'status' => 'error',
                'message' => $exception->getMessage()]);
            $response = $response->withHeader('Content-type', 'application/json')->withStatus($exception->getCode());
            $response->getBody()->write($response_main_err);
        }

        return $response;
    }

    /**
     * @param $request
     * @param $response
     * @return mixed
     * api/delete
     */
    public function delete($request, $response) {
        try {
            $token = $request->getHeader("token")[0];
            $this->authUserToken($token);
            $delete_req = (array)$request->getParsedBody();
            if (empty($delete_req['filename'])) {
                throw new \RuntimeException('no file-name available', 400);
            }
            if (!unlink('uploads/'.$delete_req['filename'])) {
                throw new \RuntimeException('no file available', 400);
            }
            $response_main = json_encode([
                'status' => "success",
                'filename' => $delete_req['filename']
            ]);
            $response = $response->withHeader('Content-type', 'application/json');
            $response->getBody()->write($response_main);
        } catch (\Exception $exception) {
            $response_main_err = json_encode([
                'status' => "error",
                'message' => $exception->getMessage()
             ]);
            $response = $response->withHeader('Content-type', 'application/json')->withStatus($exception->getCode());
            $response->getBody()->write($response_main_err);
        }
        return $response;
    }
    /**
     * @param string $token
     * ADMIN token validate
     */
    public function authToken(string $token = null) :void {

        if (empty($token)) {
            throw new \Exception('admin token not found', 401);
        } else if ($token !== $this->AdminToken) {
            throw new \Exception('admin token is invalid', 401);
        }
    }

    /**
     * @param $request
     * @param $response
     * @return mixed
     * admin registers the user for TOKEN - api/sign
     */
    public function sign($request, $response) {
        try {
            $token = $request->getHeader("token")[0];
            $this->authToken($token);
            $sign_main = (array)$request->getParsedBody();

            $token = $this->sqlite->sign($sign_main);
            $sign_response = json_encode([
                'status' => "success",
                'name' => $sign_main['name'],
                'email' => $sign_main['email'],
                'phone' => $sign_main['phone'],
                'token' => $token
            ]);
            $response = $response->withHeader('Content-type', 'application/json');
            $response->getBody()->write($sign_response);
        } catch (\Exception $e) {
            $err = json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
            $code = $e->getCode() ?: 400;
            $response = $response->withHeader('Content-type', 'application/json')->withStatus($code);
            $response->getBody()->write($err);
        }
        return $response;
    }

}