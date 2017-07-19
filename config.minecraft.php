<?php
/*
    baltGro - SMS/PayPal maksājumu sistēmas gatavais risinājums
    baltGro ir aplikācija, kura saistās ar baltGro SMS/PayPal un uzturēšanas risinājumiem. Šo aplikācija drīkst izmantot tikai baltgro.lv klienti, kuriem ir vajadzīgie dati, lai aizpildītu konfigurāciju un izveidotu savienojumu
    Aplikāciju un tās spraudņus veidoja Miks Zvirbulis
    http://twitter.com/MiksZvirbulis
	https://twitter.com/mrYtteroy
*/
/*
    NEAIZTIKT! AUTOMĀTISKI DEFINĒTAS VĒRTĪBAS!
*/
define("mc_config_present", true);
$mc = array();
require $c['dir'] . "/system/minecraft.class.php";
/*
-----------------------------------------------------
Konfigurāciju rediģēt drīkst pēc šīs līnijas
-----------------------------------------------------
*/

/*
    Datubāzes servera adrese, pēc noklusējuma "localhost"
*/
$mc['db']['host'] = "localhost";

/*
    Datubāzes pieejas lietotājvārds
*/
$mc['db']['username'] = "";

/*
    Datubāzes pieejas parole
*/
$mc['db']['password'] = "";

/*
    Datubāzes nosaukums
*/
$mc['db']['database'] = "";


$mc['servers'] = array(
	"MyServer" => (object)array(
		"title" => "Factions",
		"ip_address" => "51.255.101.238",
		"rcon_port" =>	25566 ,
		"rcon_password" => "ghdkncvkfhgnjj1h5jh5kn6k7hjf",
		"show" => true
	),
);

foreach($mc['servers'] as $type => $data){
	$mc['rcon'][$type] = new MinecraftRcon($data->ip_address, $data->rcon_port, $data->rcon_password, 10);
	if($mc['rcon'][$type]->connect() === false){
		$data->show = false;
		echo baltsms::alert("Nav iespējams savienoties ar Minecraft serveri: <strong>" . $type . "</strong>. Pārbaudi pieejas datus!", "danger");
	}
}
