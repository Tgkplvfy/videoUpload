<?php

class VideoController extends Ap_Base_Control {

    public $actions = array (
        'info' => 'actions/Video/Info.php', 
        'm3u8' => 'actions/Video/M3u8.php', 
        'hxk' => 'actions/Video/Hxk.php', 
        'status' => 'actions/Video/Status.php', 
        'testupload' => 'actions/Video/Testupload.php' 
    );
}