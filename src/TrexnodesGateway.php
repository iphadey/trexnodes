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

    private $successCodes = [200];

    public function __construct()
    {
        $this->url = config('trexnodes.sandbox-url');


        if (config('savvix.mode') == 'live') {
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

    public function setHeader($data = [])
    {
        $default = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
            'Connection'   => 'keep-alive',
            'token'        => "Bearer $this->clientToken"
        ];

        $this->header = $default + $data;

        return $this;
    }


    private function apiRequest()
    {
        $this->log = TrexnodesRequestLog::create([
            'ip'         => $this->ip,
            'url'        => $this->endpoint,
            'method'     => $this->method,
            'request'    => $this->param,
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
            $this->failed = $response->message ?? 'undefined';

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

        return $this->setParam([
            'grant_type'    => 'client_credentials',
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'scope'         => '*',
        ])->apiRequest()->getResponse();
    }

    public function tokenListing($filter = [])
    {
        $this->endpoint = "$this->url/api/coins";
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
        $this->endpoint = "$this->url/api/coins";
        $this->method = 'POST';

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

    public function createAddress($coinUuid, $label = null)
    {
        $this->endpoint = "$this->url/api/coins/$coinUuid";
        $this->method = 'POST';

        return $this->setParam([
            'label' => $label,
        ])->apiRequest()->getResponse();
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
        $this->endpoint = "$this->url/api/withdrawals";
        $this->method = 'POST';

        return $this->setParam([
            'coin_uuid' => $coinUuid,
            'amount'    => $amount,
            'address'   => $address,
        ])->apiRequest()->getResponse();
    }
}
