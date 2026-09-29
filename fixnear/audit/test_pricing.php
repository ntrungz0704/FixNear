<?php
require_once dirname(__DIR__) . '/includes/pricing_engine.php';
$p = RepairAtlasPricing::getModelPrices('phone', 'P3', 'apple', ['screen', 'battery'], 'apple-iphone-13');
print_r($p);
