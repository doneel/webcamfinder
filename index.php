
<!-- Some basic html to allow php code execution to be displayed. --!>
<html>
     <body>
          <?php startSearch(); ?>
     </body>
</html>


<?php
header( 'Content-type: text/html; charset=utf-8' );


/* * function startSearch
 *
 * This is the boss method that organizes all the other pieces.
 * It's going to load the last known correct ip from a file, and then check it.
 *   If it's not the right one, it will use the nextGuess function to get adjacent ips over and over again.
 */
function startSearch(){
	$first_guess = loadPreviousGuess(); //reads the guess from a file
        
        /* When we store an ip and look it up, we store it as a 'string', which looks like any word to the comptuer.
         * But to be able to do even basic math on it, like figure out the next ip, they have to be numbers - you can't just add and subtract numbers from words.
         * This piece of code splits the ip into a vector of 4 numbers for the 4 parts of the ip.
         */
        $addressArray = explode('.', $first_guess);
	$numArray = array(intval($addressArray[0]), intval($addressArray[1]), intval($addressArray[2]), intval($addressArray[3]));

        /* Since we want to guess what's closest to our first guess we want to remember it.
         * But we also want to have another variable that represents our current guess that we test.
         */
	$guess = $first_guess;

          /* num functions as the offset from our starting guess at the ip.
           * Every time we go through the loop below, we increment num.
           * The nextGuess method will use this figure out the next closest ip we haven't tried.
           */
	$num = 1;

        ob_start();// these ob_start and ob_flush should print out to the screen, but it's the browser's choice to display or not. When I test it, it takes ~30 seconds before it starts printing out.
	while(checkIP($guess) === False){
		echo $guess . "<br />";//display on the screen that we tried this address
                
                ob_end_flush();
                flush();
                
                $guess = nextGuess($numArray, $num); //use the nextGuess method to determine the next ip we want to try.
                
		$num = $num + 1;


                /* There are only 255 * 255 possible ip addressess without changing the first two parts of the ip addresses. Since we wrap around, we'll get to all them in 255 * 255 iterations. 
                 */
                if($num > 256*256) return;

	}
        
        echo "<a href=http://$guess>$guess</a>"; //turn the correct ip into a clickable link
        writeKnownLocation($guess); //write the correct ip to a file.
          ob_end_flush();
}


/*
 * function nextGuess
 *
 * We want to look at the ip's closest to our last known location, which means getting ips above and below our starting address.
 *
 * If you look above at how we use it, we pass in the starting guess and 'num', which gets incremented by 1 every time we try a new address.
 * Next guess uses $num as an offset and gives the next closest but not-yet-tried ip to our starting guess.
 *
 * It alternates using ip's above and then an ip below the starting address by dedicating odd 'num' values to ips above and even 'num' values to ips below our starting guess.
 */
function nextGuess($addressNums, $num){

          /* First, the even case. */
	if($num % 2 === 0){
                
             /* We're going to use 'num' as an offset, so if $num = 4, then we'll move down 4 ip addresses. However since we only move down on even values, we need divide num by 2 so we don't skip all the odd values. i.e. we want the ips 1,2,3,4 below, not just 2 and 4 below even though we'll only reach this part of code when num is an even number. */
                $num = ($num / 2); 		

                /* If our starting guess is 70.33.13.2 and we want to try the ip 5 slots below us, we can't just subtract 5 from 2. This part of the code will do the carry over operation so we would get 70.33.12.254 instead.
                 * When you see $addressNums[3], that means the last part '254' of the ip, and $addressNums[2] would be the 2nd to last part, the '12' or '13' most likely.
                 * If the 2nd to last part of the ip hits 0 or 255, we wrap it around without changing the 2nd part of the ip, so it'll start with the very high numbers and decrease from there 
                 * */ 
		while($num > $addressNums[3]){
			$num -= $addressNums[3];
                        $addressNums[2]--;
                        if($addressNums[2] < 0){
                             $addressNums[2] = 255;
                        }
                        $addressNums[3] = 255;
                        $num--;//1 more for switching [3] and [2]
		}
		$addressNums[3] = $addressNums[3] - $num; 
		
        }

        /* This is what we're going to do if $num is positive.*/
	else{
		$num = round($num / 2); //Round the division result so we don't have half numbers from division.

                /* Same idea as above. This is a type of 'saturated addition' where we're not going to let the last part of our ip address go above 255. If so, we bump up the 2nd to last portion of the ip and reset the last part to 0. 
                 * */        
		while($num + $addressNums[3] > 255){
			$num -= (255 - $addressNums[3]);
                        $addressNums[2]++;
                        if($addressNums[2] > 255){
                              $addressNums[2] = 0;
                        }
                        $addressNums[3] = 0;
                        $num--;//1 more for [2] switching [3]
		}
		$addressNums[3] = $addressNums[3] + $num;
	}

        /* Since we were doing math on the values, I had them stored as a vector of numbers.
         * We can't look up a website by a vector of numbers though, we need a 'string' or a list of characters to use as a web address, so this part just sticks all the numbers together with periods in between to make it a valid ip address.
         * */
	$string = strval($addressNums[0]) . "." . strval($addressNums[1]) . "." . strval($addressNums[2]) . "." . strval($addressNums[3]);
	
	return $string;	
}

/*
 * function loadPreviousGuess
 *
 * We store the last place we found the ip in the file ip.txt.
 * Here, we read that file and return the ip in the file so we can start our search there.
 */
function loadPreviousGuess(){
	$guess = file_get_contents('./ip.txt', FILE_USE_INCLUDE_PATH);
	return $guess;
}


/*
 * function writeKnownLocation
 *
 * This will write an ip address to the file.
 * We call it after we find the right one so we can check this address first next time.
 */
function writeKnownLocation($ip){
     $file = './ip.txt';
     $handle = fopen($file, 'w') or die('Cannot open file: '.$my_file);
     fwrite($handle, $ip);
     fclose($handle);
}


/*
 * function checkIP
 *
 * Given a specific ip, this function will open up that page and scan it to determine whether it's the webcam page.
 * The way it checks is very simple - it just scans the page for the sequence of characters "<TITLE>Start</TITLE>".
 *   Technically it's possible that some other webpage could contain that string, but I find it unlikely.
 *   We can always modify it to be stricter if we get false positives.
 *
 *   If it gets a positive hit, it'll return 'True' to signify that this is the correct ip.
 */
function checkIP($ip){

        /* We can't open the page to figure out if it's the right one without logging in, so allow the html request to log in. */     
	$username="johnoneel";
	$password="18montana";

        /* Our text to search for a la needle in a haystack */        
	$needleText = "<TITLE>Start</TITLE>";	

        /* Curl is utility to get data from websites.
         * I just looked up how to use it and copy/pasted most of it.
         */
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $ip);
	curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
	curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,1);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch,CURLOPT_TIMEOUT,1);
	
	$output = curl_exec($ch);
	curl_close($ch); //close the connection
        
        /*
         * strpos will return the position of $needleText in $output.
         * If it can't find it anywhere, it will return 'False'.
         * We don't care about the position, we just care that it's somewhere, so if strpos doesn't tell us 'False', then we return True to say that this is the right ip.
         */
        if(strpos($output,$needleText) != False){
		return True;
	}

        /* If we haven't returned 'True', that means we didn't find our text in the page.
         * In this case, we return 'False' to say this isn't the right ip.
         */ 
        return False;
}

?>
