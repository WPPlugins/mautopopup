<?php
/*
Plugin Name: mAutoPopup
Plugin URI: http://www.xhaleera.com/index.php/products/wordpress-mseries-plugins/mautopopup/
Description: Detects automatically the thumbnails in your posts and enables your visitors to magnify them to their full scale version.
Author: Christophe SAUVEUR
Author URI: http://www.xhaleera.com
Version: 0.5
*/

// Loading mautopopup plugin text domain
load_plugin_textdomain('mautopopup', 'wp-content/plugins/mautopopup/l10n');

/** \class mAutoPopup
	\brief Main class
	\warning Requires PHP version 4.3.0 or later.
*/
class mAutoPopup
{
	var $productDomain = 'mautopopup';
	var $productName;

	var $version = '0.5';		//!< Software version number
	var $storedVersion;			//!< Installed version number if any
	var $styleSheet;			//!< Style sheet
	var $closeLink;				//!< 'Close' link flag
	var $followScrolling;		//!< Follow scrolling flag
	var $behavior;				//!< Behavior flag (all, on, off, none)
	var $styleSheetStorage;		//!< Style sheet storage method flag (file, option)
	var $fssPath;				//!< File style sheet path
	var $lightboxMode;			//!< Use lightbox compatibility mode
	
	/** \brief Constructor
	*/
	function mAutoPopup()
	{
		$this->productName = __('mAutoPopup', $this->productDomain);
		$this->fssPath = dirname(__FILE__).'/mautopopup.css';
		
		$this->load_options();
		$this->compute_post();
	
		add_action('admin_menu', array(&$this, 'create_admin_page'));
		
		if ($this->installation_complete())
		{
			if (version_compare($GLOBALS['wp_version'], '2.5-RC1', '<'))
			{
				add_action('admin_head', array(&$this, 'register_mce_plugin'));
				add_filter('mce_plugins', array(&$this, 'add_to_mce_plugins'));
			}
			else
			{
				add_filter('mce_external_plugins', array(&$this, 'add_to_mce_external_plugins'));
				add_filter('mce_external_languages', array(&$this, 'add_to_mce_external_languages'));
			}
			add_filter('mce_buttons', array(&$this, 'add_to_mce_buttons'));
			add_action('wp_head', array(&$this, 'setup_framework'));
			add_action('wp_footer', array(&$this, 'install_popup'));
			add_filter('the_content', array(&$this, 'update_miniatures'));
		}
	}

	/** \brief Registers TinyMCE plugin */
	function register_mce_plugin()
	{
?>
<!-- mAutoPopup mce plugin registration start - version : <?php echo $this->version; ?> -->
<script language="javascript" type="text/javascript">
<!--
var mAutoPopup_MCEPluginURL = '<?php bloginfo('url'); ?>/wp-content/plugins/mautopopup/tinymce_plugin';
//-->
</script>
<script language="javascript" type="text/javascript" src="<?php bloginfo('url'); ?>/wp-content/plugins/mautopopup/tinymce_plugin/editor_plugin_legacy.js"></script>
<!-- mAutoPopup mce plugin registration end -->
<?php
	}

	/** \brief Adds the quicktag buttons to the rich text editor toolbars.
	*/
	function add_to_mce_buttons($buttons)
	{
		array_push($buttons, '|', 'mautopopup_disable', 'mautopopup_enable', 'mautopopup_remove');
		return $buttons;
	}
	
	/** \brief Adds the mAutoPopup plugin to the rich text editor.
	*/
	function add_to_mce_plugins($plugins)
	{
		array_push($plugins, '-mautopopup');
		return $plugins;
	}

	/** \brief Adds the mAutoPopup plugin to the rich text editor.
	*/
	function add_to_mce_external_plugins($plugins)
	{
		$plugins['mautopopup'] = get_bloginfo('url').'/wp-content/plugins/mautopopup/tinymce_plugin/editor_plugin.js';
		return $plugins;
	}

	function add_to_mce_external_languages($langs)
	{
		$langs['mautopopup'] = '../../../wp-content/plugins/mautopopup/tinymce_plugin/wp-langs.php';
		return $langs;
	}

	/** \brief Creates sub-page in the Options menu.
	*/
	function create_admin_page()
	{
		add_options_page(sprintf(__('%s Options', $this->productDomain), $this->productName), $this->productName, 'manage_options', $this->productDomain, array(&$this, 'options_page'));
	}

