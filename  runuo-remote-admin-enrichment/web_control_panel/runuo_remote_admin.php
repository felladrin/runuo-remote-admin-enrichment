<?php
/***************************************************************************
 *                           runuo_remote_admin.php
 *                            -------------------
 *   begin                : May 19, 2010
 *   copyright            : (C) Antony Ho
 *   email                : ntonyworkshop@gmail.com
 *   website              : http://antonyho.net/
 *
 ***************************************************************************/

/***************************************************************************
 *
 *   Copyright (C) 2011 Ho Man Chung
 *   This program is free software: you can redistribute it and/or modify
 *   it under the terms of the GNU General Public License as published by
 *   the Free Software Foundation, either version 3 of the License, or
 *   (at your option) any later version.
 *   
 *   This program is distributed in the hope that it will be useful,
 *   but WITHOUT ANY WARRANTY; without even the implied warranty of
 *   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *   GNU General Public License for more details.
 *   
 *   You should have received a copy of the GNU General Public License
 *   along with this program. If not, see <http://www.gnu.org/licenses/>.
 ***************************************************************************/
 
	session_start();
	
	require_once("runuo_remote_admin_lib.php");
	require_once("tbs_lib/tbs_class.php");
	
	// check whether the socket is connected
	// if it is not connected, try connect using provided information
	if ( isset($_SESSION['logged']) )
	{
		$logged = $_SESSION['logged'];
	}
	elseif ( isset($_POST['host']) && isset($_POST['port']) && isset($_POST['username']) && isset($_POST['password']) )
	{
		if ( $socket = connect_socket($_POST['host'], $_POST['port']) )
		{
			if ( admin_logon($socket, $_POST['username'], $_POST['password']) )
			{
				$logged = true;
				$_SESSION['logged'] = true;
				$_SESSION['host'] = $_POST['host'];
				$_SESSION['port'] = $_POST['port'];
				$_SESSION['username'] = $_POST['username'];
				$_SESSION['password'] = $_POST['password'];
			}
		}
	}
	
	$TBS = new clsTinyButStrong;
	$pagetitle;
	
	// if socket is not connected, ask for necessary information
	// otherwise, ask for commands.
	if ($logged)
	{
		if (isset($_POST['cmd']))
		{
			$socket = connect_socket($_SESSION['host'], $_SESSION['port']);
			admin_logon($socket, $_SESSION['username'], $_SESSION['password']);
			HandleCommand($socket, $_POST['cmd']);
		}
		elseif (isset($_POST['cmd_optn']))
		{
			Command();
		}
		else
		{
			AskCommand();
		}
	}
	else
	{
		AskLogin();
		session_destroy();
		session_start();
	}
	
	if ($socket)
		disconnect($socket);
	
	$TBS->Show();
	
	
	
	function AskLogin()
	{
		global $TBS;
		$TBS->LoadTemplate('tpl/logon.htm');
	}
	
	function AskCommand()
	{
		global $TBS;
		global $pagetitle;
		$pagetitle = "Choose Command";
		$commands = array("Add", "Update", "Ban", "Unban", "AccessLevel", "Save", "Shutdown", "Restart", "SaveShutdown", "SaveRestart", "Broadcast", "Disconnect");
		/* If you do not use Antony's remote admin enrichment C# script, comment the line above and remove the comment mark '//' of below line. */
		//$commands = array("Add", "Update", "Ban", "Unban", "AccessLevel", "Disconnect");
		
		$TBS->LoadTemplate('tpl/askcmd.htm');
		$TBS->MergeBlock('cmds', $commands);
	}
	
	function Command()
	{
		global $TBS;
		global $pagetitle;
		global $command;
		$pagetitle = $_POST['cmd_optn'];
		$command = $_POST['cmd_optn'];
		
		switch ($command)
		{
			case 'Add':
			case 'Update':
				$TBS->LoadTemplate('tpl/modifyacct.htm');
			break;
			
			case 'Ban':
			case 'Unban':
				$TBS->LoadTemplate('tpl/restrictacct.htm');
			break;
			
			case 'AccessLevel':
				$TBS->LoadTemplate('tpl/acctaccesslevel.htm');
			break;
			
			case 'Save':
			case 'Shutdown':
			case 'Restart':
			case 'SaveShutdown':
			case 'SaveRestart':
				$TBS->LoadTemplate('tpl/confirmation.htm');
			break;
			
			case 'Broadcast':
				$TBS->LoadTemplate('tpl/broadcast.htm');
			break;
			
			case 'Disconnect':
				$TBS->LoadTemplate('tpl/confirmation.htm');
			break;
		}
	}
	
	function HandleCommand($socket, $cmd)
	{
		global $TBS;
		global $pagetitle;
		global $result;
		
		$pagetitle = $cmd." result";
		
		$username = $_POST['username'];
		$password = $_POST['password'];
		
		$confirmation = $_POST['cmd_optn'];
			
		switch ($cmd)
		{
			case 'Add':
			case 'Update':
				if ( modify_account($socket, $username, $password, 0, false) )	// assuming the access level is 0 [player]
					$result = $cmd." succeed.";
				else
					$result = $cmd." failed.";
				
				$TBS->LoadTemplate('tpl/cmdresult.htm');
			break;
			
			case 'Ban':
				if ( modify_account($socket, $username, "(hidden)", 0, true) )
					$result = $cmd." succeed.";
				else
					$result = $cmd." failed.";
				
				$TBS->LoadTemplate('tpl/cmdresult.htm');
			break;
			
			case 'Unban':
				if ( modify_account($socket, $username, "(hidden)", 0, false) )
					$result = $cmd." succeed.";
				else
					$result = $cmd." failed.";
				
				$TBS->LoadTemplate('tpl/cmdresult.htm');
			break;
			
			case 'AccessLevel':
				$access_level = intval($POST['accesslevel']);
				if ( modify_account($socket, $username, "(hidden)", $access_level, false) )
					$result = "Set access level succeed.";
				else
					$result = "Set access level failed.";
				
				$TBS->LoadTemplate('tpl/cmdresult.htm');
			break;
			
			case 'Disconnect':
				//disconnect($socket);	// need not to disconnect, the socket has already closed on every page request ends.
				session_destroy();
				$result = "You have been disconnected.";
				
				$TBS->LoadTemplate('tpl/cmdresult.htm');
			break;
			
			/************************************************************************/
			/* Caution! Below features are added by Antony.
			 * If you do not want to use my remote admin enrichment C# script on your shard. Remove below cases would be a safe way to avoid problem.
			 */
			case 'Save':
				if ($confirmation == "yes")
				{
					$result = world_save($socket);
					if (!$result)
						$result = $cmd." failed.";
				}
				else
						$result = $cmd." command canceled.";
				
				$TBS->LoadTemplate('tpl/cmdresult.htm');
			break;
			
			case 'Shutdown':
				if ($confirmation == "yes")
				{
					$result = shutdown($socket, false, false);
					if ($result)
						$result = $cmd." command sent.";
					else
						$result = $cmd." command failed.";
				}
				else
						$result = $cmd." command canceled.";
				
				$TBS->LoadTemplate('tpl/cmdresult.htm');
			break;
			
			case 'Restart':
				if ($confirmation == "yes")
				{
					$result = shutdown($socket, true, false);
					if ($result)
						$result = $cmd." command sent.";
					else
						$result = $cmd." command failed.";
				}
				else
						$result = $cmd." command canceled.";
				
				$TBS->LoadTemplate('tpl/cmdresult.htm');
			break;
			
			case 'SaveShutdown':
				if ($confirmation == "yes")
				{
					$result = shutdown($socket, false, true);
					if ($result)
						$result = $cmd." command sent.";
					else
						$result = $cmd." command failed.";
				}
				else
						$result = $cmd." command canceled.";
				
				$TBS->LoadTemplate('tpl/cmdresult.htm');
			break;
			
			case 'SaveRestart':
				if ($confirmation == "yes")
				{
					$result = shutdown($socket, true, true);
					if ($result)
						$result = $cmd." command sent.";
					else
						$result = $cmd." command failed.";
				}
				else
						$result = $cmd." command canceled.";
				
				$TBS->LoadTemplate('tpl/cmdresult.htm');
			break;
			
			case 'Broadcast':
				$message = $_POST['message'];
				$hue = (int)$_POST['hue'];
				$result = world_broadcast($socket, $message, $hue);
				if (!$result)
					$result = $cmd." command failed.";
				
				$TBS->LoadTemplate('tpl/cmdresult.htm');
			break;
			/*** End of Antony's enrichment ***/
			/************************************************************************/
		}
	}
?>