<?php

namespace Binance;

class NodeApi implements ProxyApi {
    protected $server;
    protected $user;
    protected $password;
    protected $network;
    
    function __construct(string $server, string $user = null, string $password = null, string $network = 'mainnet') {
        $this->server   = $server;
        $this->user     = $user;
        $this->password = $password;
        $this->network  = $network;
    }
    
    public function send($method, $params = []) {
        $url = $this->server;
        
        $strParams   = json_encode(array_values($params));
        $data_string = <<<data
            {"jsonrpc":"2.0","method":"{$method}","params": $strParams,"id":1}
            data;
        
        $data = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'body'    => $data_string
        ];
        if ($this->user && $this->password) {
            $data['auth'] = [
                $this->user,
                $this->password
            ];
        }
        
        $res = Utils::httpRequest('POST', $url, $data);
        if (isset($res['result'])) {
            return $res['result'];
        } else {
            return false;
        }
    }
    
    function gasPrice() {
        return $this->send('eth_gasPrice');
    }
    
    function bnbBalance(string $address, int $decimals = 18) {
        $balance = $this->send('eth_getBalance', ['address' => $address, 'latest']);
        return Utils::toDisplayAmount($balance, $decimals);
    }
    
    function receiptStatus(string $txHash): bool {
        $res = $this->send('eth_getTransactionReceipt', ['txHash' => $txHash]);
        return hexdec($res['status']) ? true : false;
    }
    
    function sendRawTransaction($raw) {
        return $this->send('eth_sendRawTransaction', ['hex' => $raw]);
    }
    
    function getNonce(string $address) {
        return $this->send('eth_getTransactionCount', ['address' => $address, 'latest']);
    }
    
    function getTransactionReceipt(string $txHash) {
        return $this->send('eth_getTransactionReceipt', ['txHash' => $txHash]);
    }
    
    function getTransactionByHash(string $txHash) {
        return $this->send('eth_getTransactionByHash', ['txHash' => $txHash]);
    }
    
    function getNetwork(): string {
        return $this->network;
    }
    
    function ethCall(string $to, string $data, string $tag = 'latest'): string {
        return $this->send('eth_call', ['to' => $to, 'data' => $data, 'tag' => $tag]);
    }
    
    function blockNumber() {
        return hexdec($this->send('eth_blockNumber'));
    }
    
    function getBlockByNumber(int $blockNumber) {
        $blockNumber = Utils::toHex($blockNumber, true);
        return $this->send('eth_getBlockByNumber', ['tag' => $blockNumber, true]);
    }
    
    function getBlockTransactionCountByNumber(int $blockNumber) {
        $blockNumber = Utils::toHex($blockNumber, true);
        return $this->send('eth_getBlockTransactionCountByNumber', ['tag' => $blockNumber, true]);
    }
    
    function getTransactionByBlockNumberAndIndex(int $blockNumber, int $index) {
        return $this->send('eth_getTransactionByBlockNumberAndIndex', [
            'tag'   => Utils::toHex($blockNumber, true),
            'index' => Utils::toHex($index, true)
        ]);
    }
    
    function estimateGas(string $data, string $to, int $value, int $gas, int $gasPrice) {
        return $this->send('eth_estimateGas', [
            "data"     => $data,
            "to"       => $to,
            "value"    => Utils::toHex($value, true),
            "gas"      => Utils::toHex($gas, true),
            "gasPrice" => Utils::toHex($gasPrice, true),
        ]);
    }
}
