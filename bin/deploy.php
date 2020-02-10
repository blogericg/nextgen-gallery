<?php

class C_Deploy
{
	var $_hg_working_copy = '';
	var $_svn_working_copy = '';
	var $_plugin_folder_name = 'nextgen-gallery';
	static $_plugin_bootstrap_file = 'nggallery.php';

	static function is_cwd_root_directory()
	{
		$retval = FALSE;

		foreach (scandir(getcwd()) as $file) {
			if (is_file($file) && strpos($file, self::$_plugin_bootstrap_file) !== FALSE) {
				$retval = TRUE;
				break;
			}
		}

		return $retval;
	}


	function __construct($svn_working_copy, $tag)
	{
		if (self::is_cwd_root_directory()) {
			$this->_hg_working_copy = getcwd();
			$this->_svn_working_copy = $svn_working_copy;
			$this->_tag = $tag;

			$this->print_menu();
		}
		else die("Please execute this command from the HG working copy directory");
	}

	function print_menu()
	{
		$selection = 0;
		while (!in_array($selection, array(1,2,3,4,5,6,7))) {
			system('clear');
			echo "Enter a selection:\n";
			echo "1) Deploy to SVN and staging server as unstable release\n";
			echo "2) Deploy to SVN as stable release\n";
			echo "3) Move stable tag to unstable tag.\n";
			echo "4) Move unstable tag to stable tag.\n";
			echo "5) Upload to staging server\n";
			echo "6) Change stable tag at Wordpress.org\n";
			echo "7) Quit\n\n";
			echo "Selection: ";
			$selection = fgetc(STDIN);
			echo "\n";

			if ($selection == 'q' || $selection == 7) break;

			switch($selection) {
				case 1:
					$this->deploy_release(TRUE);
					break;
				case 2:
					$this->deploy_release();
					break;
				case 3:
					$this->move_stable_tag();
					break;
				case 4:
					$this->move_unstable_tag();
					break;
				case 5:
					$this->upload_to_staging_server();
					break;
				case 6:
					$this->change_stable_tag();
					break;
			}
		}
	}

	function change_stable_tag()
	{
		chdir($this->_svn_working_copy);
		system("svn up");
		system("svn revert -R .");
		system("rm -rf {$this->get_svn_trunk_abspath()}/*");
		system("cp -r {$this->get_svn_stable_tag_abspath()}/* {$this->get_svn_trunk_abspath()}/");

		ob_start();
		system("svn status");
		$diff = explode("\n", ob_get_clean());
		foreach ($diff as $line)
		{
			if (preg_match('#^(.)\s+(.*)$#', trim($line), $match)) {
				$status = $match[1];
				$file = $match[2];
				if ($status == '?') system("svn add {$file}");
				else if ($status == '!') system("svn rm {$file}");
			}
		}
		system("svn commit -m 'Released {$this->_tag}'");
	}

	function get_build_abspath()
	{
		return $this->_hg_working_copy.DIRECTORY_SEPARATOR.build.DIRECTORY_SEPARATOR.$this->_plugin_folder_name;
	}

	function _create_build_and_zip()
	{
		system("gulp build -z {$this->_tag}");
		return implode(DIRECTORY_SEPARATOR, array(
			$this->_hg_working_copy,
			'build',
			'zips',
			'distributables',
			"{$this->_plugin_folder_name}.{$this->_tag}.zip"
		));
	}

	/**
	 * Deploys an unstable release:
	 *
	 * - Switch to the tag provided in the mercurial working copy
	 * - Create an archive
	 * - Extract the archive to the SVN working copy and commit
	 * -
	 */
	function deploy_release($unstable=FALSE)
	{
		// Bring the working copies up-to-date
//		system("hg up -C {$this->_tag}");
		chdir($this->_svn_working_copy);
		system("svn up");
		chdir($this->_hg_working_copy);

		// Create build and zip
		$zip_abspath = $this->_create_build_and_zip();

		// Commit the distributable to SVN, temporarily, as a stable tag
		chdir($this->_svn_working_copy);
		$tag_abspath = $unstable ? $this->get_svn_unstable_tag_abspath() : $this->get_svn_stable_tag_abspath();
		$tag_relpath = str_replace($this->_svn_working_copy.'/', '', $tag_abspath);

		system("cp -r {$this->get_build_abspath()} {$tag_relpath}");
		chdir($this->_svn_working_copy);
		system("svn add {$tag_relpath}");

		if ($unstable) {
			system("svn commit -m 'Tagged v{$this->_tag} as unstable release'");
			$this->upload_to_staging_server($zip_abspath);
		}
		else system("svn commit -m 'Tagged v{$this->_tag} as stable release'");
	}

