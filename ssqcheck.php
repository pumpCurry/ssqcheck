<?php
//DDRのSSQを調べるツール (C) pumpCurry 2019-2020

require_once('BinaryUtil.php');

print "\n";
print "##############################################################\n";
print "### Check DDR SSQ/CSQ Analyze tool (C) pumpCurry 2019-2020 ###\n";
print "##############################################################\n";
print "\n";

if(isset($argv[1])){
	if (file_exists($argv[1])) {
		main($argv[1]); 
		exit(0);
	}else{
		print "file not found: [{$argv[1]}].\n";
		exit(9);
	}
}else{
	print "Usage: > {$argv[0]} filename.ssq\n\n";
	exit(1);
}


function main($filename){

	// phase01: define BUobject:
	$util = new BinaryUtil();

	// phase02: set little endian
	$util->setEndian(BinaryUtil::LITTLE_ENDIAN);

	// phase03: get file: 
	$util->read($filename);


	// phase04: analyze chunk:
	$c      = 0;
	$len    = $util->length();
	$chunks = array();
	while ($c<$len){
		$i = count($chunks);
		$chunks[$i] = getChunkHeader($util,$c);
		$c = $c + $chunks[$i]['Length'];
		if ($chunks[$i]['Type']==0){ break; }
		$chunks[$i] = getChunkBody($util,$chunks[$i]);
	} // end while.

	// phase05: print any chunk header results:
	print "\n###[ " . $filename . ' , Length: ' . $util->length() . " Byte(s) ]###\n";
	getChunckSummaryOutput($chunks);

	// phase06: print any chunk body results:
	foreach ($chunks as $i => $c){
		getChunkBodyInfoOutput($c);
	}

	// debug
	//var_dump($chunks);

} // end of main function. //


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// argv1: chunks配列
// return: (標準出力にprintする)
function getChunckSummaryOutput($chunks){

	$sBorder = "+----+------+--------------+-------------------------+-----------------------------------+------------------+\n";
	$sHeader = "|#   |Addr: |Length:( HEX )|Chunk type:              |Values                             |Entry             |\n";

	// phase 01: print list header:
	print $sBorder.$sHeader.$sBorder;


	foreach ($chunks as $i => $c){
		//$i = count($chunks);

		// phase02: print count:
		printf("[%3d]",$i+1);

		// phase03: print info1: addr & length:
		printf("[%5X][%6d (%5X)]",$chunks[$i]['Offset'],$chunks[$i]['Length'], $chunks[$i]['Length']);
		
		// phase04: print info1: chunk type:
		printf("[%02X:%-20.20s]",$chunks[$i]['Type'],$chunks[$i]['Type_name']);

		// phase05: print info1: chunk header param:
		switch ($chunks[$i]['Type']){
			case 1:  printf("[TfPS  : (%1$4X) %1$4d] %2$-14.14s"     ,$chunks[$i]['Param'],'');                                                       break;
			case 2:  printf("[param : (%1$4X) %1$4d] %2$-14.14s"     ,$chunks[$i]['Param'],'');                                                       break;
			case 3:  printf("[level : (%1$04X) %2$-8.8s %3$-10.10s]" ,$chunks[$i]['Param'],$chunks[$i]['Play_Style'],$chunks[$i]['Play_Difficulty']); break;
			default: printf("[param : (%1$4X) %1$4d] %2$-14.14s"     ,$chunks[$i]['Param'],'');                                                       break;
		} // end switch.

		// phase06: print info1: entries:
		printf("[Entry: %1$4d (%1$4X)]"   ,$chunks[$i]['Entry']);
		print "\n";

		// phase07: break if footer is comming:
		if ($chunks[$i]['Type']==0){ break; }
	} // end foreach.

	// phase08: print list footer:
	print $sBorder;


} // end function.

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// argv1: buObject
// argv2: OffsetAddress(bytes)
// return: $chunk

