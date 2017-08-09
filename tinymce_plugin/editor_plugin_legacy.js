/** \brief mAutoPopup TinyMCE plugin
	\file editor_plugin.js
	\version 0.4.3
	\author Christophe SAUVEUR <christophe\@xhaleera.com>
	
	The mAutoPopup plugin for TinyMCE enables you with the ability to insert automatically the <!--no_mautopopup--> quicktag.
*/

var TinyMCE_mAutoPopup = {
	/**
	 * Returns information about the plugin as a name/value array.
	 * The current keys are longname, author, authorurl, infourl and version.
	 *
	 * @returns Name/value array containing information about the plugin.
	 * @type Array 
	 */
	getInfo : function() {
		return {
			longname : 'mAutoPopup',
			author : 'Christophe SAUVEUR',
			authorurl : 'http://www.xhaleera.com',
			infourl : 'http://www.xhaleera.com/index.php/products/wordpress-mseries-plugins/mautopopup/',
			version : '0.4.2'
		};
	},

	/**
	 * Returns the HTML code for a specific control or empty string if this plugin doesn't have that control.
	 * A control can be a button, select list or any other HTML item to present in the TinyMCE user interface.
	 * The variable {$editor_id} will be replaced with the current editor instance id and {$pluginurl} will be replaced
	 * with the URL of the plugin. Language variables such as {$lang_somekey} will also be replaced with contents from
	 * the language packs.
	 *
	 * @param {string} cn Editor control/button name to get HTML for.
	 * @return HTML code for a specific control or empty string.
	 * @type string
	 */
	getControlHTML : function(cn) {
		switch (cn) {
			case "mautopopup_disable":
				return tinyMCE.getButtonHTML(cn, 'lang_mautopopup_insert_disable', '{$pluginurl}/images/disable.gif', 'mautopopup_disable');
			case "mautopopup_enable":
				return tinyMCE.getButtonHTML(cn, 'lang_mautopopup_insert_enable', '{$pluginurl}/images/enable.gif', 'mautopopup_enable');
			case "mautopopup_remove":
				return tinyMCE.getButtonHTML(cn, 'lang_mautopopup_remove_quicktag', '{$pluginurl}/images/remove.gif', 'mautopopup_remove');
		}

		return "";
	},

	/**
	 * Executes a specific command, this function handles plugin commands.
	 *
	 * @param {string} editor_id TinyMCE editor instance id that issued the command.
	 * @param {HTMLElement} element Body or root element for the editor instance.
	 * @param {string} command Command name to be executed.
	 * @param {string} user_interface True/false if a user interface should be presented.
	 * @param {mixed} value Custom value argument, can be anything.
	 * @return true/false if the command was executed by this plugin or not.
	 * @type
	 */
	execCommand : function(editor_id, element, command, user_interface, value) {
		// Handle commands
		switch (command) {
			case "mautopopup_disable":
				this._removeQuicktag(editor_id);
				tinyMCE.execInstanceCommand(editor_id, 'mceInsertContent', false, '<!--mautopopup:disable-->');
				return true;
				
			case "mautopopup_enable":
				this._removeQuicktag(editor_id);
				tinyMCE.execInstanceCommand(editor_id, 'mceInsertContent', false, '<!--mautopopup:enable-->');
				return true;
				
			case "mautopopup_remove":
				this._removeQuicktag(editor_id);
				return true;
		}

		// Pass to next handler in chain
		return false;
	},
	
	/**
	 * Removes quicktag from editor instance
	 *
	 * @param {string} editor_id TinyMCE editor instance from which to remove the quicktag.
	 */
	_removeQuicktag : function(editor_id)
	{
		var content = tinyMCE.getContent(editor_id);
		tinyMCE.execInstanceCommand(editor_id, 'mceSetContent', false, content.replace(/<!--mautopopup:(enable|disable)-->/, ''));
	}
};

// Adds the plugin class to the list of available TinyMCE plugins
tinyMCE.addPlugin('mautopopup', TinyMCE_mAutoPopup);
tinyMCE.setPluginBaseURL('mautopopup', mAutoPopup_MCEPluginURL);

// Add language strings to TinyMCE
tinyMCE.addToLang('mautopopup',{
insert_disable : 'Inserts mAutoPopup disabler quicktag',
insert_enable : 'Inserts mAutoPopup enabler quicktag',
remove_quicktag : 'Removes any mAutoPopup quicktag',
});
