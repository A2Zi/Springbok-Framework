<?php
/**
 * Behavior Slug Redirectable (build)
 */
class BSlugRedirectable_build{
	public static $beforeUpdate=array('_setOldSlug');
	public static $afterUpdate=array('_addSlugRedirect');
}