function getChunkHeader($buObj,$offset = 0){
	$O = 0;
	$retVal = array();
	$length = $buObj->getInt  ($offset+$O); $O=$O+4;

	//チャンクの情報を得る
	$retVal['Offset']    = $offset;
	
	if ($length <> 0) {
		$retVal['Length']    = $length;
		$retVal['Type']      = $buObj->getShort($offset+$O); $O=$O+2;
		$retVal['Param']     = $buObj->getShort($offset+$O); $O=$O+2; // arg1
		$retVal['Entry']     = $buObj->getInt  ($offset+$O); $O=$O+4; // どうやらarg2は intにみえるんだけど
		//$retVal['Agr3']    = $buObj->getShort($offset+$O); $O=$O+2; // arg3はほぼ0に見える
	} else {
		$retVal['Length']    = 0;
		$retVal['Type']      = 0;
		$retVal['Param']     = 0;
		$retVal['Entry']     = 0;
	}


	switch($retVal['Type']){
		case 0:  $retVal['Type_name'] = "End of File.";         break;
		case 1:  $retVal['Type_name'] = "Tempo/TfPS Config.";   break;
		case 2:  $retVal['Type_name'] = "Begin/Finish Config."; break;
		case 3:  $retVal['Type_name'] = "Step Data.";           break;
		default: $retVal['Type_name'] = "Unknown Data.";        break;
	} // end switch.
	
	if ($retVal['Type']==3){

		//print "<".dechex(floor( ($retVal['Param'] & hexdec('ff00')/hexdec('100')) )) ."/". dechex($retVal['Param'] & hexdec('ff')) .">";

		switch(($retVal['Param'] & hexdec('ff00'))){
			case hexdec('0100'): $retVal['Play_Difficulty'] = "Basic";         break;
			case hexdec('0200'): $retVal['Play_Difficulty'] = "Standard";      break;
			case hexdec('0300'): $retVal['Play_Difficulty'] = "Heavy";         break;
			case hexdec('0400'): $retVal['Play_Difficulty'] = "Beginner";      break;
			case hexdec('0600'): $retVal['Play_Difficulty'] = "Challenge";     break;
			case hexdec('1000'): $retVal['Play_Difficulty'] = "Battle";        break;
			default:             $retVal['Play_Difficulty'] = "Unknown";       break;
		} // end switch.

		switch($retVal['Param'] & hexdec('ff')){
			case hexdec('14'):   $retVal['Play_Style'] = "Single";             break;
			case hexdec('16'):   $retVal['Play_Style'] = "Solo";               break;
			case hexdec('18'):   $retVal['Play_Style'] = "Double";             break;
			case hexdec('24'):   $retVal['Play_Style'] = "Battle";             break;
			default:             $retVal['Play_Style'] = "Unknown";            break;
		} // end switch.

	} else {
		$retVal['Play_Difficulty'] = "";
		$retVal['Play_Style']      = "";
	}
	
	return $retVal;

} //end function

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// argv1: buObject
// argv2: $chunk
// return: $chunk

function getChunkBody($buObj,$chunk){
	$retVal = $chunk;

	//配列を付与
	$retVal['TimeOffsets'] = array();
	$retVal['Values']      = array();
	
	//チャンクの情報を得る
	$O      = $chunk['Offset'];
	$length = $chunk['Length'];
	$e      = $chunk['Entry'];

	//空配列なら即抜け
	if ($length == 0) {
		return $retVal;
	}

	//ヘッダの終わり位置にオフセット
	$O      =  $O+4+2+2+4; // => ['Length':4]+['Type':2]+['Param':2]+['Entry':4]

	//timeOffsetのリストを生成
	for ($i=0;$i<$e;$i++) {

		switch($retVal['Type']){
			case 0: return $retVal; break; // type:0はここに来れないはず

			case 1:
			case 2:
			case 3:
				{
					$retVal['TimeOffsets'][$i] = $buObj->getInt  ($offset+$O); $O=$O+4;

					//負の場合
					if($retVal['TimeOffsets'][$i] & hexdec('80000000') ){
						$retVal['TimeOffsets'][$i] = -1 - ($retVal['TimeOffsets'][$i] ^ hexdec('FFFFFFFF'));
					}
				} break;
			default:
				{
					$retVal['TimeOffsets'][$i] = $buObj->getInt  ($offset+$O); $O=$O+4;
				} break;
		} // end switch.
	} // end for.

	// byte境界の対応は不要のはず(4byte単位で探査なので)

	//valuesのリストを生成
	for ($i=0;$i<$e;$i++) {
		switch($retVal['Type']){
			case 0: return $retVal; break; // type:0はここに来れないはず

			case 1: // 01:テンポ関連：
				{
					$retVal['Values'][$i] = $buObj->getInt  ($offset+$O); $O=$O+4;
				} break;

			case 2: // 02:開始終了地点指定：
				{
					$retVal['Values'][$i] = $buObj->getShort($offset+$O); $O=$O+2;
				} break;

			case 3: // 03:譜面
				{
					$retVal['Values'][$i] = $buObj->getByte ($offset+$O); $O=$O+1;
				} break;

			default:// 突き抜けを防ぐため1バイトと仮定する
				{
					$retVal['Values'][$i] = $buObj->getByte ($offset+$O); $O=$O+1;
				} break;
		} // end switch.
	} // end for.


	return $retVal;

} //end function

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// argv1: buObject
// argv2: $chunk
// return: $chunk

