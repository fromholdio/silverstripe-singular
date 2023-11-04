<?php

namespace Fromholdio\Singular\Extensions;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DB;
use Symbiote\Multisites\Model\Site;

class MultisitesSingularPageExtension extends SingularPageExtension
{
    public function requireDefaultRecords()
    {
        $enabled = $this->getOwner()->getIsRequiredDefaultEnabled();
        if (!$enabled) {
            return;
        }

        $class = get_class($this->getOwner());
        $sites = Site::get();

        foreach ($sites as $site) {
            $existingPage = $class::get()->filter('SiteID', $site->ID)->first();
            if (!$existingPage || !$existingPage->exists()) {
                $title = $this->getOwner()->config()->get('default_title');
                if (!$title) {
                    $title = 'New ' . $this->getOwner()->i18n_singular_name();
                }
                $page = $class::create();
                $page->Title = $title;
                $page->SiteID = $site->ID;
                $page->ParentID = $site->ID;
                $page->write();
                $page->publishSingle();
                $page->flushCache();
                DB::alteration_message(
                    'Added new ' . $this->getOwner()->i18n_singular_name() . ' titled "'
                    . $title . '" to site "' . $site->Title . '"',
                    'created'
                );
            }
        }
    }

    public function onBeforeWrite()
    {
        $urlSegment = $this->getOwner()->getForcedURLSegment();
        if ($urlSegment) {
            $this->getOwner()->URLSegment = $urlSegment;
        }
    }

    public function getIsRequiredDefaultEnabled()
    {
        $enabled = $this->getOwner()->config()->get('require_default_enabled');
        if ($this->getOwner()->hasMethod('updateIsRequiredDefaultEnabled')) {
            $enabled = $this->getOwner()->updateIsRequiredDefaultEnabled($enabled);
        }
        return $enabled;
    }

    public function getIsReadonlyURLSegment()
    {
        $readOnly = $this->getOwner()->config()->get('readonly_url_segment');
        if ($this->getOwner()->hasMethod('updateIsReadonlyURLSegment')) {
            $readOnly = $this->getOwner()->updateIsReadonlyURLSegment($readOnly);
        }
        return $readOnly;
    }

    public function getForcedURLSegment()
    {
        $urlSegment = $this->getOwner()->config()->get('forced_url_segment');
        if ($this->getOwner()->hasMethod('updateForcedURLSegment')) {
            $urlSegment = $this->getOwner()->updateForcedURLSegment($urlSegment);
        }
        return $urlSegment;
    }

    public function canCreate($member = null, $context = [])
    {
        $class = get_class($this->getOwner());
        if ($class && ClassInfo::exists($class)) {
            $page = $class::get()->filter('SiteID', $this->getOwner()->SiteID)->first();
            if ($page && $page->exists()) {
                return false;
            }
        }
    }

    public function canUnpublish($member = null)
    {
        return false;
    }

    public function canArchive($member = null)
    {
        return false;
    }
}
