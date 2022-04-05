<?php
/**
 * ************************************************************************
 * *                   Test Centre Management System                     **
 * ************************************************************************
 * @package     local
 * @subpackage  Test Centre Management System
 * @name        tcs
 * @author      David Lowe
 * ************************************************************************
 * ********************************************************************* */


// error_log("\n================    raw.php   ======================================");
$package = array();

$facker1 = array();
$facker2 = array();

$facker1["id"] = 0;
$facker1["username"] = "Chuck Norris";
$facker1["exam"] = "Ninja Skills 101";
$facker1["opening_date"] = "Sometime soon";
$facker1["closing_date"] = "Hopefully never";
$facker1["notes"] = "There are no notes";

$facker2["id"] = 1;
$facker2["username"] = "Bruce Lee";
$facker2["exam"] = "Kung Fu Skills 101";
$facker2["opening_date"] = "Sometime soon";
$facker2["closing_date"] = "Hopefully never";
$facker2["notes"] = "There are no notes";

array_push($package, $facker1);
array_push($package, $facker2);

echo json_encode($package);