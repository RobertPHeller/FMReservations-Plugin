<?php
/* -*- php -*- ****************************************************************
 *
 *  System        : 
 *  Module        : 
 *  Object Name   : $RCSfile$
 *  Revision      : $Revision$
 *  Date          : $Date$
 *  Author        : $Author$
 *  Created By    : Robert Heller
 *  Created       : 2026-08-29 03:10:02
 *  Last Modified : <260829.0350>
 *
 *  Description	
 *
 *  Notes
 *
 *  History
 *	
 ****************************************************************************
 *
 *    Copyright (C) 2026  Robert Heller D/B/A Deepwoods Software
 *			51 Locke Hill Road
 *			Wendell, MA 01379-9728
 *
 *    This program is free software; you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation; either version 2 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program; if not, write to the Free Software
 *    Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * 
 *
 ****************************************************************************/

/* Load our constants */
require_once(dirname(__FILE__) . "/FMReservations_Constants.php");

/* Load Database code */
require_once(FMRESERVATIONS_INCLUDES_DIR. "/FMReservations_Database.php");

/*************************** LOAD THE BASE CLASS *******************************
  *******************************************************************************
  * The WP_List_Table class isn't automatically available to plugins, so we need
  * to check if it's available and load it if necessary.
  */
if(!class_exists('WP_List_Table')){
  require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
  * Class to display and manage the Wendell Full Moon show seat reservations.
  */

class FMReservations_List_Table extends WP_List_Table {
  var $viewmode = 'add';
  var $viewid   = 0;
  var $viewitem;
  
  function __construct() {
    //Set parent defaults
    parent::__construct( array ('singular' => 'Reservation',   // One thing
                                'plural'   => 'Reservations',  // Multiple things
                                'ajax'     => false     // AJAX?
                                ) );
  }
  /* Default column (nothing really here, since every displayed column gets 
   * its own function).
   */
  function column_default($item, $column_name) {
    return apply_filters( 'manage_items_custom_column','',
                                $column_name,$item->id);
  }
  function column_cb ($item) {
    return '<input type="checkbox" name="checked_item[]" value="'.$item->id.'" />';
  }
  function column_thedate ($item) {
    return mysql2date('F j, Y',$item->thedate);
  }
  unction column_name ($item) {
    // Build row actions 
        $actions = array(
      'edit' => '<a href="'.add_query_arg(array('page' => 'fm-add-reservation',
						'mode' => 'edit',
						'id' => $item->id),
					  admin_url('admin.php') ).'">'.
			'Edit'."</a>",
      'view' => '<a href="'.add_query_arg(array('page' => 'fm-add-reservation',
						'mode' => 'view',
						'id' => $item->id),
					  admin_url('admin.php') ).'">'.
			'View'."</a>",
      'delete' => '<a href="'.add_query_arg(array('page' => $_REQUEST['page'],
						  'action' => 'delete',
						  'id' => $item->id),
					    admin_url('admin.php') ).'">'.
			'Delete'."</a>"
        );
    return stripslashes($item->name).$this->row_actions($actions);
  }
  function column_seatid ($item) {
    return $item->seatid;
  }
  function column_seatcount ($item) {
    return $item->seatcount;
  }
  function get_columns() { 
    return array ('cb' => '<input type="checkbox" />',
                  'thedate' => 'Date',
                  'seatid' => 'Reservation ID',
                  'seatcount' => 'Seats'
                  );
  }
  function get_sortable_columns() {return array();} 
  function get_bulk_actions() {
    return array('delete' => 'Delete');
  }
  function current_action() {
    if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] )
        return $_REQUEST['action'];

    if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] )
        return $_REQUEST['action2'];
  }
  function process_bulk_action() {
    $action = $this->current_action();
    switch ($action) {
      case 'delete':
	if ( isset($_REQUEST['checked']) && !empty($_REQUEST['checked'])) {
	  foreach ($_REQUEST['checked_item'] as $theitem) {
	    FMReservations_Database::DeleteReservation($theitem);
	  }
	} else if ( isset($_REQUEST['id']) ) {
	  FMReservations_Database::DeleteReservation($_REQUEST['id']);
	}
	break;
    }
  }
  function get_column_info() {
    if ( isset($this->_column_headers) ) {return $this->_column_headers;}
    $columns = $this->get_columns( );
    $hidden = array();
    $sortable = $this->get_sortable_columns();

    $this->_column_headers = array( $columns, $hidden, $sortable );
    return $this->_column_headers;
  }
  function process_filters_and_bulk_action() {
    $this->process_bulk_action();
  }
  function prepare_items() {
    // Check permissions
    $this->check_permissions();
    // Get per page (screen option)
    $per_page = $this->per_page_screen_option->get();
    // Deal with columns
    $columns = $this->get_columns();    // All of our columns
    $hidden  = array();         // Hidden columns [none]
    $sortable = $this->get_sortable_columns(); // Sortable columns
    $this->_column_headers = array($columns,$hidden,$sortable); // Set up columns
    // Process filters and bulk action, if any
    $this->process_filters_and_bulk_action();
    $this->items = FMReservations_Database::Get_Future_Reservations();
    $total_items = count($this->items);
    $this->set_pagination_args( array (
                'total_items' => $total_items,
                'per_page'    => $total_items,
                'total_pages' => ceil($total_items/$per_page)  ));
  }
  function add_item_icon() {
    switch ($this->viewmode) {
      case 'add': return 'icon-fmr-add-db';
      case 'edit': return 'icon-fmr-edit-db';
      case 'view': return 'icon-fmr-view-db';
    }
  }
  function add_item_h2() {
    switch ($this->viewmode) {
      case 'add': return 'Add Show';
      case 'edit': return 'Edit Show';
      case 'view': return 'View Show';
    }
  }
  function display_one_item_form($returnURL) {
    if ($this->viewmode != 'add') {
      ?><input type="hidden" name="id" value="<?php echo $this->viewid; ?>" /><?php
    }
    ?><table class="form-table">
    <tr valign="top">
      <th scope="row"><label for="fm-thedate" style="width:20%;">Date:</label></th>
      <td><?php
    $dateisreadonly = true;
    if ($this->viewmode == 'add') {$dateisreadonly = false;}
      if ($this->viewmode == 'edit' &&
	  FMReservations_Database::NoOpenMics($this->viewitem->thedate)) {
	$dateisreadonly = false;
      }
      if ($dateisreadonly) {
	?><input type="hidden" name="thedate" value="<?php echo $this->viewitem->thedate; ?>" />
	  <span id="fm-thedate" style="width:75%;"><?php echo mysql2date('F j, Y',$this->viewitem->thedate); ?></span><?php
      } else {
	?><input id="fm-thedate" 
		 value="<?php echo $this->viewitem->thedate; ?>" 
		 name="thedate" style="width:75%;" /><?php
      } ?></td></tr>
	  <tr valign="top">
	    <th scope="row"><label for="fm-artist" style="width:20%;">Artist:</label></th>
	    <td><input id="fm-artist" 
			value="<?php echo stripslashes($this->viewitem->artist); ?>"
			name="artist"
			style="width:75%;" <?php if ($this->viewmode == 'view') echo 'readonly="readonly"'; ?> /></td></tr>
	  <tr valign="top">
	    <th scope="row"><label for="fm-artistinfo" style="width:20%;">Artist Info:</label></th>
	    <td><textarea id="fm-artistinfo" name="artistinfo"
			cols="50" rows="5" 
	                style="width:75%;" <?php if ($this->viewmode == 'view') echo 'readonly="readonly"'; ?>><?php echo stripslashes($this->viewitem->artistinfo); ?></textarea></td></tr>
	  <tr valign="top">
	    <th scope="row"><label for="fm-artisturl" style="width:20%;">Artist URL:</label></th>
	    <td><input id="fm-artisturl" 
			value="<?php echo stripslashes($this->viewitem->artisturl); ?>"
			name="artisturl"
			style="width:75%;" <?php if ($this->viewmode == 'view') echo 'readonly="readonly"'; ?> /></td></tr>
	  <tr valign="top">
	    <th scope="row"><label for="fm-beneficiary" style="width:20%;">Beneficiary:</label></th>
	    <td><input id="fm-beneficiary" 
			value="<?php echo stripslashes($this->viewitem->beneficiary); ?>"
			name="beneficiary"
			style="width:75%;" <?php if ($this->viewmode == 'view') echo 'readonly="readonly"'; ?> /></td></tr>
	  <tr valign="top">
	    <th scope="row"><label for="fm-beneficiaryinfo" style="width:20%;">Beneficiary Info:</label></th>
	    <td><textarea id="fm-beneficiaryinfo" name="beneficiaryinfo"
			cols="50" rows="5" 
	                style="width:75%;" <?php if ($this->viewmode == 'view') echo 'readonly="readonly"'; ?>><?php echo stripslashes($this->viewitem->beneficiaryinfo); ?></textarea></td></tr>
	  <tr valign="top">
	    <th scope="row"><label for="fm-beneficiaryurl" style="width:20%;">Beneficiary URL:</label></th>
	    <td><input id="fm-beneficiaryurl" 
			value="<?php echo stripslashes($this->viewitem->beneficiaryurl); ?>"
			name="beneficiaryurl"
			style="width:75%;" <?php if ($this->viewmode == 'view') echo 'readonly="readonly"'; ?> /></td></tr>
	  </table>
	  <p>
		<?php switch ($this->viewmode) {
			case 'add':
				?><input type="submit" name="addshow" class="button-primary" value="Add Show"><?php
				break;
			case 'edit':
				?><input type="submit" name="updateshow" class="button-primary" value="Update Show"><?php
				break;
		      } ?>
	        <a href="<?php echo $returnURL; ?>" class="button-primary">Return</a>
	  </p><?php
  }
  function check_permissions() {
    if (!current_user_can('manage_schedule'))
    {
      wp_die( __('You do not have sufficient permissions to access this page. (FMSchedule_List_Table)') );
    }
  }
  function prepare_one_item() {
    $this->check_permissions();
    if ( isset ($_REQUEST['filter_top'] ) && isset( $_REQUEST['season_top'] ) ) {
      $this->season = $_REQUEST['season_top'];
    } else if ( isset ($_REQUEST['filter_bottom'] ) && isset( $_REQUEST['season_bottom'] ) ) {
      $this->season = $_REQUEST['season_bottom'];
    } else {
      $this->season = isset( $_REQUEST['season'] ) ? $_REQUEST['season'] : FMReservations_Database::ThisSeason();
    }
    $message = '';
    if ( isset ($_REQUEST['addshow']) ) {
      $message = $this->checkiteminform();
      $item = $this->getitemfromform();
      if ($message == '') {
	$newid = FMReservations_Database::InsertNewShow($item);
	$message = '<p>'.$item->artist.' on '.
			 $item->thedate.' Inserted with id '.
			 $newid.'.</p>';
	$this->viewmode = 'edit';
	$this->viewid   = $newid;
	$this->viewitem = $item;
      } else {
	$this->viewmode = 'add';
	$this->viewid   = 0;
	$this->viewitem = $item;
      }
    } else if ( isset ($_REQUEST['updateshow']) && isset ($_REQUEST['id']) ) {
      $message = $this->checkiteminform();
      $item = $this->getitemfromform();
      $item->id = $_REQUEST['id'];
      if ($message == '') {
	FMReservations_Database::UpdateShow($item);
	$message = '<p>'.$item->artist.' on '.
			$item->thedate.' updated.</p>';
      }
      $this->viewmode = 'edit';
      $this->viewid   = $item->id;
      $this->viewitem = $item;
    } else {
      $this->viewmode = isset ($_REQUEST['mode']) ? $_REQUEST['mode'] : 'add';
      $this->viewid   = isset ($_REQUEST['id']) ? $_REQUEST['id'] : 0;
      if ($this->viewmode == 'add') {$this->viewid = 0;}
      if ($this->viewid == 0) {$this->viewmode = 'add';}
      if ($this->viewid != 0) {
	$this->viewitem = FMReservations_Database::Get_OneShow($this->viewid);
      } else {
        $this->viewitem = FMReservations_Database::Get_BlankShow();
      }
    }
    return $message;		
  }
  function checkiteminform() {
    $result = '';
    if ( empty($_REQUEST['artist']) ) {
      $result .= '<p>Artist missing.</p>';
    }
    if ( empty($_REQUEST['beneficiary']) ) {
      $result .= '<p>Beneficiary missing.</p>';
    }
    if ( empty($_REQUEST['thedate']) ) {
      $result .= '<p>Date missing.</p>';
    } else {
      $result .= FMReservations_Database::CheckDate('Date', $_REQUEST['thedate']);
    }
    return $result;
  }
  function getitemfromform() {
    $itemary = array();
    foreach (array('thedate','artist','artistinfo','artisturl',
		   'beneficiary','beneficiaryinfo','beneficiaryurl') 
			as $field) {
      $itemary[$field] = $_REQUEST[$field];
    }
    $itemary['thedate'] = FMReservations_Database::NormalizeDate($itemary['thedate']);
    $itemary['artisturl'] = FMReservations_Database::NormalizeURL($itemary['artisturl']);
    $itemary['beneficiaryurl'] = FMReservations_Database::NormalizeURL($itemary['beneficiaryurl']);
    return (object) $itemary;
  }
}
  
