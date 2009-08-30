<?php

//
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
// Original Author of file: Klavs Klavsen <kl@vsen.dk>.

//loading PHPwhois class. Only tested with v3.0.5.
include("main.whois");

class CheckDomainAvailability 
{
    var $mDomain;	//domain looked up
    var $mResult;	//contains the result of the lookup done in the constructor

    function CheckDomainAvailability($domain)
    {
	//do the whois lookup
	//
	//Should do eregi check for valid string 
	//(ie. a-zA-Z0-9.(com/net/org/info/biz/dk))
	//
	$whois = new Whois("$domain");
	$this->mDomain = $domain;
	$this->mResult = $whois->Lookup();
    }

    //
    // check for availabilty and return true for available and false for taken.
    //
    function CheckIfAvailable()
    {
	//explode - to get the tld
	$tld = explode(".", $this->mDomain);
	
	// checking for .com .net .org domain availability
	if ($argv[2] == "com" || $argv[2] == "net" || $argv[2] == "org" )
	{
	   if(empty($this->mResult["regyinfo"])) 
	   {
	      return true;
	   } else 
	   {
	      return false;
	   }
	// checking for .info or .biz availability
	} else if ($argv[2] == "info" || $argv[2] == "biz")
	{
	   if(empty($this->mResult["regrinfo"])) 
	   {
	      return true;
	   } else 
	   {
	      return false;
	   }
	} else
	{
	   $tmparray = preg_grep("/No entries found/", $this->mResult['rawdata']);
	   if($tmparray)
	   {
	      return true;
	   } else
	   {
	      return false;
	   } 
	}
      //if we ended here something went wrong
      //best to say it's taken :)
      return false;
   }
//end class
}
?>
