<?php
/**
 * This makes sure any errors are displayed and not hidden.
 */
//error_reporting ( -1 );
//ini_set ( 'display_errors', true );

function task_application($task){
    global $db;
    /**
     * @Adriano: Alert users that the deadline has been reached.
     */
    if (class_exists('MybbStuff_MyAlerts_AlertTypeManager')) {
        $alertTypeManager = MybbStuff_MyAlerts_AlertTypeManager::getInstance();
        $alertType = $alertTypeManager->getByCode('alert_application_deadline_reached');
        if ($alertType != NULL && $alertType->getEnabled()) {
            $alertManager = MybbStuff_MyAlerts_AlertManager::getInstance();
            /**
             * Send an alert to every user that has reached the deadline.
             */
            $uids = [];
            $users_query = $db->simple_select("applications", "uid", "NOW() > appdeadline");
            while($uid = $db->fetch_field($users_query, "uid")) {
                $uids[] = $uid;
            }
            foreach ($uids AS $uid) {
                $username_query = $db->simple_select("users", "username", "uid = $uid");
                $username = $db->fetch_field($username_query, "username");
                $alert = new MybbStuff_MyAlerts_Entity_Alert($uid, $alertType);
                $alert->setExtraDetails(["application_uid" => $uid, "application_username" => $username]);
                $alertManager->addAlert($alert);
            }
            /**
             * Inform administrators about an applicant reaching the deadline.
             */
            if(count($uids) > 0) {
                $administrators = [];
                /**
                 * Users with administrator as primary group.
                 */
                $administrators_query = $db->simple_select("users", "uid", "usergroup = 4");
                while($uid = $db->fetch_field($administrators_query, "uid")) {
                    $administrators[] = $uid;
                }
                /**
                 * Users with administrator as a secondary group.
                 */
                $users_query = $db->simple_select("users", "uid, additionalgroups", "4 IN (additionalgroups)");
                while($user = $db->fetch_array($administrators_query)) {
                    /**
                     * We have to make sure that the user is actually an administrator. The SQL WHERE condition is also true if
                     * the user is in any group that contains a "4" e.g. "14"...
                     */
                    $additionalgroups = explode(",", $user["additionalgroups"]);
                    if(in_array("4", $additionalgroups)) {
                        $administrators[] = $user['uid'];
                    }
                }
                foreach($administrators AS $administrator) {
                    $alert = new MybbStuff_MyAlerts_Entity_Alert($administrator, $alertType);
                    $alertManager->addAlert($alert);
                }
            }
        }
    }
    add_task_log($task, 'Bewerbungsfristen wurden erfolgreich geprüft.');
}