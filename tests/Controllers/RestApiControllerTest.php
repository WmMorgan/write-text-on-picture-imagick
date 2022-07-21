<?php


namespace Controllers;


use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;


class RestApiControllerTest extends TestCase
{
    private $client;
    private $token;
    private $siteurl = "http://phpunit.loc";


    public function setUp(): void
    {
        $this->client = new Client(['base_uri' => $this->siteurl, 'http_errors' => false]);
        $this->token = json_decode(file_get_contents(realpath($_SERVER["DOCUMENT_ROOT"]) . '/tokens.json'), true)['token'];
    }

    public function testInvokeUnauthorized()
    {
        $response = $this->client->get('/api')
            ->getStatusCode();
        $this->assertEquals(401, $response);
    }

    public function testInvokeSuccessAuthorized()
    {
        $response = $this->client->get('/api',
            ['headers' =>
                ['token' => $this->token]])
            ->getStatusCode();
        $this->assertEquals(200, $response);
    }

    public function testCreateParamsAvailable()
    {
        $response = $this->client->post('/api/create',
            ['headers' =>
                ['token' => $this->token]]);
        $response_one = $this->client->post('/api/create',
            ['headers' =>
                ['token' => $this->token],
                'json' => ['text' => "John Doe"]]);
        $response_two = $this->client->post('/api/create',
            ['headers' =>
                ['token' => $this->token],
                'json' => ['text' => "John Doe",
                    'link' => "http://"]]);

        $responseToArray = json_decode($response->getBody(), true)['message'];
        $response_oneToArray = json_decode($response_one->getBody(), true)['message'];
        $response_twoToArray = json_decode($response_two->getBody(), true)['message'];

        $this->assertJson($response->getBody()); //is the response in json?
        $this->assertEquals('no text available', $responseToArray); //text field is empty
        $this->assertEquals('no link available', $response_oneToArray); //link field is empty
        $this->assertEquals('photo not found', $response_twoToArray); //link photo incorrect
        $this->assertEquals(400, $response->getStatusCode()); // check status
    }

    public function testCreateSuccessAndDeleteSuccess()
    {
        $response = $this->client->post('/api/create',
            ['headers' =>
                ['token' => $this->token],
                'json' => ['text' => "John Doe",
                    'link' => $this->siteurl . "/fortest.jpg"]]);
        $response_delete = $this->client->delete('/api/delete',
            ['headers' =>
                ['token' => $this->token]]);
        $response_delete_one = $this->client->delete('/api/delete',
            ['headers' =>
                ['token' => $this->token],
                'json' =>
                    ['filename' => "404.jpg"]]);
        $response_filename = json_decode($response->getBody(), true)['filename'];
        $response_delete_two = $this->client->delete('/api/delete',
            ['headers' =>
                ['token' => $this->token],
                'json' =>
                    ['filename' => $response_filename]]);

        $response_deleteToArray = json_decode($response_delete->getBody(), true)['message'];
        $responseToArray = json_decode($response->getBody(), true)['status'];
        $response_delete_oneToArray = json_decode($response_delete_one->getBody(), true)['message'];
        $response_delete_twoToArray = json_decode($response_delete_two->getBody(), true)['status'];

        $this->assertJson($response_delete->getBody()); // is the response in json?
        $this->assertEquals('no file-name available', $response_deleteToArray); // no file-name available
        $this->assertEquals('success', $responseToArray); // image created successfully
        $this->assertEquals('no file available', $response_delete_oneToArray); // no file available
        $this->assertEquals('success', $response_delete_twoToArray); // the image was successfully deleted

    }

    public function testSignParamsAvailable()
    {
        $response = $this->client->post('/api/sign');;
        $header = ['headers' => ['token' => $this->token]];
        $response_one = $this->client->post('/api/sign', $header);
        $header['json'] = ['name' => "John"];
        $response_two = $this->client->post('/api/sign', $header);
        $header['json']['email'] = "john@doe.com";
        $response_three = $this->client->post('/api/sign', $header);


        $this->assertJson($response->getBody()); // is the response in json?
        $this->assertEquals(401, $response->getStatusCode()); // Admin unauthorized
        $this->assertEquals(400, $response_one->getStatusCode()); // status code after authorization
        $this->assertEquals('name must be present', json_decode($response_one->getBody())->message);
        $this->assertEquals('email must be present', json_decode($response_two->getBody())->message);
        $this->assertEquals('phone must be present', json_decode($response_three->getBody())->message);

    }

    public function testSignInvalidParams()
    {
        $header = ['headers' => ['token' => $this->token],
            'json' => ['name' => "Jo"]];
        $response = $this->client->post('/api/sign', $header);
        $header['json'] = ['email' => "error@"];
        $response_one = $this->client->post('/api/sign', $header);
        $header['json'] = ['phone' => "errorphone"];
        $response_two = $this->client->post('/api/sign', $header);

        $this->assertStringEndsWith('characters', json_decode($response->getBody())->message); //Name length should be less than 3 characters and not more than 15 characters
        $this->assertStringEndsWith('not entered', json_decode($response_one->getBody())->message); // Email number invalid or no entered
        $this->assertStringEndsWith('not entered', json_decode($response_two->getBody())->message); // phone number invalid or no entered
    }
    public function testSignSuccessCreate() {

    }


}// End tests