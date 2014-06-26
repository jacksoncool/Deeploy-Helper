<?php 

/*
==========================================================
	This software package is intended for use with 
	ExpressionEngine.	ExpressionEngine is Copyright © 
	2002-2009 EllisLab, Inc. 
	http://ellislab.com/
==========================================================
	THIS IS COPYRIGHTED SOFTWARE, All RIGHTS RESERVED.
	Written by: Travis Smith and Justin Crawford
	Copyright (c) 2009 Hop Studios
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

class Deeploy_helper_CP { 

	var $version	= '1.1.3'; 

	var $from_system_prefs = array('captcha_path', 'captcha_url', 'emoticon_path', 'theme_folder_path', 'theme_folder_url', 'site_url','webmaster_email','webmaster_name');
	var $from_member_prefs = array('avatar_path', 'avatar_url', 'photo_url', 'photo_path', 'sig_img_url', 'sig_img_path', 'prv_msg_upload_path');
	var $from_template_prefs = array('tmpl_file_basepath');
	var $from_extension_prefs = array(
		//Class => array(Setting Name 1, Setting Name 2, ...)
		'Fieldframe'	=> array('fieldtypes_url', 'fieldtypes_path'),
	);
		 
	// ------------------------- 
	//	constructor 
	// ------------------------- 
	function Deeploy_helper_CP( $switch = TRUE ) 
	{ 
		global $IN, $LANG, $DB; 

		$LANG->fetch_language_file('admin');
		$LANG->fetch_language_file('publish_ad');

		// try to grab language files for extensions we've configured
		/* this doesn't work when extensions aren't loaded.
		foreach ($this->from_extension_prefs as $class => $prefs)
		{
			$LANG->fetch_language_file(strtolower($class));
		}
		*/

		// do we need to update?
		$query = $DB->query("SELECT module_version FROM exp_modules WHERE module_name = 'Deeploy_helper'");
		if ($query->num_rows == 0)
		{
			return;
		}
		if ($query->row['module_version'] < $this->version)
		{
			// yes, we need to update
			$this->_update_module($query->row['module_version']);			        
		}
	      		 
		if ($switch) 
		{ 
			switch($IN->GBL('P')) 
			{ 
				 case 'edit'	:	$this->edit_config(); 
					 break;		
				 case 'save'	:	$this->save_config(); 
					 break; 
				 default		:	$this->edit_config(); 
					 break; 
			} 
		} 
	} 
		 
	// ------------------------------------------------------- 
	// get settings that are useful to manage on one page
	// ------------------------------------------------------- 
	function get_config()
	{
		global $DB, $PREFS, $REGX, $LANG;

		$settings = '';

		// get site preferences and member preferences and template preferences
		$query = $DB->query("SELECT site_system_preferences, site_member_preferences, site_template_preferences 
												FROM exp_sites WHERE site_id = " . $PREFS->ini('site_id'));
		if ($query->num_rows > 0)
		{
			foreach($REGX->array_stripslashes(unserialize($query->row['site_system_preferences'])) as $name => $value)
			{
				if(in_array($name, $this->from_system_prefs))
				{
					$settings[$LANG->line('site_system_preferences')][$name] = $value;
				}
			}
			foreach($REGX->array_stripslashes(unserialize($query->row['site_member_preferences'])) as $name => $value)
			{
				if(in_array($name, $this->from_member_prefs))
				{
					$settings[$LANG->line('site_member_preferences')][$name] = $value;
				}
			}
			foreach($REGX->array_stripslashes(unserialize($query->row['site_template_preferences'])) as $name => $value)
			{
				if(in_array($name, $this->from_template_prefs))
				{
					$settings[$LANG->line('site_template_preferences')][$name] = $value;
				}
			}
		}

		// get weblog preferences
		$query = $DB->query("SELECT weblog_id, blog_title, blog_url, comment_url, search_results_url FROM exp_weblogs WHERE site_id = '" . $PREFS->ini('site_id') . "' and blog_name != ''");
		if ($query->num_rows > 0)
		{
			foreach($query->result as $row)
			{
				// compound_id packs 3 data elements into one string: table::row::column.  example: exp_weblogs::1::blog_url.
				$compound_id = "exp_weblogs::" . $row['weblog_id'] . "::";
				$settings[$LANG->line('weblog_preferences') . " (" . $row['blog_title'] . ")"][$compound_id . 'blog_url'] = $row['blog_url'];
				$settings[$LANG->line('weblog_preferences') . " (" . $row['blog_title'] . ")"][$compound_id . 'comment_url'] = $row['comment_url'];
				$settings[$LANG->line('weblog_preferences') . " (" . $row['blog_title'] . ")"][$compound_id . 'search_results_url'] = $row['search_results_url'];
			}
		}

		// get upload preferences
		$query = $DB->query("SELECT id, name, server_path, url FROM exp_upload_prefs WHERE site_id = '" . $PREFS->ini('site_id') . "' and name != ''");
		if ($query->num_rows > 0)
		{
			foreach($query->result as $row)
			{
				// compound_id packs 3 data elements into one string: table::row::column.  example: exp_upload_prefs::1::server_path.
				$compound_id = "exp_upload_prefs::" . $row['id'] . "::";
				$settings[$LANG->line('upload_preferences') . " (" . $row['name'] . ")"][$compound_id . 'server_path'] = $row['server_path'];
				$settings[$LANG->line('upload_preferences') . " (" . $row['name'] . ")"][$compound_id . 'url'] = $row['url'];
			}
		}

		// get forum preferences
		$verify_query = $DB->query("SHOW TABLES LIKE 'exp_forum_boards'");
		if ($verify_query->num_rows > 0)
		{
			$query = $DB->query("SELECT board_id, board_label, board_forum_url, board_upload_path FROM exp_forum_boards WHERE board_site_id = '" . $PREFS->ini('site_id') . "' and board_name != ''");
			if ($query->num_rows > 0)
			{
				foreach($query->result as $row)
				{
					// compound_id packs 3 data elements into one string: table::row::column.  example: exp_forum_boards::1::board_upload_path.
					$compound_id = "exp_forum_boards::" . $row['board_id'] . "::";
					$settings[$LANG->line('forum_preferences') . " (" . $row['board_label'] . ")"][$compound_id . 'board_upload_path'] = $row['board_upload_path'];
					$settings[$LANG->line('forum_preferences') . " (" . $row['board_label'] . ")"][$compound_id . 'board_forum_url'] = $row['board_forum_url'];
				}
			}
		}

		// get gallery preferences
		$verify_query = $DB->query("SHOW TABLES LIKE 'exp_galleries'");
		if ($verify_query->num_rows > 0)
		{
			$query = $DB->query("SELECT gallery_full_name, gallery_id, gallery_url, gallery_upload_folder, gallery_upload_path, gallery_image_url, gallery_batch_folder, gallery_batch_path, gallery_batch_url, gallery_wm_image_path, gallery_wm_test_image_path, gallery_comment_url FROM exp_galleries WHERE gallery_full_name != ''");
			if ($query->num_rows > 0)
			{
				foreach($query->result as $row)
				{
					// compound_id packs 3 data elements into one string: table::row::column.  example: exp_gallery::1::gallery_url.
					$compound_id = "exp_galleries::" . $row['gallery_id'] . "::";
					$settings[$LANG->line('gallery_preferences') . " (" . $row['gallery_full_name'] . ")"][$compound_id . 'gallery_url'] = $row['gallery_url'];
					$settings[$LANG->line('gallery_preferences') . " (" . $row['gallery_full_name'] . ")"][$compound_id . 'gallery_upload_folder'] = $row['gallery_upload_folder'];
					$settings[$LANG->line('gallery_preferences') . " (" . $row['gallery_full_name'] . ")"][$compound_id . 'gallery_upload_path'] = $row['gallery_upload_path'];
					$settings[$LANG->line('gallery_preferences') . " (" . $row['gallery_full_name'] . ")"][$compound_id . 'gallery_image_url'] = $row['gallery_image_url'];
					$settings[$LANG->line('gallery_preferences') . " (" . $row['gallery_full_name'] . ")"][$compound_id . 'gallery_batch_folder'] = $row['gallery_batch_folder'];
					$settings[$LANG->line('gallery_preferences') . " (" . $row['gallery_full_name'] . ")"][$compound_id . 'gallery_batch_path'] = $row['gallery_batch_path'];
					$settings[$LANG->line('gallery_preferences') . " (" . $row['gallery_full_name'] . ")"][$compound_id . 'gallery_batch_url'] = $row['gallery_batch_url'];
					$settings[$LANG->line('gallery_preferences') . " (" . $row['gallery_full_name'] . ")"][$compound_id . 'gallery_wm_image_path'] = $row['gallery_wm_image_path'];
					$settings[$LANG->line('gallery_preferences') . " (" . $row['gallery_full_name'] . ")"][$compound_id . 'gallery_wm_test_image_path'] = $row['gallery_wm_test_image_path'];
					$settings[$LANG->line('gallery_preferences') . " (" . $row['gallery_full_name'] . ")"][$compound_id . 'gallery_comment_url'] = $row['gallery_comment_url'];
				}
			}
		}

		// get extension preferences
		foreach ($this->from_extension_prefs as $class => $prefs)
		{
			$query = $DB->query("SELECT ee.settings FROM exp_extensions ee WHERE ee.class = '" . $class . "' LIMIT 1");
			if ($query->num_rows > 0)
			{
				// foreach (unserialized) setting in this extension's settings
				foreach($REGX->array_stripslashes(unserialize($query->row['settings'])) as $name => $value)
				{
					// if it's one of the extension settings we said we cared about, add it to the form
					if(in_array($name, $prefs))
					{
						// compound_id packs 3 data elements into one string: table::row::column.  example: exp_extensions::FieldFrame::fieldtypes_url.
						$compound_id = "exp_extensions::" . $class . "::";
						$settings[$LANG->line('extension_preferences') . " (" . $class . ")"][$compound_id . $name] = $value;
					}
				}
			}
		}
		
		// get config.php, just for reference
		$config_file = getcwd() . "/config.php";
		if (is_readable($config_file))
		{
			foreach(file($config_file) as $line)
			{
				if(strpos($line, "=") !== FALSE)
				{
					list($name, $value) = explode("=", $line);
					$settings[$LANG->line('config_file')][$name] = $value;
				}
			}
		}
			
		// get path.php, just for reference
		$path_file = getcwd() . "/../path.php";
		if (is_readable($path_file))
		{
			foreach(file($path_file) as $line)
			{
				if(strpos($line, "=") !== FALSE)
				{
					list($name, $value) = explode("=", $line);
					$settings[$LANG->line('path_file')][$name] = $value;
				}
			}
		}

		// add a line for "Module installed in"
		$settings[$LANG->line('other')][$LANG->line('module_installed_in')] = dirname(__FILE__);

		return $settings;
	}
	// end 
		 
	// ------------------------------------------------------- 
	// display a form containing a table containing all the settings we get
	// ------------------------------------------------------- 
	function edit_config($msg='') 
	{ 
		global $DSP, $LANG, $DB, $FNS, $EXT, $SESS; 
			 
		//	html title and navigation crumblinks 
		$DSP->title = $LANG->line('deeploy_helper_module_name'); 
		$DSP->crumb = $DSP->anchor(BASE. 
														 	AMP.'C=modules'. 
														 	AMP.'M=deeploy_helper', 
														 	$LANG->line('deeploy_helper_module_name'));																		 
		$DSP->crumb .= $DSP->crumb_item($LANG->line('deeploy_helper_menu'));		 

		$r = '';

		//	page heading 
		$r .= $DSP->heading($LANG->line('deeploy_helper_menu'));		

		//	message, if any
		if ($msg != '') 
		{ 
			$r .= $DSP->qdiv('success', $msg).BR; 
		} 

		// donation text
		$r .= '<p>' . $LANG->line('pitch') . '</p>';

		// check for presence of jQuery (and include from Google if necessary) for "Quick Replace"
		if (isset($EXT->version_numbers['Cp_jquery']) === FALSE && empty($SESS->cache['scripts']['jquery']) === TRUE)
		{
			$r .= '<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.3.2/jquery.min.js"></script>';
			$SESS->cache['scripts']['jquery']['1.3.2'] = TRUE;
		}

		// javascript for "Quick Replace"
		$r .= <<<EOT
		<script type="text/javascript">
			jQuery(document).ready(function(){
				
				jQuery('form input').change(function(){
					jQuery(this).css('color', '#red');
				});

				jQuery('#quick_replace').click(function(){
					jQuery('form input').each(function(){
						var old = jQuery(this).val();
						jQuery(this).val(jQuery(this).val().replace(jQuery('#find').val(), jQuery('#replace').val()));
						if (jQuery(this).val() != old)
						{
							jQuery(this).change();
						}	
					});
				});
			});
		</script>
EOT;

		// create a table up top for "Quick Replace"
		$r .= 
		$DSP->table('tableBorder', '0', '0', '100%'). 
		$DSP->tr(). 
		$DSP->table_qcell('tableHeadingAlt', array($LANG->line('quick_replace'), '')). 
		$DSP->tr_c().
		$DSP->tr(). 
		$DSP->table_qcell('tableCellTwo', $LANG->line('current_document_root'), '30%','top'). 
		$DSP->table_qcell('tableCellTwo', $_SERVER["DOCUMENT_ROOT"],'70%','top'). 
		$DSP->tr_c(). 
		$DSP->tr(). 
		$DSP->table_qcell('tableCellOne', $LANG->line('find_text'), '30%','top'). 
		$DSP->table_qcell('tableCellOne', $DSP->input_text('find', '', 75, '1024', 'input', '100%', '', TRUE),'70%','top'). 
		$DSP->tr_c(). 
		$DSP->tr(). 
		$DSP->table_qcell('tableCellTwo', $LANG->line('replace_text'), '30%','top'). 
		$DSP->table_qcell('tableCellTwo', $DSP->input_text('replace', '', 75, '1024', 'input', '100%', '', TRUE),'70%','top'). 
		$DSP->tr_c(). 
		$DSP->tr(). 
		$DSP->table_qcell('tableCellOne','<button id="quick_replace">'.$LANG->line('quick_replace').'</button>','30%','top'). 
		$DSP->table_qcell('tableCellOne','','70%','top'). 
		$DSP->tr_c(). 
		$DSP->table_c(); 
			 
		//  declare main form
		$r .= $DSP->form('C=modules'.AMP.'M=deeploy_helper'.AMP.'P=save', 'target'); 
					
		//	main form table and table headers 
		$r .= $DSP->table('tableBorder', '0', '0', '100%'). 
					$DSP->tr(). 
					$DSP->table_qcell('tableHeadingAlt', 
														array( 
															$LANG->line('setting_name'), 
															$LANG->line('setting_value'), 
														) 
													). 
					$DSP->tr_c(); 

		$i = 0; 

		//	display rows of settings 
		foreach($this->get_config() as $section => $config)
		{

			$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo'; 
			$r .= $DSP->tr(); 
			$r .= $DSP->table_qcell($style . 'Bold',$section,'30%','top');  
			$r .= $DSP->table_qcell($style,'','70%','top');  
			$r .= $DSP->tr_c(); 

			foreach($config as $compound_id => $value) 
			{ 
				$style = ($i++ % 2) ? 'tableCellOne' : 'tableCellTwo'; 
									 
				$r .= $DSP->tr(); 

				// for now, config.php and path.php are read only.  
				if (($section == $LANG->line('config_file')) || ($section == $LANG->line('path_file')) || ($section == $LANG->line('other')))
				{
					$r .= $DSP->table_qcell($style, $compound_id,'30%','top');  
					$r .= $DSP->table_qcell($style, $value,'70%','top'); 
				}
				else
				{
					// compound_id is a compound field joined by '::'.  except when it's not, as in exp_sites.
					if (strpos($compound_id, '::') !== FALSE)
					{
						list($table, $id, $name) = explode('::', $compound_id);
						if ($LANG->line($name) != "")
						{
							$r .= $DSP->table_qcell($style, $LANG->line($name),'30%','top');  
						}
						else
						{
							$r .= $DSP->table_qcell($style, $name,'30%','top');  
						}
					}
					else
					{
						$r .= $DSP->table_qcell($style, $LANG->line($compound_id),'30%','top');  
					}
					
					// here's where the compound_id gets put into the name/id of the form elements, so we can unpack it later on submit
					$r .= $DSP->table_qcell($style, $DSP->input_text($compound_id, $value, 75, '1024', 'input', '100%', '', TRUE),'70%','top'); 
				}

				$r .= $DSP->tr_c(); 
			}	 
		}
			 
		//	close table and output to $dsp->body 
		$r .= $DSP->table_c(); 

		$r .= $DSP->qdiv('itemWrapper', BR.$DSP->input_submit($LANG->line('update_settings')));		 

		$r .= $DSP->form_c(); 
			 
		$DSP->body .= $r; 
			 
	} 
	// end 

	// ------------------------------------------------------- 
	// save settings submitted by the form.
	// ------------------------------------------------------- 
	function save_config() 
	{ 
		global $DB, $PREFS, $LANG, $REGX;

		// get serialized site preferences and member preferences and template preferences
		$query = $DB->query("SELECT site_system_preferences, site_member_preferences, site_template_preferences 
												FROM exp_sites WHERE site_id = '" . $PREFS->ini('site_id') . "'");
		if ($query->num_rows > 0)
		{
			$system_prefs = $REGX->array_stripslashes(unserialize($query->row['site_system_preferences']));
			$member_prefs = $REGX->array_stripslashes(unserialize($query->row['site_member_preferences']));
			$template_prefs = $REGX->array_stripslashes(unserialize($query->row['site_template_preferences']));
		}

		// get serialized extension preferences
		$classes = "'" . implode("','", array_keys($this->from_extension_prefs)) . "'";
		$query = $DB->query("SELECT class, settings FROM exp_extensions WHERE class in ($classes)");
		if ($query->num_rows > 0)
		{
			foreach ($query->result as $row)
			{
				$extension_prefs[$row['class']] = $REGX->array_stripslashes(unserialize($row['settings']));
			}
		}

		$updates = "";
		$changed = FALSE;
		$extension_changed = array();

		if (isset($_POST)) {
			foreach ($_POST as $compound_id => $value)
			{
				// if our compound_id is really a compound field, then this is a non-serialized table OR a serialized extension pref
				if (strpos($compound_id, "::") !== FALSE)
				{
					list($table, $id, $name) = explode("::", $compound_id);
					$table = $DB->escape_str($REGX->xss_clean($table));
					$id = $DB->escape_str($REGX->xss_clean($id));
					$name = $DB->escape_str($REGX->xss_clean($name));
					$value = $DB->escape_str($REGX->xss_clean($value));
	
					if ($table == "exp_weblogs")
					{
						$updates[] = "UPDATE $table SET $name = '$value' WHERE weblog_id = $id AND site_id = " . $PREFS->ini('site_id');
					}
					if ($table == "exp_upload_prefs")
					{
						$updates[] = "UPDATE $table SET $name = '$value' WHERE id = $id AND site_id = " . $PREFS->ini('site_id');
					}
					if ($table == "exp_forum_boards")
					{
						$updates[] = "UPDATE $table SET $name = '$value' WHERE board_id = $id AND board_site_id = " . $PREFS->ini('site_id');
					}
					if ($table == "exp_galleries")
					{
						$updates[] = "UPDATE $table SET $name = '$value' WHERE gallery_id = $id";
					}
					if ($table == "exp_extensions")
					{
						// if the extension class name and the specific pref are in the extension prefs array
						if (isset($this->from_extension_prefs[$id]) && in_array($name, $this->from_extension_prefs[$id]))
						{
							// the extension pref $name for this class ($id) gets $value (we'll process it below)
							$extension_prefs[$id][$name] = $value;		
							$extension_changed[] = $id;
						}
					}
				}
	
				// if our compound_id is not really a compound field, then this is a serialized system preference
				// if it's a preference we care about, and it's different than it was, we'll process it below
				elseif (in_array($compound_id, $this->from_system_prefs) && $system_prefs[$compound_id] != $value)
				{
					$system_prefs[$compound_id] = $value;
					$changed = TRUE;
				}
				elseif (in_array($compound_id, $this->from_member_prefs) && $member_prefs[$compound_id] != $value)
				{
					$member_prefs[$compound_id] = $value;
					$changed = TRUE;
				}
				elseif (in_array($compound_id, $this->from_template_prefs) && $template_prefs[$compound_id] != $value)
				{
					$template_prefs[$compound_id] = $value;
					$changed = TRUE;
				}
	
			}
		}
			
		// process system updates
		if ($changed)
		{
			$system_prefs = addslashes(serialize($REGX->xss_clean($system_prefs)));
			$member_prefs = addslashes(serialize($REGX->xss_clean($member_prefs)));
			$template_prefs = addslashes(serialize($REGX->xss_clean($template_prefs)));

			$updates[] = "UPDATE exp_sites set 
				site_system_preferences = '$system_prefs', 
				site_member_preferences = '$member_prefs',
				site_template_preferences = '$template_prefs'
				WHERE site_id = " . $PREFS->ini('site_id');
		}

		// process extension updates
		foreach(array_unique($extension_changed) as $class)
		{
			// serialize the prefs
			$extension_prefs[$class] = addslashes(serialize($REGX->xss_clean($extension_prefs[$class])));
			// add to update array
			$updates[] = "UPDATE exp_extensions SET settings = '" . $extension_prefs[$class] . "' WHERE class = '$class'";
		}

		//print_r($updates);
		foreach ($updates as $sql)
		{
			$DB->query($sql);
		}
		
		$this->edit_config($LANG->line('settings_saved'));
	}
		 
	// ---------------------------------------- 
	//	Module installer 
	// ---------------------------------------- 
	function deeploy_helper_module_install() 
	{ 
		global $DB;				 
			 
		$sql[] = "INSERT INTO exp_modules (module_id, module_name, module_version, has_cp_backend) 
							VALUES ('', 'Deeploy_helper', '$this->version', 'y')"; 
																				
		foreach ($sql as $query) 
		{ 
			$DB->query($query); 
		} 
		return true; 
	} 
	// END 

	// ---------------------------------------- 
	//	Module updater 
	// ---------------------------------------- 
	function _update_module($old_ver)
	{
		global $DB;

		if ($old_ver < '1.1.0')
		{
			// we don't have any extra steps.
		}

		$DB->query("UPDATE exp_modules SET module_version = '{$this->version}' WHERE module_name = 'Deeploy_helper'");
	}
		 
		 
	// ---------------------------------------- 
	//	Module de-installer 
	// ---------------------------------------- 
	function deeploy_helper_module_deinstall() 
	{ 
		global $DB;		 

		$query = $DB->query("SELECT module_id FROM exp_modules WHERE module_name = 'Deeploy_helper'"); 
						 
		$sql[] = "DELETE FROM exp_module_member_groups WHERE module_id = '".$query->row['module_id']."'";			 
							 
		$sql[] = "DELETE FROM exp_modules WHERE module_name = 'Deeploy_helper'"; 
							 
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Deeploy_helper'"; 
							 
		$sql[] = "DELETE FROM exp_actions WHERE class = 'Deeploy_helper_CP'"; 
							 
		foreach ($sql as $query) 
		{ 
			$DB->query($query); 
		} 

		return true; 
	} 
	// END 

} 
// END CLASS 
