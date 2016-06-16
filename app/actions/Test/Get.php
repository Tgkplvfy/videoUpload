<?php 

class TestGetAction extends Ap_Base_Action 
{

    public function execute () 
    {
        $str = 'group1/M00/00/00/CmCNTVdiEe6IHxcBAGLyKStnoVkAAAAAQDeVh8AYvJB899.mp4';

        $test = preg_replace('/\//', '-', $str);

        print_r($str);
        print_r($test);
    }
}