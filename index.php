<!--****************************

Name: Lionell Carlo Paquit
Project: EzyVet Practical Test

Requirements:
  ▪ All data from the CSV must be processed into the provided tables.
  ▪ Data must be sanitized to be safely inserted
  ▪ Data must be consistent when exported as is when imported.
  ▪ First and Last names must have the first letter capitalized.
  ▪ Business Names must have acronyms be capitalized.
  ▪ Mobile numbers must have a 64 prefixed
  ▪ Landline numbers must have 09 prefixed
  ▪ The `contact_id` field in the address and phone tables must match to an existing record with the same value in the `id` field of the contact table
  ▪ You can use any language for any code that is written. Preferably use PHP 7.0 where possible.
  ▪ MySQL 5.7 is to be used

NOTES:
  ▪ Rename "contact_list .csv" to "contact_list.csv"
  ▪ Added type "Fax" in phone attribute "type ENUM"
  ▪ I asked what "name" attributes is in phone table, and I was told that it is the same as the type of number.
    Although I followed the instruction, it seems that data is redundant.
  ▪ Codes here are not the cleanest or the most efficient, more time and I can do refactoring on some of the lines.
    I'm a bit rusty in coding PHP as I was focusing on R and Python lately. :)

********************************-->

<!DOCTYPE html>
<html>
  <link rel="stylesheet" type="text/css" href="style.css">
<body>

<form action="<?php echo $_SERVER['REQUEST_URI'];?>" method="POST" enctype="multipart/form-data">
    <span>Select csv file to pipe (As for now, only CSV files are allowed):</span>
    <input class="butn" type="file" name="fileToUpload" id="fileToUpload">
    <input class="butn" type="submit" value="CLICK HERE to Pipe Data" name="submit">
</form>

</body>
</html>

