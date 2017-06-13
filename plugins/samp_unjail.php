﻿<?php
/*
    baltGro - SMS/PayPal maksājumu sistēmas gatavais risinājums
    baltGro ir aplikācija, kura saistās ar baltGro SMS/PayPal un uzturēšanas risinājumiem. Šo aplikācija drīkst izmantot tikai baltgro.lv klienti, kuriem ir vajadzīgie dati, lai aizpildītu konfigurāciju un izveidotu savienojumu
    Aplikāciju un tās spraudņus veidoja Miks Zvirbulis
    http://twitter.com/MiksZvirbulis
	https://twitter.com/mrYtteroy
*/

if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) OR (isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != "xmlhttprequest")) die("Ajax Only!");
$p = basename(__FILE__, ".php");
defined("config_present") or require "../config.inc.php";
defined("samp_config_present") or require "../config.samp.php";
in_array($p, $c['sms']['plugins']['samp']) or die(baltsms::alert("Spraudnis nav ievadīts atļauto spraudņu sarakstā!", "danger"));
/*
-----------------------------------------------------
    SAMP unjail spraudņa konfigurācija
-----------------------------------------------------
*/

$c[$p]['money_symbol'] = "Unjail";

$c[$p]['prices'] = array(
    "main" => array(100 => 1),
);

$c['lang'][$p]['lv'] = array(
    "instructions" => "Lai iegādātos unban par <PRICE> EUR, sūti kodu <b><KEYWORD><CODE></b> uz <b><NUMBER></b>, lai saņemtu atslēgas kodu!<br>Ar PayPal var maksat UCP paneli<br><span style='color:red'>Tu nedrīksti atrasties spēlē pasūtot pakalpojumu!</span>",
	# Kļūdas
    "error_empty_nickname" => "Ievadi savu spēlētāja vārdu!",
    "error_inserver" => "Lūdzu izej no servera! :)",
    "error_empty_server" => "Izvēlies serveri!",
    "error_empty_price" => "Izvēlies cenu!",
    "error_empty_code" => "Ievadi atslēgas kodu!",
    "error_invalid_code" => "Atslēgas kods nav pareizi sastādīts!",
    "error_price_not_listed" => "Izvēlētā cena nav atrasta priekš izvēlētā servera!",
    "unjail_purchased" => "Jus atbrivots no cietuma. Lai jauka spēlēšana!",
	# Forma
    "form_price" => "Cena",
    "form_code" => "Atslēgas kods",
    "form_player_name" => "Vards_Uzvards",
    "form_server" => "Serveris",
    "form_select_server" => "Izvēlies serveri",
    "form_price" => "Cena",
    "form_select_price" => "Izvēlies cenu",
    "form_unlock_code" => "Atslēgas kods",
    "form_buy" => "Pirkt",
);

$c['lang'][$p]['en'] = array(
	"instructions" => "To purchase unban for <PRICE> EUR, send the following code: <b><KEYWORD><CODE></b> to <b><NUMBER></b> to receive an unclock code!",
	# Kļūdas
	"error_empty_nickname" => "Ievadi savu spēlētāja vārdu!",
	"error_inserver" => "Please logout from server! :)",
	"error_empty_server" => "Select the server!",
	"error_empty_price" => "Select the price!",
	"error_empty_code" => "Enter the unlock code!",
	"error_invalid_code" => "The format of the unlock code is not valid!",
	"error_price_not_listed" => "The selected price has not been found for the selected server!",
	"unjail_purchased" => "The money was purchased successfully. Have fun!",
	# Forma
	"form_price" => "Price",
	"form_code" => "Unlock code",
	"form_player_name" => "Vards_Uzvards",
	"form_server" => "Server",
	"form_select_server" => "Select server",
	"form_price" => "Price",
	"form_select_price" => "Select price",
	"form_unlock_code" => "Unlock code",
	"form_buy" => "Buy",
);
/*
-----------------------------------------------------
    SAMP unjail spraudņa konfigurācija
-----------------------------------------------------
*/
$db = new db($samp['db']['host'], $samp['db']['username'], $samp['db']['password'], $samp['db']['database']);
if($db->connected === false) die(baltsms::alert("Nevar izveidot savienojumu ar MySQL serveri. Pārbaudi norādītos pieejas datus!", "danger"));
$lang[$p] = $c['lang'][$p][$c['page']['lang_personal']];

