<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
==========================================================
	This software package is intended for use with
	ExpressionEngine.	ExpressionEngine is Copyright �
	2002-2009 EllisLab, Inc.
	http://ellislab.com/
==========================================================
	THIS IS COPYRIGHTED SOFTWARE, All RIGHTS RESERVED.
	Written by: Travis Smith and Justin Crawdford
	Copyright (c) 2014 Hop Studios
	http://www.hopstudios.com/software/
--------------------------------------------------------
	Please do not distribute this software without written
	consent from the author.
==========================================================
	Purpose:
	- Helps change site preferences all in one handy panel
----------------------------------------------------------
	Notes:
	- None
==========================================================
*/

class Deeploy_helper_mcp {

	var $version	= DEEPLOY_HELPER_VERSION;

	var $from_system_prefs = array('captcha_path', 'captcha_url', 'emoticon_path', 'theme_folder_path', 'theme_folder_url', 'site_url');
	var $from_member_prefs = array('avatar_path', 'avatar_url', 'photo_url', 'photo_path', 'sig_img_url', 'sig_img_path', 'prv_msg_upload_path');
	var $from_template_prefs = array('tmpl_file_basepath');
	var $from_extension_prefs = array('file_path');  // for Low Variables currently

	// -------------------------
	//	constructor
	// -------------------------
	function Deeploy_helper_mcp( $switch = TRUE )
	{
		ee()->lang->loadfile('admin');
		//ee()->lang->loadfile('publish_ad');
	}

	// -------------------------------------------------------
	// get settings that are useful to manage on one page
	// -------------------------------------------------------
	function get_config()
	{
		ee()->load->helper('string');

		$settings = '';

		// get site preferences and member preferences and template preferences
		ee()->db->select('site_system_preferences, site_member_preferences, site_template_preferences');
		ee()->db->from('sites');
		ee()->db->where('site_id', ee()->config->item('site_id'));
		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{

			//print "Settings below: <br />";
			//print base64_decode($query->row('site_system_preferences')) . "<br />";
			//print base64_decode($query->row('site_member_preferences')) . "<br />";
			//print base64_decode($query->row('site_template_preferences')) . "<br />";

			foreach(unserialize(base64_decode($query->row('site_system_preferences'))) as $name => $value)
			{
				if(in_array($name, $this->from_system_prefs))
				{
					$settings[ee()->lang->line('site_system_preferences')][$name] = $value;
				}
			}
			foreach(unserialize(base64_decode($query->row('site_member_preferences'))) as $name => $value)
			{
				if(in_array($name, $this->from_member_prefs))
				{
					$settings[ee()->lang->line('site_member_preferences')][$name] = $value;
				}
			}
			foreach(unserialize(base64_decode($query->row('site_template_preferences'))) as $name => $value)
			{
				if(in_array($name, $this->from_template_prefs))
				{
					$settings[ee()->lang->line('site_template_preferences')][$name] = $value;
				}
			}
		}

		// get channel preferences
		ee()->db->select('channel_id, channel_title, channel_url, comment_url, search_results_url');
		ee()->db->from('channels');
		ee()->db->where('site_id', ee()->config->item('site_id'));
		ee()->db->where('channel_name !=', '');
		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			if (is_array($query->result_array()))
			{
				foreach($query->result_array() as $row)
				{
					$meganame = "exp_channels::" . $row['channel_id'] . "::";
					$settings[$row['channel_title'] . " " . ee()->lang->line('channel_preferences')][$meganame . 'channel_url'] = $row['channel_url'];
					$settings[$row['channel_title'] . " " . ee()->lang->line('channel_preferences')][$meganame . 'comment_url'] = $row['comment_url'];
					$settings[$row['channel_title'] . " " . ee()->lang->line('channel_preferences')][$meganame . 'search_results_url'] = $row['search_results_url'];
				}
			}
		}

