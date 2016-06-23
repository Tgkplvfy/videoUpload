<?php

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use OAuth2\Server\Repositories\AccessTokenRepository;
use OAuth2\Server\Repositories\ClientRepository;
use OAuth2\Server\Repositories\ScopeRepository;
use OAuth2\Http\ServerRequest;
use OAuth2\Http\Response;
use OAuth2\Http\Uri;
use OAuth2\Http\Headers;
use OAuth2\Http\Stream;

class AuthController extends Ap_Base_Control {

    public function init () 
    {
        # 初始化授权服务器对象
        $clientRepository      = new ClientRepository();       // instance of ClientRepositoryInterface
        $scopeRepository       = new ScopeRepository();        // instance of ScopeRepositoryInterface
        $accessTokenRepository = new AccessTokenRepository();  // instance of AccessTokenRepositoryInterface

        // Path to public and private keys
        $privateKey = ROOT_PATH . '/storage/private.key';
        //$privateKey = new CryptKey('file://path/to/private.key', 'passphrase'); // if private key has a pass phrase
        $publicKey = ROOT_PATH . '/storage/public.key';

		$server = new AuthorizationServer(
            $clientRepository, 
            $accessTokenRepository, 
            $scopeRepository, 
            $privateKey, 
            $publicKey
        );

        // Enable the client credentials grant on the server
		$server->enableGrantType(
            new ClientCredentialsGrant(), 
            new \DateInterval('PT1H') // access tokens will expire after 1 hour
        );

		$this->_oauthServer = $server;
    }

    public function IndexAction () 
    {
        echo "hello oauth2.0, the time is moew.";
        // print_r($this->actions);
    }

    # 获取 AccessToken
    public function access_tokenAction () 
    {
        try {
            $method  = 'get';
            $uri     = new Uri();
            $headers = new Headers();
            $body    = new Stream();

            $request  = new ServerRequest($method, $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles = array());
            $response = new Response($method, $uri, $headers, $cookies, $serverParams, $body, $uploadedFiles = array());
            $this->_oauthServer->respondToAccessTokenRequest($request, $response);
        } catch (\League\OAuth2\Server\Exception\OAuthServerException $exception) {
            $this->response(NULL, 500, $exception->getMessage());
        } catch (\Exception $exception) {
            $this->response(NULL, 500, $exception->getMessage());
        }
    }
    
}