<?php
/***************************************************************************
 *                         runuo_remote_admin_lib.php
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
	
	function connect_socket($host, $port)
	{
		if ( ($socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) && @socket_connect($socket, $host, $port) )
			return $socket;
		else
			return false;
	}
	
	function remoteadmin_packet($subcmd, $packet_data)
	{
		$packet_size = strlen($packet_data) + 4;	// there are 4 bytes which is the command packet
		if ($packet_size > 65535)
			return false;
		
		if ($packet_size > 255)	// construct the command packet, the packet size is the 2nd & 3rd byte of the command packet
		{
			$len_higher_bit = $packet_size >> 8;
			$len_lower_bit = $packet_size & 0x000000ff;	// PHP integer has 4-bytes
			
			$packet = "\xf1".chr($len_higher_bit).chr($len_lower_bit).$subcmd.$packet_data;
		}
		else
		{
			$packet = "\xf1\x00".chr($packet_size).$subcmd.$packet_data;
		}
		
		return $packet;
	}
	
	function admin_logon($socket, $username, $password)
	{
		$packet = "\x7f\x01\x01\x01";	// login seed
		$packet .= "\xf1\x00\x40\x02";	// authenication subcommand by RemoteAdmin
		$packet .= str_pad( $username, 30, chr(0) );
		$packet .= str_pad( $password, 30, chr(0) );
		
		
		if ($result = @socket_write($socket, $packet, strlen($packet)))
		{
			if ( $buf = @socket_read($socket, 2, PHP_BINARY_READ) )	// login response packet is 2 bytes
			{
				$bytes = unpack("c*", $buf);
				
				if ($bytes[1] == 0x02)	// login response header 0x02
				{
					switch ($bytes[2])
					{
						case 0x04:
							//print("login succeed<br/>");
							$buf = socket_read($socket, 1024, PHP_BINARY_READ);		// read away the client info data in the stream from RemoteAdmin
							return true;
							break;
						
						case 0x00:
							print("invalid username<br/>");
							break;
						
						case 0x01:
							print("your IP is banned<br/>");
							break;
						
						case 0x02:
							print("invalid password<br/>");
							break;
						
						case 0x03:
							print("you don't have staff access<br/>");
							break;
						
						default:
							print("login failed<br/>");
							break;
					}
				}
				else
				{
					print("response is not a login header<br>");
				}
			}
			else
			{
				print("no server response or error occurred.<br/>");
				print(socket_strerror(socket_last_error())."<br/>");
			}
		}
		
		@socket_close($socket);
		return false;
	}
	
	/*
		if password not "(hidden)", the password would be updated by provided password.
	*/
	function modify_account($socket, $username, $password, $access_level, $ban)
	{
		$packet = $username.chr(0);
		$packet .= $password.chr(0);
		
		
		if ($access_level >= 0 && $access_level <= 6)	//this byte indicates the access level of this account
			$packet .= pack("c", $access_level);
		else
			$packet .= pack("c", 0);
		
		if ($ban)
			$packet .= pack("c", 1);		//this byte indicates ban this account or not (don't ban your own logged admin account)
		else
			$packet .= pack("c", 0);
		$packet .= pack("v", 0);			//this 16 bits will indicates the number of IP address which will be add to restricted IP. 0 would be used since restrict IP is not handled in this function yet.
		
		$packet = remoteadmin_packet("\x07", $packet);
		/*
		$packet_size = strlen($packet) + 4;	// there are 4 bytes which is the command packet
		
		if ($packet_size > 255)	// construct the command packet, the packet size is the 2nd & 3rd byte of the command packet
		{
			$len_higher_bit = $packet_size >> 8;
			$len_lower_bit = $packet_size & 0x000000ff;	// PHP integer has 4-bytes
			
			$packet = "\xf1".chr($len_higher_bit).chr($len_lower_bit)."\x07".$packet;
		}
		else
		{
			$packet = "\xf1\x00".chr($packet_size)."\x07".$packet;
		}
		*/
		
		if ($result = socket_write($socket, $packet, strlen($packet)))
		{
			if ( $buf = socket_read($socket, 1024, PHP_BINARY_READ) )	// read the result from RunUO
			{
				//$bytes = unpack("c*", $buf);
				
				if ( strpos($buf, "Account Updated") )
					return true;
				else
				{
					print($buf."<br/>");
					return false;
				}
			}
			else
			{
				print("no server response or error occurred.<br/>");
				print(socket_strerror(socket_last_error())."<br/>");
			}
		}
		
		return false;
	}
	
	function world_broadcast($socket, $message, $hue)
	{
		$tmessage = FixEncoding($message);
		$tmessage = substr($tmessage, 0, 499);	// max size 65535, we restrict the size to not more than 500 to avoid too large packet size.
		$packet = $tmessage.chr(0);
		$packet .= pack("n", $hue);
		$packet .= pack("c", 0);	// only use UTF-8 at this moment
		
		$packet = remoteadmin_packet("\x46", $packet);
		/*
		$packet_size = strlen($packet) + 4;	// there are 4 bytes which is the command packet
		if ($packet_size > 255)	// construct the command packet, the packet size is the 2nd & 3rd byte of the command packet
		{
			$len_higher_bit = $packet_size >> 8;
			$len_lower_bit = $packet_size & 0x000000ff;	// PHP integer has 4-bytes
			
			$packet = "\xf1".chr($len_higher_bit).chr($len_lower_bit)."\x46".$packet;
		}
		else
		{
			$packet = "\xf1\x00".chr($packet_size)."\x46".$packet;
		}
		*/
		
		if ($result = socket_write($socket, $packet, strlen($packet)))
		{
			if ( $buf = socket_read($socket, 1024, PHP_BINARY_READ) )	// read the result from RunUO
			{
				if ( strpos($buf, "Message Broadcasted") )
					return $buf;
			}
		}
		
		return false;
	}
	
	function shutdown($socket, $restart, $save)
	{
		$packet = pack("c", ($restart ? 1 : 0) );
		$packet .= pack("c", ($save ? 1 : 0) );
		
		$packet_size = strlen($packet) + 4;	// there are 4 bytes which is the command packet
		$packet = "\xf1\x00".chr($packet_size)."\x42".$packet;
		
		return socket_write($socket, $packet, strlen($packet));
	}
	
	function world_save($socket)
	{
		$packet_size = 4;	// there are 4 bytes which is the command packet
		$packet = "\xf1\x00".chr($packet_size)."\x41".$packet;
		
		if ($result = socket_write($socket, $packet, strlen($packet)))
		{
			if ( $buf = socket_read($socket, 1024, PHP_BINARY_READ) )	// read the result from RunUO
			{
				if ( strpos($buf, "World Saved") )
					return $buf;
			}
		}
		
		return false;
	}
	
	function search_account($socket, $username)
	{
		$packet = pack("n", 0);		// account search type, hardcode 0 for username
		$packet .= $username.chr(0);
		$packet = remoteadmin_packet("\x05", $packet);
		
		if ($result = socket_write($socket, $packet, strlen($packet)))
		{
			if ( $buf = socket_read($socket, 2048, PHP_BINARY_READ) )	// read the result from RunUO
			{
				if ( strpos($buf, "Invalid IP") || strpos($buf, "Too Many Results") || strpos($buf, "No Matches") )
					return $buf;
				/*
				else
					/*
					decompress zlib packet implementation
					when it is length > 100 && length < 60000
					the packet will be compressed
					otherwise, the packet will be kept original
					*/
				*/
			}
		}
		
		return false;
	}
	
	
	function disconnect($socket)
	{
		socket_shutdown($socket, 2);
		socket_close($socket);
	}
	
	
	
	
	function FixEncoding($x)
	{
		if(mb_detect_encoding($x)=='UTF-8')
			return $x;
		else
			return utf8_encode($x);
	}
?>