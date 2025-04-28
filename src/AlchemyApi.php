<?php

namespace Binance;

class AlchemyApi implements ProxyApi {
    protected $apiKey;
    protected $network;
    /**
     * @var callable
     */
    protected $errorHandler;
    
    function __construct(string $apiKey, $network = "mainnet") {
        $this->apiKey  = $apiKey;
        $this->network = $network;
    }
    
    public function send($method, $params = [], $req_id = 1) {
        $data = [
            'id'      => $req_id,
            'jsonrpc' => '2.0',
            "method"  => $method,
            "params"  => $params,
        ];
        
        $url = "https://bnb.-{$this->network}.g.alchemy.com/v2/{$this->apiKey}";
        
        $res = Utils::httpRequest('POST', $url, [
            'json' => $data
        ]);
        if (array_key_exists('error', $res)) {
            $error = match ($res['error']["code"]) {
                "-32600", "-32602" => self::ERROR_BAD_REQUEST,
                "-32601"           => self::ERROR_NOT_FOUND,
                "-32005"           => self::ERROR_RATE_LIMITED,
                default            => self::ERROR_UNKNOWN,
            };
            $message = $res['error']["message"];
        }
        if (isset($error) && is_callable($this->errorHandler)) {
            call_user_func_array($this->errorHandler, [$error, $message ?? '']);
        }
        if (array_key_exists('result', $res)) {
            return $res['result'];
        } else {
            return false;
        }
    }
    
    function gasPrice() {
        return $this->send('eth_gasPrice');
    }
    
    function bnbBalance(string $address) {
        $params['module']  = 'account';
        $params['address'] = $address;
        
        $retDiv = Utils::fromWei($this->send('eth_geBalance', $params), 'ether');
        if (is_array($retDiv)) {
            return Utils::divideDisplay($retDiv, 18);
        } else {
            return $retDiv;
        }
    }
    
    function receiptStatus(string $txHash): ?bool {
        $res = $this->send('eth_getTransactionByHash', [$txHash]);
        if (!$res) {
            return false;
        }
        
        if (!$res['blockNumber']) {
            return null;
        }
        $res = $this->send('eth_getTransactionReceipt', [$txHash]);
        return $res['status'] == '1';
    }
    
    function getTransactionReceipt(string $txHash) {
        $res = $this->send('eth_getTransactionReceipt', [$txHash]);
        return $res;
    }
    
    function getTransactionByHash(string $txHash) {
        return $this->send('eth_getTransactionByHash', [$txHash]);
    }
    
    function sendRawTransaction($raw) {
        return $this->send('eth_sendRawTransaction', [$raw]);
    }
    
    function getNonce(string $address) {
        return $this->send('eth_getTransactionCount', [$address, "last"]);
    }
    
    function getNetwork(): string {
        return $this->network;
    }
    
    function ethCall(string $to, string $data, string $tag = 'latest'): string {
        return $this->send('eth_call', [
            [
                'to'   => $to,
                'data' => $data,
                'tag'  => 'latest'
            ]
        ]);
    }
    
    function blockNumber() {
        return hexdec($this->send('eth_blockNumber'));
    }
    
    function getBlockByNumber(int $blockNumber, bool $boolean = true) {
        $blockNumber = Utils::toHex($blockNumber, true);
        return $this->send('eth_getBlockByNumber', [$blockNumber, $boolean]);
    }
    
    function getBlockTransactionCountByNumber(int $blockNumber) {
        $blockNumber = Utils::toHex($blockNumber, true);
        return $this->send('eth_getBlockTransactionCountByNumber', [$blockNumber]);
    }
    
    function getTransactionByBlockNumberAndIndex(int $blockNumber, int $index) {
        return $this->send('eth_getTransactionByBlockNumberAndIndex', [
            Utils::toHex($blockNumber, true),
            Utils::toHex($index, true)
        ]);
    }
    
    function estimateGas(string $data, string $to, int $value, int $gas, int $gasPrice) {
        return $this->send('eth_estimateGas', [
                [
                    "data"     => $data,
                    "to"       => $to,
                    "value"    => Utils::toHex($value, true),
                    "gas"      => Utils::toHex($gas, true),
                    "gasPrice" => Utils::toHex($gasPrice, true),
                ],
                "latest"
            ]
        );
    }
    
    function errorHandle(callable $fn) {
        $this->errorHandler = $fn;
    }
}
