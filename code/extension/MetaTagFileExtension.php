<?php

/**
 * add functionality to files
 *
 *
 */

class MetaTagFileExtension extends DataExtension
{
    private $stillNeedsToReplace = true;

    private $imageTrackingAddAgain = array();

    public function onBeforeWrite()
    {
        if ($this->stillNeedsToReplace && $this->owner instanceof Image && $this->owner->ID) {
            $this->stillNeedsToReplace = false;
            $oldObject = File::get()->byID($this->owner->ID);
            $oldFileName = "";
            if ($oldObject->Name) {
                $oldFileName = $oldObject->Name;
            }
            if ($oldFileName) {
                $oldPath = "";
                if (isset($oldObject->Filename)) {
                    $oldPath = $oldObject->Filename;
                }
                if (!$oldPath) {
                    $oldPath = $oldObject->getFilename();
                }
                $oldPath = str_replace($oldFileName, "", $oldPath);
                $newFileName = $this->owner->Name;
                $newPath = $this->owner->getRelativePath();
                $newPath = str_replace($newFileName, "", $newPath);
                if (($oldFileName != $newFileName) || ($oldPath != $oldPath)) {
                    $checks = MetaTagCMSControlFileUse::get()
                        ->filter(
                            array(
                                "ConnectionType" => "DB",
                                "IsLiveVersion" => "0"
                            )
                        );
                    $siteTreeItemsToChange = SiteTree::get()
                        ->where("\"SiteTree_ImageTracking\".\"FileID\" = ".$this->owner->ID)
                        ->innerJoin("SiteTree_ImageTracking", "\"SiteTree_ImageTracking\".\"SiteTreeID\" = \"SiteTree\".\"ID\"");
                    if ($siteTreeItemsToChange && $siteTreeItemsToChange->count()) {
                        foreach ($siteTreeItemsToChange as $siteTreeItemToChange) {
                            $trackings = $siteTreeItemToChange->ImageTracking();
                            if ($trackings && $trackings->count()) {
                                foreach ($trackings as $tracking) {
                                    $array = array(
                                        "DataObjectClassName" => "SiteTree",
                                        "DataObjectFieldName" => $tracking->FieldName,
                                        "FileID" => $tracking->ID,
                                        "TrackedID" => $siteTreeItemToChange->ID,
                                        "IsImageTracking" => true
                                    );
                                    $item = new ArrayData($array);
                                    $this->imageTrackingAddAgain[] = $array;
                                    $checks->push($item);
                                }
                            }
                        }
                    }
                    if ($checks && $checks->count()) {
                        foreach ($checks as $check) {
                            $className = $check->DataObjectClassName;
                            $fieldName = $check->DataObjectFieldName;
                            if (isset($check->IsImageTracking) && $check->IsImageTracking) {
                                $dosToChange = $className::get()->filter(array("ID" => $check->TrackedID));
                            } else {
                                $dosToChange = $className::get()->where("LOCATE('$oldFileName', \"".$fieldName."\") > 0");
                            }
                            if ($dosToChange && $dosToChange->count()) {
                                foreach ($dosToChange as $doToChange) {
                                    $dom = new DOMDocument;
                                    $dom->loadHTML('<?xml encoding="UTF-8">'.$doToChange->$fieldName);
                                    foreach ($dom->getElementsByTagName('img') as $node) {
                                        $oldSrc = $node->getAttribute('src');
                                        $newSrc = preg_replace('/'.str_replace("/", "\/", $oldPath).'(.*?)'.$oldFileName.'/', ''.$newPath.'$1'.$newFileName, $oldSrc);
                                        if ($oldSrc != $newSrc) {
                                            $oldFilePath = Director::baseFolder()."/".$oldSrc;
                                            $newFilePath = Director::baseFolder()."/".$newSrc;
                                            if (file_exists($oldFilePath) && !file_exists($newFilePath)) {
                                                rename($oldFilePath, $newFilePath);
                                            }
                                            $node->setAttribute('src', $newSrc);
                                        }
                                    }
                                    $doToChange->encoding = 'UTF-8';
                                    $doToChange->$fieldName = $dom->saveHTML();
                                    $data = preg_replace('/'.str_replace("/", "\/", $oldPath).'(.*?)'.$oldFileName.'/', ''.$newPath.'$1'.$newFileName, $doToChange->$fieldName);
                                    //$this->owner->generateFormattedImage($format, $arg1, $arg2)
                                    if ($doToChange instanceof SiteTree) {
                                        $doToChange->writeToStage('Stage');
                                        $doToChange->publish('Stage', 'Live');
                                    } else {
                                        $doToChange->write();
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function onAfterWrite()
    {
        if (is_array($this->imageTrackingAddAgain) && count($this->imageTrackingAddAgain)) {
            foreach ($this->imageTrackingAddAgain as $key => $array) {
                DB::query("
					INSERT IGNORE INTO  \"".$array["DataObjectClassName"]."_ImageTracking\" (
						\"ID\" ,
						\"SiteTreeID\" ,
						\"FileID\" ,
						\"FieldName\"
					)
					VALUES (
						NULL ,  '".$array["TrackedID"]."',  '".$array["FileID"]."',  '".$array["DataObjectFieldName"]."'
					);
				");
                unset($this->imageTrackingAddAgain[$key]);
            }
        }
    }
}
