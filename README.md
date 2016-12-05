<h2>Mythea War Formatter</h2>
paste war paper into box..
readlog_sql.php reads that input into 3 sql tables..

war [Id, Warstart, Warend] // this is just the start date/time and end date/time

attacks [Id, KD1, KD2, Attackdate, Attacktime, Attackmonth, Attackday, Attackyear, Acres, Attacktype, Attacker, Target, Line]
KD1 = attacker kd date/time/day/year = mythea date/time of attack
Attacker = prov name of attacker, Target = prov name of target, acres = acres gained
Line = raw line from pasted data

provs [Id, KD, Myname, Attacksmade, Attacksreceived, Acres, TM, Conq, Raze, Learn, Mass, Intra, Plunder, Ambush, Recon, Bounce]
KD = kingdom number, Myname = Prov name in that kingdom
Attacks made/received = counter for any attack incoming/outgoing
TM,Conq,Raze,Learn,Mass,Intra,Plunder,Ambush,Recon,Bounce = Counters for specific attack types

