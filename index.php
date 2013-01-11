<html>
<body>
<?php startSearch(); ?>

</body>
</html>


<?php


function startSearch(){
	$first_guess = loadPreviousGuess();
	$addressArray = explode('.', $first_guess);	
	
	$numArray = array(intval($addressArray[0]), intval($addressArray[1]), intval($addressArray[2]), intval($addressArray[3]));
	
	$guess = $first_guess;
	
	$num = 1;

	ob_start();	
	while(checkIP($guess) === False){
		echo $guess . "<br />";
		ob_flush();
		$guess = nextGuess($numArray, $num);
		$num = $num + 1;
		if($num > 255*255) return;
	}
	echo "<a href=http://$guess>$guess</a>";
	
}

function nextGuess($addressNums, $num){

	if($num % 2 === 0){
		//subtraction, saturated
		$num = ($num / 2);
		
		
		while($num > $addressNums[3]){
			$num -= $addressNums[3];
			$addressNums[2]--;
			$addressNums[3] = 255;
		}
		$addressNums[3] = $addressNums[3] - $num; 
		
	}
	else{
		$num = round($num / 2);
		
		while($num + $addressNums[3] > 255){
			$num -= (255 - $addressNums[3]);
			$addressNums[2]++;
			$addressNums[3] = 0;
		}
		$addressNums[3] = $addressNums[3] + $num;
	}

	$string = strval($addressNums[0]) . "." . strval($addressNums[1]) . "." . strval($addressNums[2]) . "." . strval($addressNums[3]);
	
	return $string;	
}

function loadPreviousGuess(){
	$guess = file_get_contents('./ip.txt', FILE_USE_INCLUDE_PATH);
	return $guess;
}


function checkIP($ip){
	
	$username="johnoneel";
	$password="18montana";
	
	$needleText = "<TITLE>Start</TITLE>";	

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $ip);
	curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,1);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	
	$output = curl_exec($ch);
	curl_close($ch);
	if(strpos($output,$needleText) != False){
		return True;
	}
	return False;
}

?>
