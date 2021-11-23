<?php
/**
 * Base WordPress Filesystem
 *
 * @package WordPress
 * @subpackage Filesystem
 */

/**
 * Base WordPress Filesystem class which Filesystem implementations extend.
 *
 * @since 2.5.0
 */
class WP_Filesystem_Base {

	/**
	 * Whether to display debug data for the connection.
	 *
	 * @since 2.5.0
	 * @var bool
	 */
	public $verbose = false;

	/**
	 * Cached list of local filepaths to mapped remote filepaths.
	 *
	 * @since 2.7.0
	 * @var array
	 */
	public $cache = array();

	/**
	 * The Access method of the current connection, Set automatically.
	 *
	 * @since 2.5.0
	 * @var string
	 */
	public $method = '';

	/**
	 * @var WP_Error
	 */
	public $errors = null;

	/**
	 */
	public $options = array();

	/**
	 * Returns the path on the remote filesystem of ABSPATH.
	 *
	 * @since 2.7.0
	 *
	 * @return string The location of the remote path.
	 */
	public function abspath() {}

	/**
	 * Returns the path on the remote filesystem of WP_CONTENT_DIR.
	 *
	 * @since 2.7.0
	 *
	 * @return string The location of the remote path.
	 */
	public function wp_content_dir() {}

	/**
	 * Returns the path on the remote filesystem of WP_PLUGIN_DIR.
	 *
	 * @since 2.7.0
	 *
	 * @return string The location of the remote path.
	 */
	public function wp_plugins_dir() {}

	/**
	 * Returns the path on the remote filesystem of the Themes Directory.
	 *
	 * @since 2.7.0
	 *
	 * @param string|false $theme Optional. The theme stylesheet or template for the directory.
	 *                            Default false.
	 * @return string The location of the remote path.
	 */
	public function wp_themes_dir( $theme = false ) {}

	/**
	 * Returns the path on the remote filesystem of WP_LANG_DIR.
	 *
	 * @since 3.2.0
	 *
	 * @return string The location of the remote path.
	 */
	public function wp_lang_dir() {}

	/**
	 * Locates a folder on the remote filesystem.
	 *
	 * @since 2.5.0
	 * @deprecated 2.7.0 use WP_Filesystem_Base::abspath() or WP_Filesystem_Base::wp_*_dir() instead.
	 * @see WP_Filesystem_Base::abspath()
	 * @see WP_Filesystem_Base::wp_content_dir()
	 * @see WP_Filesystem_Base::wp_plugins_dir()
	 * @see WP_Filesystem_Base::wp_themes_dir()
	 * @see WP_Filesystem_Base::wp_lang_dir()
	 *
	 * @param string $base The folder to start searching from.
	 * @param bool   $echo True to display debug information.
	 *                     Default false.
	 * @return string The location of the remote path.
	 */
	public function find_base_dir( $base = '.', $echo = false ) {}

	/**
	 * Locates a folder on the remote filesystem.
	 *
	 * @since 2.5.0
	 * @deprecated 2.7.0 use WP_Filesystem_Base::abspath() or WP_Filesystem_Base::wp_*_dir() methods instead.
	 * @see WP_Filesystem_Base::abspath()
	 * @see WP_Filesystem_Base::wp_content_dir()
	 * @see WP_Filesystem_Base::wp_plugins_dir()
	 * @see WP_Filesystem_Base::wp_themes_dir()
	 * @see WP_Filesystem_Base::wp_lang_dir()
	 *
	 * @param string $base The folder to start searching from.
	 * @param bool   $echo True to display debug information.
	 * @return string The location of the remote path.
	 */
	public function get_base_dir( $base = '.', $echo = false ) {}

	/**
	 * Locates a folder on the remote filesystem.
	 *
	 * Assumes that on Windows systems, Stripping off the Drive
	 * letter is OK Sanitizes \\ to / in Windows filepaths.
	 *
	 * @since 2.7.0
	 *
	 * @param string $folder the folder to locate.
	 * @return string|false The location of the remote path, false on failure.
	 */
	public function find_folder( $folder ) {}

	/**
	 * Locates a folder on the remote filesystem.
	 *
	 * Expects Windows sanitized path.
	 *
	 * @since 2.7.0
	 *
	 * @param string $folder The folder to locate.
	 * @param string $base   The folder to start searching from.
	 * @param bool   $loop   If the function has recursed. Internal use only.
	 * @return string|false The location of the remote path, false to cease looping.
	 */
	public function search_for_folder( $folder, $base = '.', $loop = false ) {}

