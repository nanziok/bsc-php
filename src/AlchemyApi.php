<?php

namespace Binance;

class AlchemyApi extends NodeApi {
    
    function __construct(string $apiKey, $network = "mainnet", array $options=[]) {
        $this->network = $network;
        $gateway  = "https://bnb-{$this->network}.g.alchemy.com/v2/{$apiKey}";
        parent::__construct($gateway, $network, $options);
    }
    
    
}
