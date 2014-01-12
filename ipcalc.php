<!--
PHP Subnet Calculator v1.3.
Copyright 01/11/2012 Randy McAnally
Released under GNU GPL.
Special thanks to krischan at jodies.cx for ipcalc.pl http://jodies.de/ipcalc
The presentation and concept was mostly taken from ipcalc.pl.
-->
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
		"http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
  <title>PHP Subnet Calculator</title>
  <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
  <meta name="GENERATOR" content="Quanta Plus">
</head>
<body bgcolor="#D3D3D3">
<center>
<form method="get" action="<?php print $_SERVER['PHP_SELF'] ?> ">
<BR><BR>
<table width="95%" align=center cellpadding=2 cellspacing=2 border=0>
  <tr><td align="center" bgcolor="#999999">
     <b><A HREF="http://djlab.com/stuff/ipcalc.php">IPv4 and IPv6 PHP Subnet Calculator</A></b>
  </td></tr>
</table>
<BR>
<table>
  <tr>
        <td>Network &amp; CIDR:&nbsp;&nbsp;&nbsp;</td>
        <td><input type="text" name="network" value="<?php echo $_REQUEST['network'] ?>" size="31" maxlength="64"></td>
        <td>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="Calculate" name="subnetcalc">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;</td>
  </tr>
  <tr>
        <td>Subnet CIDR:&nbsp;&nbsp;&nbsp;</td>
        <td><input type="text" name="subnet" value="<?php echo $_REQUEST['subnet']; ?>" size="18" maxlength="64"></td>
        <td>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="Calculate" name="subnetcalc">
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;</td>
  </tr>
</table></form><br>

<?php
//Start table
print "<table cellpadding=\"2\">\n<COL span=\"4\" align=\"left\">\n" ;

$end='</table><table width="95%" align=center cellpadding=2 cellspacing=2 border=0>
      <tr><td bgcolor="#999999"></td><tr><td align="center"><a href="http://validator.w3.org/check/referer">
      <img border="0" src="http://www.w3.org/Icons/valid-html401" alt="Valid HTML 4.01!" height="31" width="88"></a>
      </td></tr></table></center></body></html>';

if (empty($_REQUEST['network'])){
	tr('Use IP/Network & CIDR Netmask:&nbsp;', '10.0.0.1/22');
	print $end ;
	exit ;
}

function tr(){
	echo "\t<tr>";
	for($i=0; $i<func_num_args(); $i++) echo "<td>".func_get_arg($i)."</td>";
	echo "</tr>\n";
}


ini_set('display_errors',1);

$maxSubNets = '2048'; // Stop memory leak from invalid input or large ranges

$superNet = $_REQUEST['network'];
$superNetMask = ''; // optional

$subNetCdr = $_REQUEST['subnet'];
$subNetMask = ''; // optional

// Calculate supernet mask and cdr
if (ereg('/',$superNet)){  //if cidr type mask
	$charHost = inet_pton(strtok($superNet, '/'));
	$charMask = _cdr2Char(strtok('/'),strlen($charHost));
} else {
  $charHost = inet_pton($superNet);
  $charMask = inet_pton($superNetMask);
}


// Single host mask used for hostmin and hostmax bitwise operations
$charHostMask = substr(_cdr2Char(127),-strlen($charHost));

$charWC = ~$charMask; // Supernet wildcard mask
$charNet = $charHost & $charMask; // Supernet network address
$charBcst = $charNet | ~$charMask; // Supernet broadcast
$charHostMin = $charNet | ~$charHostMask; // Minimum host
$charHostMax = $charBcst & $charHostMask; // Maximum host



// Print Results
tr('Network:','<font color="blue">'.inet_ntop($charNet)."/"._char2Cdr($charMask)."</font>");
tr('Netmask:','<font color="blue">'.inet_ntop($charMask)." = /"._char2Cdr($charMask)."</font>");
tr('Wildcard:', '<font color="blue">'.inet_ntop($charWC).'</font>');
tr('Broadcast:', '<font color="blue">'.inet_ntop($charBcst).'</font>');
tr('HostMin:', '<font color="blue">'.inet_ntop($charHostMin).'</font>');
tr('HostMax:', '<font color="blue">'.inet_ntop($charHostMax).'</font>');



