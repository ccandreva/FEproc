<?php
// ----------------------------------------------------------------------
// FEproc - Mail template backend module for FormExpress for
// POST-NUKE Content Management System
// Copyright (C) 2003 by Jason Judge
// ----------------------------------------------------------------------
// Based on:
// PHP-NUKE Web Portal System - http://phpnuke.org/
// ----------------------------------------------------------------------
// LICENSE
//
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License (GPL)
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WIthOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// To read the license please visit http://www.gnu.org/copyleft/gpl.html
// ----------------------------------------------------------------------
// Original Author of file: Jason Judge.
// Current Maintainer of file: Klavs Klavsen <kl-feproc@vsen.dk>
// ----------------------------------------------------------------------

$modversion['name'] = 'FEproc';
$modversion['version'] = '0.3.5';
$modversion['description'] = 'Generic multi-form handling for FormExpress';
$modversion['credits'] = '';
$modversion['help'] = 'docs/help.html';
$modversion['changelog'] = 'docs/changelog.txt';
$modversion['license'] = '';
$modversion['official'] = 1;
$modversion['author'] = 'Jason Judge & Klavs Klavsen';
$modversion['contact'] = 'contrib.virkpaanettet.dk';
$modversion['admin'] = 1;
$modversion['securityschema'] = array(
    'FEproc::' => '::',                  // General (User and Admin) - not sure if this is needed?
    'FEproc::Set' => '::setid',          // User and Admin
    'FEproc::Stage' => '::stageid',      // Admin
    'FEproc::Handler' => '::handlerid'   // Admin
);
?>