function getChunkBodyInfoOutput($chunk){

	//チャンクの情報を得る
	$O      = $chunk['Offset'];
	$length = $chunk['Length'];
	$e      = $chunk['Entry'];

	// Chunk Title:
	switch($chunk['Type']){
		case 3:
			{
				printf("\n[[[%02X:%-20.20s]]] [level : (%5$04X) %3$-8.8s %4$-10.10s]\n",$chunk['Type'],$chunk['Type_name'],$chunk['Play_Style'],$chunk['Play_Difficulty'],$chunk['Param']);
				break;
			}
		default:
			{
				printf("\n[[[%02X:%-20.20s]]]\n",$chunk['Type'],$chunk['Type_name']);
			} break;
	}

	//timeOffsetのリストを生成
	for ($i=0;$i<$e;$i++) {

		switch($chunk['Type']){
			case 0: return; break; // type:0はfor処理自体もabortさせる

			case 1:
				{
					if ($i==0){ break; }
					$LastTimeOffset = 0;
					$DeltaOffset    = 0;
					$DeltaTicks     = 0;

					$offset_hexD = '0';
					$offset_hex0 = substr(sprintf("%1$+6X",$chunk['TimeOffsets'][$i-1]),-6,6); //負の表示対応 (DDR A)
					$offset_hex1 = substr(sprintf("%1$+6X",$chunk['TimeOffsets'][$i]  ),-6,6); //負の表示対応 (DDR A)

					$LastTimeOffset = $chunk['TimeOffsets'][$i-1];
					$DeltaOffset    = $chunk['TimeOffsets'][$i] - $LastTimeOffset;
					$DeltaTicks = $chunk['Values'][$i] - $chunk['Values'][$i - 1];
					$offset_hexD    = substr(sprintf("%1$6X", $chunk['TimeOffsets'][$i] - $LastTimeOffset),-6,6);  //負の表示対応 (DDR A)


					$TfPS           = $chunk['Param'];
					$MeasureLength  = 4096;
				
				    $BPM = ($DeltaOffset / $MeasureLength) / (($DeltaTicks / $TfPS ) / 240);

					printf("[01:BPM][(%8$6s:%9$6s) %6$8d:%7$8d][BPM: %5$' 4.5F] Delta> Offset:(%10$6s)%1$7d / Ticks:(%2$5X)%2$7d \n",
					    $DeltaOffset,$DeltaTicks,$TfPS,$MeasureLength,$BPM,$chunk['TimeOffsets'][$i-1],$chunk['TimeOffsets'][$i],$offset_hex0,$offset_hex1,$offset_hexD);
					  //1           ,2          ,3    ,4             ,5   ,6                          ,7                        ,8           ,9           ,10

				} break;

			case 2:
				{
					$func_title = '';

					$offset_hexD = '0';
					$offset_hex1 = substr(sprintf("%1$+6X",$chunk['TimeOffsets'][$i]),-6,6); //負の表示対応 (DDR4th)

					$LastTimeOffset = 0;

					if ($i > 0){
						$LastTimeOffset = $chunk['TimeOffsets'][$i-1];
						$offset_hexD    = substr(sprintf("%1$6X", $chunk['TimeOffsets'][$i] - $LastTimeOffset),-6,6);  //負の表示対応 (DDR4th)
					}

					$DeltaOffset = $chunk['TimeOffsets'][$i] - $LastTimeOffset;

					switch(($chunk['Values'][$i])){
						case hexdec('0401'): $func_title = "Start  GameMode  ?";       break;
						case hexdec('0102'): $func_title = "Start  Music     ?";       break;
						case hexdec('0202'): $func_title = "Delay  TimeOffset?";       break;
						case hexdec('0502'): $func_title = "Unknown(5th-)    ?";       break;
						case hexdec('0302'): $func_title = "Finish GameMode";          break;
						case hexdec('0402'): $func_title = "Buffer length    ?";       break;
						default:             $func_title = "Unknown";                  break;
					} // end switch.

					$offset_hex = '';

					
					printf("[02:BFC][(%5$6s) %1$8d][func.%2$4X: %4$-18.18s ] Delta> Offset:(%6$6s)%3$7d \n",
					    $chunk['TimeOffsets'][$i],$chunk['Values'][$i],$DeltaOffset,$func_title,$offset_hex1,$offset_hexD);
					  //1                        ,2                   ,3           ,4          ,5           ,6

				} break;

			case 3:
				{
					//var_dump($chunk);
					$func_title = '';
					$playstyle = $chunk['Param'] & hexdec('ff');

					$LastTimeOffset = 0;
					if ($i > 0){ $LastTimeOffset = $chunk['TimeOffsets'][$i-1]; }
					$DeltaOffset = $chunk['TimeOffsets'][$i] - $LastTimeOffset;

					// step patturn init:
					switch($playstyle){
						case hexdec('14'):   $step = array('　','…','…','…','…','　','　','　','　','　','　','　',':  '); break; // single
						case hexdec('16'):   $step = array('…','…','…','…','…','…','　','　','　','　','　','　',':  '); break; // solo
						case hexdec('18'):   $step = array('　','…','…','…','…','　','　','…','…','…','…','　',':  '); break; // double
						case hexdec('24'):   $step = array('　','…','…','…','…','　','　','…','…','…','…','　',':  '); break; // battle
						default:             $step = array('　','　','　','　','　','　','　','　','　','　','　','　',':  '); break; // unknown
					} // end switch.


					// notes analyze:
					$step[12] = sprintf(":%1$02X",$chunk['Values'][$i]);
					
					// freeze arrow判定
					if ($chunk['Values'][$i]== 0) {

						// freeze arrow
						$l = array_shift($laststep);
						$p = 0;
						$step[$l] = '＃';
						switch($l){
							case  7: case  8: case  9: case 10:          { $p = 6; } break;
							case  1: case  2: case  3: case  4: default: { $p = 5; } break;
						} // end switch

						$step[$p] = 'L' . ($lastcount - (count($laststep)));
					
					
					} else { // nomal arrow or shock arrow

						$laststep  = array(); // 通常：1つ前の譜面情報をクリア
						$lastcount = 0;

						if ( $playstyle == hexdec('14') || $playstyle == hexdec('18') || $playstyle == hexdec('24') ) { //solo以外
							if ($chunk['Values'][$i] & hexdec('01')){ $step[ 1] = '←'; array_push($laststep, 1); $lastcount++;}
							if ($chunk['Values'][$i] & hexdec('02')){ $step[ 2] = '↓'; array_push($laststep, 2); $lastcount++;}
							if ($chunk['Values'][$i] & hexdec('04')){ $step[ 3] = '↑'; array_push($laststep, 3); $lastcount++;}
							if ($chunk['Values'][$i] & hexdec('08')){ $step[ 4] = '→'; array_push($laststep, 4); $lastcount++;}
							if (($chunk['Values'][$i] & hexdec('0F')) == hexdec('0F')){ $step[ 1] = '◆'; $step[ 2] = '◆'; $step[ 3] = '◆'; $step[ 4] = '◆'; $step[ 5] = '衝';} //Shock Arrow

							if ($i<$e-1){ // 最後じゃなければ
								if (($chunk['Values'][$i] & hexdec('0F')) && $chunk['Values'][$i+1] == hexdec('00')){ $step[ 5] = '長';} // freeze arrow
							}

						}

						if ( $playstyle == hexdec('16') ) { //solo
							if ($chunk['Values'][$i] & hexdec('01')){ $step[ 0] = '←'; }
							if ($chunk['Values'][$i] & hexdec('10')){ $step[ 1] = '＼'; }
							if ($chunk['Values'][$i] & hexdec('02')){ $step[ 2] = '↓'; }
							if ($chunk['Values'][$i] & hexdec('04')){ $step[ 3] = '↑'; }
							if ($chunk['Values'][$i] & hexdec('40')){ $step[ 4] = '／'; }
							if ($chunk['Values'][$i] & hexdec('08')){ $step[ 5] = '→'; }
						} // end if

						if ( $playstyle == hexdec('18') || $playstyle == hexdec('24')  ) { // double or battle
							if ($chunk['Values'][$i] & hexdec('10')){ $step[ 7] = '←'; array_push($laststep, 7); $lastcount++;}
							if ($chunk['Values'][$i] & hexdec('20')){ $step[ 8] = '↓'; array_push($laststep, 8); $lastcount++;}
							if ($chunk['Values'][$i] & hexdec('40')){ $step[ 9] = '↑'; array_push($laststep, 9); $lastcount++;}
							if ($chunk['Values'][$i] & hexdec('80')){ $step[10] = '→'; array_push($laststep,10); $lastcount++;}
							if (($chunk['Values'][$i] & hexdec('F0')) == hexdec('F0')){ $step[ 7] = '◆'; $step[ 8] = '◆'; $step[ 9] = '◆'; $step[10] = '◆'; $step[ 6] = '衝';} //Shock Arrow

							if ($i<$e-1){ // 最後じゃなければ
								if (($chunk['Values'][$i] & hexdec('F0')) && $chunk['Values'][$i+1] == hexdec('00')){ $step[ 6] = '長';} // freeze arrow
							}

						} // end if
					} // end if (Freeze arrow)

					// output:
					printf("[03:STP][(%1$6X) %1$8d][",$chunk['TimeOffsets'][$i]);
					print implode("", $step);
					printf("] Delta> Offset:(%1$6X)%1$7d \n",$DeltaOffset);

				} break;
				
			default: break;
		} // end switch.
	} // end for.

	return;

} //end function

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////