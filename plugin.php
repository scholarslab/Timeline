<?php
/**
 * Timeline plugin is a helper function for creating SIMILE Timelines from an
 * array of items in Omeka. The plugins allows one to specify metadata elements
 * from which the time data should be derived, as well as the element for
 * defining the caption.
 *
 * This plugin requires that {@link http://www.jquery.org jQuery} be
 * loaded in your theme (preferably in the head element).
 *
 * @author    Scholars' Lab
 * @copyright 2010 The Board and Visitors of the University of Virginia
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache 2.0
 * @version   $Id$
 * @package   Timeline
 * @link      http://omeka.org/codex/Plugins/Timeline
 **/

add_plugin_hook('install', 'timeline_install');
add_plugin_hook('uninstall', 'timeline_uninstall');
add_plugin_hook('initialize', 'timeline_initialize');
add_plugin_hook('define_routes', 'timeline_routes');
add_plugin_hook('public_theme_header', 'timeline_header');

add_filter("exhibit_builder_exhibit_display_item", "timeline_show_item_in_page");
add_filter("item_square_thumbnail", "timeline_item_square_thumbnail");
add_filter("item_has_thumbnail", "timeline_item_has_thumbnail");

add_filter(array(
    'Form', 'Item', 'Item Type Metadata', 'Tag'
    ), "timeline_tag_widget");

define('TIMELINE_PLUGIN_VERSION', get_plugin_ini('Timeline', 'version'));
define('TIMELINE_PLUGIN_DIR', dirname(__FILE__));

/**
 * Install the timeline plugin
 */
function timeline_install()
{
    set_option('timeline_version', TIMELINE_PLUGIN_VERSION);

    # now we add 'Timeline' item type
    $timelinemitemtype = array(
        'name'       => "Timeline",
        'description' => "Timeline composed of items in this repository"
        );
    $timelinemitemtypemetadata =
        array(array('name' => "Tag",
            'description' => "Items with this tag should be included in the timeline"
            ));
    try {
        $itemtype = insert_item_type($timelinemitemtype,$timelinemitemtypemetadata);
        define("TIMELINE_ITEMTYPE",$itemtype->id);

        } catch (Exception $e) {
            debug("Unable to install Timeline Itemtype: " . $e->getMessage() );
    }
}

/**
 * Uninstall the timeline plugin
 */
function timeline_uninstall()
{
	delete_option('timeline_plugin_version');
}

/**
 * Add the helper functions to the plugin
 */
function timeline_initialize()
{
	require_once TIMELINE_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'helpers/CreateTimeline.php';
}

/**
 * Add Timeline javascriprts to the show and edit actions. 
 */
function timeline_header()
{
  $actionName = Zend_Controller_Front::getInstance()->getRequest()->getActionName();
	switch ($actionName) {
		case 'show' :
    case 'edit' :
      $headScript = __v()->headScript();
      $headScript->appendFile('http://static.simile.mit.edu/timeline/api-2.3.1/timeline-api.js');
      $headScript->appendScript('SimileAjax.History.enabled = true; window.jQuery = SimileAjax.jQuery');
      $timelineVars = "var Timeline_urlPrefix = 'http://static.simile.mit.edu/timeline/api-2.3.1/';var Timeline_parameters = 'bundle=true';";
      $headScript->appendScript($timelineVars);
      $headScript->appendFile(src('createTimeline.js', 'javascripts'));
      if (version_compare(OMEKA_VERSION, '1.3', '<')) {
        $headScript->prependScript('jQuery.noConflict();');
        $headScript->prependFile('http://ajax.googleapis.com/ajax/libs/jquery/1.4/jquery.min.js');
        echo $headScript;
      }
			break;
    default:
      break;
	}
}

/**
 * Inject timeline-formatted objects in to the page
 *
 * @param string $html
 * @param string $item
 * @param array $options
 * @return string 
 */
function timeline_show_item_in_page($html, $displayFilesOptions, $linkProperties, $item) {
    if ($item->getItemType()->name == "Timeline") {
        $CONFIG = array(
            'id'     =>	'timelinediv',
            'class'  =>	'',
            'height' =>	'200px'
            );
        return __v()->partial('timelines/show.phtml',array("item" => $item ));
        }
     else return $html;
}

/**
 *
 * @param <type> $thumb
 * @param <type> $item
 * @return <type>
 */
function timeline_item_has_thumbnail($thumb, $item) {
    return true;
}

/**
 *
 * @param <type> $thumb
 * @param <type> $item
 * @return <type> 
 */
function timeline_item_square_thumbnail($thumb, $item) {
    $item = $item ? $item : get_item_by_id(item('ID'));

    if ($item->getItemType()->name == "Timeline") {
        return "<img src=" . img('timeline.png') . " />";
    } else {
        return $thumb;
    }
}

/**
 *
 * @param <type> $html
 * @param <type> $inputNameStem
 * @param <type> $value
 * @param <type> $options
 * @param <type> $record
 * @param <type> $element
 * @return <type> 
 */
function timeline_tag_widget($html,$inputNameStem,$value,$options,$record,$element)
{
    $div = __v()->partial('widgets/tag.phtml', array("inputNameStem" =>$inputNameStem, "value" => $value, "options" => $options, "record" => $record, "element" => $element));
    return $div;
}

/**
 * Add the routes from routes.ini in this plugin folder.
 *
 * @param <type> $router 
 */
function timeline_routes($router)
{
    $router->addConfig(new Zend_Config_Ini(TIMELINE_PLUGIN_DIR . DIRECTORY_SEPARATOR . 'routes.ini', 'routes'));
}
/*
function timeline_user_can_edit($timeline = null, $user = null)
{
	$controllerName = Zend_Controller_Front::getInstance()->getRequest()->getControllerName();
    if (!$timeline) {
        $timeline = $item;
    }
    if (!$user) { 
        $user = current_user();
    }
    $acl = get_acl();
    if (version_compare(OMEKA_VERSION, '2.0-dev', '>=')) {
        $canEditSelf = $acl->isAllowed($user, 'ExhibitBuilder_Exhibits', 'editSelf');
        $canEditOthers = $acl->isAllowed($user, 'ExhibitBuilder_Exhibits', 'editAll');
    } else {
        $canEditSelf = $acl->checkUserPermission('ExhibitBuilder_Exhibits', 'editSelf');
        $canEditOthers = $acl->checkUserPermission('ExhibitBuilder_Exhibits', 'editAll');
    }
    
    return (($exhibit->wasAddedBy($user) && $canEditSelf) || $canEditOthers);    
}
*/
