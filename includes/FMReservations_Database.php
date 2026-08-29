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
 *  Created       : 2026-08-28 16:01:04
 *  Last Modified : <260829.0340>
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

global $wpdb;
define('FMRESERVATIONS_TABLE',$wpdb->prefix . "FMReservations");
//file_put_contents("php://stderr","*** FMRESERVATIONS_TABLE ".FMRESERVATIONS_TABLE."\n\n\n\n"); 

class FMReservations_Database {
  public static function make_reservations_table() {
    global $wpdb;
    //file_put_contents("php://stderr","*** FMReservations_Database::make_reservations_table");
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    $sql = "CREATE TABLE " . FMRESERVATIONS_TABLE . "(
        id int NOT NULL AUTO_INCREMENT,
        thedate date not NULL unique,
        seatid int NOT NULL check (seatid > 0),
        seatcount int NOT NULL check (seatcount > 0),
        name text not NULL check (name <> ''),
        PRIMARY KEY (id)
    );";
    $result = dbDelta($sql);

    //file_put_contents("php://stderr","*** FMReservations_Database::make_reservations_table: result (of $sql) is $result\n");
  }
  public static function Get_OneShow($thedate,$format='OBJECT') {
    global $wpdb;
    $sql = $wpdb->prepare('SELECT * FROM '.
                          FMRESERVATIONS_TABLE.
                          ' WHERE thedate = %s',$thedate);
    return $wpdb->get_results($sql,$format);
  }
  public static function Get_Future_Reservations($format='OBJECT') {
    global $wpdb;
    return $wpdb->get_results("SELECT * FROM " .
                              FMRESERVATIONS_TABLE.
                              " where thedate >= CURDATE() order by thedate, name",
                              $format);
  }
  public static function Get_ReservationsForDate($thedate,$format='OBJECT') {
    global $wpdb;
    $sql = $wpdb->prepare('SELECT * FROM '.
                          FMRESERVATIONS_TABLE.
                          " where thedate = %s order by name",
                          $thedate
    return $wpdb->get_results($sql,$format);
  }
  public static function TotalReservedSeatsForDate($thedate) {
    global $wpdb;
    $sql = $wpdb->prepare('SELECT SUM(seatcount) FROM '.FMRESERVATIONS_TABLE.' WHERE thedate = %s',$thedate);
    return $wpdb->get_var($sql);
  }
  public static function ReservationsForDate($thedate) {
    global $wpdb;
    $sql = $wpdb->prepare('SELECT COUNT(*) FROM '.FMRESERVATIONS_TABLE.' WHERE thedate = %s',$thedate);
    return $wpdb->get_var($sql);
  }
  public static function InsertNewReservation($thedate,$name,$seatid,$seatcount=1) {
    global $wpdb;
    $wpdb->insert(FMRESERVATIONS_TABLE,array("thedate" => $thedata,
                                             "seatid" => $seatid,
                                             "seatcount" => $seatcount,
                                             "name" => $name),
                                             array("%s","%d","%d","%s"));
    return $wpdb->insert_id;
  }
  public static function DeleteReservation($id) {
    global $wpdb;
    $sql = $wpdb->prepare('DELETE FROM '.FMRESERVATIONS_TABLE.' WHERE id = %d',$id);
    $wpdb->query($sql);
  }
}  

        
?>
