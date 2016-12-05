<?php
//init database
$uniqueid = uniqid();
if ((isset($_REQUEST['logdata']) or ($local)))
{
    $db = new SQLite3("wars/$uniqueid.sq3");
    $db->query("drop table if exists provs");
    $db->query("create table war (Id integer primary key, Warstart varchar, Warend varchar)");
    $db->query("create table attacks (Id integer primary key, KD1 varchar, KD2 varchar, Attackdate integer, Attacktime varchar, Attackmonth varchar, Attackday varchar, Attackyear varchar, Acres integer, Attacktype varchar, Attacker varchar, Target varchar, Line varchar)");
    $db->query("create table provs ( Id integer primary key, KD varchar, Myname varchar, Attacksmade integer, Attacksreceived integer, Acres integer, TM integer, Conq integer, Raze integer, Learn integer, Mass integer, Intra integer, Plunder integer, Ambush integer, Recon integer, Bounce integer)");
}
/** Error reporting */
//error_reporting(E_ALL);

$infile2 = $_REQUEST['logdata'];
$infile = preg_split("/\n/",$infile2);
$uniquehour = $_REQUEST['uniquehour'];

function aasort (&$array, $key) {
    $sorter=array();
    $ret=array();
    reset($array);
    foreach ($array as $ii => $va) {
        $sorter[$ii]=$va[$key];
    }
    arsort($sorter);
    foreach ($sorter as $ii => $va) {
        $ret[$ii]=$array[$ii];
    }
    $array=$ret;
}