// Calculate subnet mask and cdr
if ($subNetCdr) {
	preg_match('/\d+/',$subNetCdr,$cdr);
  $subNetCdr = $cdr[0];
	$charSubMask = _cdr2Char($subNetCdr,strlen($charHost));
	$charSubWC = ~$charSubMask; // Subnet wildcard mask
	$superNetMask = inet_ntop($charSubMask);
} else {
	print "$end";
	exit;
}



// Convert to unsigned short so we can do some math
$intNet=_unpackBytes($charNet);
$intSubWC=_unpackBytes($charSubWC);

// Set up initial subnet address, it will be the same as the supernet address
$intSub = $intNet;
$charSub = $charNet;
$charSubs = array();


// Generate adjacent subnets until we leave the supernet
$n = 0;
while ((($charSub & $charMask) == $charNet) && $n < $maxSubNets) {
  array_push($charSubs,$charSub);
  $intSub = _addBytes($intSub,$intSubWC);
  $charSub = _packBytes($intSub);
  $n++;
}

echo '<tr><td colspan="2" bgcolor="#999999"></td><tr>';
      
// Output result
foreach ( $charSubs as $charSub ) {
	tr('Network:','<font color="blue"><a href="?network='.urlencode(inet_ntop( $charSub)."/"._char2Cdr($charSubMask)).'">'.inet_ntop( $charSub)."/"._char2Cdr($charSubMask)."</a></font>");
}

	print "$end";
	exit;




// Convert array of short unsigned integers to binary
function _packBytes($array) {
  foreach ( $array as $byte ) {
		$chars .= pack('C',$byte);
	}
	return $chars;
}

// Convert binary to array of short integers
function _unpackBytes($string) {
	return unpack('C*',$string);
}

// Add array of short unsigned integers
function _addBytes($array1,$array2) {
	$result = array();
	$carry = 0;
	foreach ( array_reverse($array1,true) as $value1 ) {
		$value2 = array_pop($array2);
		if ( empty($result) ) { $value2++; }
		$newValue = $value1 + $value2 + $carry;
		if ( $newValue > 255 ) {
		  $newValue = $newValue - 256;
		  $carry = 1;
		} else {
		  $carry = 0;
		}
		array_unshift($result,$newValue);
	}
	return $result;
}

/* Useful Functions */

function _cdr2Bin ($cdrin,$len=4){
	if ( $len > 4 || $cdrin > 32 ) { // Are we ipv6?
		return str_pad(str_pad("", $cdrin, "1"), 128, "0");
	} else {
	  return str_pad(str_pad("", $cdrin, "1"), 32, "0");
	}
}

function _bin2Cdr ($binin){
	return strlen(rtrim($binin,"0"));
}

function _cdr2Char ($cdrin,$len=4){
	$hex = _bin2Hex(_cdr2Bin($cdrin,$len));
	return _hex2Char($hex);
}

function _char2Cdr ($char){
	$bin = _hex2Bin(_char2Hex($char));
	return _bin2Cdr($bin);
}

function _hex2Char($hex){
	return pack('H*',$hex);
}

function _char2Hex($char){
	$hex = unpack('H*',$char);
	return array_pop($hex);
}

function _hex2Bin($hex){
  $bin='';
  for($i=0;$i<strlen($hex);$i++)
    $bin.=str_pad(decbin(hexdec($hex{$i})),4,'0',STR_PAD_LEFT);
  return $bin;
}

function _bin2Hex($bin){
  $hex='';
  for($i=strlen($bin)-4;$i>=0;$i-=4)
    $hex.=dechex(bindec(substr($bin,$i,4)));
  return strrev($hex);
}

?>
