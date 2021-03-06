<?php	if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
==========================================================
	This software package is intended for use with
	ExpressionEngine.	ExpressionEngine is Copyright ©
	2002-2009 EllisLab, Inc.
	http://ellislab.com/
==========================================================
	THIS IS COPYRIGHTED SOFTWARE, All RIGHTS RESERVED.
	Written by: Travis Smith and Justin Crawford
	Copyright (c) 2016 Hop Studios
	http://www.hopstudios.com/software/
--------------------------------------------------------
	Please do not distribute this software without written
	consent from the author.
==========================================================
	Files:
	- mcp.deeploy_helper.php
	- lang.deeploy_helper.php
----------------------------------------------------------
	Purpose:
	- Helps change site preferences all in one handy panel
----------------------------------------------------------
	Notes:
	- None
==========================================================
*/

require_once PATH_THIRD."deeploy_helper/config.php";

class Deeploy_helper_upd {

	var $version = DEEPLOY_HELPER_VERSION;

	// ----------------------------------------
	//	Module installer
	// ----------------------------------------
	function install()
	{

		$data = array(
			'module_name'	=> 'Deeploy_helper',
			'module_version'	=> $this->version,
			'has_cp_backend'	=> 'y',
			'has_publish_fields' => 'n'
		);

		ee()->db->insert('exp_modules', $data);

		return true;
	}
	// END


	// ----------------------------------------
	//	Module de-installer
	// ----------------------------------------
	function uninstall()
	{
		ee()->db->select('module_id');
		$query = ee()->db->get_where('modules', array('module_name' => 'Deeploy_helper'));

		ee()->db->where('module_id', $query->row('module_id'));
		ee()->db->delete('module_member_groups');

		ee()->db->where('module_name', 'Deeploy_helper');
		ee()->db->delete('modules');

		ee()->db->where('class', 'Deeploy_helper');
		ee()->db->delete('actions');

		return true;
	}
	// END

	// ----------------------------------------
	//	Module updater
	// ----------------------------------------
	function update($current = '')
	{
		if ($current == $this->version)
		{
			return FALSE;
		}

		if ($current < 1.0)
		{
			// Do your update code here
		}

		return TRUE;
	}


}

/* END Class */

/* End of file upd.deeploy_helper.php */
