<?php
require 'vendor/autoload.php';
use Picqer\Barcode\BarcodeGeneratorPNG;

$generator = new BarcodeGeneratorPNG();
$code = $_GET['code'] ?? '';

header('Content-Type: image/png');
echo $generator->getBarcode($code, $generator::TYPE_CODE_128); 