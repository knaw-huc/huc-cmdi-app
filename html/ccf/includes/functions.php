<?php

$smarty = new Mysmarty();
$db = new db();

function show_home()
{

    list_records();
    die;    
    global $db;
    global $smarty;

    $profiles = $db->getProfiles();
    $smarty->assign('profiles', $profiles);
    $smarty->assign('title', APPLICATION_NAME);
    $smarty->view('home');
}

function show_page($params)
{
    global $db;

    switch ($params["page"]) {
        case "profile":
            if (isset($params["action"])) {
                // with action defined, we check the action first
                $recordId = $params["id"];
                $action = $params["action"];
                if ($action == "download_record") {
                    // download record; action download
                    download_record($recordId);
                } elseif ($action == "delete_record") {
                    // delete record; action delete
                    $profile_id = $params["profile_id"];
                    delete_record($recordId);
                    header("Location: " . BASE_URL . "index.php?page=profile&id=$profile_id&state=records");
                }
            } else {
                // no action; show profile page
                if (isset($params["id"]) && ($profile = $db->getProfile($params["id"]))) {
                    if (isset($params["state"])) {
                        show_profile($profile[0], $params["state"]);
                    } else {
                        show_profile($profile[0]);
                        
                    }
                }
            }
            break;
        case "metadata":
            if (isset($params["id"]) && ($profile = $db->getProfileByMetaDataID($params["id"]))) {
                // print_r($params);
                // print_r($profile); // $params["id"] = 1 (in de URL)levert op: 'Interview', 'en', 5
                show_metadata($profile[0]["name"], $profile[0]["language"], $params["id"]);
            } else {
                show_home();
            }
            break;
        case "new_rec":
            if (isset($params["profile"])) {
                add_record($params["profile"]);
            } else {
                show_home();
            }
            break;
        case "add_record":
            add_record();
            break;
        case "list_records":
            list_records();
            break;
        default:
            show_home();
    }
}


function list_records() {
    global $smarty;
    global $db;
    $title = getenv('TITLE');
    $profilename = getenv('PROFILE');
    $profile = $db->getProfileData($profilename);
    // print_array($profile);
    $profile_id = $profile[0]['profile_id'];
    $state= 'records';
    $smarty->configLoad(ROOT . 'config/my.conf', 'Tabs');
    $parser = new Ccfparser();
    $profile["json"] = $parser->cmdi2json($profile["content"]);
    $mdRecords = $db->getMetadataRecords($profile_id);
    print_array($mdRecords);
    // TODO EXTRACT WITH THE IDS the relevant information from the XML records

    // die;
    $smarty->assign('state', $state);
    $smarty->assign('records', $mdRecords);
    $smarty->assign('profile', $profile);
    $smarty->assign('title', $title);
    $smarty->view('list_records');

}

function add_record($profile)
// $profile is here profileid
{
    global $db;
    global $smarty;

    //$profile = $_POST["profile_id"];
    $title = 'Another CMDI record';
    $md_id = $db->addRecord($title, $profile);

    // count the records + 1




    $mapName = CMDI_RECORD_PATH . "md$md_id";
    error_log($mapName);
    create_map($mapName);
    create_map("$mapName/resources");
    create_map("$mapName/metadata");
    header("Location: " . BASE_URL . "index.php?page=metadata&id=$md_id");
}

function show_profile($profile, $state = 'profile')
{
    global $smarty;
    global $db;
    // print_array($profile); // hier is $profile het gehele profiel 
    // die;
    $smarty->configLoad(ROOT . 'config/my.conf', 'Tabs');
    $profileTab = $smarty->getConfigVars('profileTab');
    $tweakTab = $smarty->getConfigVars('tweakTab');
    $recordsTab = $smarty->getConfigVars('recordsTab');

    $parser = new Ccfparser();
    $profile["json"] = $parser->cmdi2json($profile["content"]); // important! Creation of the json representation of cmdi
    //$profile["parsed"] = $parser->parseTweak($profile["tweak"]);
    $mdRecords = $db->getMetadataRecords($profile["profile_id"]);
    print_array($mdRecords);
    die;

    $smarty->assign('profileTab', $profileTab);
    $smarty->assign('tweakTab', $tweakTab);
    $smarty->assign('recordsTab', $recordsTab);
    $smarty->assign('state', $state);
    $smarty->assign('records', $mdRecords);
    $smarty->assign('profile', $profile);
    $smarty->assign('title', 'CMDI profile');
    $smarty->view('profile');
}

