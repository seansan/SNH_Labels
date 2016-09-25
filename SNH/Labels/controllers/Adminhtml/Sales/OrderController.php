<?php
/**
 * Adminhtml sales orders controller extension
 *
 * @author      SNH
 */

require_once "Mage/Adminhtml/controllers/Sales/OrderController.php";

class SNH_Labels_Adminhtml_Sales_OrderController extends Mage_Adminhtml_Sales_OrderController
{

private function array_mpop($array, $iterate) {
  if(!is_array($array) && is_int($iterate))
    return false;
    
  while(($iterate--)!=false)
    array_pop($array);
  return $array;
} 

private function get_returnstr() {
	
	$store_id = Mage::app()->getStore()->getStoreId();
	$store  = Mage::getStoreConfig('general/store_information/name', $store_id);
	$str1   = Mage::getStoreConfig('shipping/origin/street_line1', $store_id) . '';
	$str2   = Mage::getStoreConfig('shipping/origin/street_line2', $store_id) . '';
	$reg    = Mage::getStoreConfig('shipping/origin/region_id', $store_id);
	$pc     = Mage::getStoreConfig('shipping/origin/postcode', $store_id);
	$city   = Mage::getStoreConfig('shipping/origin/city', $store_id);
	$country= Mage::getStoreConfig('shipping/origin/country_id', $store_id);

	if (preg_match("/^[0-9]+-?[hHsIivV]{0,4}$/i", trim($str2))) {
	  $str1 = $str1 . ' ' . $str2;
	  $str2 = '';
	 }

	return implode(' ', array_filter([$store,$str1,$str2,$pc,$city,$reg,$country]));

}

public function pdfprintlabelsAction() {
    $request = $this->getRequest();

    $ids = $request->getParam('order_ids');

if(!$ids){
  $ids = array($request->getParam('order_id'));
}

$pdfarray = array();
    
for($i=0;$i<count($ids);$i++):

  $order = Mage::getModel('sales/order')->load($ids[$i]);

  $shipaddr= trim($order->getShippingAddress()->getFormated(true));
  $splitx=explode("\n",$shipaddr);
  $split=explode("<br/>",$shipaddr);			
  
  foreach($split as $sp):
    if(stristr($sp,"<br />")){
      $temp = explode("<br />", $sp);
      foreach($temp as $t){
        $pdfarray[$i]["addr"][] .= $t;
      }
    } else {
      $pdfarray[$i]["addr"][] = $sp;
    }
  endforeach;

endfor;

$pdf = new Zend_Pdf();

foreach($pdfarray as $pdfarr):	

$temp = $this->array_mpop($pdfarr["addr"], 2);	
$temp2 = array();
$i=0;

$search_replace = array(
    "straat" => "str",
    "laan" => "ln",
    "wethouder" => "wth",
    "burgemeester" => "brgm",
    "van" => "v",
    "weg" => "w",
    "van der" => "v/d",
    "van de" => "v/d",
    "plantsoen" => "plsn",
    "eerste" => "1e",
    "tweede" => "2e",
    "derde" => "3e",
    "veld" => "v",
    "boulevard" => "blvd",
    "van van" => "v/v",
    "aan den" => "a/d",
    "nauwe" => "nw",
    "bahnhof" => "bhnf",
    "ministerie" => "min.",
    "plaat" => "plt",
    "alexander" => "alex.",
    "kade" => "kd",
    "nieuwe" => "nw",
    "prinses" => "prs",
    "hof" => "hf",
    "prins" => "pr."
);

foreach($temp as $addy){
	if (in_array($i, array(0, 1)) && (strlen(trim($addy)) > 20)) {
		$addy = trim(str_ireplace(array_keys($search_replace),array_values($search_replace), $addy));
	}
	if ($i != 0 && preg_match("/^[0-9]+-?[hHsIivV]{0,4}$/i", trim($addy))) {
		$temp2[$i-1] = $temp2[$i-1] . ' ' . trim($addy);
	} else {
		$temp2[$i] = trim($addy);
	}
	$i++;
}
	
$page = $pdf->newPage('286:153:');
$fontsize = 16; $margin = 12; $linespacing = 8;
$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), $fontsize);
$i = 153;
$ctn = 1; 
if ($temp2[3] == $temp2[4]) { unset($temp2[4]); }
foreach($temp2 as $addy){
  if (empty(trim($addy))) { continue; }
  $i = $i- $linespacing - $fontsize; // only move to next line if not only address
  $page->drawText(trim(strtoupper(trim($addy))), $margin, $i, 'UTF-8');	
  $ctn++;
}

$fontsize = 8; 
$ret = 'Return: ' .$this->get_returnstr();
$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), $fontsize);
$page->drawText(trim(strtoupper(trim($ret))), $margin, ($fontsize/2), 'UTF-8');

$pdf->pages[] = $page;

endforeach;

$pdfData = $pdf->render(); 

$this->_prepareDownloadResponse('ShippingLabels-'.date('d-m-Y-H-i-s').'.pdf', $pdf->render(), 'application/pdf');

}

}
