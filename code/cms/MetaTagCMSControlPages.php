<?php

/**
 * Batch updates to Pages...
 *
 *
 */

class MetaTagCMSControlPages extends MetaTagCMSControlFiles
{
    private static $allowed_actions = array(
        "copyfromcontent" => "ADMIN",
        "togglecopyfromtitle" => "ADMIN",
        "setpageflag" => "ADMIN",
        //standard
        "cleanupfolders" => "ADMIN",
        "childrenof" => "ADMIN",
        "lowercase" => "ADMIN",
        "titlecase" => "ADMIN",
        "upgradefilenames" => "ADMIN",
        "recyclefolder" => "ADMIN",
        "copyfromtitle" => "ADMIN",
        "update" => "ADMIN",
        "recycle" => "ADMIN"
    );

    private static $url_segment = "metatagmanagementpages";

    /***************************************************
     * CONFIG                                          *
     *                                                 *
     ***************************************************/

    private static $small_words_array = array('of','a','the','and','an','or','nor','but','is','if','then','else','when','at','from','by','on','off','for','in','out','over','to','into','with');

    protected $updatableFields = array(
        'Title',
        'MenuTitle',
        'MetaDescription',
        'UpdateMenuTitle',
        'UpdateMetaDescription',
        'Automatemetatags_advanced'
    );

    /**
     * First table is main table - e.g. $this->tableArray[0] should work
     *
     **/
    protected $tableArray = array('SiteTree', 'SiteTree_Live');


    /***************************************************
     * ACTIONS                                         *
     *                                                 *
     ***************************************************/


    public function index()
    {
        return $this->renderWith('MetaTagCMSControlPages');
    }


    public function copyfromcontent($request)
    {
        if ($fieldName = $request->param("ID")) {
            if (in_array($fieldName, $this->updatableFields)) {
                foreach ($this->tableArray as $table) {
                    $rows = DB::query("SELECT \"$table\".\"ID\", \"$table\".\"Content\" FROM \"$table\" WHERE \"$table\".\"$fieldName\" = '' OR \"$table\".\"$fieldName\" IS NULL;");
                    foreach ($rows as $row) {
                        $newValue = Convert::raw2sql(DBField::create_field("HTMLText", $row["Content"])->Summary(metatags_advancedSTE::$meta_desc_length, 15, ""));
                        DB::query("UPDATE \"$table\" SET \"$fieldName\" = '$newValue' WHERE ID = ".$row["ID"]);
                    }
                }
                Session::set("MetaTagCMSControlMessage", _t("MetaTagCMSControl.UPDATEDTOTITLECASE", "Updated empty records with first "));
                return $this->returnAjaxOrRedirectBack();
            }
        }
        Session::set("MetaTagCMSControlMessage", _t("MetaTagCMSControl.NOTUPDATEDTOTITLECASE", "Records could not be updated to <i>title case</i>."));
        return $this->returnAjaxOrRedirectBack();
    }

    public function togglecopyfromtitle($request)
    {
        if ($fieldName = $request->param("ID")) {
            if (in_array($fieldName, $this->updatableFields)) {
                DB::query("UPDATE \"SiteConfig\" SET \"$fieldName\" = IF(\"$fieldName\" = 1, 0, 1)");
                Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.UPDATEDCONFIG", "Updated configuration."));
                return $this->returnAjaxOrRedirectBack();
            }
        }
        Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.NOTUPDATEDCONFIG", "Could not update configuration."));
        return $this->returnAjaxOrRedirectBack();
    }

    public function setpageflag($request)
    {
        if ($fieldName = $request->param("ID")) {
            $value = $request->param("OtherID") ?  1 : 0;
            if (in_array($fieldName, $this->updatableFields)) {
                foreach ($this->tableArray as $table) {
                    DB::query("UPDATE \"$table\" SET \"$fieldName\" = $value");
                }
                Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.UPDATEDALLPAGES", "Updated all pages."));
                return $this->returnAjaxOrRedirectBack();
            }
        }
        Session::set("MetaTagCMSControlMessage",  _t("MetaTagCMSControl.NOTUPDATEDALLPAGES", "Could not update pages."));
        return $this->returnAjaxOrRedirectBack();
    }


    public function update()
    {
        if (isset($_GET["fieldName"])) {
            $fieldNameString = $_GET["fieldName"];
            $fieldNameArray = explode("_", $fieldNameString);
            $fieldName = $fieldNameArray[0];
            if (in_array($fieldName, $this->updatableFields)) {
                if (!isset($_GET[$fieldNameString])) {
                    $value = 0;
                } else {
                    $value = Convert::raw2sql($_GET[$fieldNameString]);
                }
                $recordID = intval($fieldNameArray[1]);
                $className = $this->tableArray[0];
                $record = $className::get()->byID($recordID);
                if ($record) {
                    if ($record->hasMethod("canPublish") && !$record->canPublish()) {
                        return Security::permissionFailure($this);
                    }
                    $record->$fieldName = $value;
                    //also update URLSegment if title is being updated.
                    $urlSegmentValue = '';
                    if ($fieldName == "Title") {
                        $urlSegmentValue = $record->generateURLSegment($value);
                        if ($urlSegmentValue) {
                            $record->URLSegment = $urlSegmentValue;
                        }
                    } elseif ($fieldName != "Automatemetatags_advanced") {
                        //turn off Automatemetatags_advanced
                        $record->Automatemetatags_advanced = 0;
                    }

                    $record->writeToStage("Stage");
                    $record->publish("Stage", "Live");
                    return  _t("MetaTagCMSControl.UPDATE", "Updated $fieldName to <i>".$value."</i> for <i>".$record->Title."</i>.");
                } else {
                    $error = "Page could not be found - id = $recordID";
                }
            }
        }
        return _t("MetaTagCMSControl.NOTUPDATE", "Record could not be updated.");
    }


