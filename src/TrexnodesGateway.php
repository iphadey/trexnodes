<?php

namespace Iphadey\Trexnodes;

use Exception;
use Iphadey\Trexnodes\Models\TrexnodesRequestLog;

class TrexnodesGateway
{
    public $mode;
    public $url;
    public $clientId;
    public $clientSecret;
    public $clientToken;
    public $log;

    public $method;
    public $endpoint;
    public $param;
    public $data;
    public $failed;
    public $ip;
    public $header;
    public $bodyType = 'query';

    private $successCodes = [200, 201];

    public function __construct()
    {
        $this->url = config('trexnodes.sandbox-url');


        if (config('trexnodes.mode') == 'live') {
            $this->url = config('trexnodes.url');
        }

        $this->clientId = config('trexnodes.client.id');
        $this->clientSecret = config('trexnodes.client.secret');
        $this->clientToken = config('trexnodes.client.token');

        $this->setHeader();
    }

    public function setParam($param = [])
    {
        $this->param = $param;

        return $this;
    }

    public function setAccessToken($accessToken = null)
    {
        if ($accessToken) {
            $this->clientToken = $accessToken;
            config('trexnodes.client.token', $accessToken);
        }

        return $this;
    }

    public function setHeader($data = [])
    {
        $default = [
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Connection'    => 'keep-alive',
            'Authorization' => "Bearer $this->clientToken",
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/88.0.4324.104 Safari/537.36',
        ];

        $this->header = $default + $data;

        return $this;
    }


    private function apiRequest()
    {
        $this->log = TrexnodesRequestLog::create([
            'ip'      => $this->ip,
            'url'     => $this->endpoint,
            'method'  => $this->method,
            'request' => $this->param,
        ]);

        $this->response = $this->clientRequest($this->method, $this->endpoint, $this->param);

        $this->isRequestSuccess();

        return $this;
    }

    private function clientRequest($method, $endpoint, $param = [])
    {
        $client = new \GuzzleHttp\Client();

        if ($this->bodyType == 'body') {
            $param = json_encode($param);
        }

        return $client->request($method, $endpoint, [
            'verify'        => false,
            'http_errors'   => false,
            'headers'       => $this->header,
            'timeout'       => 30,
            $this->bodyType => $param,
        ]);
    }

    function getResponse()
    {
        if ($this->failed) {
            return $this->generateResponse(false, $this->failed);
        }

        return $this->generateResponse(true, 'Success', $this->data);
    }

    protected function isRequestSuccess(): bool
    {
        $statusCode = $this->response->getStatusCode();
        $response = json_decode($this->response->getBody()->getContents(), true);

        if ($this->log) {
            $this->log->update([
                'status'   => $statusCode,
                'response' => $response
            ]);
        }

        if (!in_array($statusCode, $this->successCodes)) {
            $this->failed = $response['message'] ?? 'undefined';

            if ($this->log) {
                $this->log->update([
                    'message' => $this->failed
                ]);
            }

            return false;
        }

        $this->data = $response;

        return true;
    }

    public function generateResponse($status = false, $message = null, $data = [])
    {
        $data = [
            'status'  => $status,
            'message' => $message,
            'result'  => $data,
        ];

        if ($this->log) {
            $this->log->update([
                'result' => $data
            ]);
        }

        return $data;
    }

    public function login()
    {
        $this->endpoint = "$this->url/oauth/token";
        $this->method = 'POST';
        $this->bodyType = 'body';

        return $this->setParam([
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope'         => '*',
        ])->apiRequest()->getResponse();
    }

    public function tokenListing($filter = [])
    {
        $this->endpoint = "$this->url/api/tokens";
        $this->method = 'GET';

        return $this->setParam([
            'filter' => [
                'uuid'    => $filter['uuid'] ?? null,
                'name'    => $filter['name'] ?? null,
                'symbol'  => $filter['symbol'] ?? null,
                'network' => $filter['network'] ?? null,
            ]
        ])->apiRequest()->getResponse();
    }

    public function coinCreate($symbol)
    {
        $this->endpoint = "$this->url/api/coins/create";
        $this->method = 'POST';
        $this->bodyType = 'body';

        return $this->setParam([
            'symbol' => $symbol,
        ])->apiRequest()->getResponse();
    }

    public function coinListing($filter = [])
    {
        $this->endpoint = "$this->url/api/coins";
        $this->method = 'GET';

        return $this->setParam([
            'filter' => [
                'uuid'    => $filter['uuid'] ?? null,
                'symbol'  => $filter['symbol'] ?? null,
                'token'   => $filter['token'] ?? null,
                'network' => $filter['network'] ?? null,
                'status'  => $filter['status'] ?? null,
            ]
        ])->apiRequest()->getResponse();
    }

    public function coinDetail($coinUuid)
    {
        $this->endpoint = "$this->url/api/coins/$coinUuid";
        $this->method = 'GET';

        return $this->apiRequest()->getResponse();
    }

