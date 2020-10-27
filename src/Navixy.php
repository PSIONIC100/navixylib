<?php

namespace Telemovilperu\Navixylib;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Dotenv\Dotenv;
use Dotenv\Repository;

class Navixy
{
    protected $urlapi;
    protected $urlclient;

    public function __construct($dir)
    {
        $repository = Repository\RepositoryBuilder::createWithNoAdapters()
        ->addAdapter(Repository\Adapter\EnvConstAdapter::class)
        ->addWriter(Repository\Adapter\PutenvAdapter::class)
        ->immutable()
        ->make();
        $env = Dotenv::create($repository, $dir);
        $env->load();
        $this->urlapi = getenv('PANEL_URL');
        $this->urlclient = getenv('CLIENT_URL');
    }

    function loginPanel()
    {
        $api = new Client;
        $apiRequest = $api->request('POST', $this->urlapi . 'account/auth', [
            'form_params' => [
                'login' => getenv('PANEL_USR'),
                'password' => getenv('PANEL_PWD'),
            ]
        ]);
        $data = json_decode($apiRequest->getbody(), TRUE);
        return $data['hash'];
    }

    function loginUser($id)
    {
        $hash = self::loginPanel();
        $api2 = new Client;
        $apiRequest1 = $api2->request('POST', $this->urlapi . 'user/session/create', [
            'form_params' => [
                'hash' => $hash,
                'user_id' => $id,
            ]
        ]);
        $data = json_decode($apiRequest1->getbody(), TRUE);
        $hash = $data['hash'];
        return $hash;

    }

    function getPosition($unit, $client)
    {
        $hash = self::loginUser($client);
        $api3 = new Client;
        try {
            $apiRequest2 = $api3->request('POST', $this->urlclient . 'tracker/get_state', [
                'form_params' => [
                    'hash' => $hash,
                    'tracker_id' => $unit,
                ]
            ]);
            $data = json_decode($apiRequest2->getbody(), TRUE);
            if ($data['state']['gps']['speed'] >= 90) {
                $evento = 'EX';
            }
            if ($data['state']['gps']['speed'] < 90) {
                $evento = 'ER';
            }
            if ($data['state']['gps']['speed'] == 0) {
                $evento = 'PA';
            }
            if (is_null($data['state']['gps']['updated'])) {
                $paquete = array(
                    'latitud' => $data['state']['gps']['location']['lat'],
                    'longitud' => $data['state']['gps']['location']['lng'],
                    'orientacion' => $data['state']['gps']['heading'],
                    'velocidad' => $data['state']['gps']['speed'],
                    'evento' => $evento,
                    'fecha' => 'N'
                );
            } else {
                $paquete = array(
                    'latitud' => $data['state']['gps']['location']['lat'],
                    'longitud' => $data['state']['gps']['location']['lng'],
                    'orientacion' => $data['state']['gps']['heading'],
                    'velocidad' => $data['state']['gps']['speed'],
                    'evento' => $evento,
                    'fecha' => $data['state']['gps']['updated']
                );
            }

            $data = array('error' => '0', 'mensaje' => $paquete);
            return json_encode($data);
        } catch (ClientException $e) {
            $data = array('error' => '1', 'mensaje' => $e->getResponse()->getBody()->getContents());
            return json_encode($data);
        }
    }

}