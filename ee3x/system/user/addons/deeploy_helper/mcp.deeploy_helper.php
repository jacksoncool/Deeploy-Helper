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

	// -------------------------
	//	constructor
	// -------------------------
	function __construct( $switch = TRUE )
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
		$settings_n = array();
		
		// Get the current site Model to access its preferences
		$site_id = ee()->config->item('site_id');
		$site = ee('Model')->get('Site')->filter('site_id', $site_id)->all()->first();

		// Get site preferences (only the ones that matters)
		$settings_to_retrieve = array('site_url', 'theme_folder_url', 'captcha_url', 'captcha_path', 'theme_folder_path');
		foreach ($settings_to_retrieve as $setting_name)
		{
			$settings_n[ee()->lang->line('site_system_preferences')][$setting_name] = $site->site_system_preferences->{$setting_name};
		}
		
		// Get Members preferences
		// Note : those are saved in 2 places : in the site settings AND as UploadDestination
		// So when saving them, better make sure you save them on both places !
		// Re-Note : We'll use UploadDestination to add them in our form
		// $settings_to_retrieve = array('avatar_url', 'avatar_path', 'photo_url', 'photo_path', 'sig_img_url', 'sig_img_path', 'prv_msg_upload_path', 'prv_msg_upload_path');
		// foreach ($settings_to_retrieve as $setting_name)
		// {
		// 	$settings_n[ee()->lang->line('site_member_preferences')][$setting_name] = $site->site_member_preferences->{$setting_name};
		// }
		
		// Get all channels
		$channels = $site->Channels;
		
		foreach ($channels as $channel)
		{
			$unique_setting_name_prefix = 'channel::'.$channel->channel_id;
			
			$settings_n[$channel->channel_title][$unique_setting_name_prefix.'::channel_url'] = $channel->channel_url;
			
			$settings_n[$channel->channel_title][$unique_setting_name_prefix.'::comment_url'] = $channel->comment_url;
			
			$settings_n[$channel->channel_title][$unique_setting_name_prefix.'::search_results_url'] = $channel->search_results_url;
		}
		
		// Get All Upload Destinations
		$upload_destinations = $site->UploadDestinations;
		foreach ($upload_destinations as $upload_destination)
		{
			$unique_setting_name_prefix = 'upload_dest::'.$upload_destination->id;
			// Server path
			$settings_n[ee()->lang->line('upload_preferences') . " (" . $upload_destination->name . ")"][$unique_setting_name_prefix.'::server_path'] = $upload_destination->server_path->path;
			// URL
			$settings_n[ee()->lang->line('upload_preferences') . " (" . $upload_destination->name . ")"][$unique_setting_name_prefix.'::url'] = $upload_destination->url;
		}
		
		// Get Forums preferences
		// Settings : board_forum_url, board_forum_trigger, board_upload_path
		$boards = ee('Model')->get('forum:Board')->filter('board_site_id', $site_id)->all();
		foreach ($boards as $board)
		{
			$unique_setting_name_prefix = 'board::'.$board->board_id;
			// Board forum URL
			$settings_n[ee()->lang->line('forum_preferences') . ' ('.$board->board_label.')'][$unique_setting_name_prefix.'::board_forum_url'] = $board->board_forum_url;
			
			// Board Upload Path
			$settings_n[ee()->lang->line('forum_preferences') . ' ('.$board->board_label.')'][$unique_setting_name_prefix.'::board_upload_path'] = $board->board_upload_path;
		}
		
		//print_r($settings_n);

		// TODO : Convert that to models (will not be used for now)
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

		return $settings_n;
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

		// Load JS into EE js
		$js_script = file_get_contents(PATH_THIRD . '/deeploy_helper/javascript/script.js');
		ee()->javascript->output(array($js_script));
		ee()->javascript->compile();

		// vars are available as first-class variables in our view
		$vars['cp_page_title'] = ee()->lang->line('deeploy_helper_module_name');

		// form action
		$vars['form_action'] = ee('CP/URL', 'addons/settings/deeploy_helper/save');
		
		$vars['sections'] = array();
		$vars['base_url'] = ee('CP/URL', 'addons/settings/deeploy_helper/save');
		$vars['save_btn_text'] = lang('save_btn_text');
		$vars['save_btn_text_working'] = lang('save_btn_text_working');
		// Generate EE3 Settings Form
		foreach($this->get_config() as $section => $config)
		{
			$current_section = array();
			// Add all fields
			foreach($config as $meganame => $value)
			{
				if (($section == ee()->lang->line('config_file')) || ($section == ee()->lang->line('path_file')))
				{
					// $vars['table_rows'][] = array('read_only' => TRUE, 'label' => $meganame, 'value' => $value);
				}
				else
				{
					// the meganame is a compound field joined by '::'.  except when it's not, as in exp_sites.
					if (strpos($meganame, '::') !== FALSE)
					{
						list($table, $id, $name) = explode('::', $meganame);
						//$vars['table_rows'][] = array('label' => $name, 'name' => $meganame, 'value' => $value);
						$current_section[] = array(
							'title' => $name,
							// 'desc' => 'email_notif_email_desc',
							'fields' => array(
								$meganame => array('type' => 'text', 'value' => $value)
							)
						);
					}
					else
					{
						//$vars['table_rows'][] = array('label' => $meganame, 'name' => $meganame, 'value' => $value);
						$current_section[] = array(
							'title' => $meganame,
							// 'desc' => 'email_notif_email_desc',
							'fields' => array(
								$meganame => array('type' => 'text', 'value' => $value)
							)
						);
					}
				}
			}
			$vars['sections'][$section] = $current_section;
		}

		// return ee()->load->view('settings_form', $vars, TRUE);
		return array(
			'heading'		=> lang('deeploy_helper_module_name'),
			'body'			=> ee('View')->make('deeploy_helper:settings_form')->render($vars),
			'breadcrumb'	=> array(
				ee('CP/URL', 'addons/settings/deeploy_helper')->compile() => lang('deeploy_helper_module_name')
			),
		);
	}
	// end
	
	// -------------------------------------------------------
	// save settings submitted by the form.
	// -------------------------------------------------------
	function save()
	{
		ee()->load->helper('string');

		$site_id = ee()->config->item('site_id');
		$site = ee('Model')->get('Site')->filter('site_id', $site_id)->all()->first();
		$channels = $site->Channels;
		$upload_destinations = $site->UploadDestinations;
		$boards = ee('Model')->get('forum:Board')->filter('board_site_id', $site_id)->all();
		
		// We order them using their Id, it's easier to fetch them later on when saving the settings
		$channels_ordered = array();
		foreach ($channels as $channel)
		{
			$channels_ordered[$channel->channel_id] = $channel;
		}
		
		$upload_destinations_ordered = array();
		foreach ($upload_destinations as $upload_destination)
		{
			$upload_destinations_ordered[$upload_destination->id] = $upload_destination;
		}
		
		$boards_ordered = array();
		foreach ($boards as $board)
		{
			$boards_ordered[$board->board_id] = $board;
		}

		$site_changed = FALSE;
		
		foreach ($_POST as $meganame => $value)
		{
			// handle submissions from non-serialized tables
			if (strpos($meganame, "::") !== FALSE)
			{
				list($model_type, $id, $name) = explode("::", $meganame);
				
				$model_type = ee('Security/XSS')->clean($model_type);
				$id = ee('Security/XSS')->clean($id);
				$name = ee('Security/XSS')->clean($name);
				$value = ee('Security/XSS')->clean($value);

				if ($model_type == "channel")
				{
					if (array_key_exists($id, $channels_ordered))
					{
						$channel = $channels_ordered[$id];
						
						$channel->{$name} = $value;
						$channel->save();
					}
				}
				elseif ($model_type == "upload_dest")
				{
					if (array_key_exists($id, $upload_destinations_ordered))
					{
						$upload_destination = $upload_destinations_ordered[$id];
						
						$upload_destination->{$name} = $value;
						$upload_destination->save();
						
						// Change them in the site settings too !
						// This has to be checked manually as there's no link between the model and the site settings...
						// 'photo_url', 'photo_path', 'sig_img_url', 'sig_img_path'
						if ($upload_destination->name == "Avatars" && $name == "server_path")
						{
							$site->site_member_preferences->avatar_path = $value;
							$site_changed = TRUE;
						} 
						else if ($upload_destination->name == "Avatars" && $name == "url")
						{
							$site->site_member_preferences->avatar_url = $value;
							$site_changed = TRUE;
						}
						else if ($upload_destination->name == "PM Attachments" && $name == "server_path")
						{
							$site->site_member_preferences->prv_msg_upload_path = $value;
							$site_changed = TRUE;
						}
						else if ($upload_destination->name == "PM Attachments" && $name == "url")
						{
							// This one doesn't actually exists in Site settings ?! Weird...
							// $site->site_member_preferences->prv_msg_upload_url = $value;
							// $site_changed = TRUE;
						}
						else if ($upload_destination->name == "Signature Attachments" && $name == "server_path")
						{
							$site->site_member_preferences->sig_img_path = $value;
							$site_changed = TRUE;
						}
						else if ($upload_destination->name == "Signature Attachments" && $name == "url")
						{
							$site->site_member_preferences->sig_img_url = $value;
							$site_changed = TRUE;
						}
						else if ($upload_destination->name == "Member Photos" && $name == "server_path")
						{
							$site->site_member_preferences->photo_path = $value;
							$site_changed = TRUE;
						}
						else if ($upload_destination->name == "Member Photos" && $name == "url")
						{
							$site->site_member_preferences->photo_url = $value;
							$site_changed = TRUE;
						}
					}
				}
				else if ($model_type == "board")
				{
					if (array_key_exists($id, $boards_ordered))
					{
						$board = $boards_ordered[$id];
						
						$board->{$name} = $value;
						$board->save();
					}
				}

			}
			else
			{
				// We assume this is a setting from the site settings
				$site->site_system_preferences->{$meganame} = $value;
				
				$site_changed = TRUE;
			}
		}
		
		if ($site_changed)
		{
			$site->save();
		}

		//Do proper redirection + session message to inform of saved settings
		ee('CP/Alert')->makeInline('shared-form')
			->asSuccess()
			->withTitle(lang('settings_saved'))
			->addToBody(lang('settings_saved_desc'))
			->defer();
		ee()->functions->redirect(ee('CP/URL', 'addons/settings/deeploy_helper'));
	}


}

/* END Class */

/* End of file mcp.deeploy_helper.php */
