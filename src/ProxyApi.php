<?php

namespace Binance;

interface ProxyApi {
    
    //格式错误
    const ERROR_BAD_REQUEST = 400;
    //频率限制
    const ERROR_RATE_LIMITED = 429;
    //禁止访问
    const ERROR_FORBIDDEN = 403;
    //资源不存在
    const ERROR_NOT_FOUND = 404;
    //访问方法错误
    const ERROR_METHOD_NOT_ALLOWED = 405;
    //通用错误
    const ERROR_UNKNOWN = 500;
    
    function getNetwork(): string;
    
    function send($method, $params = []);
    
    function gasPrice();
    
    function bnbBalance(string $address);
    
    function receiptStatus(string $txHash): ?bool;
    
    function getTransactionReceipt(string $txHash);
    
    function getTransactionByHash(string $txHash);
    
    function sendRawTransaction($raw);
    
    function getNonce(string $address);
    
    function ethCall(string $to, string $data, string $tag = 'latest');
    
    function blockNumber();
    
    function getBlockByNumber(int $blockNumber);
    
    function getBlockTransactionCountByNumber(int $blockNumber);
    
    function getTransactionByBlockNumberAndIndex(int $blockNumber, int $index);
    
    function estimateGas(string $data, string $to, int $value, int $gas, int $gasPrice);
    
    function errorHandle(callable $fn);
}