<?php
if(isset($_POST["submit"]) && isset($_FILES["fileToUpload"])) {
  error_reporting(E_ALL | E_STRICT);

  include 'function.php'; // all functions are in this file

  $target_file = realpath($_FILES["fileToUpload"]["name"]);
  print "<br />Target file to be imported: " . $target_file . "<br />";

  // The nested array to hold all the arrays
  $the_big_array = array();
  $args = array (
    'company_name'  =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_acronym'
                            ),
    'title'         =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_title'
                            ),
    'first_name'    =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_names'
                            ),
    'last_name'     =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_names'
                            ),
    'date_of_birth' =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_date'
                            ),
    'street1'       =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_address'
                            ),
    'street2'       =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_address'
                            ),
    'suburb'        =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_address'
                            ),
    'city'          =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_address'
                            ),
    'post_code'     =>  FILTER_SANITIZE_NUMBER_INT,
    'home_number'   =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_number'
                            ),
    'fax_number'    =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_number'
                            ),
    'work_number'   =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_number'
                            ),
    'mobile_number' =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_number'
                            ),
    'other_number'  =>  array('filter'  =>  FILTER_CALLBACK,
                              'options' =>  'check_number'
                            ),
    'notes'         =>  FILTER_SANITIZE_ENCODED
  );
  $keys = array('company_name','title','first_name','last_name','date_of_birth',
          'street1','street2','suburb','city','post_code','home_number',
          'fax_number','work_number','mobile_number','other_number','notes');


  // Open the file for reading
  if (($handle = fopen($target_file, "r")) !== FALSE)
  {
    $header = fgetcsv($handle, 1000, ",");
    // Each line in the file is converted into an individual array that we call $data
    // The items of the array are comma separated
    $i = 0;
    while (($data = fgetcsv($handle, 1000, ",")) !== FALSE)
    {
      // Each individual array is being pushed into the nested array
      // Combine keys and data array in order to use the fitler_var_array function
      $the_big_array[$i] = filter_var_array(array_combine($keys, $data), $args);

      $i++;
    }

    // Close the file
    fclose($handle);
  }
  $db = start_database();
  $startTime = microtime(true);

  // Inserting data to contact table
  try {
    $db->beginTransaction();
    $stmt = $db->prepare("INSERT INTO contact (title, first_name, last_name, company_name, date_of_birth, notes)
                          VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($the_big_array as $inArr) {
        $date = date('Y-m-d', strtotime($inArr["date_of_birth"]));
        $clause = array ( $inArr["title"],$inArr["first_name"],$inArr["last_name"],
                  $inArr["company_name"],$date,$inArr["notes"]
                  );
        $stmt->execute($clause);
    }
    $db->commit();
    print "NEW contact added to EzyVet data warehouse. <br />";
  } catch(PDOException $ex) {
    $db->rollback();
    print "Connection failed: " . $ex->getMessage();
  }

  // Inserting data to address and phone table
  try {
    $db->beginTransaction();
    $qry_id = $db->prepare("SELECT id FROM contact WHERE first_name=? AND last_name=?
                            AND company_name=? AND date_of_birth=? LIMIT 1");
    $ins_addr = $db->prepare("INSERT INTO address (contact_id, street1, street2, suburb, city, post_code)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $ins_phone = $db->prepare("INSERT INTO phone (contact_id, name, content, type)
                              VALUES (?, ?, ?, ?)");

    foreach ($the_big_array as $inArr) {
        $date = date('Y-m-d', strtotime($inArr["date_of_birth"]));

        // Check and fetch contact id if same firstname, lastname, companyname and birthdate
        $qry_id_clause = array ( $inArr["first_name"],$inArr["last_name"],
                  $inArr["company_name"],$date
                  );
        $qry_id->execute($qry_id_clause);
        $id = $qry_id->fetch(PDO::FETCH_ASSOC);

        // Insert data to address table using the contact_id selected
        $ins_addr_clause = array ( $id["id"],$inArr["street1"],$inArr["street2"],
                  $inArr["suburb"],$inArr["city"],$inArr["post_code"]
                );
        $ins_addr->execute($ins_addr_clause);

        // Insert data to phone table using selected contact_id as foreign key
        // These is not a clean code as this can be refactored
        // I did not have enough time to write a cleaner code =)
        if ($inArr["home_number"] != "" && !is_null($inArr["home_number"])) {
          $ins_phone->execute ( array( $id["id"],"Home",$inArr["home_number"],"Home")
            );
        }
        if ($inArr["fax_number"] != "" && !is_null($inArr["fax_number"])) {
          $ins_phone->execute ( array( $id["id"],"Fax",$inArr["fax_number"],"Fax")
            );
        }
        if ($inArr["work_number"] != "" && !is_null($inArr["work_number"])) {
          $ins_phone->execute ( array( $id["id"],"Work",$inArr["work_number"],"Work")
            );
        }
        if ($inArr["mobile_number"] != "" && !is_null($inArr["mobile_number"])) {
          $ins_phone->execute ( array( $id["id"],"Mobile",$inArr["mobile_number"],"Mobile")
            );
        }
        if ($inArr["other_number"] != "" && !is_null($inArr["other_number"])) {
          $ins_phone->execute ( array( $id["id"],"Other",$inArr["other_number"],"Other")
            );
        }
    }

    $db->commit();
  } catch(PDOException $ex) {
    $db->rollback();
    print "Connection failed: " . $ex->getMessage();
  }

  $endTime = microtime(true);
  print ("Query took: ");
  print ($endTime-$startTime." seconds. <br />");

  //  Query data and show data into a table
  try {
    $db->beginTransaction();
    $table_contact = "SELECT contact.id,
                          contact.company_name,
                          contact.title,
                          contact.first_name,
                          contact.last_name,
                          contact.date_of_birth,
                          address.street1,
                          address.street2,
                          address.suburb,
                          address.city,
                          address.post_code,
                          contact.notes
                    FROM contact
                    INNER JOIN address ON address.contact_id = contact.id";

    // Prepare and execute contact and address table query
    $table_prep = $db->prepare($table_contact);
    $table_prep->execute();
    $table_contact_data = $table_prep->fetchAll(PDO::FETCH_ASSOC);

    // Prepare and execute phone table query
    $table_phone = $db->prepare("SELECT contact_id, type, content FROM phone");
    $table_phone->execute();
    $table_phone_data = $table_phone->fetchALL(PDO::FETCH_ASSOC);
    $db->commit();

    // Header of the table
    echo "<table style='width:100%' border='1'>";
    echo "<tr>
            <th>Business Name</th>
            <th>Title</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Date of Birth</th>
            <th>Address 1</th>
            <th>Address 2</th>
            <th>Suburb</th>
            <th>City</th>
            <th>Post Code</th>
            <th>Home Number</th>
            <th>Fax Number</th>
            <th>Work Number</th>
            <th>Mobile Number</th>
            <th>Other Number</th>
            <th>Notes</th>
          </tr>";

    // Ugly code but this is what I can think for now.
    // Get the contact numbers
    foreach ($table_contact_data as $contact_row)  {
        $home_num = "";
        $fax_num = "";
        $work_num = "";
        $mobile_num = "";
        $other_num = "";
        foreach($table_phone_data as $phone_row) {
          if($contact_row["id"] == $phone_row["contact_id"]) {
            if($phone_row["type"] == "Home")
              $home_num = $phone_row["content"];
            if($phone_row["type"] == "Fax")
              $fax_num = $phone_row["content"];
            if($phone_row["type"] == "Work")
              $work_num = $phone_row["content"];
            if($phone_row["type"] == "Mobile")
              $mobile_num = $phone_row["content"];
            if($phone_row["type"] == "Other")
              $other_num = $phone_row["content"];
          }
        }

      // Insert extracted data on table, data were decoded first
      echo "<tr>
              <td>".urldecode($contact_row['company_name'])."</td>
              <td>".$contact_row["title"]."</td>
              <td>".urldecode($contact_row["first_name"])."</td>
              <td>".urldecode($contact_row["last_name"])."</td>
              <td>".date('m/d/y',strtotime($contact_row['date_of_birth']))."</td>
              <td>".urldecode($contact_row["street1"])."</td>
              <td>".urldecode($contact_row["street2"])."</td>
              <td>".urldecode($contact_row["suburb"])."</td>
              <td>".urldecode($contact_row["city"])."</td>
              <td>".$contact_row["post_code"]."</td>
              <td>".$home_num."</td>
              <td>".$fax_num."</td>
              <td>".$work_num."</td>
              <td>".$mobile_num."</td>
              <td>".$other_num."</td>
              <td>".urldecode($contact_row["notes"])."</td>
            </tr>";
    }
    echo "</table>";
  } catch(PDOException $ex) {
    $db->rollback();
    print "Connection failed: " . $ex->getMessage();

  }
}
?>