	/** \brief Tells if the installation of the plugin has been correctly done.
		\return true if the plugin is correctly installed, false either.
	*/
	function installation_complete()
	{
		return ($this->compare_versions() == 0);
	}
	
	/** \brief Loads the plugin options from the WordPress options repository or sets them to their default if absent.
	*/
	function load_options()
	{
		$this->storedVersion = get_option('mautopopup_version');
		$this->closeLink = get_option('mautopopup_display_close_link');
		$fs = get_option('mautopopup_follow_scrolling');
		$this->followScrolling = (in_array($fs, array('smooth', 'immediate'))) ? $fs : 'no';
		$this->behavior = get_option('mautopopup_behavior');
		if (!preg_match('/^(all|on|off|none)$/', $this->behavior))
			$this->behavior = 'on';
		
		// Style sheet
		$this->styleSheetStorage = get_option('mautopopup_stylesheet_storage');
		if (!preg_match('/^(file|option)$/', $this->styleSheetStorage))
			$this->styleSheetStorage = 'option';
		// -- Fetching "option" style sheet
		$optionStyleSheet = get_option('mautopopup_stylesheet');
		if (empty($optionStyleSheet))
			$optionStyleSheet = "div#mautopopup {
	background-color: white;
	border: 1px solid black;
	text-align: center;
	padding: 10px;
	margin: 0px;
	position: absolute;
	left: 10px;
	top: 10px;
}

div#mautopopup a.text {
	display: block;
	padding: 5px 0px;
	margin: 0px;
	color: black;
	text-decoration: none;
}
								
div#mautopopup a.text:hover {
	background-color: black;
	color: white;
}
								