if(isset($_POST['code'])):
	$errors = array();

	if(empty($_POST['nickname'])){
		$errors[] = $lang[$p]['error_empty_nickname'];
	}else{
		if(in_array_r($_POST['nickname'], $samp['rcon'][$_POST['server']]->getBasicPlayers())){
			$errors[] = $lang[$p]['error_inserver'];
		}
	}

	if(empty($_POST['server'])){
		$errors[] = $lang[$p]['error_empty_server'];
	}

	if(empty($_POST['price']) AND !empty($_POST['server'])){
		$errors[] = $lang[$p]['error_empty_price'];
	}else{
		if(!isset($c[$p]['prices'][$_POST['server']][$_POST['price']])){
			$errors[] = $lang[$p]['error_price_not_listed'];
		}
	}

	if(empty($_POST['code'])){
		$errors[] = $lang[$p]['error_empty_code'];
	}else{
		if(strlen($_POST['code']) != 9 OR is_numeric($_POST['code']) === false){
			$errors[] = $lang[$p]['error_invalid_code'];
		}
	}

	if(count($errors) > 0){
		foreach($errors as $error){
			echo baltsms::alert($error, "danger");
		}
	}else{
		$baltsms = new baltsms();
		$baltsms->setPrice($_POST['price']);
		$baltsms->setCode($_POST['code']);
		$baltsms->sendRequest();
		if($baltsms->getResponse() === true){
			$db->update("UPDATE `accounts` SET `jailp` = '0', `jailptime` = '0' WHERE `name = '".$_POST['nickname']."'");
			
			$paymentStatus = 1;
			echo baltsms::alert($lang[$p]['unjail_purchased'], "success");
			?>
			<script type="text/javascript">
				setTimeout(function(){
					loadPlugin('<?php echo $p; ?>');
				}, 3000);
			</script>
			<?php
		}else{
			echo $baltsms->getResponse();
		}
	}
	
	include '../system/sendstats.php';
	
	else:
?>
	<form class="form-horizontal" method="POST" id="<?php echo $p; ?>">
		<div class="panel panel-border panel-contrast" id="instructions"><div class="panel-heading panel-heading-contrast text-center"><?php echo baltsms::instructionTemplate($lang['instructions'], array("price" => baltsms::returnPrice($c[$p]['prices'][0]), "code" => $c[$p]['prices'][0])); ?></div></div>
		<div id="alerts"></div>
		<div class="form-group">
			<label for="nickname" class="col-sm-2 control-label"><?php echo $lang[$p]['form_player_name']; ?></label>
			<div class="col-sm-10">
				<input type="text" class="form-control" name="nickname" placeholder="<?php echo $lang[$p]['form_player_name']; ?>">
			</div>
		</div>
		<div class="form-group">
			<label for="server" class="col-sm-2 control-label"><?php echo $lang[$p]['form_server']; ?></label>
			<div class="col-sm-10">
				<select class="form-control" name="server" onChange="listPrices('none', this.value)">
					<option selected disabled><?php echo $lang[$p]['form_server']; ?></option>
					<?php foreach($c[$p]['prices'] as $server => $data): ?>
						<?php if($samp['servers'][$server]->show !== false): ?>
							<option value="<?php echo $server; ?>"><?php echo $samp['servers'][$server]->title; ?></option>
						<?php endif; ?>
					<?php endforeach; ?>
				</select>
			</div>
		</div>
		<div class="form-group">
			<label for="price" class="col-sm-2 control-label"><?php echo $lang[$p]['form_price']; ?></label>
			<div class="col-sm-10">
				<select class="form-control" id="prices">
					<option selected disabled><?php echo $lang[$p]['form_select_server']; ?></option>
				</select>
				<?php foreach($c[$p]['prices'] as $server => $prices): ?>
					<select class="form-control prices" name="price" id="none-<?php echo $server; ?>-prices" style="display: none;" onChange="changePrice(this); getvalue(this);" disabled>
						<option selected disabled><?php echo $lang[$p]['form_select_price']; ?></option>
						<?php foreach($prices as $price_code => $money): ?>
							<option value="<?php echo $price_code; ?>" data-length="<?php echo $money; ?> <?php echo $c[$p]['money_symbol']; ?>"><?php echo $money; ?> <?php echo $c[$p]['money_symbol']; ?> - <?php echo baltsms::returnPrice($price_code); ?> EUR</option>
						<?php endforeach; ?>
					</select>
				<?php endforeach;  ?>
			</div>
		</div>
		<div class="form-group">
			<label for="name" class="col-sm-2 control-label"><?php echo $lang[$p]['form_unlock_code']; ?></label>
			<div class="col-sm-10">
				<input type="text" class="form-control" name="code" placeholder="<?php echo $lang[$p]['form_unlock_code']; ?>" maxlength="9" autocomplete="off">
			</div>
		</div>
		<script>
		var x = 0.00;
		function getvalue(element){
			if(jQuery(element).find(":selected").attr("data-price")){
				price = jQuery(element).find(":selected").data("price");
			}else{
				price = element.value;
			}
			x = price/100;
		}
		
		function startPayment() {
			var y = document.forms["<?php echo $p; ?>"]["code"].value;
			if (y == null || y == "") {
				openWinPayPal(x);
				return false;
			}
		}
		</script>

		<div class="form-group">
			<button type="button" class="btn btn-success" style="float:left !important; margin-left: 16px;" onclick="startPayment()"><?php echo $lang['pay_with_paypal']; ?></button>
			<div id="baltsms-form-button">
				<button type="submit" class="btn btn-primary"><?php echo $lang[$p]['form_buy']; ?></button>
			</div>
		</div>
	</form>
<?php endif; ?>