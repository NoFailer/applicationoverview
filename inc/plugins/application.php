<?php

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB")) {
    die("Direct initialization of this file is not allowed.");
}

$plugins->add_hook('misc_start', 'application_misc');
$plugins->add_hook('global_start', 'application_global');

// Alerts
if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
    $plugins->add_hook("global_start", "application_alerts");
}
// showthreads 
$plugins->add_hook('showthread_start', 'application_showthread');
// Profil
$plugins->add_hook('member_profile_end', 'application_member_profile');

function application_info()
{
    return array(
        "name" => "Bewerberverwaltung",
        "description" => "In diesem Plugin kannst du zum einen die Bewerberchecklist verwalten, ihnen die Möglichkeit, sich selbst zu verlängern und ihnen im Postbit über ein Dropdown annehmen.",
        "website" => "https://github.com/Ales12/applicationoverview",
        "author" => "Ales",
        "authorsite" => "https://github.com/Ales12",
        "version" => "1.0",
        "guid" => "",
        "codename" => "",
        "compatibility" => "18*"
    );
}

function application_install()
{
    global $db, $cache, $mybb;
    //Datenbank erstellen
    if ($db->engine == 'mysql' || $db->engine == 'mysqli') {
        $db->query("CREATE TABLE `" . TABLE_PREFIX . "applications` (
            `app_id` int(10) NOT NULL auto_increment,
            `uid` int(10) NOT NULL,
            `regdate` int(10) NOT NULL,
            `appdeadline` date,
            `appdays` int(10) NOT NULL,
            `appcount` int(10) NOT NULL,
            `corrector` int(10) NOT NULL,
            PRIMARY KEY (`app_id`)
          ) ENGINE=MyISAM" . $db->build_create_table_collation());

        $db->query("ALTER TABLE `" . TABLE_PREFIX . "users` ADD `wobdate` varchar(400) CHARACTER SET utf8 NOT NULL;");


    }

    // Einstellungen
    $setting_group = array(
        'name' => 'application',
        'title' => 'Bewerberverwaltung',
        'description' => 'Hier kannst du nun alle Einstellungen vornehmen, welche du für deine Verwaltung brauchst.',
        'disporder' => 2, // The order your setting group will display
        'isdefault' => 0
    );

    $gid = $db->insert_query("settinggroups", $setting_group);
    $setting_array = array(
        // A text setting
        'app_maindays' => array(
            'title' => 'Zeit für Bewerbung',
            'description' => 'wie viele Tage hat man ohne Verlängerung für die Bewerbung?',
            'optionscode' => 'numeric',
            'value' => '14', // Default
            'disporder' => 1
        ),
        'app_renewcount' => array(
            'title' => 'Anzahl der Verlängerungen',
            'description' => 'Wie oft darf verlängert werden?',
            'optionscode' => 'numeric',
            'value' => '2',
            'disporder' => 2
        ),
        'app_renewdays' => array(
            'title' => 'Anzahl der Tage der Verlängerung',
            'description' => 'Wie viele Tage darf auf einmal verlängert werden?',
            'optionscode' => 'numeric',
            'value' => '7',
            'disporder' => 3
        ),
        'app_alert' => array(
            'title' => 'Verlängerungsnachricht',
            'description' => 'Wann darf das Banner erscheinen, dass verlängert werden kann?',
            'optionscode' => 'numeric',
            'value' => '7',
            'disporder' => 4
        ),
        'app_appforum' => array(
            'title' => 'Bewerbungsforum',
            'description' => 'Forum, in welchem die Bewerbungen gepostet werden:',
            'optionscode' => 'forumselectsingle',
            'value' => '7',
            'disporder' => 3
        ),
        'app_groups' => array(
            'title' => 'Gruppen',
            'description' => 'In welche Gruppen können die Charaktere eingeordnet werden?',
            'optionscode' => 'groupselect ',
            'value' => '8,9,10,11',
            'disporder' => 3
        ),
        'app_userfid' => array(
            'title' => 'Spielername',
            'description' => 'In welchem fid wird der Spielername gespeichert?',
            'optionscode' => 'numeric',
            'value' => '1',
            'disporder' => 3
        ),
        'app_checklist' => array(
            'title' => 'Bewerberchecklist',
            'description' => 'Welche Profilfelder sollen für die Checkliste ausgelesen werden?',
            'optionscode' => 'text',
            'value' => '1, 2, 3',
            'disporder' => 6
        ),
        'app_wobtext' => array(
            'title' => 'Text für WoB',
            'description' => 'Welcher Text soll erscheinen, wenn der Charakter gewobbt wurde?',
            'optionscode' => 'textarea',
            'value' => 'Herzlich Glückwunsch, dein Charakter wurde angenommen und bekommt deswegen das <b>WoB</b> vom Team.',
            'disporder' => 3
        ),

    );

    foreach ($setting_array as $name => $setting) {
        $setting['name'] = $name;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
    }



    // Templates einfügen
    $insert_array = array(
        'title' => 'application_alert',
        'template' => $db->escape_string('<div class="red_alert">
	<a href="misc.php?action=application_overview">{$lang->app_alert}</a>
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'application_checklist',
        'template' => $db->escape_string('<div class="checklist trow1">
	<div class="check_title tcat">{$lang->checklist}</div>
	<div class="check_status">{$avatarstatus}</div> <div class="check_fact">{$lang->checklist_avatar}</div>
	{$checklist_fid}
		<div class="check_status">{$steckistatus}</div> <div class="check_fact">{$lang->checklist_app} {$forum}</div>
	 </div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'application_checklist_check',
        'template' => $db->escape_string('<img src="images/check.png">'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'application_checklist_fid',
        'template' => $db->escape_string('	<div class="check_status">{$fidstatus}</div> <div class="check_fact">{$checklist_point}</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'application_checklist_nocheck',
        'template' => $db->escape_string('<img src="images/nocheck.png">'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'application_correct',
        'template' => $db->escape_string('<div>
	{$correcteur}
</div>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'application_misc',
        'template' => $db->escape_string('<html>
<head>
<title>{$mybb->settings[\'bbname\']} - {$lang->app_overview}</title>
{$headerinclude}
</head>
<body>
{$header}
<table class="tborder" border="0" cellspacing="{$theme[\'borderwidth\']}" cellpadding="{$theme[\'tablespace\']}">
	<tr><td class="thead"><strong>{$lang->app_overview}</strong></td></tr>
	<tr><td class="trow1"><div class="smalltext" style="padding: 10px;">
{$lang->app_overview_text}	</div>
		<table width="100%">
	<tr>
		<td class="tcat"><strong>{$lang->app_applicat}</strong></td>
				<td class="tcat"><strong>{$lang->app_apptime}</strong></td>
				<td class="tcat"><strong>{$lang->app_correct}</strong></td>
			</tr>
			{$application_bit}
		</table>
		</td></tr>
	</table>
{$footer}
</body>
</html>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'application_misc_bit',
        'template' => $db->escape_string('<tr>
	<td class="trow1">
		{$charaname}
	</td>
	<td class="trow2">
		{$lang->app_daysleft} <b>{$dayscount}</b> (du hast insgesamt {$extendcount} Verlängert)
		<div class="smalltext">	{$lang->app_deadline} {$deadline}</div>
				
	</td>
	<td class="trow1">
		<b>	{$app_thread}</b>  {$add_correct}
	</td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    $insert_array = array(
        'title' => 'application_wob',
        'template' => $db->escape_string('<tr>
	<td class="tcat">{$lang->app_showthread}</td>
</tr>
<tr>
	<td class="trow1">		<form action="misc.php?action=wob&tid={$tid}" method="post" id="wobuser">
	 
		<input type="hidden" name="uid" value="{$thread[\'uid\']}" />
          <input type="hidden" name="tid" value="{$thread[\'tid\']}" />
          <input type="hidden" name="fid" value="{$thread[\'fid\']}" />
	<div class="wob_flex">    		<div>
		<select name="usergroup">
			<option>{$lang->app_chosegroup}</option>
			{$select_group}
		</select></div>
	<div>
		<select name="additionalgroups[]" id="additionalgroups[]" size="3" multiple="multiple">
			<option>{$lang->app_additionalgroup}</option>
			{$select_additiongroup}
		</select>
		</div>
		<div>
			<input value="{$lang->app_wobuser}" type="submit"  class="button" />
		</div>
			</div></form>	</td>
</tr>'),
        'sid' => '-1',
        'version' => '',
        'dateline' => TIME_NOW
    );
    $db->insert_query("templates", $insert_array);

    //CSS einfügen
    $css = array(
        'name' => 'application.css',
        'tid' => 1,
        'attachedto' => '',
        "stylesheet" => '/*Checklist*/

.checklist{
width:50%;
	margin:10px  auto;
 display: flex; 
	gap: 2px;
	justify-content: center;
	align-items: center;
	flex-wrap: wrap;

}

.checklist .check_title{
	font-weight: bold;
	padding: 5px;
	width: 100%;
	box-sizing: border-box;
}

.checklist .check_status{
	width: 9%;
	padding: 5px;
	text-align: center;
	box-sizing: border-box;
}

.checklist .check_fact{
	width: 90%;
		padding: 5px;
	box-sizing: border-box;
}

/*Showthread*/


.wob_flex{
	display: flex;
	align-items:  center;
	justify-content: center;
}

.wob_flex > div{
	margin: 0 10px;	
}  ',
        'cachefile' => $db->escape_string(str_replace('/', '', 'application.css')),
        'lastmodified' => time()
    );

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";

    $sid = $db->insert_query("themestylesheets", $css);
    $db->update_query("themestylesheets", array("cachefile" => "css.php?stylesheet=" . $sid), "sid = '" . $sid . "'", 1);

    $tids = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($tids)) {
        update_theme_stylesheet_list($theme['tid']);
    }

    // Don't forget this!
    rebuild_settings();
}

function application_is_installed()
{
    global $db;
    if ($db->table_exists("applications")) {
        return true;
    }
    return false;
}

function application_uninstall()
{
    global $db;
    //Tabelle aus Datenbank löschen
    if ($db->table_exists("applications")) {
        $db->drop_table("applications");
    }

    if ($db->field_exists("wobdate", "users")) {
        $db->drop_column("users", "wobdate");
    }

    $db->delete_query('settings', "name IN ('app_maindays','app_renewcount','app_renewdays','app_alert','app_appforum', 'app_groups', 'app_userfid', 'app_checklist', 'app_wobtext')");
    $db->delete_query('settinggroups', "name = 'application'");

    
    $db->delete_query("templates", "title LIKE '%application%'");

    require_once MYBB_ADMIN_DIR . "inc/functions_themes.php";
    $db->delete_query("themestylesheets", "name = 'application.css'");
    $query = $db->simple_select("themes", "tid");
    while ($theme = $db->fetch_array($query)) {
        update_theme_stylesheet_list($theme['tid']);
    }

    // Don't forget this
    rebuild_settings();
}

function application_activate()
{
    global $db, $cache;

    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        if (!$alertTypeManager) {
            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
        }

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode('alert_getcorrect'); // The codename for your alert type. Can be any unique string.
        $alertType->setEnabled(true);
        $alertType->setCanBeUserDisabled(true);

        $alertTypeManager->add($alertType);

        $alertType = new MybbStuff_MyAlerts_Entity_AlertType();
        $alertType->setCode('alert_wob'); // The codename for your alert type. Can be any unique string.
        $alertType->setEnabled(true);
        $alertType->setCanBeUserDisabled(true);

        $alertTypeManager->add($alertType);
    }

    require MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#" . preg_quote('{$pm_notice}') . "#i", '{$application_alert} {$pm_notice}');
    find_replace_templatesets("header", "#" . preg_quote('	<navigation>
				<br />	') . "#i", '	<navigation>
				<br />	{$checklist}');
    find_replace_templatesets("member_profile", "#" . preg_quote('{$userstars}<br />') . "#i", '{$userstars}<br /> {$wob}<br />');
    find_replace_templatesets("showthread", "#" . preg_quote('<strong>{$thread[\'displayprefix\']}{$thread[\'subject\']}</strong>') . "#i", '<strong>{$thread[\'displayprefix\']}{$thread[\'subject\']}</strong>
    {$application_correct}');
    find_replace_templatesets("showthread", "#" . preg_quote('<tr><td id="posts_container">') . "#i", '{$application_wob}<tr><td id="posts_container">');

}

function application_deactivate()
{
    global $db, $cache;

    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();

        if (!$alertTypeManager) {
            $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::createInstance($db, $cache);
        }

        $alertTypeManager->deleteByCode('alert_getcorrect');
        $alertTypeManager->deleteByCode('alert_wob');
    }
    require MYBB_ROOT . "/inc/adminfunctions_templates.php";
    find_replace_templatesets("header", "#" . preg_quote('{$application_alert}') . "#i", '', 0);
    find_replace_templatesets("header", "#" . preg_quote('{$checklist}') . "#i", '', 0);
    find_replace_templatesets("member_profile", "#" . preg_quote('{$wob}<br />') . "#i", '', 0);
    find_replace_templatesets("showthread", "#" . preg_quote('{$application_correct}') . "#i", '', 0);
    find_replace_templatesets("showthread", "#" . preg_quote('{$application_wob}') . "#i", '', 0);


}


function application_showthread()
{
    global $thread, $db, $mybb, $forum, $templates, $lang, $select_group, $tid, $application_wob, $correcteur, $application_correct;
    $lang->load("application");

    // settings
    $app_groups = $mybb->settings['app_groups'];
    $app_forum = $mybb->settings['app_appforum'];
    //variabeln leeren
    $tid = 0;
    $tid = $thread['tid'];

    if ($app_forum == $thread['fid']) {

        // korrigiert von
        $tuid = $thread['uid'];
        $correct = $db->fetch_field($db->simple_select("applications", "corrector", "uid = {$tuid}"), "corrector");

        if (!empty($correct)) {
            $c_user = $db->fetch_array($db->query("SELECT * FROM " . TABLE_PREFIX . "users WHERE uid = {$correct}"));
            $c_name = format_name($c_user['username'], $c_user['usergroup'], $c_user['displaygroup']);
            $charalink = build_profile_link($c_name, $c_user['uid']);
            $correcteur = $lang->sprintf($lang->app_showthread_correct, $charalink);
        } else {
            $add_correct = "<a href='misc.php?action=application_overview&correct={$tuid}' title='{$lang->app_correct_text}'>{$lang->app_correct_text}</a>";
            $correcteur = $lang->sprintf($lang->app_showthread_correct, $lang->app_showthread_correct_no) . " " . $add_correct;
        }
        eval ("\$application_correct = \"" . $templates->get("application_correct") . "\";");
        if ($app_groups == -1) {
            $get_groups = $db->query("SELECT *
        FROM " . TABLE_PREFIX . "usergroups
        where gid != 1
        and gid != 2
        ORDER BY usertitle ASC
        ");
            $select_group = "";
            while ($row = $db->fetch_array($get_groups)) {
                $select_group .= "<option value='{$row['gid']}'>{$row['usertitle']}</option>";
            }
        } else {
            $allgroups = explode(",", $app_groups);

            foreach ($allgroups as $group) {
                $get_groups = $db->query("SELECT *
            FROM " . TABLE_PREFIX . "usergroups
            WHERE gid = {$group}
            ORDER BY usertitle ASC
            ");
                $row = $db->fetch_array($get_groups);

                $select_group .= "<option value='{$row['gid']}'>{$row['usertitle']}</option>";
            }

        }

        // additionalgroups
        $get_groups = $db->query("SELECT *
        FROM " . TABLE_PREFIX . "usergroups
        where gid != 1
        and gid != 2
        ORDER BY usertitle ASC
        ");
        $select_additiongroup = "";
        while ($row = $db->fetch_array($get_groups)) {
            $select_additiongroup .= "<option value='{$row['gid']}'>{$row['usertitle']}</option>";
        }
        $uid = $db->fetch_field($db->simple_select("applications", "uid", "uid = {$tuid}"), "uid");
        if ($mybb->usergroup['canmodcp'] == 1 && $tuid == $uid) {
            eval ("\$application_wob .= \"" . $templates->get("application_wob") . "\";");
        }
    }
}

function application_misc()
{
    global $mybb, $templates, $lang, $header, $headerinclude, $footer, $db, $add_correct;
    $lang->load('application');

    // Einstellungen
    $app_maindays = intval($mybb->settings['app_maindays']);
    $app_renewcount = intval($mybb->settings['app_renewcount']);
    $app_renewdays = intval($mybb->settings['app_renewdays']);
    $playername = "fid" . intval($mybb->settings['app_userfid']);
    $appforum = intval($mybb->settings['app_appforum']);

    if ($mybb->get_input('action') == 'application_overview') {

        add_breadcrumb($lang->app_overview, "misc.php?action=application_overview");
        $lang->app_overview_text = $lang->sprintf($lang->app_overview_text, $app_renewcount, $app_renewdays);

        $get_app = $db->query("SELECT *
        FROM " . TABLE_PREFIX . "applications a
        LEFT JOIN " . TABLE_PREFIX . "users u
        on (a.uid = u.uid)
        where u.usergroup = 2
        ORDER BY a.appdeadline ASC
        ");

        while ($row = $db->fetch_array($get_app)) {
            // variabeln leeren
            $charaname = "";
            $dayscount = 0;
            $extendcount = 0;
            $deadline = "";
            $correct = "";
            $appthread = "";
            $regdate = "";
            $get_deadline = "";
            $uid = "";
            $extend = "";
            $app_thread = "";
            $add_correct = "";
            $app_addcorrecteur = "";

            $uid = $row['uid'];
            $extradays = $row['appdays'];
            $extendcount = $row['appcount'];
            if ($row['appcount'] < $app_renewcount && empty($row['corrector'])) {
                $extend = "<a href='misc.php?action=application_overview&extend={$uid}'>{$lang->app_extend}</a>";
            }

            $charaname = $chara = build_profile_link($row['username'], $row['uid']);

            $charaname = $charaname . $extend;
            $deadline = $row['appdeadline'];
            $regdate = $row['regdate'];
            $faktor = 86400;
            if ($extendcount != 0) {
                $extenddays = $row['appdays'] * $faktor;
                $deadline = $deadline + $extenddays;
            }


            $dayscount = round(($deadline - TIME_NOW) / $faktor) + 1;
            if ($dayscount > 0) {
                $dayscount = $dayscount . " Tage";
            } else {
                $dayscount = $lang->app_nodays;
            }

            $deadline = date("d.m.y", $deadline);

            $get_thread = $db->fetch_array($db->simple_select("threads", "*", "uid = {$uid} and fid = {$appforum}"));

            if (!empty($get_thread)) {
                $app_thread = "<a href='showthread.php?tid={$get_thread['tid']}'>{$lang->app_thread}</a>";
                if (empty($row['corrector']) && $mybb->usergroup['canmodcp'] == 1) {
                    $add_correct = "<a href='misc.php?action=application_overview&correct={$uid}' title='{$lang->app_correct_text}'>{$lang->app_addcorrecteur}</a>";
                } else {
                    $corr_name = $db->fetch_field($db->simple_select("userfields", $playername, "ufid = {$row['corrector']}"), $playername);
                    if (!empty($corr_name)) {
                        $add_correct = "<div class='smalltext'>{$lang->app_correcteur} {$corr_name}</div>";
                    } else {
                        $add_correct = "";
                    }

                }
            } else {
                $app_thread = $lang->app_nothread;

            }

            eval ("\$application_bit .= \"" . $templates->get("application_misc_bit") . "\";");
        }

        // Bewerbung verlängern
        $extend = $mybb->input['extend'];
        if ($extend) {

            $query = $db->simple_select("applications", "*", "uid = {$extend}");
            $charainfos = $db->fetch_array($query);

            $extradays = $charainfos['appdays'] + $app_renewdays;
            $extracount = $charainfos['appcount'] + 1;

            $extend_app = array(
                'appdays' => (int) $extradays,
                'appcount' => $extracount
            );

            $db->update_query("applications", $extend_app, "uid = {$extend}");
            redirect("misc.php?action=application_overview");
        }

        // Bewerbung übernehmen
        $correct = $mybb->input['correct'];

        if ($correct) {

            $corretor = $mybb->user['uid'];

            $get_correct = array(
                'corrector' => $corretor,
            );


            // Alert auslösen, weil wir wollen ja bescheid wissen, ne?!
            if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
                $alertType = MybbStuff_MyAlerts_AlertTypeManager::getInstance()->getByCode('alert_getcorrect');
                if ($alertType != NULL && $alertType->getEnabled() && $corretor != $correct) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert((int) $correct, $alertType);
                    MybbStuff_MyAlerts_AlertManager::getInstance()->addAlert($alert);
                }
            }

            $db->update_query("applications", $get_correct, "uid = {$correct}");
            redirect("misc.php?action=application_overview");

        }

        eval ("\$page = \"" . $templates->get("application_misc") . "\";");
        output_page($page);
    }

   // Dieser Code stammt aus dem Steckbriefplugin von risuana und wurde für diesen Plugin stellenweise angepasst.

    // Antwortpost für WoB erstellen
    if ($mybb->input['action'] == 'wob') {
        // Alle informationen ziehen
        $wobtext = $mybb->settings['app_wobtext'];
        $author = $mybb->input['uid'];
        $usergroup = $mybb->input['usergroup'];
        $subject = "{$mybb->input['subject']}";
        $username = $db->escape_string($mybb->user['username']);
        $posttid = $mybb->input['tid'];
        $fid = $mybb->input['fid'];
        $uid = $mybb->user['uid'];
        $ownip = $db->fetch_field($db->query("SELECT ip FROM " . TABLE_PREFIX . "sessions WHERE " . TABLE_PREFIX . "sessions.uid = '$uid'"), "ownip");

        // schau, ob es additionalgroups gibt
        if ($_POST['additionalgroups'] != "") {
            $get_additionalgroups = implode(",", $mybb->input['additionalgroups']);
        }

        // Usertabelle updaten
        $wob_usertable = array(
            "usergroup" => $usergroup,
            "additionalgroups" => $get_additionalgroups,
            "wobdate" => TIME_NOW
        );

        $db->update_query("users", $wob_usertable, "uid = {$author}");

        // Antwort-Post erstellen (für Annahme)
        $new_record = array(
            "tid" => $posttid,
            "replyto" => $posttid,
            "fid" => $fid,
            "subject" => "RE: ".$db->escape_string($subject),
            "icon" => "0",
            "uid" => $uid,
            "username" => $db->escape_string($username),
            "dateline" => TIME_NOW,
            "ipaddress" => $ownip,
            "message" => $db->escape_string($wobtext),
            "includesig" => "1",
            "smilieoff" => "0",
            "edituid" => "0",
            "edittime" => "0",
            "editreason" => "",
            "visible" => "1"
        );
        $db->insert_query("posts", $new_record);

        // Letzten Post im Forum updaten (für Annahme)
        $new_record = array(
            "lastpost" => TIME_NOW,
            "lastposter" => $db->escape_string($username),
            "lastposteruid" => $uid,
            "lastposttid" => $posttid,
            "lastpostsubject" => $db->escape_string($subject)
        );
	    
        $db->update_query("forums", $new_record, "fid = '$fid'");

        $new_record = array(
            "lastpost" => TIME_NOW,
            "lastposter" => $db->escape_string($username),
            "lastposteruid" => $uid,

        );
        $db->update_query("threads", $new_record, "tid = '$posttid'");

        $db->delete_query("applications", "uid = {$author}");
        redirect("showthread.php?tid={$posttid}");
    }
}

function application_global()
{
    global $db, $mybb, $lang, $templates, $application_alert, $checklist, $theme;

    // sprachdatei
    $lang->load('application');

    // Einstellungen
    $app_alert = intval($mybb->settings['app_alert']);
    $app_renewdays = intval($mybb->settings['app_renewdays']);
    $app_maindays = intval($mybb->settings['app_maindays']);
    $appforum = intval($mybb->settings['app_appforum']);
    $appchecklist = $mybb->settings['app_checklist'];

    // Funktion ausführen, bei der neue User hinzugefügt oder gelöscht werden soll
    getApplication();

    /*
    Bewerberfrist
    */

    // Einmal schauen, ob man mit Mainaccount oder Zweitaccount online ist
    $uid = intval($mybb->user['uid']);
    $as_uid = intval($mybb->user['as_uid']);
    // suche alle angehangenen accounts
    if ($as_uid == 0) {
        $select = $db->query("SELECT * FROM " . TABLE_PREFIX . "users 
        WHERE (as_uid = $uid) OR (uid = $uid)
        and usergroup = 2
        ORDER BY username ASC");
    } else if ($as_uid != 0) {
        //id des users holen wo alle angehangen sind
        $select = $db->query("SELECT * FROM " . TABLE_PREFIX . "users 
        WHERE (as_uid = $as_uid) OR (uid = $uid) OR (uid = $as_uid) 
           and usergroup = 2
        ORDER BY username ASC");
    }

    while ($app = $db->fetch_array($select)) {
        $application_alert = "";
        $uid = $app['uid'];
        $app_query = $db->query("SELECT *
        FROM " . TABLE_PREFIX . "applications
        where uid = {$uid}
        ");
        $application = $db->fetch_array($app_query);
        if (!empty($application)) {
            $extradays = $application['appdays'];
            $extendcount = $application['appcount'];
            $deadline = $application['appdeadline'];
            $regdate = $application['regdate'];
            $faktor = 86400;
            if ($extendcount != 0) {
                $extenddays = $application['appdays'] * $faktor;
                $deadline = $deadline + $extenddays;
            }
            $dayscount = round(($deadline - TIME_NOW) / $faktor) + 1;
            if ($dayscount <= $app_alert && $dayscount > 0) {
                $lang->app_alert = $lang->sprintf($lang->app_alert, $dayscount);
            }

            $get_thread = $db->fetch_array($db->simple_select("threads", "*", "uid = {$uid} and fid = {$appforum}"));
            if (empty($get_thread)) {
                eval ("\$application_alert = \"" . $templates->get("application_alert") . "\";");
            }
        }
    }

    /* 
    Bewerberchecklist auslesen
    */
    if ($mybb->user['usergroup'] == 2) {
        $avatarstatus = "";
        $steckistatus = "";
        $fidstatus = "";

        $allfids = explode(", ", $appchecklist);

        // Abfragen der Punkte

        // Avatar vorhanden
        if (!empty($mybb->user['avatar'])) {
            eval ("\$avatarstatus = \"" . $templates->get("application_checklist_check") . "\";");
        } else {
            eval ("\$avatarstatus = \"" . $templates->get("application_checklist_nocheck") . "\";");
        }

        foreach ($allfids as $fid) {
            $fidstatus = "";
            $get_fid = "";
            $get_fid = $db->fetch_field($db->simple_select("profilefields", "name, fid", "fid = {$fid}", array(
                "order_by" => 'fid',
                "order_dir" => 'ASC'
            )), "name");

            $checklist_point = $lang->sprintf($lang->checklist_fid, $get_fid);

            $fid = "fid" . $fid;
            if (!empty($mybb->user[$fid])) {
                eval ("\$fidstatus = \"" . $templates->get("application_checklist_check") . "\";");
            } else {
                eval ("\$fidstatus = \"" . $templates->get("application_checklist_nocheck") . "\";");
            }
            eval ("\$checklist_fid .= \"" . $templates->get("application_checklist_fid") . "\";");
        }


        // Bewerbung vorhanden
        $get_thread = $db->fetch_array($db->simple_select("threads", "*", "uid = {$mybb->user['uid']} and fid = {$appforum}"));
        if (!empty($get_thread)) {
            eval ("\$steckistatus = \"" . $templates->get("application_checklist_check") . "\";");
            $forum = "";
        } else {
            $forum = "<a href='forumdisplay.php?fid={$appforum}'>{$lang->checklist_appforum}</a>";
            eval ("\$steckistatus = \"" . $templates->get("application_checklist_nocheck") . "\";");
        }

        eval ("\$checklist = \"" . $templates->get("application_checklist") . "\";");
    }
}

function getApplication()
{
    global $db, $mybb;
    $app_maindays = $mybb->settings['app_maindays'];

    /*   Bewerber aus der Datenbank löschen, wenn nicht mehr existent
      Dieser Part stammt aus Sophies Bewerberplugin und wurde für diesen Plugin angepasst. */

    $get_deleteuser = $db->query("SELECT uid
      FROM " . TABLE_PREFIX . "applications 
      where uid not in (SELECT uid
      FROM " . TABLE_PREFIX . "users)
      ");

    while ($deletecharas = $db->fetch_array($get_deleteuser)) {
        $db->delete_query("applications", "uid = {$deletecharas['uid']}");
    }


    /*
     * Bewerber in die Datenbank laden. 
     * Dieser Part stammt aus Sophies Bewerberplugin und wurde für diesen Plugin angepasst.
     */

    if ($app_maindays != 0) {
        $get_newapp = $db->query("SELECT uid, regdate
        from " . TABLE_PREFIX . "users 
        where usergroup = 2      
        and uid not in(SELECT uid
        from " . TABLE_PREFIX . "applications)
        ");

        while ($row = $db->fetch_array($get_newapp)) {
            $deadline = new DateTime();
            $deadline->setTimestamp($row['regdate']);
            date_add($deadline, date_interval_create_from_date_string($app_maindays . 'days'));
            $deadline = strtotime($deadline->format('Y-m-d'));


            $addNewapp = array(
                'uid' => $row['uid'],
                'regdate' => $row['regdate'],
                'appdeadline' => $deadline,
                'appcount' => 0
            );

            $db->insert_query('applications', $addNewapp);

        }

    }
}


function application_alerts()
{
    global $mybb, $lang;
    $lang->load('application');

    /**
     * Alert, wenn der Steckbrief zur Korrektur übernommen worden ist.
     */
    class MybbStuff_MyAlerts_Formatter_AppCorrectFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        /**
         * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
         *
         * @return string The formatted alert string.
         */
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->lang->sprintf(
                $this->lang->alert_getcorrect,
                $outputAlert['from_user'],
                $outputAlert['dateline']
            );
        }


        /**
         * Init function called before running formatAlert(). Used to load language files and initialize other required
         * resources.
         *
         * @return void
         */
        public function init()
        {
        }

        /**
         * Build a link to an alert's content so that the system can redirect to it.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
         *
         * @return string The built alert, preferably an absolute link.
         */
        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
        {
            $alertContent = $alert->getExtraDetails();
            return;
        }
    }


    if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        $formatterManager->registerFormatter(
            new MybbStuff_MyAlerts_Formatter_AppCorrectFormatter($mybb, $lang, 'alert_getcorrect')
        );
    }
    /**
     * Alert, wenn der Steckbrief zur Korrektur übernommen worden ist.
     */
    class MybbStuff_MyAlerts_Formatter_AppWobFormatter extends MybbStuff_MyAlerts_Formatter_AbstractFormatter
    {
        /**
         * Format an alert into it's output string to be used in both the main alerts listing page and the popup.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to format.
         *
         * @return string The formatted alert string.
         */
        public function formatAlert(MybbStuff_MyAlerts_Entity_Alert $alert, array $outputAlert)
        {
            $alertContent = $alert->getExtraDetails();
            return $this->lang->sprintf(
                $this->lang->alert_wob,
                $outputAlert['from_user'],
                $outputAlert['dateline']
            );
        }


        /**
         * Init function called before running formatAlert(). Used to load language files and initialize other required
         * resources.
         *
         * @return void
         */
        public function init()
        {
        }

        /**
         * Build a link to an alert's content so that the system can redirect to it.
         *
         * @param MybbStuff_MyAlerts_Entity_Alert $alert The alert to build the link for.
         *
         * @return string The built alert, preferably an absolute link.
         */
        public function buildShowLink(MybbStuff_MyAlerts_Entity_Alert $alert)
        {
            $alertContent = $alert->getExtraDetails();
            return;
        }
    }


    if (class_exists('MybbStuff_MyAlerts_AlertFormatterManager')) {
        $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::getInstance();

        if (!$formatterManager) {
            $formatterManager = MybbStuff_MyAlerts_AlertFormatterManager::createInstance($mybb, $lang);
        }

        $formatterManager->registerFormatter(
            new MybbStuff_MyAlerts_Formatter_AppWobFormatter($mybb, $lang, 'alert_wob')
        );
    }
}

function application_member_profile()
{
    global $db, $mybb, $templates, $lang, $memprofile, $wob;
    $lang->load("application");

    $wob = "";
    if (!empty($memprofile['wobdate'])) {
        $wobdate = date("d.m.Y", $memprofile['wobdate']);
        $wob = $lang->sprintf($lang->app_wob_profile, $wobdate);
    } else {
        $wob = $lang->app_nowob_profile;
    }

}