function show_metadata($name, $language, $recID)
{
    global $smarty;
    // echo 'hoi';
    // echo $name, $language, $recID;
    $_SESSION["rec_id"] = $recID;

    $errors = array();
    $cmdi = PROFILE_PATH . "$name.xml";
    $tweakFile = TWEAK_PATH . $name . "Tweak.xml";
    $tweaker = TWEAKER;
    $parser = new Ccfparser();
    $record = get_record_file($recID);
    $smarty->assign('lang', $language);
    if (!file_exists($cmdi)) {
        $errors[] = "Profile $name not found on disc!";
        show_errors($errors);
    } else {
        if (!file_exists($tweakFile)) {
            $json = $parser->parseTweak($cmdi, null, null, $record);
            $smarty->assign('title', 'CMDI Form');
            $smarty->assign('json', $json);
            $smarty->view('formPage');
        } else {
            if (!file_exists(TWEAKER)) {
                $errors[] = "Tweaker xslt not found on disc!";
                show_errors($errors);
            } else {
                $json = $parser->parseTweak($cmdi, $tweakFile, $tweaker, $record);
                if ($json) {
                    $smarty->assign('title', 'CMDI Form');
                    $smarty->assign('json', $json);
                    $smarty->view('formPage');
                } else {
                    $errors[] = "Error detected in xslt tranformation!";
                    show_errors($errors);
                }
            }
        }
    }
}

function get_record_file($id)
{
    $fileName = CMDI_RECORD_PATH . "md$id/metadata/record.cmdi";
    if (file_exists($fileName)) {
        return $fileName;
    } else {
        return null;
    }
}

function send_file_to_download_stream($fileName)
{
    $quoted = sprintf('"%s"', addcslashes(basename($fileName), '"\\'));
    $size = filesize($fileName);

    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=' . $quoted);
    header('Content-Transfer-Encoding: binary');
    header('Connection: Keep-Alive');
    header('Expires: 0');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    header('Content-Length: ' . $size);

    ob_clean();
    flush();
    readfile($fileName);
}

function download_record($id)
{
    $recordPath = CMDI_RECORD_PATH . "md$id";
    $downloadName = "md$id";
    $archiveName = "/tmp/${downloadName}.tar";
    $archiveZipName = "/tmp/${downloadName}.tar.gz";

    try {
        unlink($archiveName);
        unlink($archiveZipName);
        $archive = new PharData($archiveName, null, null, Phar::TAR);
        $archive->buildFromDirectory($recordPath);
        $archive->compress(Phar::GZ);
        send_file_to_download_stream($archiveZipName);
        return true;
    } catch (Exception $e) {
        echo "Error: ", $e->getMessage(), "\n";
        return false;
    }

}

function delete_record($id)
{
    global $db;

    $folderToRemove = sprintf("%s/%s/%s", CMDI_RECORD_PATH, METADATA_PATH, METADATA_FILENAME);
    try {
        $db->removeRecord($id);
        rmdir($folderToRemove);

        return true;
    } catch (Exception $e) {
        echo "Error: ", $e->getMessage(), "\n";
        return false;
    }
}

function show_errors($errors)
{
    $this->smarty->assign('errors', $errors);
    $this->smarty->assign('title', 'Error!');
    $this->smarty->view('errors');
}

function create_map($filename)
{
    if (!file_exists($filename)) {
        mkdir($filename);
    }
}

function print_array($array) {
    echo '<pre>';
    print_r($array);
    echo '</pre>';
}