<?php

namespace Binance;

class InfuraApi extends NodeApi {
    
    function __construct(string $apiKey, $network = "mainnet", array $options = []) {
        $this->network = $network;
        $gateway = "https://bsc-{$this->network}.infura.io/v3/{$apiKey}";
        parent::__construct($gateway, $network, $options);
    }
    
    /**
     * @param string $filter_id
     *
     * @return string[]|Log[]
     */
    public function getFilterChanges(string $filter_id) {
        $res = $this->send("eth_getFilterChanges", ["filter_id" => $filter_id]);
        return $res;
    }
    
    public function getFilerLogs(string $fromBlock = "latest", string $toBlock = "latest", string $contract = "", array $topics = [], string $blockHash = "") {
        $data = [];
        if ($blockHash) {
            $data["blockHash"] = $blockHash;
        }else {
            if ($fromBlock) {
                $data["fromBlock"] = $fromBlock;
            }
            if ($toBlock) {
                $data["toBlock"] = $toBlock;
            }
        }
        if ($contract) {
            $data["address"] = $contract;
        }
        if ($topics) {
            $data["topics"] = $topics;
        }
        $res = $this->send("eth_getFilterLogs", $data);
        return $res;
    }
    
    public function newBlockFilter() {
        $res = $this->send("eth_newBlockFilter");
        return $res;
    }
    
    public function newFilter(string $contract, string $fromBlock = 'latest', string $toBlock = 'latest', $topics = []) {
        $res = $this->send("eth_newFilter");
        return $res;
    }
    
    public function newPendingTransactionFilter() {
        $res = $this->send("eth_newPendingTransactionFilter");
        return $res;
    }
    
    public function uninstallFilter(string $filter_id) {
        $res = $this->send("eth_uninstallFilter", ["filter_id" => $filter_id]);
        return $res;
    }
    
    
}
