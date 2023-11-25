<?php
$configfilePath = 'app/config.php';

if(isset($_POST['index'])) {

    if((isset($_POST['server'])) && $_POST['server'] <> '') {
        $server=trim($_POST['server']);
    } else {
        $server = "localhost";
    }
	if(isset($_POST['username'])) $username=trim($_POST['username']);
	if(isset($_POST['password'])) $password=trim($_POST['password']);
	if(isset($_POST['database'])) $database=trim($_POST['database']);
	if(isset($_POST['numrecordsperpage'])) $numrecordsperpage=$_POST['numrecordsperpage'];

    if((isset($_POST['appname'])) && $_POST['appname'] <> '') {
        $appname=trim($_POST['appname']);
    } else {
        $appname = "Database Admin";
    }

    /* Attempt to connect to MySQL database */
	$link = mysqli_connect($server, $username, $password, $database);
	// Check connection
	if($link === false)
		die("ERROR: Could not connect. " . mysqli_connect_error());

	/* Clean up User inputs against SQL injection */
	foreach($_POST as $k => $v) {
		$_POST[$k] = mysqli_real_escape_string($link, $v);
	}

	if (!file_exists('app'))
		mkdir('app', 0777, true);


    $helpersfilename = 'helpers.php';
    $handle = fopen('helpers.php', "r") or die("Unable to open Helpers file! Please check your file permissions.");;
    $helpers = fread($handle, filesize($helpersfilename));
    fclose($handle);

    $helpersfile = fopen("app/".$helpersfilename, "w") or die("Unable to create Helpers file! Please check your file permissions");
    fwrite($helpersfile, $helpers);
	fclose($helpersfile);

    $configfile = fopen($configfilePath, "w") or die("Unable to open Config file!");

    $configfileTemplatePath = 'templates/config.php';
    fopen($configfileTemplatePath, "r") or die("Unable to open Config template file!");

    // Read config file template
    $templateContent = file_get_contents($configfileTemplatePath);

    // Replace placeholders with actual values
    $replacements = [
        '{{db_server}}' => $server,
        '{{db_name}}' => $database,
        '{{db_user}}' => $username,
        '{{db_password}}' => $password,
        '{{no_of_records_per_page}}' => $numrecordsperpage,
        '{{appname}}' => $appname,
    ];

    foreach ($replacements as $placeholder => $realValue) {
        $templateContent = str_replace($placeholder, $realValue, $templateContent);
    }

    $configfile = fopen($configfilePath, "w") or die("Unable to open Config file!");
    if (fwrite($configfile, $templateContent) === false) die("Error writing Config file!");
    fclose($configfile);

}
require $configfilePath;

if(isset($_POST['submit'])){
    $tablename = $_POST['tablename'];
    $fkname = $_POST['fkname'];

    $sql = "ALTER TABLE $tablename DROP FOREIGN KEY $fkname";
    if ($result = mysqli_query($link, $sql)) {
        echo "The foreign_key '$fkname' was deleted from '$tablename'";
    } else {
        echo("Something went wrong. Error description: " . mysqli_error($link));
    }
}

if(isset($_POST['addkey'])){
    $primary = $_POST['primary'];
    $fk = $_POST['fk'];

    $split_primary=explode('|', $primary);
    $split_fk=explode('|', $fk);

    $fk_name = $split_fk[0].'_ibfk_1';

    $ondel_val = $_POST['ondelete'];
    $onupd_val = $_POST['onupdate'];

    switch ($ondel_val) {
        case "cascade":
           $ondel = "ON DELETE CASCADE";
            break;
        case "setnull":
            $ondel = "ON DELETE SET NULL";
            break;
       case "restrict":
           $ondel = "ON DELETE RESTRICT";
           break;
        case "noaction":
            $ondel = "ON DELETE NO ACTION";
            break;
       default:
           $ondel = "";
    }

    switch ($onupd_val) {
        case "cascade":
           $onupd = "ON UPDATE CASCADE";
            break;
        case "setnull":
            $onupd = "ON UPDATE SET NULL";
            break;
       case "restrict":
            $onupd = "ON UPDATE RESTRICT";
            break;
        case "noaction":
            $onupd = "ON UPDATE NO ACTION";
            break;
       default:
            $onupd = "";
    }

    $sql = "ALTER TABLE $split_fk[0] ADD FOREIGN KEY $fk_name ($split_fk[1]) REFERENCES $split_primary[0]($split_primary[1]) $ondel $onupd;";

    if ($result = mysqli_query($link, $sql)) {
        echo "The foreign_key '$fk_name' was created from ' $split_fk[0]($split_fk[1])' to '$split_primary[0]($split_primary[1])'.";
    } else {
         echo("Something went wrong. Error description: " . mysqli_error($link));
    }
}

?>
<!doctype html>
<html lang="en">
<head>
    <title>Select Relations</title>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" integrity="sha384-9aIt2nRpC12Uk9gS9baDl411NQApFmC26EwAOH8WgZl5MYYxFfc+NcPb1dKGj7Sk" crossorigin="anonymous">
    <link rel="stylesheet" href="cruddiy.css">
