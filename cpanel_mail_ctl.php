<?php
// cpanel shadow file location
$f_shadow = dirname(__FILE__) . '/../etc/example.com/shadow';
// a file where suspended mail account password hashes must be stored
$f_suspended = dirname(__FILE__) . '/../etc/example.com/suspended';
// Suspension password hash, generated from a random complex password
$suspass = '$6$623GSJOE7o2QsVyp$HhTGzOsQrblYG4BeiZwvdXFypZO.sro8B/Yjgwfzm2p8EUqISbv13b3hugSVn6XO9oQvRw0Lm6NQz8WS2wPjS1';
if (isset($_GET['action']) and isset($_GET['user']))
{
	$user = urldecode($_GET['user']);
	switch($_GET['action'])
	{
		case 'suspend':		acc_suspend(); break;
		case 'unsuspend':	acc_unsuspend(); break;
		case 'check':		acc_check(); break;
		default: exit; break;
	}	
}

function acc_check()
{
	global $user, $f_shadow, $suspass;
	foreach (file($f_shadow) as $line)
		if (preg_match('/^'.$user.':/', $line))
		{
			if (preg_match('/^'.$user.':'.preg_quote($suspass,'/').'/', $line))
			{	
				print_r('suspended');
				return;
			}
			print_r('!suspended');
			return;
		}
	print_r('!exist');
}

function acc_suspend()
{
	global $user, $f_shadow, $f_suspended, $suspass;
	$result = '';
	$susresult = '';
	$found = false;
	$susfound = false;
	foreach (file($f_shadow) as $line)
	{
		$tmp = explode(':', $line);
		if ($tmp[0] == $user and $tmp[1] != $suspass)
		{
			$found = true;
			// check if account is already in suspended list and if not add it
			foreach (file($f_suspended) as $susline)
			{
				$susresult .= $susline;
				if (preg_match('/^'.$tmp[0].':/', $susline)) $susfound = true;
			}
			if (!$susfound)
			{
				$susresult .= $line;
				file_put_contents($f_suspended, $susresult);
			}
			// change account password to suspended
			$result .= str_replace($tmp[1],$suspass,$line);
		}
		else
			$result .= $line;
	}
	if ($found) file_put_contents($f_shadow, $result);
	acc_check();
}

function acc_unsuspend()
{
	global $user, $f_shadow, $f_suspended;
	$result = '';
	$susresult = '';
	$found = false;
	$susfound = false;
	foreach (file($f_suspended) as $susline)
	{
		if (preg_match('/^'.$user.':/', $susline))
		{
			$susfound = true;
			foreach (file($f_shadow) as $line)
				if (preg_match('/^'.$user.':/', $line))
				{
					$found = true;
					$result .= $susline;
				}
				else
					$result .= $line;
			if ($found)
			{ 
				file_put_contents($f_shadow, $result);
				$susline = '';
			}
		}
		$susresult .= $susline;
	}
	if ($susfound) file_put_contents($f_suspended, $susresult);
	acc_check();
}
?>
