# Bewerberübersicht
Mit diesem Plugin können deine Bewerber ihre Bewerbung selbstständig verlängern, bekommen eine Alert, wenn ihre Bewerbung vom Team übernommen wurde und bekomm eine Checkliste angezeigt, welche Profilfelder sie alles ausfüllen können. Es ist zudem möglich, sowohl Primäre, als auch sekundäre Usergruppen auszuwählen. Zudem wird das Datum des WoBs im Profil ausgelesen.

## DB Einträge
neue Tabelle: applications<br />
neues Spalte in der Usertabelle: wobdate

## neue Templates
- application_alert 	
- application_checklist 	
- application_checklist_check 	
- application_checklist_fid 	
- application_checklist_nocheck 	
- application_correct 	
- application_misc 	
- application_misc_bit 	
- application_wob

## CSS
application.css
```
/*Checklist*/

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
}
```
