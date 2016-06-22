<?php

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use OAuth2\Server\Repositories\AccessTokenRepository;
use OAuth2\Server\Repositories\ClientRepository;
use OAuth2\Server\Repositories\ScopeRepository;

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

        $grantType = new ClientCredentialsGrant();
		$server->enableGrantType($grantType);

		// $this->_oauthServer = $server;
    }

    public function IndexAction () 
    {
        echo "hello oauth2.0, the time is moew.";
        // print_r($this->actions);
    }

    # 获取 AccessToken
    public function access_tokenAction () 
    {
        echo 'access token action';
    }
    
}