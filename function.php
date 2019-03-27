<?php
// Check and sanitize Title
function check_title ( $title ) {
  $title = preg_replace("/[^a-zA-Z]/", "", $title);
  $title = filter_var($title, FILTER_SANITIZE_STRING);

  return $title;
}

// Check, sanitize and transform contact numbers
function check_number( $number ) {
  $number = preg_replace("/[^0-9]/", "", $number);
  $num_digits = strlen($number);

  // If the length of the number is less than 7, number is invalid
  // Landline has a minimum of 7 digits
  // Return empty instead
  if ($num_digits < 7) {
    $number = "";
  }

  // Assuming this is NZ format contact numbers, and
  // My knowledge on NZ numbering format is limited from what I have searched
  // Although, I think there's still more efficient way of doing this
  // But as of now, this is how I transform the contact number prefix
  if (!preg_match("/^64|^09/", $number) && $number != "") {
    if ($num_digits < 9) {
      if($num_digits == 8 && preg_match("/^9/", $number)) {
        $number = "0{$number}";
      }
      else
        $number = "09{$number}";
    }
    elseif ($num_digits >= 9 && preg_match("/^0/", $number)) {
        $number = substr_replace($number, '64', 0, 1);
    }
    else {
        $number = "64{$number}";
    }
  }
  elseif (preg_match("/^64/", $number)) {
    if ($num_digits == 7) {
      $number = "09{$number}";
    }
    else
    if ($num_digits == 8) {
      $number = "64{$number}";
    }
  }
  elseif (preg_match("/^09/", $number)) {
    if ($num_digits == 8) {
      $number = substr_replace($number, '09', 0, 1);
    }
    else
    if ($num_digits > 9) {
      $number = substr_replace($number, '64', 0, 1);
    }
  }
  return $number;
}

// This function is created by Armand Niculescu for correcting names
// https://www.media-division.com/correct-name-capitalization-in-php/
// Made some corrections on his codes as it does not reads single quotes like O'Malley
function check_names( $string ) {
  $word_splitters = array('%20', '-', "O%27", "L%27", "D%27", 'St.', 'Mc');
  $lowercase_exceptions = array('the', 'van', 'den', 'von', 'und', 'der', 'de', 'da', 'of', 'and', "l'", "d%27");
  $uppercase_exceptions = array('III', 'IV', 'VI', 'VII', 'VIII', 'IX');

  $string = filter_var(strtolower($string), FILTER_SANITIZE_ENCODED);
  foreach ($word_splitters as $delimiter)  {
    $words = explode($delimiter, $string);
    $newwords = array();

    foreach ($words as $word) {
      if (in_array(strtoupper($word), $uppercase_exceptions))
        $word = strtoupper($word);
      else
      if (!in_array($word, $lowercase_exceptions))
        $word = ucfirst($word);

      $newwords[] = $word;
    }

    if (in_array(strtolower($delimiter), $lowercase_exceptions))
      $delimiter = strtolower($delimiter);

    $string = join($delimiter, $newwords);
  }
  return $string;
}

// Checking acronym on business names
// Same concept with check_names function with few customisation
function check_acronym( $string ) {
  $word_splitters = array('%20', '.');
  $uppercase_exceptions = array('ABC'); // Dictionary for acronyms without delimiter

  $string = filter_var(strtolower($string), FILTER_SANITIZE_ENCODED);
  foreach ($word_splitters as $delimiter) {
      $words = explode($delimiter, $string);
      $newwords = array();

      foreach ($words as $word) {
        if (in_array(strtoupper($word), $uppercase_exceptions))
          $word = strtoupper($word);
        else
          $word = ucfirst($word);

        $newwords[] = $word;
      }
      $string = join($delimiter, $newwords);
  }
  return $string;
}

// Checking correct date format
function check_date( $date ) {
  return date('m/d/Y', strtotime($date));
}

// Check capitalization of addresses
function check_address( $string ) {
  $string = ucwords(strtolower($string));
  $string = filter_var($string, FILTER_SANITIZE_ENCODED);

  return $string;
}

// Initialise database
function start_database() {
  // Database credentials on my localhost
  $servername = '127.0.0.1';
  $dbname = 'ezyvet';
  $username = 'root';
  $password = '';
  $port = '80';
  try {
    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
    // set the PDO error mode to exception
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "EzyVet DB connected successfully! <br />";
  } catch(PDOException $e)
  {
    echo "EzyVET DB connection failed: " . $e->getMessage();
  }
  return $conn;
}

// Stop database connection
function stop_database() {
  //
}
?>
