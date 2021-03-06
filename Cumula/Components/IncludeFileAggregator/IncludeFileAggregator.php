<?php
namespace Cumula\Components\IncludeFileAggregator;
/**
 * IncludeFileAggregator
 *
 * IncludeFileAggregator — Implements a framework for aggregating and inserting CSS/JS files into a page.
 *
 * @package    Cumula
 * @subpackage Components
 * @version    0.1.0
 * @author     Seabourne Consulting
 * @license    MIT License
 * @copyright  2012 Seabourne Consulting
 * @link       https://mjreich@github.com/mjreich/IncludeFileAggregator.git
 */

/**
 * IncludeFileAggregator Class
 *
 * The IncludeFileAggregator defines a framework of events for collecting, aggregating and inserting CSS and JS files into a 
 * page before render.  Two events are defined: GatherJSFiles and GatherCSSFiles.  To insert CSS or JS into a page, components
 * need to implement an event listener for the respective events above, and return a simple array of public paths to the relevant
 * files.  This module does not handle assets; use the built in Component asset handling in Cumula. 
 *
 * To insert the CSS/JS into the page, the component defines two template variables that are exposed on page render: $css and $js.
 *
 * Future plans are for this module to aggregate and minimize if on a production environment, otherwise include the files as is
 * for easier debugging.
 *
 * ### Events
 * The IncludeFileAggregator Class defines the following events:
 *
 * #### GatherCSSFiles
 * This event is fired to collect the CSS files from other components.  Each returned array should contain at least one public
 * path (relative URL) to the CSS file to be included.  Multiple values can be submitted.
 *
 * **Args**:
 * 
 * None.
 *
 * **Return**:
 * 
 * 1. Array: an array of CSS public paths (Relative Urls) to include in the page.
 *
 * #### GatherJSFiles
 * This event is fired to collect the JS files from other components.  Each returned array should contain at least one public
 * path (relative URL) to the JS file to be included.  Multiple values can be submitted.
 *
 * **Args**:
 * 
 * None.
 *
 * **Return**:
 * 
 * 1. Array: an array of JS public paths (Relative Urls) to include in the page.
 *
 * @package    Cumula
 * @subpackage Components
 * @author     Seabourne Consulting
 */
class IncludeFileAggregator extends \Cumula\Base\Component {
	
	/**
	 * Public constructor.  Defines the two events used by this component.
	 *
	 * @author Mike Reich
	 */
	public function __construct() {
		parent::__construct();
		
		$this->addEvent('GatherJSFiles');
		$this->addEvent('GatherCSSFiles');
	}
	
	/**
	 * Startup Method.  Adds an event listener to the AfterBootProcess event.
	 *
	 * @return void
	 * @author Mike Reich
	 */
	public function startup() {
		A('Application')->bind('AfterBootProcess', array($this, 'gatherFiles'));
	}
	
	/**
	 * The AfterBootProcess event handler.  This method dispatches the two Gather events and collects the 
	 * returned paths.  It then generates the JS and CSS tags, and renders them into the $css and $js template
	 * variables.
	 *
	 * @return void
	 * @author Mike Reich
	 */
	public function gatherFiles() {
		$jsfiles = array();
		$cssfiles = array();
		$this->dispatch('GatherJSFiles', array(), function($file) use (&$jsfiles) {
			if(is_array($file))
				$jsfiles = array_merge($jsfiles, $file);
			else
				$jsfiles[] = $file;
		});
		$this->dispatch('GatherCSSFiles', array(), function($file) use (&$cssfiles) {
			if(is_array($file))
				$cssfiles = array_merge($cssfiles, $file);
			else
				$cssfiles[] = $file;
		});
		
		$jsMarkup = $this->generateJS($jsfiles);
		$cssMarkup = $this->generateCSS($cssfiles);
		$this->renderBlock($jsMarkup, 'js');
		$this->renderBlock($cssMarkup, 'css');
	}
	
	/**
	 * Internal method to generate the CSS markup.
	 *
	 * @param array $files An incoming array of public paths.
	 * @return string The generated markup for the CSS includes.
	 * @author Mike Reich
	 */
	protected function generateCSS($files) {
		$output = '';
		foreach($files as $file) {
			$output .= "<link href=\"$file\" rel=\"stylesheet\" type=\"text/css\" />\n";
		}
		return $output;
	}
	
	/**
	 * Internal method to generate the JS markup.
	 *
	 * @param array $files An incoming array of public paths.
	 * @return string The generated markup for the JS includes.
	 * @author Mike Reich
	 */
	protected function generateJS($files) {
		$output = '';
		foreach($files as $file) {
			$output .= "<script src=\"$file\" type=\"text/javascript\"></script>\n";
		}
		return $output;
	}
	
	/**
	 * The Cumula getInfo metadata method.
	 *
	 * @return void
	 * @author Mike Reich
	 */
	public static function getInfo() {
        return array(
            'name' => 'Include File Aggregator',
            'description' => 'Packages and includes CSS and JS files provided by other components.',
            'version' => '0.1',
            'dependencies' => array(),
        );
    }
}