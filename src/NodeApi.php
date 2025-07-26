<?php

namespace Binance;

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

class NodeApi implements ProxyApi {
    protected $gateway;
    protected $options;
    protected $network;
    /**
     * @var callable
     */
    protected $errorHandler;
    
    function __construct(string $gateway, string $network = 'mainnet', array $options = []) {
        $this->gateway = $gateway;
        $this->network = $network;
        $this->options = array_merge([
            "http_errors" => false,
        ], $options);
    }
    
    public function send($method, $params = [], $req_id = 1) {
        $strParams   = json_encode(array_values($params));
        $data_string = <<<data
            {"jsonrpc":"2.0","method":"{$method}","params": $strParams,"id":$req_id}
            data;
        
        $this->options["body"] = $data_string;
        if (!isset($this->options["headers"])) {
            $this->options["headers"] = [];
        }
        $this->options["headers"]["Content-Type"] = "application/json";
        try {
            $res = Utils::httpRequest('POST', $this->gateway, $this->options);
            if (!is_array($res)){
                $error = self::ERROR_UNKNOWN;
                $message = "接口返回错误：" . var_export($res, true);
            }else if (array_key_exists('error', $res)) {
                $error   = match ($res['error']["code"]) {
                    "-32600", "-32602" => self::ERROR_BAD_REQUEST,
                    "-32601"           => self::ERROR_NOT_FOUND,
                    "-32005"           => self::ERROR_RATE_LIMITED,
                    default            => self::ERROR_UNKNOWN,
                };
                $message = <<<TEXT
                    URL: {$this->gateway}
                    BODY: {$this->options["body"]}
                    RESPONSE: {$res["error"]["message"]}
                    TEXT;
            }else if (empty($res['result'])) {
                $error = self::ERROR_UNKNOWN;
                $message = "接口返回错误：" . var_export($res, true);
            }
        } catch (ConnectException $e) {
            $res     = [];
            $error   = self::ERROR_UNKNOWN;
            $message = "网络请求失败";
        } catch (RequestException $e) {
            $res   = [];
            $error = self::ERROR_RATE_LIMITED;
            if ($e->hasResponse()) {
                $message = "网络请求失败: http-code:【{$e->getResponse()->getStatusCode()}】，" . $e->getMessage();
            } else {
                $message = "网络请求失败: " . $e->getMessage();
            }
        }
        if (isset($error) && is_callable($this->errorHandler)) {
            call_user_func_array($this->errorHandler, [$error, $message ?? '']);
        }
        if (is_array($res) && array_key_exists('result', $res)) {
            return $res['result'];
        } else {
            return false;
        }
    }
    
    function gasPrice() {
        return $this->send('eth_gasPrice');
    }
    
    function bnbBalance(string $address, int $decimals = 18) {
        $balance = $this->send('eth_getBalance', ['address' => $address, 'block' => 'latest']);
        return Utils::toDisplayAmount($balance, $decimals);
    }
    
    function receiptStatus(string $txHash) {
        $res = $this->send('eth_getTransactionReceipt', ['txHash' => $txHash]);
        if (empty($res)) {
            return null;
        }
        return hexdec($res['status']) == 1;
    }
    
    function sendRawTransaction($raw) {
        return $this->send('eth_sendRawTransaction', ['hex' => $raw]);
    }
    
    function getNonce(string $address) {
        return $this->send('eth_getTransactionCount', ['address' => $address, 'block' => 'latest']);
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
    
    function ethCall(string $from, string $to, string $data, string $tag = 'latest'): string {
        return $this->send('eth_call', [
            "params" => ['from' => $from, 'to' => $to, 'data' => $data],
            "tag"    => $tag
        ]);
    }
    
    function blockNumber() {
        return hexdec($this->send('eth_blockNumber'));
    }
    
    function getBlockByNumber(int $blockNumber) {
        $blockNumber = Utils::toHex($blockNumber, true);
        return $this->send('eth_getBlockByNumber', ['block' => $blockNumber, 'is_rich' => true]);
    }
    
    function getBlockTransactionCountByNumber(int $blockNumber) {
        $blockNumber = Utils::toHex($blockNumber, true);
        return $this->send('eth_getBlockTransactionCountByNumber', ['block' => $blockNumber]);
    }
    
    function getTransactionByBlockNumberAndIndex(int $blockNumber, int $index) {
        return $this->send('eth_getTransactionByBlockNumberAndIndex', [
            'block' => Utils::toHex($blockNumber, true),
            'index' => Utils::toHex($index, true)
        ]);
    }
    
    function estimateGas(string $data, string $to, int $value, int $gas, int $gasPrice) {
        return $this->send('eth_estimateGas', [
            "params" => [
                "data"     => $data,
                "to"       => $to,
                "value"    => Utils::toHex($value, true),
                "gas"      => Utils::toHex($gas, true),
                "gasPrice" => Utils::toHex($gasPrice, true),
            ]
        ]);
    }
    
    function errorHandle(callable $fn) {
        $this->errorHandler = $fn;
    }
}
