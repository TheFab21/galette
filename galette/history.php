<?php
/* history.php
 * - Historique
 * Copyright (c) 2003 Frédéric Jaqcuot
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.
 *
 */
 
require_once('includes/galette.inc.php');

if( !$login->isLogged() )
{
	header('location: index.php');
	die();
}
if( !$login->isAdmin())
{
	header('location: voir_adherent.php');
	die();
}

$page = 1;
if (isset($_GET['page']))
	$page = $_GET['page'];
	
if (isset($_GET['reset']))
{
	$requete[0] = 'DELETE FROM ' . PREFIX_DB . 'logs';
	$DB->Execute($requete[0]);
	dblog('Logs flushed');
	header ('location: history.php');
}

$numrows = PREF_NUMROWS;
if (isset($_GET['nbshow']))
	if (is_numeric($_GET['nbshow']))
		$numrows = $_GET['nbshow'];

//echo 'show ' . $numrows . 'lines';
// Tri
if (isset($_GET['tri']))
	if (is_numeric($_GET['tri']))
	{
		if ($_SESSION['tri_log'] == $_GET['tri'])
			$_SESSION['tri_log_sens']=($_SESSION['tri_log_sens']+1)%2;
		else
		{
			$_SESSION['tri_log'] = $_GET['tri'];
			$_SESSION['tri_log_sens']=0;
		}
	}
    
$requete[0] = 'SELECT date_log, adh_log, text_log, ip_log, action_log, sql_log FROM ' . PREFIX_DB . 'logs';
$requete[1] = 'SELECT count(id_log) FROM ' . PREFIX_DB . 'logs';
	
	// phase de tri	
	if ($_SESSION["tri_log_sens"]=="0")
		$tri_log_sens_txt="ASC";
	else
		$tri_log_sens_txt="DESC";

	$requete[0] .= " ORDER BY ";
	
	// tri par date
	if ($_SESSION["tri_log"]=="0")
		$requete[0] .= "date_log ".$tri_log_sens_txt.",";
		
	// tri par ip
	elseif ($_SESSION["tri_log"]=="1")
		$requete[0] .= "ip_log ".$tri_log_sens_txt.",";

	// tri par adhérent
	elseif ($_SESSION["tri_log"]=="2")
		$requete[0] .= "adh_log ".$tri_log_sens_txt.",";

	// tri par description
	elseif ($_SESSION["tri_log"]=="3")
		$requete[0] .= "text_log ".$tri_log_sens_txt.",";
    
	$requete[0] .= "id_log ".$tri_log_sens_txt;

	if ($numrows==0)
		$resultat = &$DB->Execute($requete[0]);
	else
		$resultat = &$DB->SelectLimit($requete[0],$numrows,($page-1)*$numrows);

	$nb_lines = &$DB->Execute($requete[1]);

	if ($nb_lines->fields[0]%$numrows==0) 
		$nbpages = intval($nb_lines->fields[0]/$numrows);
	else 
		$nbpages = intval($nb_lines->fields[0]/$numrows)+1;
	if ($nbpages == 0) $nbpages = 1;
	
	$compteur = 1+($page-1)*$numrows;
	$logs = array();
	while (!$resultat->EOF) 
	{
		$logs[$compteur]['date'] = $resultat->fields[0];
		$logs[$compteur]['ip'] = $resultat->fields[3];
		$logs[$compteur]['adh'] = $resultat->fields[1];
		$logs[$compteur]['action'] = _T($resultat->fields[4]);
		$logs[$compteur]['desc'] = $resultat->fields[2]."<br/>".$resultat->fields[5];
		$resultat->MoveNext();
		$compteur++;
	}
	$resultat->Close();

	$tpl->assign("logs",$logs);
	$tpl->assign("nb_lines",count($logs));
	$tpl->assign("nb_pages",$nbpages);
	$tpl->assign("page",$page);
	$tpl->assign("numrows",$numrows);
	$tpl->assign('nbshow_options', array(
			10 => "10",
			20 => "20",
			50 => "50",
			100 => "100",
			0 => _T("All")));
	$content = $tpl->fetch("history.tpl");
	$tpl->assign("content",$content);
	$tpl->display("page.tpl");
?>
