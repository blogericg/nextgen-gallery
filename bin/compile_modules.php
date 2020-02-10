<?php
require_once('vendor/autoload.php');

use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;

class C_Module_Compiler
{
	static $skipped_modules_directories = array('autoupdate', 'autoupdate_admin', 'ngglegacy');

	function is_cwd_root_directory()
	{
		$retval = FALSE;

		foreach (scandir(getcwd()) as $file) {
			if (is_file($file) && strpos($file, 'nggallery.php') !== FALSE) {
				$retval = TRUE;
				break;
			}
		}

		return $retval;
	}

	function __construct()
	{
		// Are we in the root directoy of the plugin?
		if (!$this->is_cwd_root_directory()) die("Ensure you're in the plugin root directory before executing this script");

		// Where are the module directories found?
		// Where are the module directories found?
		$modules_abspath = implode(DIRECTORY_SEPARATOR, array(
			'products',
			'photocrati_nextgen',
			'modules'
		));

		// Compile each module
		$compiled_files = $this->compile_modules($modules_abspath);
		foreach ($compiled_files as $compiled_file_abspath) {
			echo "Compiled {$compiled_file_abspath}\n";
		}

		// Check syntax of each file
		foreach ($compiled_files as $compiled_file_abspath){
			system("php -l {$compiled_file_abspath}");
		}
	}

	function compile_modules($modules_abspath)
	{
		$retval = array();

		foreach (scandir($modules_abspath) as $module_dir) {
			if (in_array($module_dir, array('.', '..'))) continue;
			$module_abspath = $modules_abspath.DIRECTORY_SEPARATOR.$module_dir;
			if (($compiled_file_abspath = $this->compile_module($module_abspath))) $retval[] = $compiled_file_abspath;
		}

		return $retval;
	}

	function compile_module($module_abspath)
	{
		$retval = FALSE;

		$factory = new PhpParser\ParserFactory;
		$parser = $factory->create(ParserFactory::PREFER_PHP5);
		$printer = new PhpParser\PrettyPrinter\Standard;

		if (!in_array(@array_pop(explode(DIRECTORY_SEPARATOR, $module_abspath)), self::$skipped_modules_directories)) {
			$abspaths = array();
			$module_file_abspath = NULL;
			$compiled_file_abspath = NULL;
			$temp_file_abspath	   = NULL;

			// Find module file, and get list of all other PHP files that the module provides
			foreach (scandir($module_abspath) as $module_file) {
				$file_abspath = $module_abspath . DIRECTORY_SEPARATOR . $module_file;
				if ( strpos( $file_abspath, 'module.' ) !== FALSE ) {
					if (strpos($file_abspath, 'package.') !== FALSE) {
						continue;
					}
					$module_file_abspath = $file_abspath;
					$compiled_file_abspath = $module_abspath.DIRECTORY_SEPARATOR.'package.'.$module_file;
					$temp_file_abspath = $compiled_file_abspath.'.tmp';
				} elseif ( strpos( $file_abspath, '.php' ) ) {
					$abspaths[] = $file_abspath;
				}
			}

			// Concatenate files into a buffer
			$buffer = array('<?php');

			foreach ($abspaths as $file_abspath) {
				$found_opening_tag = FALSE;
				$content = file_get_contents($file_abspath);
				$lines = explode("\n", $content);
				$file_buffer = array();
				foreach ($lines as $line) {
					if (!$found_opening_tag && strpos($line, '<?php') !== FALSE) $found_opening_tag = TRUE;
					else $file_buffer[] = $line;
				}
				$buffer[] = implode("\n", $file_buffer);
			}

			// We use PHP-Parser to determine if a class (child) extends another (parent) and if child is defined
			// before parent. If so we swap the position of their declaration so that the parent is declared first,
			// this fixes issues found on home.pl where declaring the parent class late in the file caused a WSOD
			//
			// How this works below can best be explained by adding a var_dump($stmt) immediately inside the first
			// foreach loop. PHP-Parser doesn't natively provide a way to re-arrange node positions, so we do manually
			if (count($buffer) > 1) { try {
				$stmts = $parser->parse(implode("\n", $buffer));
				foreach ($stmts as $ndx => $stmt) {
					if (gettype($stmt) == 'object' && get_class($stmt) == 'PhpParser\Node\Stmt\Class_')
					{
						if (!empty($stmt->extends))
						{
							foreach ($stmts as $ndx2 => $stmt2) {
								if (gettype($stmt2) == 'object' && get_class($stmt2) == 'PhpParser\Node\Stmt\Class_')
								{
									if (in_array($stmt2->name, $stmt->extends->parts))
									{
										if ($ndx < $ndx2)
										{
											$tmp = $stmt;
											$stmts[$ndx] = $stmts[$ndx2];
											$stmts[$ndx2] = $tmp;
										}
									}
								}
							}

						}
					}
				}
				$output = "<?php\n" . $printer->prettyPrint($stmts);
			}
			catch (PhpParser\Error $e) {
				echo 'Parse error: ', $e->getMessage();
			}}

			if (count($buffer) > 1 && file_put_contents($temp_file_abspath, $output) !== FALSE)
			{
				// Check if we've compiled a file previously. If so, only override it if
				// the compiled version has actually changed
				if (file_exists($compiled_file_abspath)) {
					if (md5(file_get_contents($compiled_file_abspath)) != md5($output)) {
						echo "Change detected in {$compiled_file_abspath}\n";
						copy($temp_file_abspath, $compiled_file_abspath);
						$retval = $compiled_file_abspath;
					}
				}
				else {
					copy($temp_file_abspath, $compiled_file_abspath);
					$retval = $compiled_file_abspath;
				}
				unlink($temp_file_abspath);
				reset($abspaths);
				foreach ($abspaths as $file_abspath) {
					unlink($file_abspath);
				}
			}

			if (count($buffer) == 1)
			{
				file_put_contents($compiled_file_abspath, $buffer[0]. "\n");
			}
		}

		return $retval;
	}
}

// If not called from the C_Distributable class, then instantiate immediately and get the args STDIN
if (!class_exists('C_Distributable')) new C_Module_Compiler();