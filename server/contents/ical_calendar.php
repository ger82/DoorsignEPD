<?php
    checkFreeType();
	
	$fontSize = 0.5*$scale;
	
	$cursorX = $fontSize * 1.5;
	imagettftext( $im, $fontSize, 90, $cursorX, 370, $red, $DEFAULT_FONT[ 'bold' ], "Termine der kommenden 7 Tage" );
	$cursorX += 5;
	imageline( $im, $cursorX, 374, $cursorX, 10, $black );
	$cursorX += 15;
	
	$fontSize = 0.4*$scale;
	
	/**
	 *  http://sabre.io/vobject/
	 *  https://github.com/sabre-io/vobject
	 *  Here you can find the vobject Library. This is necessary to process the ical calendar.
	 **/
	require 'vobject/vendor/autoload.php';
	use
		Sabre\VObject;

	$calData = [];

	/**
	 * https://username:passwort@mydomain.net/owncloud/remote.php/dav/calendars/username/personal?export
	 * This example show you how to import a calendar form owncloud with name "personal"
	 * You must replace username and passwort with real data
	 * http://evertpot.com/resources/files/posts/icalendartest.ics
	 * This is an example from the former maintainer of the sabre project.
	 *
	 * Here you can insert more then one calendar / ical file
	 **/
	$calendarAdress = ['icalendartest.ics'];

	foreach($calendarAdress as $adress) {
		$calendar = VObject\Reader::read( file_get_contents($adress) );

		$heute = new DateTime('2010-11-20');
		$kommendeTage = new DateTime('+7 days');
		$newVCalendar = $calendar->expand( $heute, $kommendeTage );
		
		if(!empty($newVCalendar->vevent)){
			foreach($newVCalendar->vevent as $event) {

				/**
				 *  This Website is helpful for working with DateTime
				 *  https://web.archive.org/web/20160403000844/http://www.paulund.co.uk:80/datetime-php
				 **/
				$dtstart = new DateTime( (string)$event->dtstart );
				
				if( !empty( $event->dtend ) ){
					$dtend = new DateTime( (string)$event->dtend );
					$tageDifferenz = $dtstart->diff( $dtend );
				}
				/**
				 *  A distinction is made here between single and multi-day events.
				 **/
				if( empty( $tageDifferenz->days ) ){
					$iEnde = 1;
				}else{
					$iEnde = $tageDifferenz->days;
				}
				
				for($i = 1; $i <= $iEnde; $i++){
					/**
					 *  The if and the date adjustment are necessary so that
					 *  events lasting several days are not only displayed on the first day.
					 **/
					if($i > 1){
						$dtstart->modify('+1 day');
					}
					if($dtstart->getTimestamp() >= $today->getTimestamp() ){
						$calData[] = [ (string)$dtstart->format('d-m-Y H:i'), (string)$event->summary ];
					}
				}	

			}
		}
	}

	/**
	 * Here you you can find more information about multisort
	 * https://secure.php.net/manual/de/function.array-multisort.php
	 **/
	$ord = array();
	foreach ($calData as $key => $value){
		$ord[] = strtotime($value['0']);
	}
	array_multisort($ord, SORT_ASC, $calData);
	/**
	 * This array contains the individual weekday names in German.
	 **/
	$weekDaysNameFull = array("Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Sonnabend");

	$datum = "";
	$i = 1;
	foreach($calData as $event) {
		
		if($i > 1){
			$cursorX += $fontSize * 1.5;
		}
		
		$datetime = new DateTime($event[0]);
		
		if( empty($datum) || $datum != $datetime->format('d-m-Y') ){
			$cursorX += $fontSize * 1.5;
			$datum = $datetime->format('d-m-Y');
			$valueText = $weekDaysNameFull[ $datetime->format('w') ] . "  " . $datetime->format('d.m.Y');
			imagettftext( $im, $fontSize, 90, $cursorX, 370, $black, $DEFAULT_FONT[ 'bold' ],  $valueText );
			$cursorX += $fontSize * 1.5;
		}
		/**
		 * This is for example for birthdays which should be displayed all day.
		 */
		if($datetime->format('H:i') == "00:00"){ 
			imagettftext( $im, $fontSize, 90, $cursorX, 370, $black, $DEFAULT_FONT[ 'regular' ], $event[1] );
		}else{
			$valueText = $datetime->format('H:i') ." -> ". $event[1];
			imagettftext( $im, $fontSize, 90, $cursorX, 370, $black, $DEFAULT_FONT[ 'regular' ], $valueText );
		}
		$i++;
	}
?>
