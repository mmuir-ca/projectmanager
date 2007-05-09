<?php
/**************************************************************************\
* eGroupWare - ProjectManager: Admin-, Preferences- and SideboxMenu-Hooks  *
* http://www.eGroupWare.org                                                *
* Written and (c) 2005 by Ralf Becker <RalfBecker@outdoor-training.de>     *
* -------------------------------------------------------                  *
*  This program is free software; you can redistribute it and/or modify it *
*  under the terms of the GNU General Public License as published by the   *
*  Free Software Foundation; either version 2 of the License, or (at your  *
*  option) any later version.                                              *
\**************************************************************************/

/* $Id$ */

class pm_admin_prefs_sidebox_hooks
{
	var $public_functions = array(
//		'check_set_default_prefs' => true,
	);
	var $weekdays = array(
		1 => 'monday',
		2 => 'tuesday',
		3 => 'wednesday',
		4 => 'thursday',
		5 => 'friday',
		6 => 'saturday',
		0 => 'sunday',
	);
	var $config = array();

	function pm_admin_prefs_sidebox_hooks()
	{
		$config =& CreateObject('phpgwapi.config','projectmanager');
		$config->read_repository();
		$this->config =& $config->config_data;
		unset($config);
	}

	/**
	 * hooks to build projectmanager's sidebox-menu plus the admin and preferences sections
	 *
	 * @param string/array $args hook args
	 */
	function all_hooks($args)
	{
		$appname = 'projectmanager';
		$location = is_array($args) ? $args['location'] : $args;
		//echo "<p>ranking_admin_prefs_sidebox_hooks::all_hooks(".print_r($args,True).") appname='$appname', location='$location'</p>\n";

		if ($location == 'sidebox_menu')
		{
			// project-dropdown in sidebox menu
			if (!is_object($GLOBALS['egw']->html))
			{
				$GLOBALS['egw']->html =& CreateObject('phpgwapi.html');
			}
			if (!is_object($GLOBALS['boprojectmanager']))
			{
				// dont assign it to $GLOBALS['boprojectmanager'], as the constructor does it!!!
				CreateObject('projectmanager.uiprojectmanager');
			}
			if (isset($_REQUEST['pm_id']))
			{
				$GLOBALS['egw']->session->appsession('pm_id','projectmanager',$pm_id = (int) $_REQUEST['pm_id']);
			}
			else
			{
				$pm_id = (int) $GLOBALS['egw']->session->appsession('pm_id','projectmanager');
			}
			$file = array(
				'Projectlist' => $GLOBALS['egw']->link('/index.php',array(
					'menuaction' => 'projectmanager.uiprojectmanager.index' )),
				array(
					'text' => 'Elementlist',
					'link' => $pm_id ? $GLOBALS['egw']->link('/index.php',array(
						'menuaction' => 'projectmanager.uiprojectelements.index', 
					)) : False,
				),
				array(
					'text' => 'Ganttchart',
					'link' => $pm_id ? $GLOBALS['egw']->link('/index.php',array(
						'menuaction' => 'projectmanager.ganttchart.show',
					)) : False,
				),
			);
			// show pricelist menuitem only if we use pricelists
			if (!$this->config['accounting_types'] || in_array('pricelist',explode(',',$this->config['accounting_types'])))
			{
				// menuitem links to project-spezific priclist only if user has rights and it is used
				// to not always instanciate the priclist class, this code dublicats bopricelist::check_acl(EGW_ACL_READ),
				// specialy the always existing READ right for the general pricelist!!!
				$file['Pricelist'] = $GLOBALS['egw']->link('/index.php',array(
					'menuaction' => 'projectmanager.uipricelist.index',
					'pm_id' => $pm_id && $GLOBALS['boprojectmanager']->check_acl(EGW_ACL_BUDGET,$pm_id) &&
						 $GLOBALS['boprojectmanager']->data['pm_accounting_type'] == 'pricelist' ? $pm_id : 0,
				));
			}

			// include the filter of the projectlist into the projectlist, eg. if you watch the list of templates, include them in the tree
			$filter = array('pm_status' => 'active');
			$list_filter = $GLOBALS['egw']->session->appsession('project_list','projectmanager');
			if ($_GET['menuaction'] == 'projectmanager.uiprojectmanager.index' && isset($_POST['exec']['nm']['filter2']))
			{
				//echo "<p align=right>set pm_status={$_POST['exec']['nm']['filter2']}</p>\n";
				$list_filter['pm_status'] = $_POST['exec']['nm']['filter2'];	// necessary as uiprojectmanager::get_rows is not yet executed
			}
			if(in_array($list_filter['filter2'],array('nonactive','archive','template')))
			{
				$filter['pm_status'] = array('active',$list_filter['filter2']);
			}
			switch($_GET['menuaction'])
			{
				case 'projectmanager.ganttchart.show':
				case 'projectmanager.uipricelist.index':
					$selbox_action = $_GET['menuaction'];
					break;
				default:
					$selbox_action = 'projectmanager.uiprojectelements.index';
					break;
			}
			$select_link = $GLOBALS['egw']->link('/index.php',array('menuaction' => $selbox_action)).'&pm_id=';
			
			// show the project-selection as tree or -selectbox
			// $_POST['user']['show_projectselection'] is used to give the user immediate feedback, if he changes the prefs
			$type = isset($_POST['user']['show_projectselection']) ? $_POST['user']['show_projectselection'] :
				$GLOBALS['egw_info']['user']['preferences']['projectmanager']['show_projectselection'];
			if (substr($type,-5) == 'title')
			{
				$label = 'pm_title';
				$title = 'pm_number';
			}
			else
			{
				$label = 'pm_number';
				$title = 'pm_title';
			}
			if (substr($type,0,9) == 'selectbox')
			{
				$projectlist =& $this->_project_selectbox($pm_id,$filter,$select_link,$label,$title);
			}
			else
			{
				$projectlist =& $this->_project_tree($pm_id,$filter,$select_link,$label,$title);
			}
			if ($projectlist) $file[] =& $projectlist;

			display_sidebox($appname,$GLOBALS['egw_info']['apps'][$appname]['title'].' '.lang('Menu'),$file);
		}

		if ($GLOBALS['egw_info']['user']['apps']['preferences'] && $location != 'admin')
		{
			$file = array(
				'Preferences'     => $GLOBALS['egw']->link('/index.php','menuaction=preferences.uisettings.index&appname='.$appname),
				'Grant Access'    => $GLOBALS['egw']->link('/index.php','menuaction=preferences.uiaclprefs.index&acl_app='.$appname),
				'Edit Categories' => $GLOBALS['egw']->link('/index.php','menuaction=preferences.uicategories.index&cats_app=' . $appname . '&cats_level=True&global_cats=True')
			);
			if ($location == 'preferences')
			{
				display_section($appname,$file);
			}
			else
			{
				display_sidebox($appname,lang('Preferences'),$file);
			}
		}

		if ($GLOBALS['egw_info']['user']['apps']['admin'] && $location != 'preferences')
		{
			$file = Array(
				'Site configuration' => $GLOBALS['egw']->link('/index.php','menuaction=projectmanager.admin.config'),
				'Custom fields' => $GLOBALS['egw']->link('/index.php','menuaction=admin.customfields.edit&appname=projectmanager'),
				'Global Categories'  => $GLOBALS['egw']->link('/index.php',array(
					'menuaction' => 'admin.uicategories.index',
					'appname'    => $appname,
					'global_cats'=> True)),
			);
			if ($location == 'admin')
			{
				display_section($appname,$file);
			}
			else
			{
				display_sidebox($appname,lang('Admin'),$file);
			}
		}
	}
	