	/**
	 * Returns the *nix-style file permissions for a file.
	 *
	 * From the PHP documentation page for fileperms().
	 *
	 * @link https://www.php.net/manual/en/function.fileperms.php
	 *
	 * @since 2.5.0
	 *
	 * @param string $file String filename.
	 * @return string The *nix-style representation of permissions.
	 */
	public function gethchmod( $file ) {}

	/**
	 * Gets the permissions of the specified file or filepath in their octal format.
	 *
	 * @since 2.5.0
	 *
	 * @param string $file Path to the file.
	 * @return string Mode of the file (the last 3 digits).
	 */
	public function getchmod( $file ) {}

	/**
	 * Converts *nix-style file permissions to a octal number.
	 *
	 * Converts '-rw-r--r--' to 0644
	 * From "info at rvgate dot nl"'s comment on the PHP documentation for chmod()
	 *
	 * @link https://www.php.net/manual/en/function.chmod.php#49614
	 *
	 * @since 2.5.0
	 *
	 * @param string $mode string The *nix-style file permissions.
	 * @return string Octal representation of permissions.
	 */
	public function getnumchmodfromh( $mode ) {}

	/**
	 * Determines if the string provided contains binary characters.
	 *
	 * @since 2.7.0
	 *
	 * @param string $text String to test against.
	 * @return bool True if string is binary, false otherwise.
	 */
	public function is_binary( $text ) {}

	/**
	 * Changes the owner of a file or directory.
	 *
	 * Default behavior is to do nothing, override this in your subclass, if desired.
	 *
	 * @since 2.5.0
	 *
	 * @param string     $file      Path to the file or directory.
	 * @param string|int $owner     A user name or number.
	 * @param bool       $recursive Optional. If set to true, changes file owner recursively.
	 *                              Default false.
	 * @return bool True on success, false on failure.
	 */
	public function chown( $file, $owner, $recursive = false ) {}

	/**
	 * Connects filesystem.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @return bool True on success, false on failure (always true for WP_Filesystem_Direct).
	 */
	public function connect() {}

	/**
	 * Reads entire file into a string.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Name of the file to read.
	 * @return string|false Read data on success, false on failure.
	 */
	public function get_contents( $file ) {}

	/**
	 * Reads entire file into an array.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to the file.
	 * @return array|false File contents in an array on success, false on failure.
	 */
	public function get_contents_array( $file ) {}

	/**
	 * Writes a string to a file.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string    $file     Remote path to the file where to write the data.
	 * @param string    $contents The data to write.
	 * @param int|false $mode     Optional. The file permissions as octal number, usually 0644.
	 *                            Default false.
	 * @return bool True on success, false on failure.
	 */
	public function put_contents( $file, $contents, $mode = false ) {}

	/**
	 * Gets the current working directory.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @return string|false The current working directory on success, false on failure.
	 */
	public function cwd() {}

	/**
	 * Changes current directory.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $dir The new current directory.
	 * @return bool True on success, false on failure.
	 */
	public function chdir( $dir ) {}

	/**
	 * Changes the file group.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string     $file      Path to the file.
	 * @param string|int $group     A group name or number.
	 * @param bool       $recursive Optional. If set to true, changes file group recursively.
	 *                              Default false.
	 * @return bool True on success, false on failure.
	 */
	public function chgrp( $file, $group, $recursive = false ) {}

	/**
	 * Changes filesystem permissions.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string    $file      Path to the file.
	 * @param int|false $mode      Optional. The permissions as octal number, usually 0644 for files,
	 *                             0755 for directories. Default false.
	 * @param bool      $recursive Optional. If set to true, changes file permissions recursively.
	 *                             Default false.
	 * @return bool True on success, false on failure.
	 */
	public function chmod( $file, $mode = false, $recursive = false ) {}

	/**
	 * Gets the file owner.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to the file.
	 * @return string|false Username of the owner on success, false on failure.
	 */
	public function owner( $file ) {}

	/**
	 * Gets the file's group.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to the file.
	 * @return string|false The group on success, false on failure.
	 */
	public function group( $file ) {}

	/**
	 * Copies a file.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string    $source      Path to the source file.
	 * @param string    $destination Path to the destination file.
	 * @param bool      $overwrite   Optional. Whether to overwrite the destination file if it exists.
	 *                               Default false.
	 * @param int|false $mode        Optional. The permissions as octal number, usually 0644 for files,
	 *                               0755 for dirs. Default false.
	 * @return bool True on success, false on failure.
	 */
	public function copy( $source, $destination, $overwrite = false, $mode = false ) {}

