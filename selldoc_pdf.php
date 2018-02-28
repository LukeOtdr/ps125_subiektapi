<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../../config/config.inc.php');
require_once('../../init.php');
require_once('ps125_subiektgt_api.php');

$cookie = new Cookie('psAdmin');
//var_Dump($cookie);
if (!$cookie->id_employee){
	echo Tools::displayError("You must be logged in!");
	exit;
}

$ps125_subiektgtapi = new Ps125_SubiektGT_Api();
$id_order = Tools::getValue('id_order',0);
$filename = $ps125_subiektgtapi->getPdfFile($id_order);
if(!$filename){
	exit('File not found!');
}

$pdf_content = file_get_contents($ps125_subiektgtapi->getPdfPath().'/'.$filename);
header("Content-type:application/pdf");
echo $pdf_content;
?>