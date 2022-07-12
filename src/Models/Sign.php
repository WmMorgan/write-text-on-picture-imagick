<?php


namespace App\Models;

use Respect\Validation\Exceptions\AllOfException;
use Respect\Validation\Exceptions\NestedValidationException;
use Respect\Validation\Exceptions\ValidationException;
use Respect\Validation\Validator as v;

class Sign
{
    public $db;

    public function __construct()
    {
        $db = require_once($_SERVER['DOCUMENT_ROOT'] . '/src/Config/conf.php');
        $this->db = $db;
    }

    /**
     * @param $phone
     * @return string
     */
    public function jwttoken($phone)
    {
// Create token header as a JSON string
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
// Create token payload as a JSON string
        $payload = json_encode(['user_id' => str_replace('+', '', $phone)]);
// Encode Header to Base64Url String
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
// Encode Payload to Base64Url String
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
// Create Signature Hash
        $signature = hash_hmac('sha256', $base64UrlHeader . "." . $base64UrlPayload, 'abC123!', true);
// Encode Signature to Base64Url String
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
// Create JWT
        $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;
        return $jwt;
    }
public function signValidate(array $data) {
    $sign_validate = $this->db->queryValue('SELECT COUNT(*) FROM tokens WHERE phone = "' . $data['phone'] . '" OR email = "' . $data['email'] . '" ');
if ($sign_validate == true) {
    throw new \Exception('this email or phone already exits');
}

        $validator = new v();
        $validator->addRule(v::key('name', v::notEmpty()->setTemplate('The property name must not be empty'))
            ->key('name', v::length(3, 15)->setTemplate('Name length should be less than 3 characters and not more than 15 characters')));
        $validator->addRule(v::key('email', v::email()->setTemplate('Email address invalid')));
        $validator->addRule(v::key('phone', v::digit('+')->setTemplate('Phone number invalid'))
        ->key('phone', v::length(12, 15)->setTemplate('Length number min 12 max 15')));

        $validator->check($data);
}
    public function sign(array $data)
    {
        try {
            $this->signValidate($data);

                $token = $this->jwttoken($data['phone']);
                $this->db->insert('tokens',
                    array('name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'],
                        'token' => $token));
                return $token;

        } catch (Exception $e) {
return $e->getMessage();
        }
    }
} //END CLASS