	/**
	 * Moves a file.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $source      Path to the source file.
	 * @param string $destination Path to the destination file.
	 * @param bool   $overwrite   Optional. Whether to overwrite the destination file if it exists.
	 *                            Default false.
	 * @return bool True on success, false on failure.
	 */
	public function move( $source, $destination, $overwrite = false ) {}

	/**
	 * Deletes a file or directory.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string       $file      Path to the file or directory.
	 * @param bool         $recursive Optional. If set to true, deletes files and folders recursively.
	 *                                Default false.
	 * @param string|false $type      Type of resource. 'f' for file, 'd' for directory.
	 *                                Default false.
	 * @return bool True on success, false on failure.
	 */
	public function delete( $file, $recursive = false, $type = false ) {}

	/**
	 * Checks if a file or directory exists.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to file or directory.
	 * @return bool Whether $file exists or not.
	 */
	public function exists( $file ) {}

	/**
	 * Checks if resource is a file.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file File path.
	 * @return bool Whether $file is a file.
	 */
	public function is_file( $file ) {}

	/**
	 * Checks if resource is a directory.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path Directory path.
	 * @return bool Whether $path is a directory.
	 */
	public function is_dir( $path ) {}

	/**
	 * Checks if a file is readable.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to file.
	 * @return bool Whether $file is readable.
	 */
	public function is_readable( $file ) {}

	/**
	 * Checks if a file or directory is writable.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to file or directory.
	 * @return bool Whether $file is writable.
	 */
	public function is_writable( $file ) {}

	/**
	 * Gets the file's last access time.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to file.
	 * @return int|false Unix timestamp representing last access time, false on failure.
	 */
	public function atime( $file ) {}

	/**
	 * Gets the file modification time.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to file.
	 * @return int|false Unix timestamp representing modification time, false on failure.
	 */
	public function mtime( $file ) {}

	/**
	 * Gets the file size (in bytes).
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file Path to file.
	 * @return int|false Size of the file in bytes on success, false on failure.
	 */
	public function size( $file ) {}

	/**
	 * Sets the access and modification times of a file.
	 *
	 * Note: If $file doesn't exist, it will be created.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $file  Path to file.
	 * @param int    $time  Optional. Modified time to set for file.
	 *                      Default 0.
	 * @param int    $atime Optional. Access time to set for file.
	 *                      Default 0.
	 * @return bool True on success, false on failure.
	 */
	public function touch( $file, $time = 0, $atime = 0 ) {}

	/**
	 * Creates a directory.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string           $path  Path for new directory.
	 * @param int|false        $chmod Optional. The permissions as octal number (or false to skip chmod).
	 *                                Default false.
	 * @param string|int|false $chown Optional. A user name or number (or false to skip chown).
	 *                                Default false.
	 * @param string|int|false $chgrp Optional. A group name or number (or false to skip chgrp).
	 *                                Default false.
	 * @return bool True on success, false on failure.
	 */
	public function mkdir( $path, $chmod = false, $chown = false, $chgrp = false ) {}

	/**
	 * Deletes a directory.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path      Path to directory.
	 * @param bool   $recursive Optional. Whether to recursively remove files/directories.
	 *                          Default false.
	 * @return bool True on success, false on failure.
	 */
	public function rmdir( $path, $recursive = false ) {}

	/**
	 * Gets details for files in a directory or a specific file.
	 *
	 * @since 2.5.0
	 * @abstract
	 *
	 * @param string $path           Path to directory or file.
	 * @param bool   $include_hidden Optional. Whether to include details of hidden ("." prefixed) files.
	 *                               Default true.
	 * @param bool   $recursive      Optional. Whether to recursively include file details in nested directories.
	 *                               Default false.
	 * @return array|false {
	 *     Array of files. False if unable to list directory contents.
	 *
	 *     @type string $name        Name of the file or directory.
	 *     @type string $perms       *nix representation of permissions.
	 *     @type string $permsn      Octal representation of permissions.
	 *     @type string $owner       Owner name or ID.
	 *     @type int    $size        Size of file in bytes.
	 *     @type int    $lastmodunix Last modified unix timestamp.
	 *     @type mixed  $lastmod     Last modified month (3 letter) and day (without leading 0).
	 *     @type int    $time        Last modified time.
	 *     @type string $type        Type of resource. 'f' for file, 'd' for directory.
	 *     @type mixed  $files       If a directory and $recursive is true, contains another array of files.
	 * }
	 */
	public function dirlist( $path, $include_hidden = true, $recursive = false ) {}

}
