<?php

/* System directory */
$config['site']['coredir'] = dirname(dirname(__FILE__)).'/';

/* Custom Controller directory */
$config['site']['cust_controller_dir'] = $config['site']['appdir'] . 'controller/';

/* System Controller directory */
$config['site']['core_controller_dir'] = $config['site']['coredir'];

/* View directory */
$config['site']['viewdir'] = $config['site']['appdir'] . 'view/';

/* View URL */
if(strpos($config['site']['url'],'public') > 0) {
    $config['site']['viewurl'] = str_replace('/public','',$config['site']['url']) . '/application/view/';
}else{
    $config['site']['viewurl'] = $config['site']['url'] . '/application/view/';
}

/* Custom Model directory */
$config['site']['modeldir'] = $config['site']['appdir'] . 'model/';

/* Template directory */
$config['site']['templatedir'] = $config['site']['appdir'] . 'templates/';

/* Worker directory */
$config['site']['workerdir'] = $config['site']['coredir'] . 'worker/';

/* Assembly directory */
$config['site']['assemblydir'] = $config['site']['coredir'] . 'assembly/';