div#mautopopup img {
	border: none;
	padding: 0px;
	margin: 0px;
}";
		// -- Fetching "file" style sheet if applying
		if ($this->styleSheetStorage == 'file' && file_exists($this->fssPath) && is_readable($this->fssPath)
				&& ($fileStyleSheet = file_get_contents($this->fssPath)) !== FALSE)
			$this->styleSheet = $this->filter_stylesheet($fileStyleSheet);
		else
			$this->styleSheet = $this->filter_stylesheet($optionStyleSheet);
		
		// Lightbox compatibility mode
		$this->lightboxMode = (get_option('mautopopup_lightbox_mode') == 'yes');
	}
	
	/** \brief Displays the global setup form.
	*/
	function options_page()
	{
		$legacy = version_compare($GLOBALS['wp_version'], '2.5-RC1', '<');
		$hl = ($legacy) ? 2 : 3;
			
		if (!$legacy)
			$this->display_messages();
?>
<div class="wrap">
<h2><?php echo $this->productName; ?></h2>
<p><?php
		if (empty($this->storedVersion))
			_e('No version installed.', $this->productDomain);
		else
			printf(__('Current version is %s.', $this->productDomain), $this->storedVersion);
?></p>
<?php
		if ($legacy)
		{
			echo "</div>\n";
			$this->display_messages();
		}

		// **
		//	Install form
		// **
		if (empty($this->storedVersion) || $this->compare_versions() < 0)
		{
			if ($legacy)
				echo '<div class="wrap">';
?>
<h2><?php _e('Installation', $this->productDomain); ?></h2>
<form action="" method="post">
<?php
			if (empty($this->storedVersion))
			{
?>
<p><?php printf(__('%s must be installed before any further use.', $this->productDomain), $this->productName); ?></p>
<input type="hidden" name="form_name" value="<?php echo $this->productDomain; ?>_install" />
<input type="hidden" name="form_action" value="install" />
<p class="submit"><input type="submit" name="submit" value="<?php printf(__('Setup %s', $this->productDomain), $this->productName); ?>" /></p>
<?php
			}
			else
			{
?>
<p><?php printf(__('You are currently using %s version %s.', $this->productDomain), $this->productName, $this->version); ?><br>
<?php printf(__('Yet, your database seems to match a more recent version of the plugin (version %s).', $this->productDomain), $this->storedVersion); ?><br>
<br>
<?php printf(__('You must reinstall %s before any further use or get back to a suitable version of the software.', $this->productDomain), $this->productName); ?><br>
<strong><?php _e('If you choose to reinstall the plugin, all formerly saved options will be lost.', $this->productDomain); ?></strong></p>
<input type="hidden" name="form_name" value="<?php echo $this->productDomain; ?>_install" />
<input type="hidden" name="form_action" value="downgrade" />
<p class="submit"><input type="submit" name="submit" value="<?php printf(__('Reinstall %s', $this->productDomain), $this->productName); ?>" /></p>
<?php
			}
?>
</form>
</div>
<?php
			return;
		}

// **
//	Upgrade form
// **
		if ($this->compare_versions() > 0)
		{
			if ($legacy)
				echo '<div class="wrap">';
?>
<h2><?php _e('Upgrade', $this->productDomain); ?></h2>
<form action="" method="post">
<p><?php printf(__('Before now, you were using version %s of %s.', $this->productDomain), $this->storedVersion, $this->productName); ?><br>
<?php printf(__('The new installed version is %s and your database must be upgraded before any further use.', $this->productDomain), $this->version); ?></p>
<input type="hidden" name="form_name" value="<?php echo $this->productDomain; ?>_upgrade" />
<input type="hidden" name="form_action" value="upgrade" />
<p class="submit"><input type="submit" name="submit" value="<?php printf(__('Upgrade %s', $this->productDomain), $this->productName); ?>" /></p>
</form>
</div>
<?php
			return;
		}

// **
//	Setup panel
// **
		if ($legacy)
			echo '<div class="wrap">';
?>
<h<?php echo $hl; ?>><?php _e('Global Setup', $this->productDomain); ?></h<?php echo $hl; ?>>
<form action="" method="post">
<table class="form-table">
<tr>
<th><?php _e('"Close" link position', $this->productDomain); ?></th>
<td><select name="display_close_link">
<?php
		$positions = array('above' => __('Above image', $this->productDomain), 'below' => __('Below image', $this->productDomain), 'nowhere' => __('Nowhere', $this->productDomain));
		while (list($key, $value) = each($positions))
		{
			echo "<option value=\"{$key}\"";
			if ($key == $this->closeLink)
				echo 'selected';
			echo ">{$value}</option>\n";
		}
?>
</select></td>
</tr>
<tr>
<th valign="top"><?php _e('Style sheet :', $this->productDomain); ?></th>
<td><textarea name="stylesheet" rows="10" style="width: 100%"><?php echo $this->styleSheet; ?></textarea></td>
</tr>
<tr>
<th><?php _e('Scrolling'); ?></th>
<td><select name="scroll">
<?php
		$pairs = array('no' => __("Don't follow page scrolling", $this->productDomain), 'immediate' => __('Readjust position immediately', $this->productDomain), 'smooth' => __('Smoothly follow page scrolling (Default)', $this->productDomain));
		while (list($key, $value) = each($pairs))
		{
			echo "<option value=\"{$key}\"";
			if ($key == $this->followScrolling)
				echo 'selected';
			echo ">{$value}</option>\n";
		}
?>
</select></td>
</tr>
<tr>
<th valign="top"><?php _e('Behavior', $this->productDomain); ?></th>
<td><input type="radio" name="behavior" value="all" id="behavior_all" <?php if ($this->behavior == 'all') echo 'checked'; ?> /> <label for="behavior_all"><?php _e('Active for all posts and pages (quicktags ignored)', $this->productDomain); ?></label><br />
<input type="radio" name="behavior" value="on" id="behavior_on" <?php if ($this->behavior == 'on') echo 'checked'; ?> /> <label for="behavior_on"><?php _e('Active for all posts and pages but those where the disabling quicktag is present (Default)', $this->productDomain); ?></label><br />
<input type="radio" name="behavior" value="off" id="behavior_off" <?php if ($this->behavior == 'off') echo 'checked'; ?> /> <label for="behavior_off"><?php _e('Disabled for all posts and pages but those where the enabling quicktag is present', $this->productDomain); ?></label><br />
<input type="radio" name="behavior" value="none" id="behavior_none" <?php if ($this->behavior == 'none') echo 'checked'; ?> /> <label for="behavior_none"><?php _e('Disabled (quicktags ignored)', $this->productDomain); ?></label><br />
<input type="checkbox" name="lightbox_mode" value="yes" id="lightbox_mode" <?php if ($this->lightboxMode) echo 'checked'; ?> /> <label for="lightbox_mode"><?php _e('Use LightBox Compatibility Mode', $this->productDomain); ?></label></td>
</tr>
<tr>
<th valign="top"><?php _e('Style sheet storage method', $this->productDomain); ?></th>
<td><input type="radio" name="stylesheet_storage" value="option" id="sss_option" <?php if ($this->styleSheetStorage == 'option') echo 'checked'; ?>/> <label for="sss_option"><?php _e("Database using WordPress' option system (Default)", $this->productDomain); ?></label><br />
<input type="radio" name="stylesheet_storage" value="file" id="sss_file" <?php if ($this->styleSheetStorage == 'file') echo 'checked'; ?>/> <label for="sss_file"><?php _e('Regular file', $this->productDomain); ?></label></td>
</tr>
</table>
<p class="submit"><input type="submit" name="submit" value="<?php _e('Save changes', $this->productDomain); ?>" /></p>
<input type="hidden" name="form_name" value="<?php echo $this->productDomain; ?>_global_setup" />
<input type="hidden" name="form_action" value="set" />
</form>
<?php
		if ($legacy)
		{
			echo "</div>\n";
			
			echo '<div class="wrap">';
		}	
?>
<h<?php echo $hl; ?>><?php _e('Import / Export Style Sheet', $this->productDomain); ?></h<?php echo $hl; ?>>
<form action="" method="post" enctype="multipart/form-data">
<table class="form-table">
<tr>
<th><?php _e('Export', $this->productDomain); ?></th>
<td><input type="button" value="<?php _e('Download Style Sheet', $this->productDomain); ?>" onclick="form.form_action.value = 'export'; form.submit();" class="button" /></td>
</tr>
<tr>
<td colspan="2">&nbsp;</td>
</tr>
<tr>
<th><?php _e('Import', $this->productDomain); ?></th>
<td><input type="file" name="imported_file" class="button" /></td>
</tr>
<tr>
<th>&nbsp;</th>
<td><input type="button" value="<?php _e('Import Style Sheet', $this->productDomain); ?>" onclick="form.form_action.value = 'import'; form.submit();" class="button" /></td>
</tr>
</table>
<input type="hidden" name="form_name" value="<?php echo $this->productDomain; ?>_impexp" />
<input type="hidden" name="form_action" value="" />
<?php $this->MFS(); ?>
</form>
<?php
		if ($legacy)
		{
			echo "</div>\n";
			
			echo '<div class="wrap">';
		}	

		// **
		//	Uninstall form
		// **
?>
<h<?php echo $hl; ?>><?php _e('Uninstall', $this->productDomain); ?></h<?php echo $hl; ?>>
<form action="" method="post">
<p><?php printf(__('Removing %s will erase all saved options.', $this->productDomain), $this->productName); ?><br>
<strong><?php _e('This operation CANNOT be undone.', $this->productDomain); ?></strong></p>
<input type="hidden" name="form_name" value="<?php echo $this->productDomain; ?>_uninstall" />
<input type="hidden" name="form_action" value="uninstall" />
<p class="submit"><input type="submit" name="submit" value="<?php printf(__('Uninstall %s', $this->productDomain), $this->productName); ?>" /></p>
</form>
</div>
<?php
	}

	/** \brief Compares version numbers between the actual software version and the installed one.
		\return a value < 0 if the installed version is out-of-date, > 0 if the installed version is more recent or 0 if both version numbers are matching together.
	*/
	function compare_versions()
	{
		// Checking values
		$ra = preg_match('/^(?:[0-9]+(?:RC|pl|a|alpha|b|beta)?(?:[1-9][0-9]*)?\.?)+$/', $this->version);
		$rb = preg_match('/^(?:[0-9]+(?:RC|pl|a|alpha|b|beta)?(?:[1-9][0-9]*)?\.?)+$/', $this->storedVersion);
		if ($ra == 0 || $rb == 0)
			return $ra - $rb;
		return version_compare($this->version, $this->storedVersion);
	}
	
	/** \brief Displays well-formatted error and confirmation messages using default WordPress admin style sheet.
	*/
	function display_messages()
	{
		if (!empty($this->errorMessage))
			echo "<div id=\"message\" class=\"error\"><p>{$this->errorMessage}</p></div>\n";
		if (!empty($this->confirmMessage))
			echo "<div id=\"message\" class=\"updated fade\"><p>{$this->confirmMessage}</p></div>\n";
	}
	
	/** \brief Processes POST values sent by the multiples forms of the plugin configuration pages.
	*/
	function compute_post()
	{
		// Short circuit
		if (count($_POST) == 0)
			return;

		// Checking access clearance
		if (!current_user_can('manage_options'))
		{
			$this->errorMessage = __('Sorry, but you\'re not allowed to access this page.', $this->productDomain);
			return;
		}
		
		// Uninstalling ?
		if (!empty($_POST['form_name']) && $_POST['form_name'] == $this->productDomain.'_uninstall'
				&& !empty($_POST['form_action']) && $_POST['form_action'] == 'uninstall')
		{
			delete_option('mautopopup_version');
			delete_option('mautopopup_stylesheet');
			delete_option('mautopopup_display_close_link');
			delete_option('mautopopup_follow_scrolling');
			delete_option('mautopopup_behavior');
			delete_option('mautopopup_stylesheet_storage');
			delete_option('mautopopup_lightbox_mode');
			$this->confirmMessage = sprintf(__('%s has been successfully uninstalled.', $this->productDomain), $this->productName);
		}
		
		// Installing
		if (!empty($_POST['form_name']) && $_POST['form_name'] == $this->productDomain.'_install'
				&& !empty($_POST['form_action']) && $_POST['form_action'] == 'install')
		{
			delete_option('mautopopup_stylesheet');
			add_option('mautopopup_stylesheet', $this->styleSheet, 'mAutoPopup style sheet', 'no');
			delete_option('mautopopup_version');
			add_option('mautopopup_version', $this->version, 'mAutoPopup plugin version', 'no');
			delete_option('mautopopup_display_close_link');
			add_option('mautopopup_display_close_link', 'above', 'mAutoPopup close link flag', 'no');
			delete_option('mautopopup_follow_scrolling');
			add_option('mautopopup_follow_scrolling', 'smooth', 'mAutoPopup follow scrolling mode', 'no');
			delete_option('mautopopup_behavior');
			add_option('mautopopup_behavior', 'on', 'mAutoPopup behavior flag', 'no');
			delete_option('mautopopup_stylesheet_storage');
			add_option('mautopopup_stylesheet_storage', 'option', 'mAutoPopup style sheet storage method flag', 'no');
			delete_option('mautopopup_lightbox_mode');
			add_option('mautopopup_lightbox_mode', 'no', 'mAutoPopup LightBox compatibility mode flag', 'no');
			$this->confirmMessage = sprintf(__('%s has been successfully installed.', $this->productDomain), $this->productDomain);
		}
		
		// Downgrading
		if (!empty($_POST['form_name']) && $_POST['form_name'] == $this->productDomain.'_install'
				&& !empty($_POST['form_action']) && $_POST['form_action'] == 'downgrade')
		{
			update_option('mautopopup_stylesheet', $this->styleSheet);
			update_option('mautopopup_version', $this->version);
			update_option('mautopopup_display_close_link', 'above');
			update_option('mautopopup_follow_scrolling', 'smooth');
			update_option('mautopopup_behavior', 'on');
			update_option('mautopopup_stylesheet_storage', 'option');
			update_option('mautopopup_lightbox_mode', 'no');
			$this->confirmMessage = sprintf(__('%s has been successfully downgraded.', $this->productDomain), $this->productDomain);
		}
		
		// Upgrading
		if (!empty($_POST['form_name']) && $_POST['form_name'] == $this->productDomain.'_upgrade'
				&& !empty($_POST['form_action']) && $_POST['form_action'] == 'upgrade')
		{
			update_option('mautopopup_version', $this->version);
			if (version_compare($this->storedVersion, '0.1', '=='))
				add_option('mautopopup_display_close_link', 'above', 'mAutoPopup close link flag', 'no');
			if (version_compare($this->storedVersion, '0.3', '<'))
				add_option('mautopopup_follow_scrolling', 1, 'mAutoPopup follow scrolling flag', 'no');
			if (version_compare($this->storedVersion, '0.4', '<'))
			{
				add_option('mautopopup_behavior', 'on', 'mAutoPopup behavior flag', 'no');
				add_option('mautopopup_stylesheet_storage', 'option', 'mAutoPopup style sheet storage method flag', 'no');
			}
			if (version_compare($this->storedVersion, '0.5', '<'))
				update_option('mautopopup_follow_scrolling', ($this->followScrolling  == 'no') ? 'no' : 'smooth');
			add_option('mautopopup_lightbox_mode', 'no', 'mAutoPopup LightBox compatibility mode flag', 'no');
			$this->confirmMessage = sprintf(__('%s has been successfully upgraded.', $this->productDomain), $this->productName);
		}
		
		// Specific functions
		$this->global_setup();
		$this->download_stylesheet();
		$this->import_stylesheet();
		
		// Reloading options after alteration
		$this->load_options();
	}
	
	/** \brief Compute global setup operations
	*/
	function global_setup()
	{
		// Global Setup
		if (!empty($_POST['form_name']) && $_POST['form_name'] == 'mautopopup_global_setup'
				&& !empty($_POST['form_action']) && $_POST['form_action'] == 'set')
		{
			// Style sheet
			if (!isset($_POST['stylesheet']))
				$this->errorMessage = __('Unable to set the style sheet.', $this->productDomain);
			else
			{
				// Style sheet storage method
				$sssm = (preg_match('/^(file|option)$/', $_POST['stylesheet_storage'])) ? $_POST['stylesheet_storage'] : 'option';
				$ss = $this->filter_stylesheet(stripslashes($_POST['stylesheet']));
				// -- Writting file if applying
				if ($sssm == 'file')
				{
					if (!$this->store_stylesheet_file($ss))
					{
						$this->errorMessage = __('Unable to write the style sheet file.', $this->productDomain);
						return;
					}
				}
				else
					update_option('mautopopup_stylesheet', $ss);
				update_option('mautopopup_stylesheet_storage', $sssm);
				
				$dcl = (preg_match('/^(above|below|nowhere)$/', $_POST['display_close_link'])) ? $_POST['display_close_link'] : 'above';
				update_option('mautopopup_display_close_link', $dcl);
				update_option('mautopopup_stylesheet', $this->filter_stylesheet($_POST['stylesheet']));
				update_option('mautopopup_follow_scrolling', $_POST['scroll']);
				update_option('mautopopup_behavior', (preg_match('/^(all|on|off|none)$/', $_POST['behavior'])) ? $_POST['behavior'] : 'on');
				update_option('mautopopup_lightbox_mode', (!empty($_POST['lightbox_mode'])) ? 'yes' : 'no');
				$this->confirmMessage = __('The global setup has been successfully set.', $this->productDomain);
			}
		}
	}
	
	/** \brief Sends style sheet as a file for export.
	*/ 
	function download_stylesheet()
	{
		// Short circuit
		if (empty($_POST['form_name']) || $_POST['form_name'] != 'mautopopup_impexp'
				|| empty($_POST['form_action']) || $_POST['form_action'] != 'export')
			return;
			
		// Sending headers
		header('Content-Description: File Transfer');
		header('Content-Disposition: attachment; filename=mautopopup-stylesheet.css');
		header('Content-type: text/css; charset='.get_bloginfo('blog_charset'), true);
		
		// Sending style sheet
		echo $this->styleSheet;
		
		// Conclusion
		die();
	}
	
	/** \brief Imports a style sheet as a placeholder of the current one.
	*/
	function import_stylesheet()
	{
		// Short circuit
		if (empty($_POST['form_name']) || $_POST['form_name'] != 'mautopopup_impexp'
				|| empty($_POST['form_action']) || $_POST['form_action'] != 'import')
			return;

		// No file
		if (empty($_FILES['imported_file']))
		{
			$this->errorMessage = __('You must submit a file.', $this->productDomain);
			return;
		}
			
		// Checking file state
		switch ($_FILES['imported_file']['error'])
		{
			case UPLOAD_ERR_OK:
				break;
			case UPLOAD_ERR_INI_SIZE:
			case UPLOAD_ERR_FORM_SIZE:
				$this->errorMessage = __('The imported file exceeds maximum granted size for file uploads.', $this->productDomain);
				return;
			case UPLOAD_ERR_PARTIAL:
				$this->errorMessage = __('The imported file was only partially uploaded. Please resubmit.', $this->productDomain);
				return;
			case UPLOAD_ERR_NO_FILE:
				$this->errorMessage = __('You must submit a file.', $this->productDomain);
				return;
			case UPLOAD_ERR_NO_TMP_DIR:
				$this->errorMessage = __('Your server does not seem to have a proper temporary folder for uploaded files. Please contact your administrator to solve this problem.', $this->productDomain);
				return;
			default:
				$this->errorMessage = __('An unidentified error has occured while uploading your file.', $this->productDomain);
				return;
		}
		
		// Checking file presence
		if (!is_uploaded_file($_FILES['imported_file']['tmp_name'])
				|| !is_readable($_FILES['imported_file']['tmp_name'])
				|| ($this->styleSheet = $this->filter_stylesheet(file_get_contents($_FILES['imported_file']['tmp_name']))) === FALSE)
		{
			$this->errorMessage = __('mAutoPopup is unable to access your uploaded file.', $this->productDomain);
			return;
		}
		
		// Setting option / file value
		if ($this->styleSheetStorage == 'file')
		{
			if (!$this->store_stylesheet_file($this->styleSheet))
			{
				$this->errorMessage = __('Unable to write the style sheet file.', $this->productDomain);
				return;
			}
		}
		else
			update_option('mautopopup_stylesheet', $this->styleSheet);
		
		// Conclusion
		$this->confirmMessage = __('The style sheet has been successfully replaced by the imported one.', $this->productDomain);
	}
	
	/** \brief Stores the style sheet file
		\param ss_buffer Style sheet content buffer
		\return true if the file has been successfully written, false either.
	*/
	function store_stylesheet_file($ss_buffer)
	{
		if (!is_writable(dirname(__FILE__)) || (file_exists($this->fssPath) && !is_writable($this->fssPath)))
			return false;
					
		if ($fp = fopen($this->fssPath, 'w'))
		{
			if (fwrite($fp, $ss_buffer) === FALSE)
			{
				fclose($fp);
				return false;
			}
			fclose($fp);
		}
		else
			return false;
			
		// Changing mode for file
		if (!chmod($this->fssPath, 0777))
			return false;
		
		return true;
	}
	
	/** \brief Filters the style sheet to remove unauthorized components
		\param ss_buffer Style sheet buffer to filter
		\return A string containing the filtered style sheet
	*/
	function filter_stylesheet($ss_buffer)
	{
		// Short circuit
		if (!preg_match_all('/(.*){[[:space:]]*(.*)}/Us', $ss_buffer, $stylesheets, PREG_SET_ORDER))
			return '';
		
		// Computing style sheet
		$ss_result = '';
		foreach ($stylesheets as $stylesheet)
		{
			$stylesheet = array_map('trim', $stylesheet);

			// Skipping if not well formed
			if (!preg_match('/#mautopopup/', $stylesheet[1]))
				continue;
			
			// Main component
			if (preg_match('/^(div)#mautopopup$/', $stylesheet[1]))
			{
				if (preg_match('/({|[[:space:]]|;)position[[:space:]]*:[[:space:]]*(?:.*);/', $stylesheet[2], $regs))
					$stylesheet[2] = str_replace($regs[0], "{$regs[1]}position: absolute;", $stylesheet[2]);
			}
			
			$ss_result .= "{$stylesheet[1]} { {$stylesheet[2]}\n}\n";
		}
		
		return $ss_result;
	}
	
	/** \brief Setups the javascript and CSS framework for the plugin.
	*/
	function setup_framework()
	{
		// Short circuit : disabled
		if ($this->behavior == 'none')
			return;
	
		// Computing initial vertical offset
		$initialTop = 0;
		if (preg_match('/div#mautopopup[[:space:]]*{.*top:[[:space:]]*([1-9][0-9]*)px.*}/Us', $this->styleSheet, $regs))
			$initialTop = $regs[1];
?>
<!-- mAutoPopup framework start - version : <?php echo $this->version; ?> -->
<script language="javascript" src="<?php bloginfo('url'); ?>/wp-content/plugins/mautopopup/mautopopup.js"></script>
<script language="javascript">
<!--
var mAutoP = new mAutoPopup('<?php bloginfo('url'); ?>/wp-content/plugins/mautopopup/spacer.gif', <?php echo $initialTop; ?>, '<?php echo $this->followScrolling; ?>');
//-->
</script>
<?php

		// Style sheet
		if ($this->styleSheetStorage == 'option' || !file_exists($this->fssPath) || !is_readable($this->fssPath))
		{
?>
<style type="text/css" media="screen">
<?php echo $this->styleSheet; ?>
</style>
<?php
		}
		else
		{
?>
<link rel="stylesheet" media="screen" href="<?php bloginfo('url'); ?>/wp-content/plugins/mautopopup/mautopopup.css" />
<?php
		}
?>
<!-- mAutoPopup framework end -->
<?php
	}
	
	/** \brief Builds the HTML code for the "popup" layer.
	*/
	function install_popup()
	{
		// Short circuit : disabled
		if ($this->behavior == 'none')
			return;
	
		$closeLinkHTML = '<div class="close"><a href="#" onclick="mAutoP.Hide(); return false;" class="text">'.__('Close', $this->productDomain).'</a></div>';
?>

<!-- mAutoPopup popup layer start - version : <?php echo $this->version; ?> -->
<div id="mautopopup" style="visibility: hidden">
<?php if ($this->closeLink == "above") echo $closeLinkHTML; ?>
<a href="#" onclick="mAutoP.Hide(); return false;"><img src="<?php bloginfo('url'); ?>/wp-content/plugins/mautopopup/spacer.gif" alt="" id="mautopopup_image" border="0" /></a>
<?php if ($this->closeLink == "below") echo $closeLinkHTML; ?>
</div>
<!-- mAutoPopup popup layer end -->
<?php
	}
	
	/** \brief (Filter function) Parses the post / page content to find magnifiable miniatures.
		\param content Post / Page content
		\return the post / page content after miniature parsing or the exact input if the short circuit quicktag has been found.
	*/
	function update_miniatures($content = '')
	{
		// Short circuit : does post/page comply with mAutoPopup behavior ?
		switch ($this->behavior)
		{
			case 'all':
				break;
			case 'on':
				if (preg_match('<!--mautopopup:disable-->', $content))
					return $content;
				break;
			case 'off':
				if (!preg_match('<!--mautopopup:enable-->', $content))
					return $content;
				break;
			case 'none':
				return $content;
		}
	
		// Matching patterns
		$thumbnail_suffix = '.thumbnail';
		if (version_compare($GLOBALS['wp_version'], '2.2', '<'))
			$thumbnail_suffix = __($thumbnail_suffix);
		else if (version_compare($GLOBALS['wp_version'], '2.5-RC1', '>='))
			$thumbnail_suffix = '(?:'.$thumbnail_suffix.'|-[0-9]+x[0-9]+)';
		if (!preg_match_all('#(<a([^<]*)>)?([^<>]*)(<img(.*)src="((.+)'.$thumbnail_suffix.'.(gif|jpeg|jpg|png))"(.*)/?>)([^<>]*)(?(1)(</a>))#Uisu', $content, $regs, PREG_SET_ORDER))
			return $content;

		// Parsing patterns
		foreach ($regs as $r)
		{	
			// Skipping if link around
			if (!empty($r[1]))
				continue;

			$content = str_replace($r[0], $this->build_miniature_link("{$r[7]}.{$r[8]}", $r[4]), $content);
		}
		
		// Conclusion
		return $content;
	}
	
	/** \brief Constructs the link tags around miniatures.
		\param targetFile Fullscale image target file.
		\param text Text to insert between the link tags.
		\return the miniature surrounded by the link tags.
	*/
	function build_miniature_link($targetFile, $text)
	{
		if ($this->lightboxMode)
			return "<a href=\"{$targetFile}\" rel=\"lightbox\">{$text}</a>";
		else
		{
			// Converting remote path to local
			$filePath = $targetFile;
			if (strpos($targetFile, get_bloginfo('url')) === 0)
				$filePath = ABSPATH.str_replace(get_bloginfo('url'), '', $targetFile);
			
			// Fetching image info
			if (!file_exists($filePath) || ($imgInfo = getimagesize($filePath)) === FALSE)
				return $text;
	
			// Margin hack
			$closeLinkMargin = ($this->closeLink != 'nowhere') ? 20 : 0;
			
			// Building link
			return "<a href=\"#\" onClick=\"mAutoP.Show('{$targetFile}', {$imgInfo[0]}, ".($imgInfo[1] + $closeLinkMargin)."); return false;\">{$text}</a>";
		}
	}
	
	/** \brief Builds MAX_FILE_SIZE hidden HTML form input field.
	*/
	function MFS()
	{
		if (!preg_match('/^([1-9][0-9]*)(M?)$/', ini_get('upload_max_filesize'), $regs))
			return;
		$mfs = $regs[1];
		if (!empty($regs[2]))
			$mfs *= 1048576;
		
		echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"{$mfs}\" />";
	}
}

/** \brief Plugin launch function
*/
function mAutoPopup_launch()
{
	$GLOBALS['this'] = new mAutoPopup();
}

// Loading plugin after all have been loaded
add_action('plugins_loaded', 'mAutoPopup_launch');
?>