	/**
	 * Show the project-selection as tree
	 *
	 * @param int $pm_id current active project
	 * @param array $filter filter for the project-list
	 * @param string $select_link link to load the elementslist of an appended project (pm_id)
	 * @param string $label column to use as label
	 * @param string $title column to use as title (tooltip)
	 * @return array suitable for the sidebox-menu
	 */
	function &_project_tree($pm_id,$filter,$select_link,$label,$title)
	{
		$selected_project = false;
		$projects = array();
		foreach($GLOBALS['boprojectmanager']->get_project_tree($filter) as $project)
		{
			$projects[$project['path']] = array(
				'label' => $project[$label],
				'title' => $project[$title],
			);
			if (!$selected_project && $pm_id == $project['pm_id']) $selected_project = $project['path'];
		}
		if ($_GET['menuaction'] == 'projectmanager.uipricelist.index')
		{
			$projects['general'] = array(
				'label' => lang('General pricelist'),
				'image' => 'kfm_home.png',
			);
			if (!$pm_id) $selected_project = 'general';	
		}
		if (!$projects)	// show project-tree only if it's not empty
		{
			return null;
		}
		$tree = $GLOBALS['egw']->html->tree($projects,$selected_project,false,'load_project');
		// hack for stupid ie (cant set it as a class!)
		if ($GLOBALS['egw']->html->user_agent == 'msie') $tree = str_replace('id="foldertree"','id="foldertree" style="overflow: auto; width: 198px;"',$tree);
		
		return array(
			'text' => "<script>function load_project(_nodeId) { location.href='$select_link'+_nodeId.substr(_nodeId.lastIndexOf('/')+1,99); }</script>\n".$tree,
			'no_lang' => True,
			'link' => False,
			'icon' => False,
		);
	}
	
