<?php

namespace Binance;

interface ProxyApi {
    
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
}
