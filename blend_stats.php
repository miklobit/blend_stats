<?php
/*
This file is part of BlendStats.

BlendStats is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

BlendStats is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with BlendStats.  If not, see <http://www.gnu.org/licenses/>.
*/


if ( ! function_exists('debug') ) {
	/**
	 * Utility debugging function, defined only if no global version exists
	 * @param  mixed $var Anything that print_r() can print
	 */
	function debug( $var = null ) {
		echo '<pre>';
		print_r( $var );
		echo '</pre>';
	}
}

/**
 * A class to be used in your PHP application for access to blend file stats
 * Usage:
 * 		$bst = new BlendStats( $path_to_blend_file );
 * 		$stats = $bst->get_stats();
 * 		// do operations on $stats
 */
class BlendStats {

	public $start_marker = "---STATS---BEGIN---";

	public $end_marker = "---STATS---END---";

	public $blend_path = null;

	public $blend_dir = null;

	public $blender_bin = '/usr/local/bin/blender';

	public $stats = null;

	/**
	 * Constructor for this class
	 * you should pass the blend file path and the Blender binary call as it
	 * will be called
	 * @param [type] $file_path   full path of the blend file you want to read
	 * @param [type] $blender_bin Blender binary, use if you don't have Blender in $PATH
	 */
	public function __construct( $blend_path = null, $bin = 'blender' ) {
		if ( ! defined('DS') ) {
			define('DS', DIRECTORY_SEPARATOR);
		}
		$this->script_path = dirname(__FILE__) . DS . 'blend_stats.py';
		$this->test_file = dirname(__FILE__) . DS . "test/test.blend";
		if ( file_exists($blend_path) ) {
			$this->blend_path = $blend_path;
			$this->blender_bin = $bin;
		} else {
			$this->blend_path = $this->test_file;
		}
	}

	/**
	 * Helper function to set the blend file path
	 * @param string $blend_path new absolute path to blend file
	 * @return boolean path was set corretly and is equal to passed arg
	 */
	public function set_blend_path( $blend_path ) {
		$this->blend_path = $blend_path;
		return $this->blend_path === $blend_path;
	}

	/**
	 * Returns the currently set blend file path
	 * @return string the blend path. returns false if path was set to the test file fallback.
	 */
	public function get_blend_path() {
		if ( $this->blend_path === $this->test_file ) {
			return false;
		} else {
			return $this->blend_path;
		}
	}

	/**
	 * Reads file from disk and return full uotput from Blender.
	 * @param  string $path the absolute path to the blend file
	 * @return string       the full Blender output on runtime. returns null if shell_exec() fails
	 */
	public function get_blender_output() {
		$output = "";
		// Flag --disable_autoexec in shell command is prefered over -Y, as it's clearer.
		// Blender 2.4x might even use -Y to allow script execution, and -y to disable (needs testing).
		$cmd = $this->blender_bin .' -noaudio --disable-autoexec --background '
			   . $this->blend_path .' --python '. $this->script_path;
		$output = shell_exec( $cmd );
		return $output;
	}

	/**
	 * [get_stats description]
	 * @return [type] [description]
	 */
	public function get_stats() {
		if ( ! $this->stats ) {
			$this->load_stats();
		}
		return $this->stats;
	}

	/**
	 * Cleans the raw output from get_blender_output and returns a PHP object
	 * containing all the stats for direct access
	 * @param  [type] $path [description]
	 * @return stdClass     Standard object with stats
	 */
	public function load_stats() {
		if ( ! $this->blend_path ) {
			return null;
		}
		$raw_output = $this->get_blender_output( $this->blend_path );
		$json_string = $this->isolate_json( $raw_output );
		$this->stats = json_decode( $json_string );
		return $this->stats;
	}

	/**
	 * Takes the raw output and returns the json string we want
	 * @param  string $raw Raw output from Blender
	 * @return string      the isolated json string in the Blender output
	 */
	private function isolate_json( $raw = null ) {
		$start_pos = strpos($raw, $this->start_marker);
		$end_pos = strpos($raw, $this->end_marker);
		if ( false === $start_pos || false === $end_pos ) {
			return null;
		}
		$start_pos += strlen( $this->start_marker );

		$json_string = substr($raw, $start_pos, $end_pos - strlen($raw) );

		return trim( $json_string );
	}


	public function test() {
		$bst = new BlendStats( $this->test_file );
		$bst->get_stats();
		debug( $bst->stats );
		debug( shell_exec("ls") );
	}

}