	/**
	 * Show the project-selection as selectbox
	 *
	 * @param int $pm_id current active project
	 * @param array $filter filter for the project-list
	 * @param string $select_link link to load the elementslist of an appended project (pm_id)
	 * @param string $label column to use as label
	 * @param string $title column to use as title (tooltip)
	 * @return array suitable for the sidebox-menu
	 */
	function &_project_selectbox($pm_id,$filter,$select_link,$label,$title)
	{
		$projects = array();
		foreach((array)$GLOBALS['boprojectmanager']->search(array(
			'pm_status' => 'active',
			'pm_id'     => $pm_id,        // active or the current one
		),$GLOBALS['boprojectmanager']->table_name.'.pm_id AS pm_id,pm_number,pm_title','pm_number','','',False,'OR') as $project)
		{
			$projects[$project['pm_id']] = $project[$label].($label == 'pm_number' ? ': '.$project[$title] : ' ('.$project[$title].')');
		}
		if ($_GET['menuaction'] == 'projectmanager.uipricelist.index')
		{
			$projects[0] = lang('General pricelist');
		}
		elseif (!$pm_id) 
		{
			$projects[0] = lang('Select a project');
		}
		return array(
			'text' => $GLOBALS['egw']->html->select('pm_id',$pm_id,$projects,true,' style="width: 100%;"'.
				' onchange="location.href=\''.$select_link.'\'+this.value;" title="'.$GLOBALS['egw']->html->htmlspecialchars(
				$pm_id && isset($projects[$pm_id]) ? $projects[$pm_id] : lang('Select a project')).'"'),
			'no_lang' => True,
			'link' => False
		);
	}
	
