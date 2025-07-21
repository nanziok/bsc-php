<?php

namespace Binance;

use GuzzleHttp\Exception\ConnectException;

class EtherscanApi implements ProxyApi {
    protected $apiKey;
    protected $chainId;
    protected $options;
    protected $errorHandler = null;
    
    function __construct(string $apiKey, $chainId = 1, array $options = []) {
        $this->apiKey  = $apiKey;
        $this->chainId = $chainId;
        $this->options = $options;
    }
    
    public function send($method, $params = []) {
        $defaultParams = [
            'module' => 'proxy',
            'tag'    => 'latest',
        ];
        
        foreach ($defaultParams as $key => $val) {
            if (!isset($params[$key])) {
                $params[$key] = $val;
            }
        }
        
        $url = "https://api.etherscan.io/v2/api?chainid={$this->chainId}&action={$method}&apikey={$this->apiKey}";
        if ($params && count($params) > 0) {
            $strParams = http_build_query($params);
            $url       .= "&{$strParams}";
        }
        try {
            $res = Utils::httpRequest('GET', $url, $this->options);
            if (!is_array($res)) {
                $error   = self::ERROR_UNKNOWN;
                $message = "接口返回错误：" . var_export($res, true);
            } else if (array_key_exists('status', $res) && $res['status'] == '0') {
                if (is_string($res['result'])) {
                    if (str_contains($res['result'], 'rate')) {
                        $error = self::ERROR_RATE_LIMITED;
                    } else {
                        $error = self::ERROR_UNKNOWN;
                    }
                    $message = $res['result'];
                } else {
                    $error   = self::ERROR_UNKNOWN;
                    $message = var_export($res, true);
                }
            } elseif (array_key_exists('error', $res) && !empty($res['error'])) {
                $res_str = var_export($res, true);
                $message = <<<TEXT
                    URL: {$url}
                    RESPONSE: {$res_str}
                    TEXT;
                if (is_string($res['error'])) {
                    $error = self::ERROR_UNKNOWN;
                } else if (isset($res['error']['code'])) {
                    $error = match ($res['error']["code"]) {
                        "-32600", "-32602" => self::ERROR_BAD_REQUEST,
                        "-32601"           => self::ERROR_NOT_FOUND,
                        "-32005"           => self::ERROR_RATE_LIMITED,
                        default            => self::ERROR_UNKNOWN,
                    };
                } else {
                    $error = self::ERROR_UNKNOWN;
                }
            } elseif (!array_key_exists('result', $res)) {
                $message = "返回错误：" . var_export($res, true);
                $error   = self::ERROR_UNKNOWN;
            }
        } catch (ConnectException $e) {
            $res     = [];
            $error   = self::ERROR_UNKNOWN;
            $message = "网络请求失败";
        }
        
        if (isset($error) && is_callable($this->errorHandler)) {
            call_user_func_array($this->errorHandler, [$error, $message ?? '']);
        }
        return $res['result'];
    }
    
    function gasPrice() {
        return $this->send('eth_gasPrice');
    }
    
    function bnbBalance(string $address) {
        $params['module']  = 'account';
        $params['address'] = $address;
        
        $retDiv = Utils::fromWei($this->send('balance', $params), 'ether');
        if (is_array($retDiv)) {
            return Utils::divideDisplay($retDiv, 18);
        } else {
            return $retDiv;
        }
    }
    
    function receiptStatus(string $txHash) {
        $res = $this->send('eth_getTransactionByHash', ['txhash' => $txHash]);
        if (!$res) {
            return false;
        }
        
        if (!$res['blockNumber']) {
            return null;
        }
        
        $params['module'] = 'transaction';
        $params['txhash'] = $txHash;
        
        $res = $this->send('gettxreceiptstatus', $params);
        if (!$res) {
            return null;
        }
        return $res['status'] == '1';
    }
    
    function getTransactionReceipt(string $txHash) {
        $res = $this->send('eth_getTransactionReceipt', ['txhash' => $txHash]);
        return $res;
    }
    
    function getTransactionByHash(string $txHash) {
        return $this->send('eth_getTransactionByHash', ['txHash' => $txHash]);
    }
    
    function sendRawTransaction($raw) {
        return $this->send('eth_sendRawTransaction', ['hex' => $raw]);
    }
    
    function getNonce(string $address) {
        return $this->send('eth_getTransactionCount', ['address' => $address]);
    }
    
    function getNetwork(): string {
        return 'mainnet';
    }
    
    function ethCall(string $from, string $to, string $data, string $tag = 'latest'): string {
        return $this->send('eth_call', [
            'to'   => $to,
            'data' => $data,
            'tag'  => 'latest'
        ]);
    }
    
    function blockNumber() {
        return hexdec($this->send('eth_blockNumber'));
    }
    
    function getBlockByNumber(int $blockNumber, bool $boolean = true) {
        $blockNumber = Utils::toHex($blockNumber, true);
        return $this->send('eth_getBlockByNumber', ['tag' => $blockNumber, "boolean" => $boolean]);
    }
    
    function getBlockTransactionCountByNumber(int $blockNumber) {
        $blockNumber = Utils::toHex($blockNumber, true);
        return $this->send('eth_getBlockTransactionCountByNumber', ['tag' => $blockNumber]);
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
    
    function errorHandle(callable $fn) {
        $this->errorHandler = $fn;
    }
}