$firstline = true;
$war = array();
foreach ($infile as $line)
{
    $line = trim($line);

    $tmp = preg_split("/ /",$line);
    $month = $tmp[0];

    $month2 = "";
    if ($month == "January") { $month2 = 1; }
    if ($month == "February") { $month2 = 2; }
    if ($month == "March") { $month2 = 3; }
    if ($month == "April") { $month2 = 4; }
    if ($month == "May") { $month2 = 5; }
    if ($month == "June") { $month2 = 6; }
    if ($month == "July") { $month2 = 7; }



    $day = preg_replace('/[^0-9]/','',$tmp[1]);
    $now = $tmp[0]." ".$tmp[1];
    $tmp2 = preg_split("/\t/",$tmp[2]);
    $now .= " ".$tmp2[0];
    $tmp3 = preg_split("/YR/",$tmp2[0]);
    $year = $tmp3[1];

    $now2 = "$year $day";


    $atime = $month." ".$day; //attack time
    $atime2 = mktime(0,0,0,$month2,$day,$year);


    if ($firstline)
    {
        $firstline = false;
        $startdate = $now;
        $sql = "insert into war(Warstart,Warend) values ('$now','$now')";
        $db->exec($sql);

    } else {
        $tmpnow = trim($now);
        if ($tmpnow != '')
        {
            $sql = "update war set Warend = '$tmpnow'";
            $db->exec($sql);
        }
    }

    if ((strpos($line,"captured") > 0 ) and (strpos($line,"recaptured") === false))
    {
        $atype = "TM";
        $tmp = preg_split("/\(/",$line);
        $tkd1 = preg_split("/\)/",$tmp[1]);
        $tkd2 = preg_split("/\)/",$tmp[2]);
        $kd1 = $tkd1[0];
        $kd2 = $tkd2[0];

        $tmp1 = preg_split("/\(/",$line);
        $tmp2 = preg_split("/ /",$tmp1[0]);
        $tmp3 = preg_split("/YR\d{1,2}/",$tmp1[0]);
        $mydate = $tmp2[0]." ".$tmp2[1]." ".$tmp2[3];
        $myname = trim($tmp3[1]);

        $tmp1 = preg_split("/from/",$tmp1[1]);
        if (strpos($tmp1[0],"invaded") > 0)
        {
            $tmp2 = preg_split("/invaded/",$tmp1[0]);
            $myname2 = trim($tmp2[1]);
        } else {
            $myname2 = trim($tmp1[1]);
        }

        $grab = preg_split("/captured/",$line);
        $grab2 = preg_split("/acres/",$grab[1]);
        $acres = chop(trim($grab2[0]));


        $war["$kd1"]['month']["$month"]['acres'] += $acres;
        $war["$kd2"]['month']["$month"]['acres'] -= $acres;


        $war["$kd1"]['acres'] += $acres;
        $war["$kd1"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['acres'] += $acres;
        $war["$kd1"]["$myname"]['tm'] += 1;
        $war["$kd1"]["$myname"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['attacksreceived'] += 0;


        $war["$kd1"]["$myname"]['Last'] = "$year $day";
        $war["$kd1"]["$myname"]['Unique'] += 1;



        $war["$kd2"]['acres'] -= $acres;
        $war["$kd2"]['attacksreceived'] += 1;
        $war["$kd2"][$myname2]['acres'] -= $acres;
        $war["$kd2"][$myname2]['attacksreceived'] += 1;
        $war["$kd2"][$myname2]['attacksmade'] += 0;

        //attacking prov
        $sql = "select exists(select * from provs where Kd = '$kd1' and Myname = '$myname')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd1','$myname','$acres',1,0,1,0,0,0,0,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set TM = TM + 1, Acres = Acres + $acres, Attacksmade = Attacksmade + 1 where Kd = '$kd1' and Myname = '$myname'";
            $db->exec($sql);
        }

        //attacked prov
        $sql = "select exists(select * from provs where Kd = '$kd2' and Myname = '$myname2')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd2','$myname2','-$acres',0,1,0,0,0,0,0,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Acres = Acres - $acres, Attacksreceived = Attacksreceived + 1 where Kd = '$kd2' and Myname = '$myname2'";
            $db->exec($sql);
        }
        $sql = "insert into attacks(KD1, KD2, Attackdate,Attacktime, Attacktype, Attacker, Target, Attackmonth, Attackday, Attackyear, Acres, Line) values('$kd1','$kd2','$atime2','$atime','$atype','$myname','$myname2','$month','$day','$year','$acres','$line')";
        $db->exec($sql);
    }

    if (strpos($line,"conquered") > 0 )
    {
        $atype = "Conq";
        $tmp = preg_split("/\(/",$line);
        $tkd1 = preg_split("/\)/",$tmp[1]);
        $tkd2 = preg_split("/\)/",$tmp[2]);
        $kd1 = $tkd1[0];
        $kd2 = $tkd2[0];

        $tmp1 = preg_split("/\(/",$line);
        $tmp2 = preg_split("/ /",$tmp1[0]);
        $tmp3 = preg_split("/YR\d{1,2}/",$tmp1[0]);
        $mydate = $tmp2[0]." ".$tmp2[1]." ".$tmp2[3];
        $myname = trim($tmp3[1]);

        $tmp1 = preg_split("/from/",$tmp1[1]);
        if (strpos($tmp1[0],"invaded") > 0)
        {
            $tmp2 = preg_split("/invaded/",$tmp1[0]);
            $myname2 = trim($tmp2[1]);
        } else {
            $myname2 = trim($tmp1[1]);
        }

        $grab = preg_split("/conquered/",$line);
        $grab2 = preg_split("/acres/",$grab[1]);
        $acres = chop(trim($grab2[0]));


        $war["$kd1"]['month']["$month"]['acres'] += $acres;
        $war["$kd2"]['month']["$month"]['acres'] -= $acres;


        $war["$kd1"]['acres'] += $acres;
        $war["$kd1"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['acres'] += $acres;
        $war["$kd1"]["$myname"]['conquest'] += 1;
        $war["$kd1"]["$myname"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['attacksreceived'] += 0;


        $war["$kd1"]["$myname"]['Last'] = "$year $day";
        $war["$kd1"]["$myname"]['Unique'] += 1;



        $war["$kd2"]['acres'] -= $acres;
        $war["$kd2"]['attacksreceived'] += 1;
        $war["$kd2"][$myname2]['acres'] -= $acres;
        $war["$kd2"][$myname2]['attacksreceived'] += 1;


        //attacking prov
        $sql = "select exists(select * from provs where Kd = '$kd1' and Myname = '$myname')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd1','$myname','$acres',1,0,0,1,0,0,1,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Conq = Conq + 1, Acres = Acres + $acres, Attacksmade = Attacksmade + 1 where Kd = '$kd1' and Myname = '$myname'";
            $db->exec($sql);
        }

        //attacked prov
        $sql = "select exists(select * from provs where Kd = '$kd2' and Myname = '$myname2')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd2','$myname2','-$acres',0,1,0,0,0,0,0,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Acres = Acres - $acres, Attacksreceived = Attacksreceived + 1 where Kd = '$kd2' and Myname = '$myname2'";
            $db->exec($sql);
        }
        $sql = "insert into attacks(KD1, KD2, Attackdate,Attacktime, Attacktype, Attacker, Target, Attackmonth, Attackday, Attackyear, Acres, Line) values('$kd1','$kd2','$atime2','$atime','$atype','$myname','$myname2','$month','$day','$year','$acres','$line')";
        $db->exec($sql);

    }

    if (strpos($line,"razed") > 0 )
    {
        $atype = "Raze";
        $tmp = preg_split("/\(/",$line);
        $tkd1 = preg_split("/\)/",$tmp[1]);
        $tkd2 = preg_split("/\)/",$tmp[2]);
        $kd1 = $tkd1[0];
        $kd2 = $tkd2[0];

        $tmp1 = preg_split("/\(/",$line);
        $tmp2 = preg_split("/ /",$tmp1[0]);
        $tmp3 = preg_split("/YR\d{1,2}/",$tmp1[0]);
        $mydate = $tmp2[0]." ".$tmp2[1]." ".$tmp2[3];
        $myname = trim($tmp3[1]);

        $tmp1 = preg_split("/land in/",$tmp1[1]);
        if (strpos($tmp1[0],"invaded") > 0)
        {
            $tmp2 = preg_split("/invaded/",$tmp1[0]);
            $myname2 = trim($tmp2[1]);
        } else {
            $myname2 = trim($tmp1[1]);
        }

        $grab = preg_split("/razed/",$line);
        $grab2 = preg_split("/acres/",$grab[1]);
        $acres = chop(trim($grab2[0]));

        $war["$kd1"]['month']["$month"]['acres'] += 0;
        $war["$kd2"]['month']["$month"]['acres'] -= $acres;


        $war["$kd1"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['acres'] += 0;
        $war["$kd1"]["$myname"]['raze'] += 1;
        $war["$kd1"]["$myname"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['attacksreceived'] += 0;

        $war["$kd1"]["$myname"]['Last'] = "$year $day";
        $war["$kd1"]["$myname"]['Unique'] += 1;



        $war["$kd2"]['acres'] -= $acres;
        $war["$kd2"]['attacksreceived'] += 1;
        $war["$kd2"][$myname2]['acres'] -= $acres;
        $war["$kd2"][$myname2]['attacksreceived'] += 1;

        //attacking prov
        $sql = "select exists(select * from provs where Kd = '$kd1' and Myname = '$myname')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd1','$myname',0,1,0,0,0,1,0,0,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Raze = Raze + 1, Attacksmade = Attacksmade + 1 where Kd = '$kd1' and Myname = '$myname'";
            $db->exec($sql);
        }

        //attacked prov
        $sql = "select exists(select * from provs where Kd = '$kd2' and Myname = '$myname2')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd2','$myname2','-$acres',0,1,0,0,0,0,0,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Acres = Acres - $acres, Attacksreceived = Attacksreceived + 1 where Kd = '$kd2' and Myname = '$myname2'";
            $db->exec($sql);
        }
        $sql = "insert into attacks(KD1, KD2, Attackdate,Attacktime, Attacktype, Attacker, Target, Attackmonth, Attackday, Attackyear, Acres, Line) values('$kd1','$kd2','$atime2','$atime','$atype','$myname','$myname2','$month','$day','$year','$acres','$line')";
        $db->exec($sql);
    }

    if (strpos($line,"killed ") > 0 )
    {
        $atype = "Mass";
        $tmp = preg_split("/\(/",$line);
        $tkd1 = preg_split("/\)/",$tmp[1]);
        $tkd2 = preg_split("/\)/",$tmp[2]);
        $kd1 = $tkd1[0];
        $kd2 = $tkd2[0];

        $tmp1 = preg_split("/\(/",$line);
        $tmp2 = preg_split("/ /",$tmp1[0]);
        $tmp3 = preg_split("/YR\d{1,2}/",$tmp1[0]);
        $mydate = $tmp2[0]." ".$tmp2[1]." ".$tmp2[3];
        $myname = trim($tmp3[1]);

        $tmp1 = preg_split("/from/",$tmp1[1]);
        if (strpos($tmp1[0],"invaded") > 0)
        {
            $tmp2 = preg_split("/invaded/",$tmp1[0]);
            $myname2 = trim($tmp2[1]);
        } else {
            $tmp2 = preg_split("/in /",$tmp1[0]);
            $myname2 = trim($tmp2[1]);
        }

        $grab = preg_split("/killed/",$line);
        $grab2 = preg_split("/people/",$grab[1]);
        $acres = chop(trim($grab2[0]));

        $war["$kd1"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['acres'] += 0;
        $war["$kd1"]["$myname"]['massacre'] += 1;
        $war["$kd1"]["$myname"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['attacksreceived'] += 0;


        $war["$kd1"]["$myname"]['Last'] = "$year $day";
        $war["$kd1"]["$myname"]['Unique'] += 1;

        $war["$kd2"]['attacksreceived'] += 1;
        $war["$kd2"][$myname2]['acres'] += 0;
        $war["$kd2"][$myname2]['attacksmade'] += 0;
        $war["$kd2"][$myname2]['attacksreceived'] += 1;

        //attacking prov
        $sql = "select exists(select * from provs where Kd = '$kd1' and Myname = '$myname')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd1','$myname',0,1,0,0,0,0,0,1,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Mass = Mass + 1, Attacksmade = Attacksmade + 1 where Kd = '$kd1' and Myname = '$myname'";
            $db->exec($sql);
        }

        //attacked prov
        $sql = "select exists(select * from provs where Kd = '$kd2' and Myname = '$myname2')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd2','$myname2',0,0,1,0,0,0,0,0,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Acres = Acres - $acres, Attacksreceived = Attacksreceived + 1 where Kd = '$kd2' and Myname = '$myname2'";
            $db->exec($sql);
        }
        $sql = "insert into attacks(KD1, KD2, Attackdate,Attacktime, Attacktype, Attacker, Target, Attackmonth, Attackday, Attackyear, Acres, Line) values('$kd1','$kd2','$atime2','$atime','$atype','$myname','$myname2','$month','$day','$year','$acres','$line')";
        $db->exec($sql);
    }

    if (strpos($line,"stole from") > 0 )
    {
        $atype = "Learn";
        $tmp = preg_split("/\(/",$line);
        $tkd1 = preg_split("/\)/",$tmp[1]);
        $tkd2 = preg_split("/\)/",$tmp[2]);
        $kd1 = $tkd1[0];
        $kd2 = $tkd2[0];

        $tmp1 = preg_split("/\(/",$line);
        $tmp2 = preg_split("/ /",$tmp1[0]);
        $tmp3 = preg_split("/YR\d{1,2}/",$tmp1[0]);
        $mydate = $tmp2[0]." ".$tmp2[1]." ".$tmp2[3];
        $myname = trim($tmp3[1]);

        $tmp1 = preg_split("/from/",$tmp1[1]);
        $myname2 = trim($tmp1[1]);
        $acres = 0;

        $war["$kd1"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['acres'] += 0;
        $war["$kd1"]["$myname"]['learn'] += 1;
        $war["$kd1"]["$myname"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['attacksreceived'] += 0;

        $war["$kd1"]["$myname"]['Last'] = "$year $day";
        $war["$kd1"]["$myname"]['Unique'] += 1;

        if (!isset($war["$kd1"]["$myname"]['Last']))
        {
            $war["$kd1"]["$myname"]['Last'] = "$year $day";
            $war["$kd1"]["$myname"]['Unique'] += 1;
        }

        $war["$kd2"]['attacksreceived'] += 1;
        $war["$kd2"][$myname2]['attacksreceived'] += 1;


        //attacking prov
        $sql = "select exists(select * from provs where Kd = '$kd1' and Myname = '$myname')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd1','$myname',0,1,0,0,0,0,1,0,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Learn = Learn + 1, Attacksmade = Attacksmade + 1 where Kd = '$kd1' and Myname = '$myname'";
            $db->exec($sql);
        }

        //attacked prov
        $sql = "select exists(select * from provs where Kd = '$kd2' and Myname = '$myname2')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd2','$myname2',0,0,1,0,0,0,0,0,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Acres = Acres - $acres, Attacksreceived = Attacksreceived + 1 where Kd = '$kd2' and Myname = '$myname2'";
            $db->exec($sql);
        }
        $sql = "insert into attacks(KD1, KD2, Attackdate,Attacktime, Attacktype, Attacker, Target, Attackmonth, Attackday, Attackyear, Acres, Line) values('$kd1','$kd2','$atime2','$atime','$atype','$myname','$myname2','$month','$day','$year','$acres','$line')";
        $db->exec($sql);
    }

    if (strpos($line,"pillaged ") > 0 )
    {
        $atype = "Plunder";
        $tmp = preg_split("/\(/",$line);
        $tkd1 = preg_split("/\)/",$tmp[1]);
        $tkd2 = preg_split("/\)/",$tmp[2]);
        $kd1 = $tkd1[0];
        $kd2 = $tkd2[0];

        $tmp1 = preg_split("/\(/",$line);
        $tmp2 = preg_split("/ /",$tmp1[0]);
        $tmp3 = preg_split("/YR\d{1,2}/",$tmp1[0]);
        $mydate = $tmp2[0]." ".$tmp2[1]." ".$tmp2[3];
        $myname = trim($tmp3[1]);

        $tmp1 = preg_split("/from/",$tmp1[1]);
        $myname2 = trim($tmp1[1]);
        $acres = 0;

        $war["$kd1"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['acres'] += 0;
        $war["$kd1"]["$myname"]['plunder'] += 1;
        $war["$kd1"]["$myname"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['attacksreceived'] += 0;

        $war["$kd1"]["$myname"]['Last'] = "$year $day";
        $war["$kd1"]["$myname"]['Unique'] += 1;

        if (!isset($war["$kd1"]["$myname"]['Last']))
        {
            $war["$kd1"]["$myname"]['Last'] = "$year $day";
            $war["$kd1"]["$myname"]['Unique'] += 1;
        }

        $war["$kd2"]['attacksreceived'] += 1;
        $war["$kd2"][$myname2]['attacksreceived'] += 1;


        //attacking prov
        $sql = "select exists(select * from provs where Kd = '$kd1' and Myname = '$myname')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd1','$myname',0,1,0,0,0,0,0,0,1,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Plunder = Plunder + 1, Attacksmade = Attacksmade + 1 where Kd = '$kd1' and Myname = '$myname'";
            $db->exec($sql);
        }

        //attacked prov
        $sql = "select exists(select * from provs where Kd = '$kd2' and Myname = '$myname2')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd2','$myname2',0,0,1,0,0,0,0,0,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Acres = Acres - $acres, Attacksreceived = Attacksreceived + 1 where Kd = '$kd2' and Myname = '$myname2'";
            $db->exec($sql);
        }
        $sql = "insert into attacks(KD1, KD2, Attackdate,Attacktime, Attacktype, Attacker, Target, Attackmonth, Attackday, Attackyear, Acres, Line) values('$kd1','$kd2','$atime2','$atime','$atype','$myname','$myname2','$month','$day','$year','$acres','$line')";
        $db->exec($sql);
    }


    if (strpos($line,"repelled.") > 0 )
    {
        $atype = "Bounce";
        $tmp = preg_split("/\(/",$line);
        $tkd1 = preg_split("/\)/",$tmp[1]);
        $tkd2 = preg_split("/\)/",$tmp[2]);
        $kd1 = $tkd1[0];
        $kd2 = $tkd2[0];

        $tmp1 = preg_split("/\(/",$line);
        $tmp2 = preg_split("/ /",$tmp1[0]);
        $tmp3 = preg_split("/YR\d{1,2}/",$tmp1[0]);
        $mydate = $tmp2[0]." ".$tmp2[1]." ".$tmp2[3];
        $myname = trim($tmp3[1]);

        $tmp1 = preg_split("/from/",$tmp1[1]);
        $myname2 = trim($tmp1[1]);
        $acres = 0;

        $war["$kd1"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['acres'] += 0;
        $war["$kd1"]["$myname"]['bounce'] += 1;
        $war["$kd1"]["$myname"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['attacksreceived'] += 0;

        $war["$kd1"]["$myname"]['Last'] = "$year $day";
        $war["$kd1"]["$myname"]['Unique'] += 1;

        if (!isset($war["$kd1"]["$myname"]['Last']))
        {
            $war["$kd1"]["$myname"]['Last'] = "$year $day";
            $war["$kd1"]["$myname"]['Unique'] += 1;
        }

        $war["$kd2"]['attacksreceived'] += 0;
        $war["$kd2"][$myname2]['attacksreceived'] += 0;


        //attacking prov
        $sql = "select exists(select * from provs where Kd = '$kd1' and Myname = '$myname')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd1','$myname',0,1,0,0,0,0,0,0,0,0,0,0,1)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Bounce = Bounce + 1, Attacksmade = Attacksmade + 1 where Kd = '$kd1' and Myname = '$myname'";
            $db->exec($sql);
        }

        //attacked prov
        $sql = "select exists(select * from provs where Kd = '$kd2' and Myname = '$myname2')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd2','$myname2',0,0,0,0,0,0,0,0,0,0,0,0,0)";
            $db->exec($sql);
        } 
        
        $sql = "insert into attacks(KD1, KD2, Attackdate,Attacktime, Attacktype, Attacker, Target, Attackmonth, Attackday, Attackyear, Acres, Line) values('$kd1','$kd2','$atime2','$atime','$atype','$myname','$myname2','$month','$day','$year','$acres','$line')";
        $db->exec($sql);
    }

    if (strpos($line,"gathered intelligence on ") > 0 )
    {
        $atype = "Recon";
        $tmp = preg_split("/\(/",$line);
        $tkd1 = preg_split("/\)/",$tmp[1]);
        $tkd2 = preg_split("/\)/",$tmp[2]);
        $kd1 = $tkd1[0];
        $kd2 = $tkd2[0];

        $tmp1 = preg_split("/\(/",$line);
        $tmp2 = preg_split("/ /",$tmp1[0]);
        $tmp3 = preg_split("/YR\d{1,2}/",$tmp1[0]);
        $mydate = $tmp2[0]." ".$tmp2[1]." ".$tmp2[3];
        $myname = trim($tmp3[1]);

        $tmp1 = preg_split("/from/",$tmp1[1]);
        $myname2 = trim($tmp1[1]);
        $acres = 0;

        $war["$kd1"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['acres'] += 0;
        $war["$kd1"]["$myname"]['recon'] += 1;
        $war["$kd1"]["$myname"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['attacksreceived'] += 0;

        $war["$kd1"]["$myname"]['Last'] = "$year $day";
        $war["$kd1"]["$myname"]['Unique'] += 1;

        if (!isset($war["$kd1"]["$myname"]['Last']))
        {
            $war["$kd1"]["$myname"]['Last'] = "$year $day";
            $war["$kd1"]["$myname"]['Unique'] += 1;
        }

        $war["$kd2"]['attacksreceived'] += 0;
        $war["$kd2"][$myname2]['attacksreceived'] += 0;


        //attacking prov
        $sql = "select exists(select * from provs where Kd = '$kd1' and Myname = '$myname')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd1','$myname',0,1,0,0,0,0,0,0,0,0,0,1,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Recon = Recon + 1, Attacksmade = Attacksmade + 1 where Kd = '$kd1' and Myname = '$myname'";
            $db->exec($sql);
        }

        //attacked prov
        $sql = "select exists(select * from provs where Kd = '$kd2' and Myname = '$myname2')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd2','$myname2',0,0,0,0,0,0,0,0,0,0,0,0,0)";
            $db->exec($sql);
        }         
        $sql = "insert into attacks(KD1, KD2, Attackdate,Attacktime, Attacktype, Attacker, Target, Attackmonth, Attackday, Attackyear, Acres, Line) values('$kd1','$kd2','$atime2','$atime','$atype','$myname','$myname2','$month','$day','$year','$acres','$line')";
        $db->exec($sql);
    }


    if (strpos($line,"ambushed") > 0 )
    {
        $atype = "Ambush";
        $tmp = preg_split("/\(/",$line);
        $tkd1 = preg_split("/\)/",$tmp[1]);
        $tkd2 = preg_split("/\)/",$tmp[2]);
        $kd1 = $tkd1[0];
        $kd2 = $tkd2[0];

        $tmp1 = preg_split("/\(/",$line);
        $tmp2 = preg_split("/ /",$tmp1[0]);
        $tmp3 = preg_split("/YR\d{1,2}/",$tmp1[0]);
        $mydate = $tmp2[0]." ".$tmp2[1]." ".$tmp2[3];
        $myname = trim($tmp3[1]);

        $tmp1 = preg_split("/from/",$tmp1[1]);
        if (strpos($tmp1[0],"invaded") > 0)
        {
            $tmp2 = preg_split("/invaded/",$tmp1[0]);
            $myname2 = trim($tmp2[1]);
        } else {
            $myname2 = trim($tmp1[1]);
        }

        $grab = preg_split("/took/",$line);
        $grab2 = preg_split("/acres/",$grab[1]);
        $acres = chop(trim($grab2[0]));


        $war["$kd1"]['month']["$month"]['acres'] += $acres;
        $war["$kd2"]['month']["$month"]['acres'] -= $acres;



        $war["$kd1"]['acres'] += $acres;
        $war["$kd1"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['acres'] += $acres;
        $war["$kd1"]["$myname"]['ambush'] += 1;
        $war["$kd1"]["$myname"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['attacksreceived'] += 0;


        $war["$kd1"]["$myname"]['Last'] = "$year $day";
        $war["$kd1"]["$myname"]['Unique'] += .5;


        $war["$kd2"]['acres'] -= $acres;
        $war["$kd2"]['attacksreceived'] += 1;
        $war["$kd2"][$myname2]['acres'] -= $acres;
        $war["$kd2"][$myname2]['attacksreceived'] += 1;
        $war["$kd2"][$myname2]['attacksmade'] += 0;


        //attacking prov
        $sql = "select exists(select * from provs where Kd = '$kd1' and Myname = '$myname')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd1','$myname','$acres',1,0,0,0,0,0,0,0,0,1,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Ambush = Ambush + 1, Acres = Acres + $acres, Attacksmade = Attacksmade + 1 where Kd = '$kd1' and Myname = '$myname'";
            $db->exec($sql);
        }

        //attacked prov
        $sql = "select exists(select * from provs where Kd = '$kd2' and Myname = '$myname2')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd2','$myname2','-$acres',0,1,0,0,0,0,0,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Acres = Acres - $acres, Attacksreceived = Attacksreceived + 1 where Kd = '$kd2' and Myname = '$myname2'";
            $db->exec($sql);
        }
        $sql = "insert into attacks(KD1, KD2, Attackdate,Attacktime, Attacktype, Attacker, Target, Attackmonth, Attackday, Attackyear, Acres, Line) values('$kd1','$kd2','$atime2','$atime','$atype','$myname','$myname2','$month','$day','$year','$acres','$line')";
        $db->exec($sql);

    }

    if (strpos($line,"recaptured") > 0 )
    {
        $atype = "Ambush";
        $tmp = preg_split("/\(/",$line);
        $tkd1 = preg_split("/\)/",$tmp[1]);
        $tkd2 = preg_split("/\)/",$tmp[2]);
        $kd1 = $tkd1[0];
        $kd2 = $tkd2[0];

        $tmp1 = preg_split("/\(/",$line);
        $tmp2 = preg_split("/ /",$tmp1[0]);
        $tmp3 = preg_split("/YR\d{1,2}/",$tmp1[0]);
        $mydate = $tmp2[0]." ".$tmp2[1]." ".$tmp2[3];
        $myname = trim($tmp3[1]);

        $tmp1 = preg_split("/from/",$tmp1[1]);
        if (strpos($tmp1[0],"invaded") > 0)
        {
            $tmp2 = preg_split("/invaded/",$tmp1[0]);
            $myname2 = trim($tmp2[1]);
        } else {
            $myname2 = trim($tmp1[1]);
        }

        $grab = preg_split("/captured/",$line);
        $grab2 = preg_split("/acres/",$grab[1]);
        $acres = chop(trim($grab2[0]));


        $war["$kd1"]['month']["$month"]['acres'] += $acres;
        $war["$kd2"]['month']["$month"]['acres'] -= $acres;


        $war["$kd1"]['acres'] += $acres;
        $war["$kd1"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['acres'] += $acres;
        $war["$kd1"]["$myname"]['ambush'] += 1;
        $war["$kd1"]["$myname"]['attacksmade'] += 1;
        $war["$kd1"]["$myname"]['attacksreceived'] += 0;

        $war["$kd1"]["$myname"]['Last'] = "$year $day";
        $war["$kd1"]["$myname"]['Unique'] += .5;


        $war["$kd2"]['acres'] -= $acres;
        $war["$kd2"]['attacksreceived'] += 1;
        $war["$kd2"][$myname2]['acres'] -= $acres;
        $war["$kd2"][$myname2]['attacksreceived'] += 1;
        $war["$kd2"][$myname2]['attacksmade'] += 0;


        //attacking prov
        $sql = "select exists(select * from provs where Kd = '$kd1' and Myname = '$myname')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd1','$myname','$acres',1,0,0,0,0,0,0,0,0,1,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Ambush = Ambush + 1, Acres = Acres + $acres, Attacksmade = Attacksmade + 1 where Kd = '$kd1' and Myname = '$myname'";
            $db->exec($sql);
        }

        //attacked prov
        $sql = "select exists(select * from provs where Kd = '$kd2' and Myname = '$myname2')";
        $row = $db->querySingle($sql);
        if (!$row) {
            $sql = "insert into provs(Kd,Myname,Acres,Attacksmade,Attacksreceived,TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce) values ('$kd2','$myname2','-$acres',0,1,0,0,0,0,0,0,0,0,0,0)";
            $db->exec($sql);
        } else {
            $sql = "update provs set Acres = Acres - $acres, Attacksreceived = Attacksreceived + 1 where Kd = '$kd2' and Myname = '$myname2'";
            $db->exec($sql);
        }
        $sql = "insert into attacks(KD1, KD2, Attackdate,Attacktime, Attacktype, Attacker, Target, Attackmonth, Attackday, Attackyear, Acres, Line) values('$kd1','$kd2','$atime2','$atime','$atype','$myname','$myname2','$month','$day','$year','$acres','$line')";
        $db->exec($sql);

    }


    $tmp2 = explode(" ", $line);
    $enddate = $tmp2[0]." ".$tmp2[1];

}

$db->close();
echo $uniqueid;
?>