	/**
	 * populates $GLOBALS['settings'] for the preferences
	 */
	function settings()
	{
		$this->check_set_default_prefs();
		
		$start = array();
		for($i = 0; $i < 24*60; $i += 30)
		{
			if ($GLOBALS['egw_info']['user']['preferences']['common']['timeformat'] == 12)
			{
				if (!($hour = ($i / 60) % 12)) 
				{
					$hour = 12;
				}
				$start[$i] = sprintf('%01d:%02d %s',$hour,$i % 60,$i < 12*60 ? 'am' : 'pm');
			}
			else
			{
				$start[$i] = sprintf('%01d:%02d',$i/60,$i % 60);
			}
		}
		$duration = array(0 => lang('not working'));
		for($i = 30; $i <= 24*60; $i += 30)
		{
			$duration[$i] = sprintf('%3.1lf',$i / 60.0).' '.lang('hours');
		}
		foreach($this->weekdays as $day => $label)
		{
			$GLOBALS['settings']['duration_'.$day] = array(
				'type'   => 'select',
				'label'  => lang('Working duration on %1',lang($label)),
				'run_lang' => -1,
				'name'   => 'duration_'.$day,
				'values' => $duration,
				'help'   => 'How long do you work on the given day.',
				'xmlrpc' => True,
				'admin'  => !$this->config['allow_change_workingtimes'],
			);
			$GLOBALS['settings']['start_'.$day] = array(
				'type'   => 'select',
				'label'  => lang('Start working on %1',lang($label)),
				'run_lang' => -1,
				'name'   => 'start_'.$day,
				'values' => $start,
				'help'   => 'At which time do you start working on the given day.',
				'xmlrpc' => True,
				'admin'  => !$this->config['allow_change_workingtimes'],
			);
		}
		$GLOBALS['settings']['show_custom_app_icons'] = array(
			'type'   => 'check',
			'label'  => 'Show status icons of the datasources',
			'name'   => 'show_custom_app_icons',
			'help'   => 'Should Projectmanager display the status icons of the datasource (eg. InfoLog) or just a progressbar with the numerical status (faster).',
			'xmlrpc' => True,
			'admin'  => False,
		);
		$GLOBALS['settings']['show_projectselection'] = array(
			'type'   => 'select',
			'label'  => 'Show the project selection as',
			'name'   => 'show_projectselection',
			'values' => array(
				'tree_with_number'      => lang('Tree with %1',lang('Project ID')),
				'tree_with_title'       => lang('Tree with %1',lang('Title')),
				'selectbox_with_number' => lang('Selectbox with %1',lang('Project ID').': '.lang('Title')),
				'selectbox_with_title'  => lang('Selectbox with %1',lang('Title').' ('.lang('Project ID').')'),
			),
			'help'   => 'How should the project selection in the menu be displayed: A tree gives a better overview, a selectbox might perform better.',
			'xmlrpc' => True,
			'admin'  => False,
		);
		return true;	// otherwise prefs say it cant find the file ;-)
	}
	
	/**
	 * Check if reasonable default preferences are set and set them if not
	 *
	 * It sets a flag in the app-session-data to be called only once per session
	 */
	function check_set_default_prefs()
	{
		if ($GLOBALS['egw']->session->appsession('default_prefs_set','projectmanager'))
		{
			return;
		}
		$GLOBALS['egw']->session->appsession('default_prefs_set','projectmanager','set');

		$default_prefs =& $GLOBALS['egw']->preferences->default['projectmanager'];

		$defaults = array(
			'start_1' => 9*60,
			'duration_1' => 8*60,
			'start_2' => 9*60,
			'duration_2' => 8*60,
			'start_3' => 9*60,
			'duration_3' => 8*60,
			'start_4' => 9*60,
			'duration_4' => 8*60,
			'start_5' => 9*60,
			'duration_5' => 6*60,
			'duration_6' => 0,
			'duration_0' => 0,
			'show_custom_app_icons' => '0',
			'show_tree'  => 'pm_number',
		);
		foreach($defaults as $var => $default)
		{
			if (!isset($default_prefs[$var]) || (string)$default_prefs[$var] === '')
			{
				$GLOBALS['egw']->preferences->add('projectmanager',$var,$default,'default');
				$need_save = True;
			}
		}
		if ($need_save)
		{
			$GLOBALS['egw']->preferences->save_repository(False,'default');
		}
	}
}