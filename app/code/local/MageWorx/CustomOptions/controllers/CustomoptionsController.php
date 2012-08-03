<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Denis
 * Date: 01.06.12
 * Time: 16:43
 * To change this template use File | Settings | File Templates.
 */

class MageWorx_CustomOptions_CustomoptionsController extends Mage_Core_Controller_Front_Action
{
    public function getRightBlockAction()
    {
        $this->getResponse()->setBody(json_encode($this->getLayout()->createBlock("core/template")->setTemplate("customoptions/optionsinfo.phtml")->toHtml()));
    }

public function imagesUploadAction() { 
    $id = $this->getRequest()->getParam('option_id');
    if($_FILES[$id]['name'] != "")
    {
        $ext = explode('.',$_FILES[$id]['name']);
        $ext = end($ext);
       // if($ext != "jpeg" && $ext != "jpg" && $ext != 'bmp' && $ext != 'gif' && $ext != 'png')  return;
        if(move_uploaded_file($_FILES[$id]['tmp_name'],Mage::getBaseDir().'/media/'.$_FILES[$id]['name']))
        {
            $img = str_replace("index.php/", "" ,Mage::getUrl().'/media/'.$_FILES[$id]['name']);
            echo $img;
        }

    }
    //print_r($_FILES);
}
}
