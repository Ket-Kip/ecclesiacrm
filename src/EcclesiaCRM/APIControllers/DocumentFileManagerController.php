<?php

namespace EcclesiaCRM\APIControllers;

use Psr\Container\ContainerInterface;
use Slim\Http\Response;
use Slim\Http\ServerRequest;

// Documents filemanager APIs
use EcclesiaCRM\UserQuery;
use EcclesiaCRM\PersonQuery;
use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\Note;
use EcclesiaCRM\NoteQuery;
use EcclesiaCRM\Utils\MiscUtils;
use Propel\Runtime\ActiveQuery\Criteria;
use EcclesiaCRM\SessionUser;

class DocumentFileManagerController
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    private function reArrayFiles(&$file_post)
    {

        $file_ary = array();
        $file_count = count($file_post['name']);
        $file_keys = array_keys($file_post);

        for ($i = 0; $i < $file_count; $i++) {
            foreach ($file_keys as $key) {
                $file_ary[$i][$key] = $file_post[$key][$i];
            }
        }

        return $file_ary;
    }

    private function numberOfFiles($personID)
    {
        $user = UserQuery::create()->findPk($personID);

        if (is_null($user)) {// in the case the user is null
            return 0;
        }

        $realNoteDir = $userDir = $user->getUserRootDir();
        $userName = $user->getUserName();
        $currentpath = $user->getCurrentpath();

        $currentNoteDir = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath;

        $files = array_diff(scandir($currentNoteDir), array('.', '..', '.DS_Store', '._.DS_Store'));

        return count($files);
    }

    public function getAllFileNoteForPerson(ServerRequest $request, Response $response, array $args): Response
    {
        $user = UserQuery::create()->findPk($args['personID']);

        $realUserID = SessionUser::getUser()->getPersonId();

        if (SessionUser::getUser()->isEDriveEnabled() and is_null($user) || $realUserID != $args['personID']) {// in the case the user is null
            return $response->withJson(["files" => [] ]);
        }

        $realNoteDir = $userDir = $user->getUserRootDir();
        $userName = $user->getUserName();
        $currentpath = $user->getCurrentpath();

        $currentNoteDir = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath;

        $result = [];
        $files = array_diff(scandir($currentNoteDir), array('.', '..', '.DS_Store', '._.DS_Store'));
        foreach ($files as $file) {
            if ($file[0] == '.') {
                continue;
            }

            $extension = pathinfo($file, PATHINFO_EXTENSION);

            $note = NoteQuery::Create()->filterByPerId($args['personID'])->findOneByText($userName . $currentpath . $file);

            $item['isShared'] = 0;
            $item['id'] = 0;
            $item['perID'] = 0;// by default the file longs to the owner

            if (!is_null($note)) {
                $item['id'] = $note->getId();
                $item['isShared'] = $note->isShared();
                $item['perID'] = $note->getPerId();
            } else {
                $fileName = basename($file);

                // now we create the note
                $note = new Note();
                $note->setPerId($args['personID']);
                $note->setFamId(0);
                $note->setTitle($fileName);
                $note->setPrivate(1);
                $note->setText($userName . $currentpath . $fileName);
                $note->setType('file');
                $note->setEntered(SessionUser::getUser()->getPersonId());
                $note->setInfo(gettext('Create file'));

                $note->save();

                $item['id'] = $note->getId();
                $item['isShared'] = $note->isShared();
                $item['perID'] = $note->getPerId();
            }

            $item['name'] = $file;
            $item['date'] = date(SystemConfig::getValue("sDateFormatLong"), filemtime($currentNoteDir . "/" . $file));
            $item['type'] = $extension;
            $item['size'] = MiscUtils::FileSizeConvert(filesize($currentNoteDir . "/" . $file));
            $item['icon'] = MiscUtils::FileIcon($file);
            $item['path'] = $userName . $currentpath . $file;

            $size = 28;

            $item['dir'] = false;
            if (is_dir("$currentNoteDir/$file")) {
                $item['name'] = "/" . $file;
                $item['dir'] = true;
                $item['icon'] = SystemURLs::getRootPath() . "/Images/Icons/FOLDER.png"; //'far fa-folder text-yellow';
                $item['type'] = gettext("Folder");
                $size = 40;
            }

            $item['icon'] = '<img src="' . $item['icon']  . '" width="' . $size . '">';//;"<i class='" . $item['icon'] . " fa-2x'></i>";

            $result[] = $item;
        }

        return $response->withJson(["files" => $result ]);
    }

    public function getRealFile(ServerRequest $request, Response $response, array $args): Response
    {
        $user = UserQuery::create()->findPk($args['personID']);
        $name = $request->getAttribute('path');

        if (!is_null($user) ) {
            $per = PersonQuery::Create()->findOneById($args['personID']);

            $realNoteDir = $userDir = $user->getUserRootDir();
            $userName = $user->getUserName();
            $currentpath = $user->getCurrentpath();

            $searchLikeString = $name . '%';
            $searchLikeString = str_replace("//", "/", $searchLikeString);

            $note = NoteQuery::Create()->filterByPerId($args['personID'])->filterByText($searchLikeString, Criteria::LIKE)->findOne();

            if (is_null($note)) {
                $fileName = basename($name);

                // now we create the note
                $note = new Note();
                $note->setPerId($args['personID']);
                $note->setFamId(0);
                $note->setTitle($fileName);
                $note->setPrivate(1);
                $note->setText($userName . $currentpath . $fileName);
                $note->setType('file');
                $note->setEntered(SessionUser::getUser()->getPersonId());
                $note->setInfo(gettext('Create file'));

                $note->save();
            }

            if (!is_null($note) && ($note->isShared() > 0 || SessionUser::getUser()->isAdmin()
                    || SessionUser::getUser()->getPersonId() == $args['personID']
                    || $per->getFamId() == SessionUser::getUser()->getPerson()->getFamId())) {
                $file = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . MiscUtils::convertUTF8AccentuedString2Unicode($name);

                if ( !file_exists($file) ) {// in the case the file name isn't in unicode format
                    $file = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $name;
                }

                if ( !file_exists($file) ) {
                    // in this case the note is no more usefull
                    if ( !is_null($note) ) {
                        $note->delete();
                    }
                    return $response->withStatus(404)
                        ->withHeader('Content-Type', 'text/html')
                        ->write( gettext('Document not found') );
                }

                $response = $response
                    ->withHeader('Content-Type', 'application/octet-stream')
                    ->withHeader('Content-Disposition', 'attachment;filename="' . basename($file) . '"')
                    ->withAddedHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                    ->withHeader('Cache-Control', 'post-check=0, pre-check=0')
                    ->withHeader('Pragma', 'no-cache')
                    ->withBody((new \Slim\Psr7\Stream(fopen($file, 'rb'))));

                return $response;
            }
        }

        return $response->withStatus(404);
    }

    public function getPreview(ServerRequest $request, Response $response, array $args): Response
    {
        $params = (object)$request->getParsedBody();

        if (SessionUser::getUser()->isEDriveEnabled() and isset ($params->personID) and isset ($params->name) and SessionUser::getId() == $params->personID) {
            $user = UserQuery::create()->findPk($params->personID);
            if (!is_null($user)) {
                $userName = $user->getUserName();
                $currentPath = $user->getCurrentpath();
                $extension = pathinfo($params->name, PATHINFO_EXTENSION);

                if (!(
                    strtolower($extension) == 'mp4' || strtolower($extension) == 'mov' || strtolower($extension) == 'ogg' || strtolower($extension) == 'm4a'
                    || strtolower($extension) == 'txt' || strtolower($extension) == 'ps1' || strtolower($extension) == 'c' || strtolower($extension) == 'cpp'
                    || strtolower($extension) == 'php' || strtolower($extension) == 'js' || strtolower($extension) == 'mm' || strtolower($extension) == 'vcf'
                    || strtolower($extension) == 'pdf' || strtolower($extension) == 'mp3' || strtolower($extension) == 'py' || strtolower($extension) == 'ru'
                    || strtolower($extension) == 'm' || strtolower($extension) == 'vbs' || strtolower($extension) == 'admx' || strtolower($extension) == 'adml'
                    || strtolower($extension) == 'ics' || strtolower($extension) == 'csv' || strtolower($extension) == 'sql' || strtolower($extension) == 'docx'
                )) {
                    return $response->withJson(['success' => true, 'path' => MiscUtils::simpleEmbedFiles(SystemURLs::getRootPath() . "/api/filemanager/getFile/" . $params->personID . "/" . $userName . $currentPath . $params->name)]);
                } else {
                    $realNoteDir = $userDir = $user->getUserRootDir();
                    return $response->withJson(['success' => true, 'path' => MiscUtils::simpleEmbedFiles(SystemURLs::getRootPath() . "/api/filemanager/getFile/" . $params->personID . "/" . $userName . $currentPath . $params->name, SystemURLs::getRootPath() . "/" . $user->getUserRootDir() . "/" . $userName . $currentPath . $params->name)]);
                }
            }
        }

        return $response->withStatus(404);
    }

    public function changeFolder(ServerRequest $request, Response $response, array $args): Response
    {
        $params = (object)$request->getParsedBody();

        if (SessionUser::getUser()->isEDriveEnabled() and isset ($params->personID) && isset ($params->folder) and SessionUser::getId() == $params->personID) {

            $user = UserQuery::create()->findPk($params->personID);
            if (!is_null($user)) {
                $user->setCurrentpath($user->getCurrentpath() . substr($params->folder, 1) . "/");
                $user->save();

                $_SESSION['user'] = $user;

                return $response->withJson(['success' => true, "currentPath" => MiscUtils::pathToPathWithIcons($user->getCurrentpath()), "numberOfFiles" => $this->numberOfFiles($params->personID)]);
            }
        }

        return $response->withJson(['success' => false]);
    }

    public function folderBack(ServerRequest $request, Response $response, array $args): Response
    {
        $params = (object)$request->getParsedBody();

        if (SessionUser::getUser()->isEDriveEnabled() and isset ($params->personID) and SessionUser::getId() == $params->personID) {

            $user = UserQuery::create()->findPk($params->personID);
            if (!is_null($user)) {
                $currentPath = $user->getCurrentpath();

                $len = strlen($currentPath);

                for ($i = $len - 2; $i > 0; $i--) {
                    if ($currentPath[$i] == "/") {
                        break;
                    }
                }

                $currentPath = substr($currentPath, 0, $i + 1);

                if ($currentPath == '') {
                    $currentPath = "/";
                }

                $user->setCurrentpath($currentPath);

                $user->save();

                $_SESSION['user'] = $user;

                return $response->withJson(['success' => true, "currentPath" => MiscUtils::pathToPathWithIcons($currentPath), "isHomeFolder" => ($currentPath == "/") ? true : false, "numberOfFiles" => $this->numberOfFiles($params->personID)]);
            }
        }

        return $response->withJson(['success' => false]);
    }

    public function deleteOneFolder(ServerRequest $request, Response $response, array $args): Response
    {
        $params = (object)$request->getParsedBody();

        if (SessionUser::getUser()->isEDriveEnabled() and isset ($params->personID) and isset ($params->folder) and SessionUser::getId() == $params->personID) {

            $user = UserQuery::create()->findPk($params->personID);
            if (!is_null($user)) {
                $realNoteDir = $userDir = $user->getUserRootDir();
                $userName = $user->getUserName();
                $currentpath = $user->getCurrentpath();

                $currentNoteDir = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . $params->folder;

                $searchLikeString = $userName . $currentpath . substr($params->folder, 1) . '%';
                $searchLikeString = str_replace("//", "/", $searchLikeString);
                $notes = NoteQuery::Create()->filterByPerId($params->personID)->filterByText($searchLikeString, Criteria::LIKE)->find();

                if ($notes->count() > 0) {
                    $notes->delete();
                }

                $ret = MiscUtils::delTree($currentNoteDir);

                return $response->withJson(['success' => $ret, "numberOfFiles" => $this->numberOfFiles($params->personID)]);
            }
        }

        return $response->withJson(['success' => false]);
    }

    public function deleteOneFile(ServerRequest $request, Response $response, array $args): Response
    {
        $params = (object)$request->getParsedBody();

        if (SessionUser::getUser()->isEDriveEnabled() and isset ($params->personID) and isset ($params->file) and SessionUser::getId() == $params->personID) {

            $user = UserQuery::create()->findPk($params->personID);
            if (!is_null($user)) {
                $realNoteDir = $userDir = $user->getUserRootDir();
                $userName = $user->getUserName();
                $currentpath = $user->getCurrentpath();

                $currentNoteDir = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . MiscUtils::convertUTF8AccentuedString2Unicode($params->file);

                if (!file_exists($currentNoteDir)) {// in the case the file name isn't in unicode format
                    $currentNoteDir = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . $params->file;
                }

                $searchLikeString = $userName . $currentpath . $params->file . '%';
                $searchLikeString = str_replace("//", "/", $searchLikeString);
                $notes = NoteQuery::Create()->filterByPerId($params->personID)->filterByText($searchLikeString, Criteria::LIKE)->find();

                if ($notes->count() > 0) {
                    $notes->delete();
                }

                $ret = unlink($currentNoteDir);

                return $response->withJson(['success' => $ret, "numberOfFiles" => $this->numberOfFiles($params->personID)]);
            }
        }

        return $response->withJson(['success' => false]);
    }

    public function deleteFiles(ServerRequest $request, Response $response, array $args): Response
    {
        $params = (object)$request->getParsedBody();

        if (SessionUser::getUser()->isEDriveEnabled() and isset ($params->personID) and isset ($params->files) and SessionUser::getId() == $params->personID ) {

            $error = [];

            $user = UserQuery::create()->findPk($params->personID);
            if (!is_null($user)) {
                $realNoteDir = $userDir = $user->getUserRootDir();
                $userName = $user->getUserName();
                $currentpath = $user->getCurrentpath();

                foreach ($params->files as $file) {
                    if ($file[0] == '/') {
                        // we're in a case of a folder
                        $currentNoteDir = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . $file;

                        $currentNoteDir = str_replace("//", "/", $currentNoteDir);

                        if ($currentpath . $file == "//public") {
                            $error[] = _("You can't erase the public folder !");
                            continue;
                        }

                        if (MiscUtils::delTree($currentNoteDir)) {
                            $searchLikeString = $userName . $currentpath . $file . '%';
                            $searchLikeString = str_replace("//", "/", $searchLikeString);

                            $notes = NoteQuery::Create()->filterByPerId($params->personID)->filterByText($searchLikeString, Criteria::LIKE)->find();

                            if ($notes->count() > 0) {
                                $notes->delete();
                            }
                        }
                    } else {
                        // in the case of a file
                        $currentNoteDir = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . $file;

                        $currentNoteDir = str_replace("//", "/", $currentNoteDir);

                        $utf8Test = MiscUtils::convertUTF8AccentuedString2Unicode($currentNoteDir);
                        if (file_exists($utf8Test)) {// in the case the file name isn't in unicode format
                            $currentNoteDir = $utf8Test;
                        }

                        if (unlink($currentNoteDir)) {
                            $searchLikeString = $userName . $currentpath . $file . '%';
                            $searchLikeString = str_replace("//", "/", $searchLikeString);

                            $notes = NoteQuery::Create()->filterByPerId($params->personID)->filterByText($searchLikeString, Criteria::LIKE)->find();

                            if ($notes->count() > 0) {
                                $notes->delete();
                            }
                        }
                    }
                }

                return $response->withJson(['success' => true, "numberOfFiles" => $this->numberOfFiles($params->personID), 'error' => $error]);
            }
        }

        return $response->withJson(['success' => false]);
    }

    public function movefiles(ServerRequest $request, Response $response, array $args): Response
    {
        $params = (object)$request->getParsedBody();

        if (SessionUser::getUser()->isEDriveEnabled() and isset ($params->personID) and isset ($params->folder) and isset ($params->files) and SessionUser::getId() == $params->personID) {
            $user = UserQuery::create()->findPk($params->personID);

            if (!is_null($user)) {
                $realNoteDir = $userDir = $user->getUserRootDir();
                $userName = $user->getUserName();
                $currentpath = $user->getCurrentpath();

                $currentNoteDir = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . $params->files;

                foreach ($params->files as $file) {
                    if ($file[0] == '/') {
                        // we're in a case of a folder
                        // $file is a folder here
                        $currentDest = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . $file;
                        $newDest = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . substr($params->folder, 1) . $file;

                        if (strpos($newDest, $userName . "/public/../") > 0) {
                            $newDest = str_replace("/public/../", "/", $newDest);
                        }

                        if (is_dir($newDest)) {
                            return $response->withJson(['success' => false, "message" => gettext("A Folder") . " \"" . substr($file, 1) . "\" " . gettext("already exists at this place.")]);
                            break;
                        }

                        mkdir($newDest, 0755, true);

                        if (rename($currentDest, $newDest)) {
                            $searchLikeString = $userName . $currentpath . substr($file, 1) . '%';
                            $searchLikeString = str_replace("//", "/", $searchLikeString);

                            $notes = NoteQuery::Create()->filterByPerId($params->personID)->filterByText($searchLikeString, Criteria::LIKE)->find();

                            if ($notes->count() > 0) {
                                if ($params->folder == '/..') {
                                    // we're goaing back
                                    $dropDir = $userName . dirname($currentpath) . "/";
                                } else {
                                    // the new currentPath
                                    $dropDir = $userName . $currentpath . substr($params->folder, 1) . "/";
                                }

                                $dropDir = str_replace("//", "/", $dropDir);

                                foreach ($notes as $note) {
                                    // we have to change all the files and the folders
                                    $rest = str_replace($userName . $currentpath, "", $note->getText());

                                    $note->setText($dropDir . $rest);

                                    if ($note->getType() == 'folder') {
                                        $note->setInfo(gettext('Folder modification'));
                                    } else {
                                        $note->setInfo(gettext('File modification'));
                                    }

                                    $note->setEntered(SessionUser::getUser()->getPersonId());
                                    $note->save();
                                }
                            }
                        }
                    } else {
                        $currentDest = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . $file;
                        $newDest = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . substr($params->folder, 1) . "/" . $file;

                        if (strpos($newDest, $userName . "/public/../") > 0) {
                            $newDest = str_replace("/public/../", "/", $newDest);
                        }


                        if (file_exists($newDest)) {
                            return $response->withJson(['success' => false, "message" => gettext("A File") . " \"" . $file . "\" " . gettext("already exists at this place.")]);
                            break;
                        }

                        if (rename($currentDest, $newDest)) {
                            $searchLikeString = $userName . $currentpath . $file . '%';
                            $searchLikeString = str_replace("//", "/", $searchLikeString);
                            $notes = NoteQuery::Create()->filterByPerId($params->personID)->filterByText($searchLikeString, Criteria::LIKE)->find();

                            if ($notes->count() > 0) {
                                // we have to change all the files
                                if ($params->folder == '/..') {
                                    // we're going back
                                    $dropDir = $userName . dirname($currentpath) . "/";
                                } else {
                                    // the new currentPath
                                    $dropDir = $userName . $currentpath . substr($params->folder, 1) . "/";
                                }

                                $dropDir = str_replace("//", "/", $dropDir);

                                foreach ($notes as $note) {
                                    $rest = str_replace($userName . $currentpath, "", $note->getText());

                                    $note->setText($dropDir . $rest);
                                    $note->setInfo(gettext('File modification'));
                                    $note->setEntered(SessionUser::getUser()->getPersonId());
                                    $note->save();
                                }
                            }
                        }
                    }
                }

                return $response->withJson(['success' => true, "numberOfFiles" => $this->numberOfFiles($params->personID)]);
            }
        }

        return $response->withJson(['success' => false]);
    }

    public function newFolder(ServerRequest $request, Response $response, array $args): Response
    {
        $params = (object)$request->getParsedBody();

        if (SessionUser::getUser()->isEDriveEnabled() and isset ($params->personID) and isset ($params->folder) and SessionUser::getId() == $params->personID) {

            $user = UserQuery::create()->findPk($params->personID);
            if (!is_null($user)) {
                $realNoteDir = $userDir = $user->getUserRootDir();
                $userName = $user->getUserName();
                $currentpath = $user->getCurrentpath();

                $currentNoteDir = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . $params->folder;

                if (is_dir($currentNoteDir)) {
                    return $response->withJson(['success' => false, "message" => gettext("A Folder") . " \"" . $params->folder . "\" " . gettext("already exists at this place.")]);
                }

                // now we create the note
                $note = new Note();
                $note->setPerId($params->personID);
                $note->setFamId(0);
                $note->setTitle($params->folder);
                $note->setPrivate(1);
                $note->setText($userName . $currentpath . $params->folder);
                $note->setType('folder');
                $note->setEntered(SessionUser::getUser()->getPersonId());
                $note->setInfo(gettext('New Folder'));

                $note->save();

                mkdir($currentNoteDir, 0755, true);

                return $response->withJson(['success' => $currentNoteDir, "numberOfFiles" => $this->numberOfFiles($params->personID)]);
            }
        }

        return $response->withJson(['success' => false]);
    }

    public function renameFile(ServerRequest $request, Response $response, array $args): Response
    {
        $params = (object)$request->getParsedBody();

        if (SessionUser::getUser()->isEDriveEnabled() and isset ($params->personID) and isset ($params->oldName) and isset ($params->newName) and isset ($params->type) and SessionUser::getId() == $params->personID) {

            $user = UserQuery::create()->findPk($params->personID);
            if (!is_null($user)) {
                $realNoteDir = $userDir = $user->getUserRootDir();
                $userName = $user->getUserName();
                $currentpath = $user->getCurrentpath();
                $extension = pathinfo($params->oldName, PATHINFO_EXTENSION);

                $oldName = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . MiscUtils::convertUTF8AccentuedString2Unicode($params->oldName);
                if (!file_exists($oldName)) {// in the case the file name isn't in unicode format
                    $oldName = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . $params->oldName;
                }
                $newName = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath . $params->newName . (($params->type == 'file') ? "." . $extension : "");

                if (rename($oldName, $newName)) {
                    $searchLikeString = $userName . $currentpath . $params->oldName;

                    $oldDir = $searchLikeString = str_replace("//", "/", $searchLikeString);

                    $notes = NoteQuery::Create()->filterByPerId($params->personID)->filterByText($searchLikeString . '%', Criteria::LIKE)->find();

                    if ($notes->count() > 0) {
                        foreach ($notes as $note) {
                            // we have to change all the files
                            $oldName = $note->getText();
                            if ($params->type == 'file') {
                                $note->setText($userName . $currentpath . $params->newName . "." . $extension);
                            } else {
                                // in the case of a folder
                                $newDir = $userName . $currentpath . $params->newName;
                                $newDir = str_replace("//", "/", $newDir);

                                $note->setText(str_replace($oldDir, $newDir, $oldName));
                            }

                            $note->setEntered(SessionUser::getUser()->getPersonId());
                            $note->save();
                        }
                    }

                    return $response->withJson(['success' => true, "numberOfFiles" => $this->numberOfFiles($params->personID)]);
                }
            }
        }

        return $response->withJson(['success' => false]);
    }

    public function uploadFile(ServerRequest $request, Response $response, array $args): Response
    {
        if (SessionUser::getUser()->isEDriveEnabled() and SessionUser::getId() != $args['personID']) {
            return $response->withStatus(401);
        }

        $user = UserQuery::create()->findPk($args['personID']);

        $realNoteDir = $userDir = $user->getUserRootDir();
        $publicNoteDir = $user->getUserPublicDir();
        $userName = $user->getUserName();
        $currentpath = $user->getCurrentpath();

        if (!isset($_FILES['noteInputFile'])) {
            return $response->withJson(['success' => "failed"]);
        }

        $currentNoteDir = dirname(__FILE__) . "/../../" . $realNoteDir . "/" . $userName . $currentpath;

        $file_ary = $this->reArrayFiles($_FILES['noteInputFile']);

        foreach ($file_ary as $file) {

            $fileName = basename($file["name"]);
            $real_extension = pathinfo($fileName, PATHINFO_EXTENSION);
            /*if (str_starts_with(  $currentpath, '/public/' )) {
                $extension = MiscUtils::SanitizeExtension(pathinfo($fileName, PATHINFO_EXTENSION));
            } else {*/
                $extension = $real_extension;
            //}

            if ($real_extension != $extension) {
                $fileName = str_replace(".".$real_extension, ".".$extension, $fileName);
            }
            $target_file = $currentNoteDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                // now we create the note
                $note = new Note();
                $note->setPerId($args['personID']);
                $note->setFamId(0);
                $note->setTitle($fileName);
                $note->setPrivate(1);
                $note->setText($userName . $currentpath . $fileName);
                $note->setType('file');
                $note->setEntered(SessionUser::getUser()->getPersonId());
                $note->setInfo(gettext('Create file'));

                $note->save();
            }
        }

        return $response->withJson(['success' => true, "numberOfFiles" => $this->numberOfFiles($args['personID'])]);
    }

    public function getRealLink(ServerRequest $request, Response $response, array $args): Response
    {
        $params = (object)$request->getParsedBody();

        if (SessionUser::getUser()->isEDriveEnabled() and isset ($params->personID) and isset ($params->pathFile) and SessionUser::getId() == $params->personID) {
            $user = UserQuery::create()->findPk($params->personID);
            if (!is_null($user)) {

                $userName = $user->getUserName();
                $currentpath = $user->getCurrentpath();
                $privateNoteDir = $user->getUserRootDir();
                $publicNoteDir = $user->getUserPublicDir();
                $fileName = basename($params->pathFile);
                $publicDir = $user->getUserName() . "/public/";

                $protocol = isset($_SERVER["HTTPS"]) ? 'https' : 'http';

                if (strpos($params->pathFile, $publicDir) === false) {
                    $dropAddress = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/api/filemanager/getFile/" . $user->getPersonId() . "/" . $userName . $currentpath . $fileName;
                } else {
                    $fileName = str_replace($publicDir, "", $params->pathFile);
                    $dropAddress = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/" . $publicNoteDir . "/" . $fileName;
                }

                return $response->withJson(['success' => "success", "privateNoteDir" => $privateNoteDir, "publicNoteDir" => $publicNoteDir, 'fileName' => $fileName, "address" => $dropAddress]);
            }
        }

        return $response->withJson(['success' => "failed"]);
    }

    public function setpathtopublicfolder(ServerRequest $request, Response $response, array $args): Response
    {
        $currentpath = SessionUser::getUser()->getCurrentpath();

        if (strpos($currentpath, "/public/") === false) {
            $user = UserQuery::create()->findPk(SessionUser::getUser()->getPersonId());
            $user->setCurrentpath("/public/");
            $user->save();

            $_SESSION['user'] = $user;

            return $response->withJson(['success' => "failed"]);
        }

        return $response->withJson(['success' => "success"]);
    }
}
