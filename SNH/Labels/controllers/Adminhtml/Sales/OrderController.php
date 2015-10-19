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
foreach($temp as $addy){
  if ($i != 0 && preg_match("/^[0-9]+-?[hHsIivV]{0,4}$/i", trim($addy))) {
    $temp2[$i-1] = $temp2[$i-1] . ' ' . trim($addy);
  } else {
    $temp2[$i] = trim($addy);
  }
  $i++;
}
$page = $pdf->newPage('286:153:');
$fontsize = 16; $margin = 12; $linespacing = 4;
$page->setFont(Zend_Pdf_Font::fontWithName(Zend_Pdf_Font::FONT_HELVETICA), $fontsize);
$i = 153;
$ctn = 1; 
foreach($temp2 as $addy){
  if (empty(trim($addy))) { continue; }
  $i = $i- $linespacing - $fontsize; // only move to next line if not only address
  $page->drawText(trim(strtoupper(trim($addy))), $margin, $i, 'UTF-8');	
  $ctn++;
}
$pdf->pages[] = $page;

endforeach;

$pdfData = $pdf->render(); 

$this->_prepareDownloadResponse('ShippingLabels-'.date('d-m-Y-H-i-s').'.pdf', $pdf->render(), 'application/pdf');

}

}