    public function addressCreate($coinUuid, $label = null)
    {
        $this->endpoint = "$this->url/api/addresses/create/$coinUuid";
        $this->method = 'POST';
        $this->bodyType = 'body';

        return $this->setParam([
            'label' => $label,
        ])->apiRequest()->getResponse();
    }

    public function addressListing($filter = [])
    {
        $this->endpoint = "$this->url/api/addresses";
        $this->method = 'GET';

        return $this->setParam([
            'filter' => [
                'uuid'    => $filter['uuid'] ?? null,
                'symbol'  => $filter['symbol'] ?? null,
                'token'   => $filter['token'] ?? null,
                'network' => $filter['network'] ?? null,
                'status'  => $filter['status'] ?? null,
            ]
        ])->apiRequest()->getResponse();
    }

    public function addressDetail($addressUuid)
    {
        $this->endpoint = "$this->url/api/addresses/$addressUuid";
        $this->method = 'GET';

        return $this->apiRequest()->getResponse();
    }

    public function transactionHistories($filter = [])
    {
        $this->endpoint = "$this->url/api/transactions";
        $this->method = 'GET';

        return $this->setParam([
            'filter' => [
                'uuid'              => $filter['uuid'] ?? null,
                'type'              => $filter['type'] ?? null,
                'coin_uuid'         => $filter['coin_uuid'] ?? null,
                'coin_address_uuid' => $filter['coin_address_uuid'] ?? null,
                'from'              => $filter['from'] ?? null,
                'to'                => $filter['to'] ?? null,
                'created_at'        => $filter['created_at'] ?? null,
                'created_between'   => $filter['created_between'] ?? null,
                'symbol'            => $filter['symbol'] ?? null,
                'token'             => $filter['token'] ?? null,
                'network'           => $filter['network'] ?? null,
                'status'            => $filter['status'] ?? null,
            ]
        ])->apiRequest()->getResponse();
    }

    public function transactionDetail($transactionUuid)
    {
        $this->endpoint = "$this->url/api/transactions/$transactionUuid";
        $this->method = 'GET';

        return $this->apiRequest()->getResponse();
    }

    public function withdrawalHistories($filter = [])
    {
        $this->endpoint = "$this->url/api/transactions/type/withdrawal";
        $this->method = 'GET';

        return $this->setParam([
            'filter' => [
                'uuid'              => $filter['uuid'] ?? null,
                'type'              => $filter['type'] ?? null,
                'coin_uuid'         => $filter['coin_uuid'] ?? null,
                'coin_address_uuid' => $filter['coin_address_uuid'] ?? null,
                'from'              => $filter['from'] ?? null,
                'to'                => $filter['to'] ?? null,
                'created_at'        => $filter['created_at'] ?? null,
                'created_between'   => $filter['created_between'] ?? null,
                'symbol'            => $filter['symbol'] ?? null,
                'token'             => $filter['token'] ?? null,
                'network'           => $filter['network'] ?? null,
                'status'            => $filter['status'] ?? null,
            ]
        ])->apiRequest()->getResponse();
    }

    public function depositHistories($filter = [])
    {
        $this->endpoint = "$this->url/api/transactions/type/deposit";
        $this->method = 'GET';

        return $this->setParam([
            'filter' => [
                'uuid'              => $filter['uuid'] ?? null,
                'type'              => $filter['type'] ?? null,
                'coin_uuid'         => $filter['coin_uuid'] ?? null,
                'coin_address_uuid' => $filter['coin_address_uuid'] ?? null,
                'from'              => $filter['from'] ?? null,
                'to'                => $filter['to'] ?? null,
                'created_at'        => $filter['created_at'] ?? null,
                'created_between'   => $filter['created_between'] ?? null,
                'symbol'            => $filter['symbol'] ?? null,
                'token'             => $filter['token'] ?? null,
                'network'           => $filter['network'] ?? null,
                'status'            => $filter['status'] ?? null,
            ]
        ])->apiRequest()->getResponse();
    }

    public function withdrawalRequest($coinUuid, $amount, $address)
    {
        $this->endpoint = "$this->url/api/withdrawal/request";
        $this->method = 'POST';
        $this->bodyType = 'body';

        return $this->setParam([
            'coin_uuid' => $coinUuid,
            'amount'    => $amount,
            'address'   => $address,
        ])->apiRequest()->getResponse();
    }

    public function depositRequest($coinUuid)
    {
        $this->endpoint = "$this->url/api/deposit/request";
        $this->method = 'POST';
        $this->bodyType = 'body';

        return $this->setParam([
            'coin_uuid' => $coinUuid,
        ])->apiRequest()->getResponse();
    }

    public function exchangeRate($from, $to)
    {
        $this->endpoint = "$this->url/api/misc/exchange-rate";
        $this->method = 'GET';

        return $this->setParam([
            'from' => $from,
            'to'   => $to,
        ])->apiRequest()->getResponse();
    }
}