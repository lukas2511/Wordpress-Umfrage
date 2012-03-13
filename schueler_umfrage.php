<?php
/*
Plugin Name: Schueler Umfragen
Plugin URI: http://adenauer-bonn.de
Description: Bla bla bla ganz viele informationen bla bla
Version: 1.0
Author: Lukas Schauer
Author URI: http://lukas-schauer.de
License: GPL2
*/

/*  Copyright 2012  Lukas Schauer  (email : lukas.schauer@googlemail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if(!function_exists('add_action')){
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit;
}


function schueler_umfrage_init(){
	$tables=array(
		"wp_schueler_umfragen" => "`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`anonym` INT( 1 ) NOT NULL DEFAULT  '0',`frage` VARCHAR( 100 ) NOT NULL , `validierung` VARCHAR(100) NOT NULL",
		"wp_schueler_umfragen_moeglichkeiten" => "`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`umfrage` INT( 10 ) NOT NULL ,`antwort` VARCHAR( 100 ) NOT NULL",
		"wp_schueler_umfragen_antworten" => "`id` INT( 10 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,`umfrage` INT( 10 ) NOT NULL ,`antwort` VARCHAR( 100 ) NOT NULL ,`name` VARCHAR( 100 ) NOT NULL ,`vorname` VARCHAR( 100 ) NOT NULL ,`klasse` VARCHAR( 10 ) NOT NULL ,`validierung` VARCHAR( 100 ) NOT NULL, date INT (12) NOT NULL",
	);
	global $wpdb;
	if(!get_option("schueler_umfragen_db_erstellt")){
		foreach($tables as $table => $columns){
			//$wpdb->query("DROP TABLE `".$table."` IF EXISTS");
			$wpdb->query("CREATE TABLE `".$table."` (".$columns.")");
		}
		add_option("schueler_umfragen_db_erstellt",1);
	}else{
		schueler_umfrage_downloads_loeschen("");
	}
}

function schueler_umfrage_content($input=""){
	schueler_umfrage_init();
	if(preg_match('/^(.*)(\\[schuelerumfrage,\d+\])(.*)$/',strtr($input,array("\n"=>"<br />")),$matches)){
		array_shift($matches);
		$output="";
		$postid=$GLOBALS['post']->ID;
		foreach($matches as $match){
			if(preg_match('/\\[schuelerumfrage,(\d+)+\\]$/',$match,$parms)){
				$id=$parms[1];
				global $wpdb;
				$umfrage=$wpdb->get_results("SELECT * FROM `wp_schueler_umfragen` WHERE `id` = ".$id);
				if(count($umfrage)>0){
					$umfrage=$umfrage[0];
					if(isset($_POST['umfrage_id']) && $_POST['umfrage_id']==$id && is_numeric($_POST['umfrage_antwort']) && $_POST['umfrage_antwort']>0){
						$antwort=$wpdb->get_results("SELECT * FROM `wp_schueler_umfragen_moeglichkeiten` WHERE `id` = ".$_POST['umfrage_antwort'].' ORDER BY `id` ASC');
						if(count($antwort)==1 && $antwort[0]->umfrage==$id){
							$umfrage=$wpdb->get_results("SELECT * FROM `wp_schueler_umfragen` WHERE `id` = ".$antwort[0]->umfrage);
							if(count($umfrage)==1){
								$umfrage=$umfrage[0];
								if($umfrage->anonym){
									$wpdb->query($wpdb->prepare("INSERT INTO `wp_schueler_umfragen_antworten` (`umfrage`,`antwort`,`date`) VALUES (%d,%s,UNIX_TIMESTAMP())",$umfrage->id,$antwort[0]->antwort));
									$output.="<p>Vielen Dank, deine Auswahl wurde gespeichert.</p>";
								}else{
									if(!isset($_POST['umfrage_name']) || !isset($_POST['umfrage_vorname']) || !isset($_POST['umfrage_klasse']) || empty($_POST['umfrage_name']) || ($umfrage->validierung!=keine && (!isset($_POST['umfrage_validierung']) || empty($_POST['umfrage_validierung'])))){
										$output.="<p style='font-weight:bold'>Bitte versuche es <a href='?p=".$postid."'>noch einmal</a>, und geb dieses mal alle ben&ouml;tigten Daten ein ;)<p>";
									}else{
										if($umfrage->validierung=="keine"){
											$alt=$wpdb->get_results($wpdb->prepare("SELECT `id` FROM `wp_schueler_umfragen_antworten` WHERE `umfrage` = '".$umfrage->id."' && `name` = %s && `vorname` = %s && klasse = %s",$_POST['umfrage_name'],$_POST['umfrage_vorname'],$_POST['umfrage_klasse']));
											if(count($alt)){
												$wpdb->query($wpdb->prepare("UPDATE `wp_schueler_umfragen_antworten` SET `antwort` = %s, `date` = UNIX_TIMESTAMP() WHERE `id` = ".$alt[0]->id,$antwort[0]->antwort));
											}else{
												$wpdb->query($wpdb->prepare("INSERT INTO `wp_schueler_umfragen_antworten` (`umfrage`,`antwort`,`name`,`vorname`,`klasse`,`date`) VALUES (%d,%s,%s,%s,%s,UNIX_TIMESTAMP())",$umfrage->id,$antwort[0]->antwort,$_POST['umfrage_name'],$_POST['umfrage_vorname'],$_POST['umfrage_klasse']));
											}
										}else{
											$alt=$wpdb->get_results($wpdb->prepare("SELECT `id` FROM `wp_schueler_umfragen_antworten` WHERE `umfrage` = '".$umfrage->id."' && `name` = %s && `vorname` = %s && `klasse` = %s && `validierung` = %s",$_POST['umfrage_name'],$_POST['umfrage_vorname'],$_POST['umfrage_klasse'],$_POST['umfrage_validierung']));
											if(count($alt)){
												$wpdb->query($wpdb->prepare("UPDATE `wp_schueler_umfragen_antworten` SET `antwort` = %s, `date` = UNIX_TIMESTAMP() WHERE `id` = ".$alt[0]->id,$antwort[0]->antwort));
											}else{
												$wpdb->query($wpdb->prepare("INSERT INTO `wp_schueler_umfragen_antworten` (`umfrage`,`antwort`,`name`,`vorname`,`klasse`,`validierung`,`date`) VALUES (%d,%s,%s,%s,%s,%s,UNIX_TIMESTAMP())",$umfrage->id,$antwort[0]->antwort,$_POST['umfrage_name'],$_POST['umfrage_vorname'],$_POST['umfrage_klasse'],$_POST['umfrage_validierung']));
											}
										}
										$output.="<p>Vielen Dank, deine Auswahl wurde gespeichert.</p>";
									}
								}
							}else{
								$output.="<p style='font-weight:bold'>Sorry, es ist ein Fehler aufgetreten. Versuche es sp&auml;ter noch einmal..<p>";
							}
						}else{
							$output.="<p style='font-weight:bold'>Sorry, es ist ein Fehler aufgetreten. Versuche es sp&auml;ter noch einmal..<p>";
						}
					}else{
						$output.='<p><form action="" method="post">';
						$output.='<span style="font-weight:bold;">'.$umfrage->frage.'</span><br />';
						if(!$umfrage->anonym) $output.='Name: <input type="text" name="umfrage_name" value="" /><br />';
						if(!$umfrage->anonym) $output.='Vorname: <input type="text" name="umfrage_vorname" value="" /><br />';
						if(!$umfrage->anonym) $output.='Klasse: <input type="text" name="umfrage_klasse" value="" /><br />';
						$moeglichkeiten=$wpdb->get_results("SELECT * FROM `wp_schueler_umfragen_moeglichkeiten` WHERE `umfrage` = ".$id);
						foreach($moeglichkeiten as $moeglichkeit){
							$output.='<input type="radio" name="umfrage_antwort" value="'.$moeglichkeit->id.'" /> '.$moeglichkeit->antwort."<br />";
						}
						if($umfrage->validierung!="keine" && $umfrage->anonym==0){
							$output.=$umfrage->validierung.': <input type="text" name="umfrage_validierung" value="" autocomplete="off" />';
						}
						$output.='<br />';
						$output.='<input type="hidden" name="umfrage_id" value="'.$id.'" />';
						$output.='<input type="submit" value="Absenden" />';
						$output.='</form></p>';
					}
				}else{
					$output.="Fehler: Umfrage $id nicht gefunden.";
				}
			}else{
				$output.=$match;
			}
		}
		return $output;
	}else{
		return $input;
	}
}

function schueler_umfragen_admin(){
	schueler_umfrage_init();
	if (!current_user_can('publish_posts')){
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	if(isset($_GET['del2']) && is_numeric($_GET['del2'])){
		global $wpdb;
		$wpdb->query("DELETE FROM `wp_schueler_umfragen` WHERE `id` = ".$_GET['del2']);
		$wpdb->query("DELETE FROM `wp_schueler_umfragen_moeglichkeiten` WHERE `umfrage` = ".$_GET['del2']);
		$wpdb->query("DELETE FROM `wp_schueler_umfragen_antworten` WHERE `umfrage` = ".$_GET['del2']);
	}
	if(isset($_GET['download']) && is_numeric($_GET['download'])){
		echo '<div class="wrap"><p>';
		global $wpdb;
		$umfrage=$wpdb->get_results($wpdb->prepare("SELECT * FROM `wp_schueler_umfragen` WHERE `id` = %d",$_GET['download']));
		if(count($umfrage)==0){
			echo '<h1>Fehler</h1><br />Umfrage nicht gefunden.<br /><a href="?page='.$_GET['page'].'">Zur&uuml;ck</a>';
		}else{
			$umfrage=$umfrage[0];
			echo '<h1>Download</h1><br /><br />';
			$antworten=$wpdb->get_results("SELECT * FROM `wp_schueler_umfragen_antworten` WHERE `umfrage` = ".$umfrage->id." ORDER BY `name` ASC");
			$csv="";
			//$csv='"ID";';
			if(!$umfrage->anonym) $csv.='"NAME";';
			if(!$umfrage->anonym) $csv.='"VORNAME";';
			if(!$umfrage->anonym) $csv.='"KLASSE";';
			$csv.='"'.$umfrage->frage.'";"DATUM / UHRZEIT"';
			if($umfrage->validierung!="keine") $csv.=';"'.$umfrage->validierung.'"';
			$csv.="\r\n";
			foreach($antworten as $antwort){
				//$csv.='"'.$antwort->id.'";';
				if(!$umfrage->anonym) $csv.='"'.$antwort->name.'";';
				if(!$umfrage->anonym) $csv.='"'.$antwort->vorname.'";';
				if(!$umfrage->anonym) $csv.='"'.$antwort->klasse.'";';
				$csv.='"'.$antwort->antwort.'";';
				$csv.='"'.date("d.m.Y / H:i",$antwort->date).'"';
				if($umfrage->validierung!="keine") $csv.=';"'.$antwort->validierung.'"';
				$csv.="\r\n";
			}

			$folder=dirname($_SERVER['SCRIPT_FILENAME'])."/csv_export";
			if(!is_dir($folder)) mkdir($folder);
			if(!is_dir($folder)) die("Server Error");
			$randomname="";
			for($i=1;$i<=30;$i++){
				$randomname.=chr(mt_rand(97,122));
			}
			file_put_contents($folder."/".$randomname.".csv",$csv);
			$url="http://".$_SERVER['HTTP_HOST'].dirname($_SERVER['REQUEST_URI'])."/csv_export/".$randomname.".csv";
			echo '<a href="'.$url.'">'.$url.'</a><br /><br />';
			echo '<a href="?page='.$_GET['page'].'">Zur&uuml;ck</a>';
		}
		echo '</p></div>';
	}elseif(isset($_GET['del']) && is_numeric($_GET['del'])){
		echo '<div class="wrap"><p>';
		echo '<h1>Wirklich l&ouml;schen?</h1>';
		echo '<input type="button" style="height:70px;width:200px;font-size:20px;margin:5px;" value="Ja" onclick="window.location.href=\'?page='.$_GET['page'].'&del2='.$_GET['del'].'\'" /><input type="button"  style="height:70px;width:200px;font-size:20px;margin:5px;" value="Nein" onclick="window.location.href=\'?page='.$_GET['page'].'\'" />';
		echo '</p></div>';
	}elseif(isset($_GET['edit']) && is_numeric($_GET['edit'])){
		global $wpdb;
		$umfrage=$wpdb->get_results($wpdb->prepare("SELECT * FROM `wp_schueler_umfragen` WHERE `id` = %d",$_GET['edit']));
		if(count($umfrage)==1){
			if(isset($_POST['umfrage_frage']) && !empty($_POST['umfrage_frage'])){
				$wpdb->query($wpdb->prepare("UPDATE `wp_schueler_umfragen` SET `frage` = %s WHERE `id` = %d",$_POST['umfrage_frage'],$_GET['edit']));
				$umfrage=$wpdb->get_results($wpdb->prepare("SELECT * FROM `wp_schueler_umfragen` WHERE `id` = %d",$_GET['edit']));
			}
			if(isset($_POST['umfrage_validierung'])){
				if(empty($_POST['umfrage_validierung'])) $_POST['umfrage_validierung']="keine";
				$wpdb->query($wpdb->prepare("UPDATE `wp_schueler_umfragen` SET `validierung` = %s WHERE `id` = %d",$_POST['umfrage_validierung'],$_GET['edit']));
				$umfrage=$wpdb->get_results($wpdb->prepare("SELECT * FROM `wp_schueler_umfragen` WHERE `id` = %d",$_GET['edit']));
			}
			$umfrage=$umfrage[0];
			echo '<div class="wrap"><p>';
			if($umfrage->anonym){
				echo '<h1>Anonyme Umfrage bearbeiten</h1>';
			}else{
				echo '<h1>Umfrage bearbeiten</h1>';
			}
			echo "<form action='' method='post'>Frage: <input type='text' value='".$umfrage->frage."' name='umfrage_frage' style='width:350px' /><input type='submit' value='Speichern' />";
			echo '<h3>Antwortm&ouml;glichkeiten:</h3>';
			if(isset($_POST['umfrage_antwort']) && !empty($_POST['umfrage_antwort'])){
				$wpdb->query($wpdb->prepare("INSERT INTO `wp_schueler_umfragen_moeglichkeiten` (`umfrage`,`antwort`) VALUES (%d,%s)",$_GET['edit'],$_POST['umfrage_antwort']));
			}
			$antworten=$wpdb->get_results($wpdb->prepare("SELECT * FROM `wp_schueler_umfragen_moeglichkeiten` WHERE `umfrage` = %d",$_GET['edit'])." ORDER BY `id` ASC");
			echo '<ul>';
			foreach($antworten as $antwort){
				if(isset($_GET['delant']) && $_GET['delant']==$antwort->id){
					$wpdb->query("DELETE FROM `wp_schueler_umfragen_moeglichkeiten` WHERE `id` = ".$antwort->id);
					echo '<li><span style="color:red">Gel&ouml;scht.</span></li>';
				}else{
					echo '<li>'.$antwort->antwort.' <a href="?page='.$_GET['page'].'&edit='.$_GET['edit'].'&delant='.$antwort->id.'">L&ouml;schen</a></li>';
				}
			}
			echo '</ul>';
			echo '<form action="" method="post"><input type="text" name="umfrage_antwort" /><input type="submit" value="Hinzuf&uuml;gen" /></form><br />';
			if(!$umfrage->anonym){
				echo '<form action="" method="post">Validierung: <input type="text" name="umfrage_validierung" value="'.$umfrage->validierung.'" /><input type="submit" value="Speichern" /></form><br />';
			}
			echo 'Zum Einbinden in den Post kopieren:<br /><pre>[schuelerumfrage,'.$_GET['edit'].']</pre>';
			echo '<input type="button" value="Antworten herunterladen" onclick="window.location.href=\'?page='.$_GET['page'].'&download='.$_GET['edit'].'\'" style="font-size:20px;padding:5px;margin-bottom:5px;" /><br />';
			echo '<a href="?page='.$_GET['page'].'&del='.$_GET['edit'].'" style="color:red">L&ouml;schen</a><br />';
			echo '<a href="?page='.$_GET['page'].'">Zur&uuml;ck</a>';
			echo '</p></div>';
		}else{
			echo '<div class="wrap"><p>';
			echo '<h1>Fehler</h1><br />Umfrage nicht gefunden.<br /><a href="?page='.$_GET['page'].'">Zur&uuml;ck</a>';
			echo '</p></div>';
		}
	}elseif(isset($_GET['new'])){
		if(isset($_POST['umfrage_frage']) && !empty($_POST['umfrage_frage'])){
			if(empty($_POST['umfrage_validierung'])) $_POST['umfrage_validierung']="keine";
			global $wpdb;
			if(isset($_POST['umfrage_anonym']) && !empty($_POST['umfrage_anonym'])) $anonym=1; else $anonym=0;
			if($anonym) $_POST['umfrage_validierung']="keine";
			$wpdb->query($wpdb->prepare("INSERT INTO `wp_schueler_umfragen` (`frage`,`anonym`,`validierung`) VALUES (%s,%d,%s)",$_POST['umfrage_frage'],$anonym,$_POST['umfrage_validierung']));
			echo '<div class="wrap"><p>';
			echo '<h1>Umfrage erstellt</h1><br />';
			echo 'Die neue Umfrage wurde erstellt.<br />Antworten k&ouml;nnen nach Weiterleitung erstellt werden..<br />Falls Weiterleitung nicht erfolgt, <a href="?page='.$_GET['page'].'&edit='.$wpdb->insert_id.'">hier klicken</a>';
			echo '<meta http-equiv="refresh" content="5; url=?page='.$_GET['page'].'&edit='.$wpdb->insert_id.'">';
			echo '</p></div>';
		}else{
			echo '<div class="wrap"><p>';
			echo '<h1>Neue Umfrage:</h1>';
			echo '<form action="" method="post"><br /><br />';
			echo 'Frage: <input type="text" name="umfrage_frage" autocomplete="off" /><br /><br />';
			echo 'Validierung: <input type="text" name="umfrage_validierung" value="keine" autocomplete="off" /><br /><br />';
			echo 'Anonym: <input type="checkbox" name="umfrage_anonym" /> (schaltet validierung ab.)<br /><br />';
			echo '<input type="submit" value="Erstellen" />';
			echo '</form>';
			echo '</p></div>';
		}
	}else{
		echo '<div class="wrap"><p>';
		echo "<h1>Umfragen:</h1><br />";
		echo "<table cellspacing='0' class='wp-list-table widefat fixed pages'>";
		echo '<a href="?page='.$_GET['page'].'&new">Neue Umfrage</a><br /><br />';
		echo '<thead>';
		echo '<th scope="col" class="manage-column column-title sortable desc">ID</th>';
		echo '<th scope="col" class="manage-column column-title sortable asc">Frage</th>';
		echo '<th scope="col" class="manage-column column-title">Anonym</th>';
	        echo '<th scope="col" class="manage-column column-title">Validierung</th>';
		echo '<th scope="col" class="manage-column column-title">Bearbeiten</th>';
		echo '</thead>';
		echo '<tbody id="the-list">';
		global $wpdb;
		$umfragen=$wpdb->get_results("SELECT * FROM `wp_schueler_umfragen` ORDER by `id` DESC");
		foreach($umfragen as $umfrage){
			echo '<tr>';
			echo '<td>'.$umfrage->id.'</td>';
			echo '<td>'.$umfrage->frage.'</td>';
			echo '<td>';
			if($umfrage->anonym) echo 'Ja'; else echo 'Nein';
			echo '</td>';
			echo '<td>'.$umfrage->validierung.'</td>';
			echo '<td><a href="?page='.$_GET['page'].'&edit='.$umfrage->id.'">Bearbeiten</a></td>';
			echo '</tr>';
		}
		echo '</tbody>';
		echo "</table>";
		echo '<br /><a href="?page='.$_GET['page'].'&new">Neue Umfrage</a>';
		echo '</p></div>';
	}
}

add_filter('the_content','schueler_umfrage_content',10);

add_action('admin_menu', 'schueler_umfrage_adminmenu');

function schueler_umfrage_downloads_loeschen($bla){
	$folder=dirname($_SERVER['SCRIPT_FILENAME'])."/csv_export";
	if(is_dir($folder)){
		$files=glob($folder."/*");
		foreach($files as $fileid => $file){
			if(filemtime($file)<(time()-3600)){
				unlink($file);
				unset($files[$fileid]);
			}
		}
		if(!count($files)) rmdir($folder);
	}
}

function schueler_umfrage_adminmenu(){
	add_menu_page('Sch&uuml;ler Umfragen','Sch&uuml;ler Umfragen','publish_posts','schueler_umfragen','schueler_umfragen_admin');
}

?>