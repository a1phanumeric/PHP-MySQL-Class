<?php
/*
 *  Copyright (C) 2011
 *     Ed Rackham (http://github.com/a1phanumeric/PHP-MySQL-Class)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

// MySQL Class
class MySQL {
	// Base variables
	var $sLastError;				// Holds the last error
	var $sLastQuery;				// Holds the last query
	var $aResult;					// Holds the MySQL query result
	var $iRecords;					// Holds the total number of records returned
	var $iAffected;					// Holds the total number of records affected
	var $aRawResults;				// Holds raw 'arrayed' results
	var $aArrayedResult;			// Holds a single 'arrayed' result
	var $aArrayedResults;			// Holds multiple 'arrayed' results (usually with a set key)
	
	var $sHostname = MYSQL_HOST;	// MySQL Hostname
	var $sUsername = MYSQL_USER;	// MySQL Username
	var $sPassword = MYSQL_PASS;	// MySQL Password
	var $sDatabase = MYSQL_NAME;	// MySQL Database
	
	var $sDBLink;			// Database Connection Link
	
	// Class Constructor
	// Assigning values to variables
	function MySQL(){
		$this->Connect();
	}
	
	// Connects class to database
	// $bPersistant (boolean) - Use persistant connection?
	function Connect($bPersistant = false){
		if($this->sDBLink){
			mysql_close($this->sDBLink);
		}
		
		if($bPersistant){
			$this->sDBLink = mysql_pconnect($this->sHostname, $this->sUsername, $this->sPassword);
		}else{
			$this->sDBLink = mysql_connect($this->sHostname, $this->sUsername, $this->sPassword);
		}
		
		if (!$this->sDBLink){
   			$this->sLastError = 'Could not connect to server: ' . mysql_error($this->sDBLink);
			return false;
		}
		
		if(!$this->UseDB()){
			$this->sLastError = 'Could not connect to database: ' . mysql_error($this->sDBLink);
			return false;
		}
		return true;
	}
	
	// Select database to use
	function UseDB(){
		if (!mysql_select_db($this->sDatabase, $this->sDBLink)) {
			$this->sLastError ='Cannot select database: ' . mysql_error($this->sDBLink);
			return false;
		}else{
			return true;
		}
	}
	
	// Executes MySQL query
	function ExecuteSQL($sSQLQuery){
		$this->sLastQuery 	= $sSQLQuery;
		if($this->aResult 		= mysql_query($sSQLQuery, $this->sDBLink)){
			$this->iRecords 	= @mysql_num_rows($this->aResult);
			$this->iAffected	= @mysql_affected_rows($this->sDBLink);
			return true;
		}else{
			$this->sLastError = mysql_error($this->sDBLink);
			return false;
		}
	}
	
	// Adds a record to the database
	// based on the array key names
	function Insert($aVars, $sTable, $aExclude = ''){
		// Catch Exceptions
		if($aExclude == ''){
			$aExclude = array();
		}
		
		array_push($aExclude, 'MAX_FILE_SIZE');
		
		// Prepare Variables
		$aVars = $this->SecureData($aVars);
		
		$sSQLQuery = 'INSERT INTO `' . $sTable . '` SET ';
		foreach($aVars as $iKey=>$sValue){
			if(in_array($iKey, $aExclude)){
				continue;
			}
			$sSQLQuery .= '`' . $iKey . '` = "' . $sValue . '", ';
		}
		
		$sSQLQuery = substr($sSQLQuery, 0, -2);
		
		if($this->ExecuteSQL($sSQLQuery)){
			return true;
		}else{
			return false;
		}
	}
	
	// Deletes a record from the database
	function Delete($sTable, $aWhere='', $sLimit='', $bLike=false){
		$sSQLQuery = 'DELETE FROM `' . $sTable . '` WHERE ';
		if(is_array($aWhere) && $aWhere != ''){
			// Prepare Variables
			$aWhere = $this->SecureData($aWhere);
			
			foreach($aWhere as $iKey=>$sValue){
				if($bLike){
					$sSQLQuery .= '`' . $iKey . '` LIKE "%' . $sValue . '%" AND ';
				}else{
					$sSQLQuery .= '`' . $iKey . '` = "' . $sValue . '" AND ';
				}
			}
			
			$sSQLQuery = substr($sSQLQuery, 0, -5);
		}
		
		if($sLimit != ''){
			$sSQLQuery .= ' LIMIT ' .$sLimit;
		}
		
		if($this->ExecuteSQL($sSQLQuery)){
			return true;
		}else{
			return false;
		}
	}
	
	// Gets a single row from $1
	// where $2 is true
	function Select($sFrom, $aWhere='', $sOrderBy='', $sLimit='', $bLike=false, $sOperand='AND'){
		// Catch Exceptions
		if(trim($sFrom) == ''){
			return false;
		}
		
		$sSQLQuery = 'SELECT * FROM `' . $sFrom . '` WHERE ';
		
		if(is_array($aWhere) && $aWhere != ''){
			// Prepare Variables
			$aWhere = $this->SecureData($aWhere);
			
			foreach($aWhere as $iKey=>$sValue){
				if($bLike){
					$sSQLQuery .= '`' . $iKey . '` LIKE "%' . $sValue . '%" ' . $sOperand . ' ';
				}else{
					$sSQLQuery .= '`' . $iKey . '` = "' . $sValue . '" ' . $sOperand . ' ';
				}
			}
			
			$sSQLQuery = substr($sSQLQuery, 0, -5);

		}else{
			$sSQLQuery = substr($sSQLQuery, 0, -7);
		}
		
		if($sOrderBy != ''){
			$sSQLQuery .= ' ORDER BY ' .$sOrderBy;
		}
		
		if($sLimit != ''){
			$sSQLQuery .= ' LIMIT ' .$sLimit;
		}
		
		if($this->ExecuteSQL($sSQLQuery)){
			if($this->iRecords > 0){
				$this->ArrayResults();
			}
			return true;
		}else{
			return false;
		}
		
	}
	
	// Updates a record in the database
	// based on WHERE
	function Update($sTable, $aSet, $aWhere, $aExclude = ''){
		// Catch Exceptions
		if(trim($sTable) == '' || !is_array($aSet) || !is_array($aWhere)){
			return false;
		}
		if($aExclude == ''){
			$aExclude = array();
		}
		
		array_push($aExclude, 'MAX_FILE_SIZE');
		
		$aSet 	= $this->SecureData($aSet);
		$aWhere = $this->SecureData($aWhere);
		
		// SET
		
		$sSQLQuery = 'UPDATE `' . $sTable . '` SET ';
		
		foreach($aSet as $iKey=>$sValue){
			if(in_array($iKey, $aExclude)){
				continue;
			}
			$sSQLQuery .= '`' . $iKey . '` = "' . $sValue . '", ';
		}
		
		$sSQLQuery = substr($sSQLQuery, 0, -2);
		
		// WHERE
		
		$sSQLQuery .= ' WHERE ';
		
		foreach($aWhere as $iKey=>$sValue){
			$sSQLQuery .= '`' . $iKey . '` = "' . $sValue . '" AND ';
		}
		
		$sSQLQuery = substr($sSQLQuery, 0, -5);
		
		if($this->ExecuteSQL($sSQLQuery)){
			return true;
		}else{
			return false;
		}
	}
	
	// 'Arrays' a single result
	function ArrayResult(){
		$this->aArrayedResult = mysql_fetch_assoc($this->aResult) or die (mysql_error($this->sDBLink));
		return $this->aArrayedResult;
	}

	// 'Arrays' multiple result
	function ArrayResults(){
		$this->aArrayedResults = array();
		while ($aData = mysql_fetch_assoc($this->aResult)){
			$this->aArrayedResults[] = $aData;
		}
		return $this->aArrayedResults;
	}
	
	// 'Arrays' multiple results with a key
	function ArrayResultsWithKey($sKey='id'){
		if(isset($this->aArrayedResults)){
			unset($this->aArrayedResults);
		}
		$this->aArrayedResults = array();
		while($aRow = mysql_fetch_assoc($this->aResult)){
			foreach($aRow as $sTheKey => $sTheValue){
				$this->aArrayedResults[$aRow[$sKey]][$sTheKey] = $sTheValue;
			}
		}
		return $this->aArrayedResults;
	}
	
	// Performs a 'mysql_real_escape_string' on the entire array/string
	function SecureData($aData){
		if(is_array($aData)){
			foreach($aData as $iKey=>$sVal){
				if(!is_array($aData[$iKey])){
					$aData[$iKey] = mysql_real_escape_string($aData[$iKey], $this->sDBLink);
				}
			}
		}else{
			$aData = mysql_real_escape_string($aData, $this->sDBLink);
		}
		return $aData;
	}
}

?>
