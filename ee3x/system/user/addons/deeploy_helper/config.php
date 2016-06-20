<?php
$config['name']='Deeploy Helper';
$config['version']='3.0.0';
$config['nsm_addon_updater']['versions_xml']='http://www.hopstudios.com/software/versions/deeploy_helper/';

// Version constant
if (!defined("DEEPLOY_HELPER_VERSION")) {
	define('DEEPLOY_HELPER_VERSION', $config['version']);
}
