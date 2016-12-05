<?php
error_reporting(E_ALL);
$myfile = $_REQUEST['war'];

if ($myfile == "") { $myfile = $argv[1]; }

if (!file_exists("war/".$myfile)) {
    $myfile .= ".sq3";
    if (!file_exists("wars/".$myfile)) {
        $myfile = "";
    }
}
$results = array();
//echo "File: $myfile\n";
if ($myfile != '') {
    $db = new SQLite3("wars/$myfile");
    $sql = "select distinct(KD) from provs order by Acres desc";
    $result = $db->query($sql);
    $mykds = array();
    $x = 1;
    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
        $row['month'] = $x;
        $mykds[] = $row['KD'];
        $x++;
    }

    $x = 1;
    foreach ($mykds as $kd)
    {
        $sql = "select Myname,Acres from provs where KD = '$kd' order by Acres desc";
        $result = $db->query($sql);
        while ($row = $result->fetchArray(SQLITE3_ASSOC))
        {
            $results["kd$x"][] = $row;
        }
        $x++;
    }



    $results['kds'] = $mykds;

}


echo json_encode($results);
?>