	function upload_to_staging_server($zip_abspath=NULL)
	{
		$server = getenv('NGG_STAGING_HOST');
		$user   = getenv('NGG_STAGING_USER');
		$pwd    = getenv('NGG_STAGING_PWD');
		$dir    = getenv('NGG_STAGING_DIR');

		if ($server && $user && $pwd) {
			if (!$zip_abspath) {
				$zip_abspath = $this->_create_build_and_zip();
			}

			$zip_name = basename($zip_abspath);
			$fp = fopen($zip_abspath, 'r');

			$ftp = ftp_connect($server);
			ftp_login($ftp, $user, $pwd);
			//'/var/www/vhosts/imagely.com/wp-content/staging'
			ftp_chdir($ftp, $dir);

			ftp_fput($ftp, $zip_name, $fp, FTP_BINARY);
			fclose($fp);
			ftp_close($ftp);
		}
		else echo "Please define NGG_STAGING_HOST, NGG_STAGING_USER, and NGG_STAGING_PWD as environment variables\n";
	}

	function move_stable_tag()
	{
		$this->svn_copy($this->_tag, $this->get_svn_stable_dir_abspath(), $this->get_svn_unstable_dir_abspath(), TRUE);
		system("svn commit -m 'Tagged v{$this->_tag} as unstable'");

	}

	function move_unstable_tag()
	{
		$this->svn_copy($this->_tag, $this->get_svn_unstable_dir_abspath(), $this->get_svn_stable_dir_abspath(), TRUE);
		system("svn commit -m 'Tagged v{$this->_tag} as stable'");
	}

	function svn_copy($tag, $source_abspath, $dest_abspath, $move=FALSE)
	{
		$source_tag_abspath = $source_abspath.DIRECTORY_SEPARATOR.$tag;
		$source_tag_relpath     = str_replace($this->_svn_working_copy.'/', '', $source_tag_abspath);
		$dest_tag_abspath   = $dest_abspath.DIRECTORY_SEPARATOR.$tag;
		$dest_tag_relpath       = str_replace($this->_svn_working_copy.'/', '', $dest_tag_abspath);
		$cmd                = $move ? 'move' : 'copy';

		chdir($this->_svn_working_copy);

		// If the destination directory doesn't exist, create it
		if (!file_exists($dest_abspath)) {
			system("mkdir -p {$dest_abspath}");
		}

		if (file_exists($dest_tag_abspath)) {
			system("svn {$cmd} {$source_tag_relpath}/* {$dest_tag_relpath}");
		}
		else {
			system("svn {$cmd} {$source_tag_relpath} {$dest_tag_relpath}");
		}
	}

	function get_svn_trunk_abspath()
	{
		return $this->_svn_working_copy.DIRECTORY_SEPARATOR.'trunk';
	}

	function get_svn_unstable_dir_abspath()
	{
		return $this->_svn_working_copy.DIRECTORY_SEPARATOR.'unstable_tags';
	}

	function get_svn_stable_dir_abspath()
	{
		return $this->_svn_working_copy.DIRECTORY_SEPARATOR.'tags';
	}

	function get_svn_unstable_tag_abspath()
	{
		return $this->get_svn_unstable_dir_abspath().DIRECTORY_SEPARATOR.$this->_tag;
	}

	function get_svn_stable_tag_abspath()
	{
		return $this->get_svn_stable_dir_abspath().DIRECTORY_SEPARATOR.$this->_tag;
	}
}

// Has the developer provided the svn working copy?
$run = FALSE;
if (isset($argv[1]) && ($svn_working_copy = $argv[1])) {
	// Does the svn working exist?
	if (file_exists($svn_working_copy) && in_array('.svn', scandir($svn_working_copy))) {

		// Has the developer provided the hg tag?
		if (isset($argv[2]) && ($tag = $argv[2])) {
			$run = TRUE;
		}
	}
	else die("Not a valid SVN working copy");
}

if ($run) new C_Deploy($svn_working_copy, $tag);
else die("Usage: php bin/deploy.php {svn_working_copy} {tag}\n");
