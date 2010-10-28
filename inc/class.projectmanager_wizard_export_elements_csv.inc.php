<?php
/**
 * eGroupWare - Wizard for Project CSV export
 *
 * @license http://opensource.org/licenses/gpl-license.php GPL - GNU General Public License
 * @package projectmanager
 * @subpackage importexport
 * @link http://www.egroupware.org
 * @author Nathan Gray
 * @version $Id$
 */

class projectmanager_wizard_export_elements_csv extends importexport_wizard_basic_export_csv
{
	public function __construct() {
		parent::__construct();
		// Field mapping
		$this->export_fields = array(
			'pe_id'		=> lang('Element ID'),
			'pe_app'		=> lang('Application'),
			'pe_title'		=> lang('Title'),
			'pe_completion'	=> lang('Completion'),
			'pe_planned_time'	=> lang('Planned time'),
			'pe_replanned_time'=> lang('Re-planned time'),
			'pe_used_time'	=> lang('Time'),
			'pe_planned_budget'=> lang('Planned budget'),
			'pe_used_budget'	=> lang('Used budget'),
			'pe_planned_start'	=> lang('Planned start'),
			'pe_real_start'	=> lang('Startdate'),
			'pe_planned_end'	=> lang('Planned end'),
			'pe_real_end'	=> lang('Enddate'),
			'pe_overwrite'	=> lang('overwrite'),
			'pe_synced'	=> lang('synced'),
			'pe_modified'	=> lang('Last modified'),
			'pe_modifier'	=> lang('Modified by'),
			'pe_status'	=> lang('Status'),
			'pe_unitprice'	=> lang('Unitprice'),
			'cat_id'	=> lang('Category'),
			'pe_share'		=> lang('Share'),
			'pe_health'	=> lang('Health'),
			'pe_resources'	=> lang('Resources'),
			'pe_details'	=> lang('Details'),
			'pe_planned_quantity'=> lang('Planned quantity'),
			'pe_used_quantity'	=> lang('Used quantity')
		);
	}
}