</head>
<body>
<section class="py-5">
    <div class="container" style='max-width:75%'>
        <div class="row">
            <div class="col-md-12 mx-auto">
                <div class="text-center">
                    <h4 class="mb-0">Existing Table Relations</h4><br>
                    <fieldset>
                        <table class="table table-bordered">
                          <thead>
                            <tr>
                              <?php
                                $sql = "SELECT i.TABLE_NAME as 'Table Name', k.COLUMN_NAME as 'Foreign Key',
                                    k.REFERENCED_TABLE_NAME as 'Primary Table', k.REFERENCED_COLUMN_NAME as 'Primary Key',
                                    i.CONSTRAINT_NAME as 'Constraint Name', 'Delete' as 'Delete'
                                        FROM information_schema.TABLE_CONSTRAINTS i
                                        LEFT JOIN information_schema.KEY_COLUMN_USAGE k ON i.CONSTRAINT_NAME = k.CONSTRAINT_NAME
                                        WHERE i.CONSTRAINT_TYPE = 'FOREIGN KEY' AND i.TABLE_SCHEMA = DATABASE()";
                                if (($result = mysqli_query($link, $sql)) && $result->num_rows > 0) {
                                    $row = mysqli_fetch_assoc($result);
                                    foreach ($row as $col => $value) {
                                        echo "<th>";
                                        echo $col;
                                        echo "</th>";
									}
									echo "</thead><tbody>";
									mysqli_data_seek($result, 0);
									while($row = mysqli_fetch_array($result))
									{
										echo "<tr>";
										echo "<td>" . $row['Table Name'] . "</td>";
										echo "<td>" . $row['Foreign Key'] . "</td>";
										echo "<td>" . $row['Primary Table'] . "</td>";
										echo "<td>" . $row['Primary Key'] . "</td>";
										echo "<td>" . $row['Constraint Name'] . "</td>";
										echo "<td class='fk-delete'>";
										?><form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
										<?php
												echo '<input type="hidden" name="tablename" value="';
												echo $row['Table Name'] .'">';
												echo '<input type="hidden" name="fkname" value="';
												echo $row['Constraint Name'] . '">';
												echo "<button type='submit' id='singlebutton' name='submit' class='btn btn-danger'>Delete</button>";
												echo "</form></td>";
												echo "</tr>";
									}
								} else echo "</thead><tbody><tr><td>No relations found</td></tr>";
                            ?>
                                </tbody>
                                </table>
                <div class="text-center">
                    <h4 class="mb-0">Add New Table Relation</h4><br>
                      <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
<?php
                        $sql = "select TABLE_NAME as TableName, COLUMN_NAME as ColumnName from information_schema.columns where table_schema = '$db_name'";
                        $result = mysqli_query($link,$sql);
                        echo "<label>This column:</label>
                            <select name='fk' id='fk' style='max-width:20%;'><br>";
                        while ($column = mysqli_fetch_array($result)) {
                            echo '<option name="'.$column[0]. '|'.$column[1]. '  " value="'.$column[0].'|'.$column[1]. '">'.$column[0].' ('.$column[1].')</option>';
                        }
                        echo '</select>';

                        mysqli_free_result($result);
                        $result = mysqli_query($link,$sql);
                        echo "<label>has a foreign key relation to:</label>
                            <select name='primary' id='primary' style='max-width:20%'>";
                        while ($column = mysqli_fetch_array($result)) {
                            echo '<option name="'.$column[0]. '|'.$column[1]. '  " value="'.$column[0].'|'.$column[1]. '">'.$column[0].' ('.$column[1].')</option>';

                        }
                        echo '</select>';
?>
                       <select name='ondelete' id='ondelete' style='max-width:15%'>";
                            <option name="ondelete_action" value="">Pick action</option>
                            <option name="ondelete_cascade" value="cascade">On Delete: Cascade</option>
                            <option name="ondelete_setnull" value="setnull">On Delete: Set Null</option>
                            <option name="ondelete_restrict" value="restrict">On Delete: Restrict</option>
                            <option name="ondelete_noaction" value="noaction">On Delete: No Action</option>
                       </select>

                       <select name='onupdate' id='onupdate' style='max-width:15%'>";
                            <option name="onupdate_action" value="">Pick action</option>
                            <option name="onupdate_cascade" value="cascade">On Update: Cascade</option>
                            <option name="onupdate_setnull" value="setnull">On Update: Set Null</option>
                            <option name="onupdate_restrict" value="restrict">On Update: Restrict</option>
                            <option name="onupdate_noaction" value="noaction">On Update: No Action</option>
                       </select>
                                <label class="col-form-label mt-3" for="singlebutton"></label>
                                <button type="submit" id="singlebutton" name="addkey" class="btn btn-primary">Create relation</button>
                    </fieldset>
                </form>
            </div>
        </div>
    </div>
<hr>
On this page you can add new or delete existing table relations i.e. foreign keys. Having foreign keys will result in Cruddiy forms with cascading deletes/updates and dropdown fields populated by foreign keys. If it is not clear what you want or need to do here, it is SAFER to skip this step and move to the next step! You can always come back later and regenerate new forms.
<hr>
<form method="post" action="tables.php">
    <button type="submit" id="singlebutton" name="singlebutton" class="btn btn-success">Continue CRUD Creation Process</button>
</form>
</section>

<script src="https://code.jquery.com/jquery-3.5.1.min.js" integrity="sha256-9/aliU8dGd2tb6OSsuzixeV4y/faTqgFtohetphbbj0=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" integrity="sha384-Q6E9RHvbIyZFJoft+2mJbHaEWldlvI9IOYy5n3zV9zzTtmI3UksdQRVvoxMfooAo" crossorigin="anonymous"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" integrity="sha384-OgVRvuATP1z7JjHLkuOU7Xw704+h835Lr+6QL9UvYjZE3Ipu6Tp75j7Bh/kR0JKI" crossorigin="anonymous"></script>
</body>
</html>