		// get upload preferences
		ee()->db->select('id, name, server_path, url');
		ee()->db->from('upload_prefs');
		ee()->db->where('site_id', ee()->config->item('site_id'));
		ee()->db->where('name !=', '');
		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			if (is_array($query->result_array()))
			{
				foreach($query->result_array() as $row)
				{
					$meganame = "exp_upload_prefs::" . $row['id'] . "::";
					$settings[ee()->lang->line('upload_preferences') . " (" . $row['name'] . ")"][$meganame . 'server_path'] = $row['server_path'];
					$settings[ee()->lang->line('upload_preferences') . " (" . $row['name'] . ")"][$meganame . 'url'] = $row['url'];
				}
			}
		}

		// get forum preferences
		$verify_query = ee()->db->query("SHOW TABLES LIKE 'exp_forum_boards'");
		if ($verify_query->num_rows() > 0)
		{
			ee()->db->select('board_id, board_label, board_forum_url, board_upload_path');
			ee()->db->from('forum_boards');
			ee()->db->where('board_site_id', ee()->config->item('site_id'));
			ee()->db->where('board_name !=', '');
			$query = ee()->db->get();

			if ($query->num_rows() > 0)
			{
				if (is_array($query->result_array()))
				{
					foreach($query->result_array() as $row)
					{
						$meganame = "exp_forum_boards::" . $row['board_id'] . "::";
						$settings[ee()->lang->line('forum_preferences') . " (" . $row['board_label'] . ")"][$meganame . 'board_upload_path'] = $row['board_upload_path'];
						$settings[ee()->lang->line('forum_preferences') . " (" . $row['board_label'] . ")"][$meganame . 'board_forum_url'] = $row['board_forum_url'];
					}
				}
			}
		}


		// get Low Variable preferences
		$query = ee()->db->select('settings')
			->where('class','Low_variables_ext')
			->get('extensions', 1); // fetch just 1, as all should have the same value

		if ($query->num_rows() > 0)
		{
			if (is_array($query->result_array()))
			{
				foreach($query->result_array() as $row)
				{
					$working_array = unserialize($row['settings']);
					if (is_array($working_array))
					{
						foreach($working_array as $name => $value)
						{
							if($name == "file_path")
							{
								$settings['Low_variables_ext'][$name] = $value;
							}
						}
					}
				}
			}
		}


		// get config.php
		//$config_file = getcwd() . "/config.php";
		$config_file = APPPATH . "config/config.php";
		if (is_readable($config_file))
		{
			foreach(file($config_file) as $line)
			{
				if(strpos($line, "=") !== FALSE)
				{
					list($name, $value) = explode("=", $line);
					$settings[ee()->lang->line('config_file')][$name] = $value;
				}
			}
		}

		return $settings;
	}
	// end

	// -------------------------------------------------------
	// display a form containing a table containing all the settings we get
	// -------------------------------------------------------
	//function index($msg = '')
	function index($msg = '')
	{

		// we need to replace all $DSP with helpers: form helpers, html helpers, etc.
		ee()->load->helper('url');
		ee()->load->helper('form');
		ee()->load->library('table');

		//	html title and navigation crumblinks

		// vars are available as first-class variables in our view
		$vars['cp_page_title'] = ee()->lang->line('deeploy_helper_module_name');
		$vars['cp_heading'] = ee()->lang->line('deeploy_helper_menu');

		// message, if any
		$vars['message'] = $msg;

		// donation text
		$vars['pitch'] = ee()->lang->line('pitch');
		$vars['pitch_title'] = ee()->lang->line('pitch_title');

		// form action
		//$vars['form_action'] = BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=deeploy_helper'.AMP.'method=save';
		$vars['form_action'] = 'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=deeploy_helper'.AMP.'method=save';

		// table header
		$vars['table_heading1'] = array(ee()->lang->line('quick_replace'),'');
		$vars['table_heading2'] = array(ee()->lang->line('setting_name'), ee()->lang->line('setting_value'));

		$vars['table_rows'] = array();

		// generate table rows
		foreach($this->get_config() as $section => $config)
		{
			$vars['table_rows'][] = array('section' => $section);

			foreach($config as $meganame => $value)
			{
				// for now, config.php and path.php are read only.
				if (($section == ee()->lang->line('config_file')) || ($section == ee()->lang->line('path_file')))
				{
					$vars['table_rows'][] = array('read_only' => TRUE, 'label' => $meganame, 'value' => $value);
				}
				else
				{
					// the meganame is a compound field joined by '::'.  except when it's not, as in exp_sites.
					if (strpos($meganame, '::') !== FALSE)
					{
						list($table, $id, $name) = explode('::', $meganame);
						$vars['table_rows'][] = array('label' => $name, 'name' => $meganame, 'value' => $value);
					}
					else
					{
						$vars['table_rows'][] = array('label' => $meganame, 'name' => $meganame, 'value' => $value);
					}
				}
			}
		}

		return ee()->load->view('settings_form', $vars, TRUE);
	}
	// end

	// -------------------------------------------------------
	// save settings submitted by the form.
	// -------------------------------------------------------
	function save()
	{
		ee()->load->helper('string');

		// get serialized site preferences and member preferences and template preferences
		ee()->db->select('site_system_preferences, site_member_preferences, site_template_preferences');
		ee()->db->from('sites');
		ee()->db->where('site_id', ee()->config->item('site_id'));
		$query = ee()->db->get();

		if ($query->num_rows() > 0)
		{
			$system_prefs = strip_slashes(unserialize(base64_decode($query->row('site_system_preferences'))));
			$member_prefs = strip_slashes(unserialize(base64_decode($query->row('site_member_preferences'))));
			$template_prefs = strip_slashes(unserialize(base64_decode($query->row('site_template_preferences'))));
		}

		$updates = array();
		$changed = FALSE;
		$extension_changed = FALSE;

		foreach ($_POST as $meganame => $value)
		{
			// handle submissions from non-serialized tables
			if (strpos($meganame, "::") !== FALSE)
			{
				list($table, $id, $name) = explode("::", $meganame);
				$table = ee()->security->xss_clean($table);
				$id = ee()->security->xss_clean($id);
				$name = ee()->security->xss_clean($name);
				$value = ee()->security->xss_clean($value);

				if ($table == "exp_channels")
				{
					$updates[] = "UPDATE `$table` SET `$name` = " . ee()->db->escape($value) . " WHERE channel_id = " . ee()->db->escape($id) . " AND site_id = " . ee()->config->item('site_id');
				}
				if ($table == "exp_upload_prefs")
				{
					$updates[] = "UPDATE `$table` SET `$name` = " . ee()->db->escape($value) . " WHERE id = " . ee()->db->escape($id) . " AND site_id = " . ee()->config->item('site_id');
				}
				if ($table == "exp_forum_boards")
				{
					$updates[] = "UPDATE `$table` SET `$name` = " . ee()->db->escape($value) . " WHERE board_id = " . ee()->db->escape($id) . " AND board_site_id = " . ee()->config->item('site_id');
				}
			}

			// handle submissions from serialized tables
			elseif (in_array($meganame, $this->from_system_prefs))
			{
				$system_prefs[$meganame] = $value;
				$changed = TRUE;
			}
			elseif (in_array($meganame, $this->from_member_prefs))
			{
				$member_prefs[$meganame] = $value;
				$changed = TRUE;
			}
			elseif (in_array($meganame, $this->from_template_prefs))
			{
				$template_prefs[$meganame] = $value;
				$changed = TRUE;
			}
			elseif (in_array($meganame, $this->from_extension_prefs)) // for Low Variables
			{
				$extension_prefs[$meganame] = $value;
				$extension_changed = TRUE;
			}
		}

		if ($changed)
		{
			$system_prefs = base64_encode(serialize(ee()->security->xss_clean($system_prefs)));
			$member_prefs = base64_encode(serialize(ee()->security->xss_clean($member_prefs)));
			$template_prefs = base64_encode(serialize(ee()->security->xss_clean($template_prefs)));

			// just in case we want to echo some debug output -- easier to read than base64
			//$system_prefs = serialize(ee()->security->xss_clean($system_prefs));
			//$member_prefs = serialize(ee()->security->xss_clean($member_prefs));
			//$template_prefs = serialize(ee()->security->xss_clean($template_prefs));

			$updates[] = "UPDATE exp_sites set
				site_system_preferences = '$system_prefs',
				site_member_preferences = '$member_prefs',
				site_template_preferences = '$template_prefs'
				WHERE site_id = " . ee()->config->item('site_id');
		}

		if ($extension_changed)
		{
			$query = ee()->db->select('settings')
				->where('class','Low_variables_ext')
				->get('extensions', 1); // fetch just 1, as all should have the same value

			if ($query->num_rows() > 0)
			{
				if (is_array($query->result_array()))
				{
					foreach($query->result_array() as $row)
					{
						$working_array = unserialize($row['settings']);
						if (is_array($working_array))
						{
							foreach($working_array as $name => &$value) // & means you can modify the original value directly
							{
								if($name == "file_path")
								{
									$value = $extension_prefs[$name];
								}
							}
						}
					}
				}
			}
			$extension_prefs = serialize(ee()->security->xss_clean($working_array));

			$data =  array(
               'settings' => $extension_prefs
            );

			$query = ee()->db->where('class', 'Low_variables_ext')
				->update('exp_extensions', $data);
		}

		foreach ($updates as $sql)
		{
			ee()->db->query($sql);
		}

		//Do proper redirection + session message to inform of saved settings
		// return $this->index(ee()->lang->line('settings_saved'));
		ee()->session->set_flashdata('message_success', ee()->lang->line('settings_saved'));
		ee()->functions->redirect(BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=deeploy_helper');
	}


}

/* END Class */

/* End of file mcp.deeploy_helper.php */