    protected function returnAjaxOrRedirectBack($verbose = false)
    {
        if (Director::is_ajax()) {
            return $this->renderWith("MetaTagCMSControlPagesAjax");
        } else {
            if (!$verbose) {
                $this->redirect($this->Link());
            }
            return array();
        }
    }

    /***************************************************
     * CONTROLS                                        *
     *                                                 *
     ***************************************************/

    /**
     * @return Boolean
     */
    public function SeparateMetaTitle()
    {
        return Config::inst()->get("metatags_advancedContentControllerEXT", "use_separate_metatitle") == 1;
    }

    /**
     * @return ArrayList
     */
    public function MyRecords()
    {
        $excludeWhere = "AND \"ShowInSearch\" = 1 AND \"ClassName\" <> 'ErrorPage'";
        $className = $this->tableArray[0];
        $pages = $className::get()
            ->filter(array("ParentID" => $this->ParentID, "ShowInSearch" => 1))
            ->exclude(array("ClassName" => 'ErrorPage'))
            ->limit($this->myRecordsLimit());
        $dos = null;
        $ar = new ArrayList();
        if ($pages->count()) {
            foreach ($pages as $page) {
                if ($page instanceof ErrorPage || !$page->canView(new Member())) {
                    $pages->remove($page);
                }
                $page->ChildrenLink = '';
                $page->MenuTitleIdentical = false;
                $page->MenuTitleAutoUpdate = false;
                if (strtolower($page->MenuTitle) == strtolower($page->Title)) {
                    $page->MenuTitleIdentical = true;
                }
                if ($this->mySiteConfig()->UpdateMenuTitle && $page->Automatemetatags_advanced) {
                    $page->MenuTitleAutoUpdate = true;
                }
                $className = $this->tableArray[0];
                $hasChildren = $className::get()->filter(array("ParentID" => $page->ID, "ShowInSearch" => 1))->count();
                if ($hasChildren) {
                    $page->ChildrenLink = $this->createLevelLink($page->ID);
                }

                $dos[$page->ID] = new ArrayList();
                $segmentArray = array();
                $item = $page;
                $segmentArray[] = array(
                    "URLSegment" => $item->URLSegment,
                    "ID" => $item->ID,
                    "ClassName" => $item->ClassName,
                    "Title" => $item->Title,
                    "CMSEditLink" => $item->CMSEditLink()
                );
                while ($item && $item->ParentID) {
                    $className = $this->tableArray[0];
                    $item = $className::get()->byID($item->ParentID);
                    if ($item) {
                        $segmentArray[] = array(
                            "URLSegment" => $item->URLSegment,
                            "ID" => $item->ID,
                            "ClassName" => $item->ClassName,
                            "Title" => $item->Title,
                            "Link" => $this->createLevelLink(intval($item->ParentID)-0),
                            "CMSEditLink" => $item->CMSEditLink()
                        );
                    }
                }
                $segmentArray = array_reverse($segmentArray);
                foreach ($segmentArray as $segment) {
                    $dos[$page->ID]->push(new ArrayData($segment));
                }
                $page->ParentSegments = $dos[$page->ID] ;
                $page->GoOneUpLink = $this->GoOneUpLink();
                $page->SeparateMetaTitle = $this->SeparateMetaTitle() ? true : false;
                $dos = null;
                $ar->push($page);
            }
        }
        return $ar;
    }


    public function AlwaysUpdateMenuTitle()
    {
        return $this->mySiteConfig()->UpdateMenuTitle;
    }

    public function AlwaysUpdateMetaDescription()
    {
        return $this->mySiteConfig()->UpdateMetaDescription;
    }


    public function Link($action = '')
    {
        if ($action) {
            $action .= "/";
        }
        return $this->Config()->get("url_segment") . "/" . $action;
    }


    /***************************************************
     * PROTECTED                                       *
     *                                                 *
     ***************************************************/




    protected function convert2TitleCase($title)
    {
        $title = trim($title);
        $words = explode(' ', $title);
        foreach ($words as $key => $word) {
            if ($key == 0 or !in_array($word, $this->Config()->get("small_words_array"))) {
                $words[$key] = ucwords(strtolower($word));
            }
        }
        $newtitle = implode(' ', $words);
        return $newtitle;
    }
}
